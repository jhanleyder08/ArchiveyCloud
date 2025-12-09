<?php

namespace App\Services;

use App\Models\CCD;
use App\Models\CCDNivel;
use App\Models\CCDVersion;
use App\Models\CCDVocabulario;
use App\Models\TablaRetencionDocumental;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CCDService
{
    public function crear(array $datos, User $usuario): CCD
    {
        DB::beginTransaction();
        try {
            $ccd = new CCD($datos);
            $ccd->created_by = $usuario->id;
            $ccd->version = $datos['version'] ?? '1.0';
            $ccd->estado = 'borrador';
            $ccd->save();
            DB::commit();
            return $ccd;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function agregarNivel(CCD $ccd, array $datos): CCDNivel
    {
        DB::beginTransaction();
        try {
            $nivel = new CCDNivel($datos);
            $nivel->ccd_id = $ccd->id;
            if (!isset($datos['nivel'])) {
                $nivel->nivel = $nivel->parent_id ? CCDNivel::find($nivel->parent_id)->nivel + 1 : 1;
            }
            $nivel->save();
            $nivel->actualizarRuta();
            DB::commit();
            return $nivel;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function actualizar(CCD $ccd, array $datos, User $usuario): CCD
    {
        DB::beginTransaction();
        try {
            // Actualizar los campos del CCD
            $ccd->fill($datos);
            $ccd->updated_by = $usuario->id;
            $ccd->save();
            
            DB::commit();
            return $ccd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar CCD', [
                'ccd_id' => $ccd->id,
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }

    /**
     * Aprobar un CCD y generar estructura documental automáticamente
     */
    public function aprobar(CCD $ccd, User $usuario): CCD
    {
        DB::beginTransaction();
        try {
            $ccd->estado = 'activo';
            $ccd->fecha_aprobacion = now();
            $ccd->aprobado_por = $usuario->id;
            $ccd->updated_by = $usuario->id;
            $ccd->save();
            
            // Generar estructura documental automáticamente
            $this->generarEstructuraDocumental($ccd, $usuario);
            
            DB::commit();
            
            Log::info('CCD aprobado y estructura documental generada', [
                'ccd_id' => $ccd->id,
                'usuario' => $usuario->id
            ]);
            
            return $ccd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aprobar CCD', [
                'ccd_id' => $ccd->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Genera automáticamente la estructura documental (TRD + Series + Subseries)
     * basada en la estructura jerárquica del CCD
     */
    public function generarEstructuraDocumental(CCD $ccd, User $usuario): ?TablaRetencionDocumental
    {
        // Verificar si ya existe una TRD asociada a este CCD
        $trdExistente = TablaRetencionDocumental::where('ccd_id', $ccd->id)->first();
        if ($trdExistente) {
            Log::info('TRD ya existe para este CCD, actualizando series/subseries', [
                'ccd_id' => $ccd->id,
                'trd_id' => $trdExistente->id
            ]);
            $trd = $trdExistente;
        } else {
            // Crear nueva TRD asociada al CCD
            $trd = TablaRetencionDocumental::create([
                'codigo' => 'TRD-' . $ccd->codigo,
                'nombre' => 'TRD - ' . $ccd->nombre,
                'descripcion' => 'Tabla de Retención Documental generada automáticamente desde el CCD: ' . $ccd->nombre,
                'entidad' => $ccd->nombre,
                'version' => 1,
                'fecha_aprobacion' => now(),
                'fecha_vigencia_inicio' => $ccd->fecha_vigencia_inicio ?? now(),
                'fecha_vigencia_fin' => $ccd->fecha_vigencia_fin,
                'estado' => 'vigente',
                'vigente' => true,
                'ccd_id' => $ccd->id,
                'created_by' => $usuario->id,
                'aprobado_por' => $usuario->id,
            ]);
            
            Log::info('TRD creada automáticamente', [
                'trd_id' => $trd->id,
                'ccd_id' => $ccd->id
            ]);
        }

        // Obtener todos los niveles del CCD ordenados jerárquicamente
        $niveles = CCDNivel::where('ccd_id', $ccd->id)
            ->where('activo', true)
            ->orderBy('nivel')
            ->orderBy('orden')
            ->get();

        // Mapeo de CCDNivel ID a SerieDocumental para vincular subseries
        $seriesMap = [];

        // Procesar niveles tipo 'serie'
        foreach ($niveles->where('tipo_nivel', CCDNivel::TIPO_SERIE) as $nivelSerie) {
            // Verificar si ya existe la serie
            $serieExistente = SerieDocumental::where('trd_id', $trd->id)
                ->where('codigo', $nivelSerie->codigo)
                ->first();

            if (!$serieExistente) {
                $serie = SerieDocumental::create([
                    'codigo' => $nivelSerie->codigo,
                    'nombre' => $nivelSerie->nombre,
                    'descripcion' => $nivelSerie->descripcion ?? 'Serie generada desde CCD',
                    'trd_id' => $trd->id,
                    'dependencia' => $this->obtenerDependenciaDesdeJerarquia($nivelSerie),
                    'orden' => $nivelSerie->orden,
                    'activa' => true,
                ]);
                
                $seriesMap[$nivelSerie->id] = $serie;
                
                Log::info('Serie creada desde CCD', [
                    'serie_id' => $serie->id,
                    'nivel_id' => $nivelSerie->id,
                    'codigo' => $serie->codigo
                ]);
            } else {
                $seriesMap[$nivelSerie->id] = $serieExistente;
            }
        }

        // Procesar niveles tipo 'subserie'
        foreach ($niveles->where('tipo_nivel', CCDNivel::TIPO_SUBSERIE) as $nivelSubserie) {
            // Encontrar la serie padre
            $seriePadre = $this->encontrarSeriePadre($nivelSubserie, $seriesMap, $niveles);
            
            if (!$seriePadre) {
                Log::warning('No se encontró serie padre para subserie', [
                    'subserie_nivel_id' => $nivelSubserie->id,
                    'codigo' => $nivelSubserie->codigo
                ]);
                continue;
            }

            // Verificar si ya existe la subserie
            $subserieExistente = SubserieDocumental::where('serie_documental_id', $seriePadre->id)
                ->where('codigo', $nivelSubserie->codigo)
                ->first();

            if (!$subserieExistente) {
                $subserie = SubserieDocumental::create([
                    'codigo' => $nivelSubserie->codigo,
                    'nombre' => $nivelSubserie->nombre,
                    'descripcion' => $nivelSubserie->descripcion ?? 'Subserie generada desde CCD',
                    'serie_documental_id' => $seriePadre->id,
                    'tiempo_archivo_gestion' => 2, // Valor por defecto: 2 años
                    'tiempo_archivo_central' => 8, // Valor por defecto: 8 años
                    'disposicion_final' => SubserieDocumental::DISPOSICION_SELECCION, // Por defecto
                    'activa' => true,
                    'created_by' => $usuario->id,
                ]);
                
                Log::info('Subserie creada desde CCD', [
                    'subserie_id' => $subserie->id,
                    'serie_id' => $seriePadre->id,
                    'codigo' => $subserie->codigo
                ]);
            }
        }

        return $trd;
    }

    /**
     * Obtiene el nombre de la dependencia desde la jerarquía del nivel
     * (busca el padre tipo 'seccion' o 'subseccion')
     */
    private function obtenerDependenciaDesdeJerarquia(CCDNivel $nivel): string
    {
        $padre = $nivel->padre;
        while ($padre) {
            if (in_array($padre->tipo_nivel, [CCDNivel::TIPO_SECCION, CCDNivel::TIPO_SUBSECCION])) {
                return $padre->nombre;
            }
            $padre = $padre->padre;
        }
        return '';
    }

    /**
     * Encuentra la serie padre para una subserie basándose en la jerarquía
     */
    private function encontrarSeriePadre(CCDNivel $nivelSubserie, array $seriesMap, $todosNiveles): ?SerieDocumental
    {
        // Primero buscar en el padre directo
        if (isset($seriesMap[$nivelSubserie->parent_id])) {
            return $seriesMap[$nivelSubserie->parent_id];
        }

        // Si el padre no es una serie, buscar recursivamente hacia arriba
        $padre = $nivelSubserie->padre;
        while ($padre) {
            if ($padre->tipo_nivel === CCDNivel::TIPO_SERIE && isset($seriesMap[$padre->id])) {
                return $seriesMap[$padre->id];
            }
            $padre = $padre->padre;
        }

        return null;
    }

    public function obtenerEstructuraJerarquica(CCD $ccd): array
    {
        return $ccd->getEstructuraJerarquica();
    }

    /**
     * Crear una nueva versión del CCD
     */
    public function crearVersion(CCD $ccd, string $version, string $cambios, $user): CCD
    {
        return DB::transaction(function () use ($ccd, $version, $cambios, $user) {
            // Guardar versión histórica del CCD actual
            CCDVersion::create([
                'ccd_id' => $ccd->id,
                'version_anterior' => $ccd->version,
                'version_nueva' => $version,
                'datos_anteriores' => [
                    'codigo' => $ccd->codigo,
                    'nombre' => $ccd->nombre,
                    'descripcion' => $ccd->descripcion,
                    'estado' => $ccd->estado,
                    'estructura' => $this->obtenerEstructuraJerarquica($ccd),
                ],
                'cambios' => $cambios,
                'modificado_por' => $user->id,
                'fecha_cambio' => now(),
            ]);

            // Actualizar el CCD con la nueva versión
            // El estado cambia a 'borrador' para requerir nueva aprobación
            $ccd->update([
                'version' => $version,
                'estado' => 'borrador',
                'fecha_aprobacion' => null,
                'aprobado_por' => null,
                'updated_by' => $user->id,
            ]);

            return $ccd->fresh();
        });
    }
}
