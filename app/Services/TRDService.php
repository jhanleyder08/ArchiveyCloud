<?php

namespace App\Services;

use App\Models\TRD;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\TipoDocumental;
use App\Models\Retencion;
use App\Models\TRDVersion;
use App\Models\TRDImportacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class TRDService
{
    /**
     * Crear nueva TRD
     */
    public function crear(array $datos, User $usuario): TRD
    {
        DB::beginTransaction();
        
        try {
            $trd = new TRD($datos);
            $trd->created_by = $usuario->id;
            $trd->version = $datos['version'] ?? '1.0';
            $trd->estado = 'borrador';
            $trd->save();

            Log::info('TRD creada', ['trd_id' => $trd->id, 'usuario' => $usuario->id]);

            DB::commit();
            return $trd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear TRD', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar TRD
     */
    public function actualizar(TRD $trd, array $datos, User $usuario): TRD
    {
        DB::beginTransaction();
        
        try {
            $trd->fill($datos);
            $trd->updated_by = $usuario->id;
            $trd->save();

            Log::info('TRD actualizada', ['trd_id' => $trd->id, 'usuario' => $usuario->id]);

            DB::commit();
            return $trd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar TRD', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Aprobar TRD
     */
    public function aprobar(TRD $trd, User $usuario): TRD
    {
        DB::beginTransaction();
        
        try {
            $trd->estado = 'activa';
            $trd->fecha_aprobacion = now();
            $trd->aprobado_por = $usuario->id;
            $trd->save();

            Log::info('TRD aprobada', ['trd_id' => $trd->id, 'usuario' => $usuario->id]);

            DB::commit();
            return $trd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al aprobar TRD', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Crear nueva versión de TRD
     */
    public function crearVersion(TRD $trd, string $nuevaVersion, string $cambios, User $usuario): TRD
    {
        DB::beginTransaction();
        
        try {
            // Crear registro de versión
            $trd->crearVersion($nuevaVersion, $cambios, $usuario);

            // Actualizar estado
            $trd->estado = 'borrador';
            $trd->save();

            Log::info('Nueva versión TRD creada', [
                'trd_id' => $trd->id,
                'version' => $nuevaVersion,
                'usuario' => $usuario->id
            ]);

            DB::commit();
            return $trd->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear versión TRD', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Agregar serie documental a TRD
     */
    public function agregarSerie(TRD $trd, array $datos): SerieDocumental
    {
        DB::beginTransaction();
        
        try {
            $serie = new SerieDocumental($datos);
            $serie->trd_id = $trd->id;
            $serie->save();

            Log::info('Serie agregada a TRD', [
                'trd_id' => $trd->id,
                'serie_id' => $serie->id
            ]);

            DB::commit();
            return $serie;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar serie', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Agregar subserie a serie
     */
    public function agregarSubserie(SerieDocumental $serie, array $datos): SubserieDocumental
    {
        DB::beginTransaction();
        
        try {
            $subserie = new SubserieDocumental($datos);
            $subserie->serie_id = $serie->id;
            $subserie->save();

            Log::info('Subserie agregada', [
                'serie_id' => $serie->id,
                'subserie_id' => $subserie->id
            ]);

            DB::commit();
            return $subserie;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar subserie', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Configurar retención para serie/subserie
     */
    public function configurarRetencion(array $datos): Retencion
    {
        DB::beginTransaction();
        
        try {
            $retencion = Retencion::updateOrCreate(
                [
                    'serie_id' => $datos['serie_id'] ?? null,
                    'subserie_id' => $datos['subserie_id'] ?? null,
                    'tipo_documental_id' => $datos['tipo_documental_id'] ?? null,
                ],
                $datos
            );

            Log::info('Retención configurada', ['retencion_id' => $retencion->id]);

            DB::commit();
            return $retencion;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al configurar retención', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Importar TRD desde archivo XML
     */
    public function importarDesdeXML(string $rutaArchivo, User $usuario): TRDImportacion
    {
        $importacion = new TRDImportacion([
            'tipo' => TRDImportacion::TIPO_IMPORTACION,
            'formato' => TRDImportacion::FORMATO_XML,
            'nombre_archivo' => basename($rutaArchivo),
            'ruta_archivo' => $rutaArchivo,
            'estado' => TRDImportacion::ESTADO_PROCESANDO,
            'usuario_id' => $usuario->id,
        ]);
        $importacion->save();

        DB::beginTransaction();
        
        try {
            $xml = simplexml_load_file($rutaArchivo);
            
            // Crear TRD
            $trd = $this->crear([
                'codigo' => (string)$xml->codigo,
                'nombre' => (string)$xml->nombre,
                'descripcion' => (string)$xml->descripcion,
                'version' => (string)$xml->version,
            ], $usuario);

            $importacion->trd_id = $trd->id;

            // Importar series
            foreach ($xml->series->serie as $serieXml) {
                $serie = $this->agregarSerie($trd, [
                    'codigo' => (string)$serieXml->codigo,
                    'nombre' => (string)$serieXml->nombre,
                    'descripcion' => (string)$serieXml->descripcion,
                ]);

                $importacion->incrementarProcesados();

                // Importar subseries si existen
                if (isset($serieXml->subseries)) {
                    foreach ($serieXml->subseries->subserie as $subserieXml) {
                        $this->agregarSubserie($serie, [
                            'codigo' => (string)$subserieXml->codigo,
                            'nombre' => (string)$subserieXml->nombre,
                            'descripcion' => (string)$subserieXml->descripcion,
                        ]);

                        $importacion->incrementarProcesados();
                    }
                }

                // Importar retención
                if (isset($serieXml->retencion)) {
                    $this->configurarRetencion([
                        'serie_id' => $serie->id,
                        'retencion_archivo_gestion' => (int)$serieXml->retencion->archivo_gestion,
                        'retencion_archivo_central' => (int)$serieXml->retencion->archivo_central,
                        'disposicion_final' => (string)$serieXml->retencion->disposicion_final,
                    ]);
                }
            }

            $importacion->marcarCompletado();
            DB::commit();

            Log::info('TRD importada desde XML', [
                'trd_id' => $trd->id,
                'importacion_id' => $importacion->id
            ]);

            return $importacion;
        } catch (Exception $e) {
            DB::rollBack();
            $importacion->marcarError(['mensaje' => $e->getMessage()]);
            Log::error('Error al importar TRD desde XML', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Exportar TRD a XML
     */
    public function exportarAXML(TRD $trd): string
    {
        $xml = new \SimpleXMLElement('<trd/>');
        
        $xml->addChild('codigo', $trd->codigo);
        $xml->addChild('nombre', $trd->nombre);
        $xml->addChild('descripcion', $trd->descripcion);
        $xml->addChild('version', $trd->version);
        $xml->addChild('estado', $trd->estado);

        $seriesXml = $xml->addChild('series');

        foreach ($trd->series as $serie) {
            $serieXml = $seriesXml->addChild('serie');
            $serieXml->addChild('codigo', $serie->codigo);
            $serieXml->addChild('nombre', $serie->nombre);
            $serieXml->addChild('descripcion', $serie->descripcion);

            // Subseries
            if ($serie->subseries->count() > 0) {
                $subseriesXml = $serieXml->addChild('subseries');
                foreach ($serie->subseries as $subserie) {
                    $subserieXml = $subseriesXml->addChild('subserie');
                    $subserieXml->addChild('codigo', $subserie->codigo);
                    $subserieXml->addChild('nombre', $subserie->nombre);
                    $subserieXml->addChild('descripcion', $subserie->descripcion);
                }
            }

            // Retención
            $retencion = Retencion::where('serie_id', $serie->id)->first();
            if ($retencion) {
                $retencionXml = $serieXml->addChild('retencion');
                $retencionXml->addChild('archivo_gestion', $retencion->retencion_archivo_gestion);
                $retencionXml->addChild('archivo_central', $retencion->retencion_archivo_central);
                $retencionXml->addChild('disposicion_final', $retencion->disposicion_final);
            }
        }

        return $xml->asXML();
    }

    /**
     * Obtener TRDs activas y vigentes
     */
    public function obtenerVigentes(): Collection
    {
        return TRD::vigentes()
            ->with(['series.subseries'])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Validar estructura de TRD
     */
    public function validar(TRD $trd): array
    {
        $errores = [];

        // Validar que tenga al menos una serie
        if ($trd->series()->count() === 0) {
            $errores[] = 'La TRD debe tener al menos una serie documental';
        }

        // Validar series
        foreach ($trd->series as $serie) {
            // Validar que cada serie tenga configuración de retención
            $retencion = Retencion::where('serie_id', $serie->id)->first();
            if (!$retencion) {
                $errores[] = "La serie '{$serie->nombre}' no tiene configuración de retención";
            }
        }

        return $errores;
    }

    /**
     * Obtener estadísticas generales de TRDs
     */
    public function obtenerEstadisticasGenerales(): array
    {
        return [
            'total_trds' => TRD::count(),
            'trds_activas' => TRD::where('estado', 'activa')->count(),
            'trds_borradores' => TRD::where('estado', 'borrador')->count(),
            'total_series' => SerieDocumental::count(),
            'total_subseries' => SubserieDocumental::count(),
            'total_tipos_documentales' => TipoDocumental::count(),
            'disposiciones_finales' => Retencion::selectRaw('disposicion_final, COUNT(*) as total')
                ->groupBy('disposicion_final')
                ->pluck('total', 'disposicion_final')
                ->toArray(),
        ];
    }
}
