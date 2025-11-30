<?php

namespace App\Services;

use App\Models\IndiceElectronico;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class IndiceElectronicoService
{
    /**
     * Genera índice automático para un expediente
     */
    public function indexarExpediente(Expediente $expediente, User $usuario): IndiceElectronico
    {
        // Verificar si ya existe un índice para este expediente
        $indiceExistente = IndiceElectronico::where('tipo_entidad', 'expediente')
            ->where('entidad_id', $expediente->id)
            ->first();

        if ($indiceExistente) {
            return $this->actualizarIndice($indiceExistente, $expediente, $usuario);
        }

        // Extraer metadatos del expediente
        $metadatos = $this->extraerMetadatosExpediente($expediente);
        $palabrasClave = $this->generarPalabrasClave($expediente->titulo . ' ' . ($expediente->descripcion ?? ''));

        $indice = IndiceElectronico::create([
            'tipo_entidad' => 'expediente',
            'entidad_id' => $expediente->id,
            'codigo_clasificacion' => $expediente->codigo_clasificacion,
            'titulo' => $expediente->titulo,
            'descripcion' => $expediente->descripcion,
            'metadatos' => $metadatos,
            'palabras_clave' => $palabrasClave,
            'serie_documental' => $expediente->serie?->codigo,
            'subserie_documental' => $expediente->subserie?->codigo,
            'fecha_inicio' => $expediente->fecha_apertura,
            'fecha_fin' => $expediente->fecha_cierre,
            'responsable' => $expediente->usuarioResponsable?->name,
            'ubicacion_fisica' => $expediente->ubicacion_fisica,
            'ubicacion_digital' => $expediente->ubicacion_digital,
            'nivel_acceso' => $expediente->nivel_acceso ?? 'publico',
            'estado_conservacion' => $this->evaluarEstadoConservacion($expediente),
            'cantidad_folios' => $this->calcularFoliosExpediente($expediente),
            'es_vital' => $this->esInformacionVital($expediente),
            'es_historico' => $this->esValorHistorico($expediente),
            'fecha_indexacion' => now(),
            'usuario_indexacion_id' => $usuario->id,
        ]);

        // Registrar en auditoría
        $this->registrarAuditoria($indice, $usuario, 'indexacion_creada');

        Log::info("Índice electrónico creado para expediente {$expediente->id}");

        return $indice;
    }

    /**
     * Genera índice automático para un documento
     */
    public function indexarDocumento(Documento $documento, User $usuario): IndiceElectronico
    {
        // Verificar si ya existe un índice para este documento
        $indiceExistente = IndiceElectronico::where('tipo_entidad', 'documento')
            ->where('entidad_id', $documento->id)
            ->first();

        if ($indiceExistente) {
            return $this->actualizarIndice($indiceExistente, $documento, $usuario);
        }

        // Extraer metadatos del documento
        $metadatos = $this->extraerMetadatosDocumento($documento);
        $palabrasClave = $this->generarPalabrasClave($documento->titulo . ' ' . ($documento->descripcion ?? ''));

        $indice = IndiceElectronico::create([
            'tipo_entidad' => 'documento',
            'entidad_id' => $documento->id,
            'codigo_clasificacion' => $documento->codigo_documento,
            'titulo' => $documento->titulo,
            'descripcion' => $documento->descripcion,
            'metadatos' => $metadatos,
            'palabras_clave' => $palabrasClave,
            'serie_documental' => $documento->expediente?->serie?->codigo,
            'subserie_documental' => $documento->expediente?->subserie?->codigo,
            'fecha_inicio' => $documento->fecha_documento,
            'fecha_fin' => $documento->fecha_documento,
            'responsable' => $documento->autor,
            'ubicacion_fisica' => $documento->expediente?->ubicacion_fisica,
            'ubicacion_digital' => $documento->ruta_archivo,
            'nivel_acceso' => $documento->expediente?->nivel_acceso ?? 'publico',
            'estado_conservacion' => $this->evaluarEstadoConservacionDocumento($documento),
            'cantidad_folios' => $documento->numero_paginas ?? 1,
            'formato_archivo' => $documento->extension,
            'tamaño_bytes' => $documento->tamaño,
            'hash_integridad' => $documento->hash_integridad,
            'es_vital' => $this->esDocumentoVital($documento),
            'es_historico' => $this->esDocumentoHistorico($documento),
            'fecha_indexacion' => now(),
            'usuario_indexacion_id' => $usuario->id,
        ]);

        // Registrar en auditoría
        $this->registrarAuditoria($indice, $usuario, 'indexacion_creada');

        Log::info("Índice electrónico creado para documento {$documento->id}");

        return $indice;
    }

    /**
     * Actualiza un índice existente
     */
    public function actualizarIndice($indice, $entidad, User $usuario): IndiceElectronico
    {
        $datosAnteriores = $indice->toArray();

        if ($indice->tipo_entidad === 'expediente') {
            $metadatos = $this->extraerMetadatosExpediente($entidad);
            $indice->update([
                'titulo' => $entidad->titulo,
                'descripcion' => $entidad->descripcion,
                'metadatos' => $metadatos,
                'fecha_fin' => $entidad->fecha_cierre,
                'responsable' => $entidad->usuarioResponsable?->name,
                'ubicacion_fisica' => $entidad->ubicacion_fisica,
                'cantidad_folios' => $this->calcularFoliosExpediente($entidad),
                'fecha_ultima_actualizacion' => now(),
                'usuario_actualizacion_id' => $usuario->id,
            ]);
        } else {
            $metadatos = $this->extraerMetadatosDocumento($entidad);
            $indice->update([
                'titulo' => $entidad->titulo,
                'descripcion' => $entidad->descripcion,
                'metadatos' => $metadatos,
                'tamaño_bytes' => $entidad->tamaño,
                'hash_integridad' => $entidad->hash_integridad,
                'fecha_ultima_actualizacion' => now(),
                'usuario_actualizacion_id' => $usuario->id,
            ]);
        }

        // Registrar cambios en auditoría
        $this->registrarAuditoria($indice, $usuario, 'indexacion_actualizada', $datosAnteriores);

        return $indice->fresh();
    }

    /**
     * Regenera todos los índices de un tipo específico
     */
    public function regenerarIndices(string $tipo, User $usuario, bool $soloFaltantes = false): array
    {
        $resultados = [
            'procesados' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'errores' => []
        ];

        switch ($tipo) {
            case 'expedientes':
                $expedientes = Expediente::with(['serie', 'subserie', 'usuarioResponsable'])->get();
                
                foreach ($expedientes as $expediente) {
                    try {
                        $resultados['procesados']++;
                        
                        $indiceExistente = IndiceElectronico::where('tipo_entidad', 'expediente')
                            ->where('entidad_id', $expediente->id)
                            ->first();

                        if ($indiceExistente && $soloFaltantes) {
                            continue;
                        }

                        if ($indiceExistente) {
                            $this->actualizarIndice($indiceExistente, $expediente, $usuario);
                            $resultados['actualizados']++;
                        } else {
                            $this->indexarExpediente($expediente, $usuario);
                            $resultados['creados']++;
                        }
                    } catch (\Exception $e) {
                        $resultados['errores'][] = "Expediente {$expediente->id}: " . $e->getMessage();
                        Log::error("Error indexando expediente {$expediente->id}: " . $e->getMessage());
                    }
                }
                break;

            case 'documentos':
                $documentos = Documento::with(['expediente.serie', 'expediente.subserie'])->get();
                
                foreach ($documentos as $documento) {
                    try {
                        $resultados['procesados']++;
                        
                        $indiceExistente = IndiceElectronico::where('tipo_entidad', 'documento')
                            ->where('entidad_id', $documento->id)
                            ->first();

                        if ($indiceExistente && $soloFaltantes) {
                            continue;
                        }

                        if ($indiceExistente) {
                            $this->actualizarIndice($indiceExistente, $documento, $usuario);
                            $resultados['actualizados']++;
                        } else {
                            $this->indexarDocumento($documento, $usuario);
                            $resultados['creados']++;
                        }
                    } catch (\Exception $e) {
                        $resultados['errores'][] = "Documento {$documento->id}: " . $e->getMessage();
                        Log::error("Error indexando documento {$documento->id}: " . $e->getMessage());
                    }
                }
                break;
        }

        return $resultados;
    }

    /**
     * Construir consulta base para búsqueda
     */
    public function construirConsultaBusqueda(array $filtros)
    {
        $query = IndiceElectronico::query();

        // Filtro por tipo de entidad
        if (!empty($filtros['tipo_entidad']) && $filtros['tipo_entidad'] !== 'all') {
            $query->porTipo($filtros['tipo_entidad']);
        }

        // Búsqueda de texto completo
        if (!empty($filtros['busqueda_texto'])) {
            $query->busquedaTexto($filtros['busqueda_texto']);
        }

        // Filtro por serie documental
        if (!empty($filtros['serie_documental']) && $filtros['serie_documental'] !== 'all') {
            $query->porSerie($filtros['serie_documental']);
        }

        // Filtro por nivel de acceso
        if (!empty($filtros['nivel_acceso']) && $filtros['nivel_acceso'] !== 'all') {
            $query->porNivelAcceso($filtros['nivel_acceso']);
        }

        // Filtro por fechas
        if (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])) {
            $query->porFechas($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null);
        }

        // Filtro por palabras clave
        if (!empty($filtros['palabras_clave'])) {
            $palabras = is_array($filtros['palabras_clave']) 
                ? $filtros['palabras_clave'] 
                : explode(',', $filtros['palabras_clave']);
            $query->porPalabrasClave(array_map('trim', $palabras));
        }

        // Filtros especiales
        if (!empty($filtros['solo_vitales'])) {
            $query->vitales();
        }

        if (!empty($filtros['solo_historicos'])) {
            $query->historicos();
        }

        // Ordenamiento
        $ordenPor = $filtros['orden_por'] ?? 'fecha_indexacion';
        $direccion = $filtros['direccion'] ?? 'desc';
        $query->orderBy($ordenPor, $direccion);

        // Incluir relaciones
        $query->with(['usuarioIndexacion', 'usuarioActualizacion']);

        return $query;
    }

    /**
     * Búsqueda avanzada en índices
     */
    public function busquedaAvanzada(array $filtros): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->construirConsultaBusqueda($filtros);
        return $query->paginate($filtros['per_page'] ?? 15);
    }

    /**
     * Obtiene estadísticas de indexación
     */
    public function obtenerEstadisticas(): array
    {
        return [
            'total_indices' => IndiceElectronico::count(),
            'por_tipo' => IndiceElectronico::estadisticasPorTipo(),
            'por_serie' => IndiceElectronico::estadisticasPorSerie(),
            'documentos_vitales' => IndiceElectronico::documentosVitales(),
            'documentos_historicos' => IndiceElectronico::documentosHistoricos(),
            'tamaño_total' => $this->formatearTamaño(IndiceElectronico::totalTamaño()),
            'indices_recientes' => IndiceElectronico::where('fecha_indexacion', '>=', now()->subDays(7))->count(),
            'indices_desactualizados' => IndiceElectronico::where(function($query) {
                $query->where('fecha_ultima_actualizacion', '<', now()->subMonths(6))
                      ->orWhere(function($subQuery) {
                          $subQuery->whereNull('fecha_ultima_actualizacion')
                                  ->where('fecha_indexacion', '<', now()->subMonths(6));
                      });
            })->count(),
            'por_estado_conservacion' => IndiceElectronico::selectRaw('estado_conservacion, COUNT(*) as total')
                ->groupBy('estado_conservacion')
                ->pluck('total', 'estado_conservacion')
                ->toArray(),
            'por_nivel_acceso' => IndiceElectronico::selectRaw('nivel_acceso, COUNT(*) as total')
                ->groupBy('nivel_acceso')
                ->pluck('total', 'nivel_acceso')
                ->toArray(),
        ];
    }

    // Métodos privados auxiliares

    private function extraerMetadatosExpediente(Expediente $expediente): array
    {
        return [
            'codigo' => $expediente->codigo,
            'estado' => $expediente->estado,
            'prioridad' => $expediente->prioridad,
            'fecha_apertura' => $expediente->fecha_apertura?->toISOString(),
            'fecha_cierre' => $expediente->fecha_cierre?->toISOString(),
            'total_documentos' => $expediente->documentos()->count(),
            'configuracion' => [
                'permite_documentos_electronicos' => $expediente->permite_documentos_electronicos,
                'requiere_firma_digital' => $expediente->requiere_firma_digital,
                'control_version_documentos' => $expediente->control_version_documentos,
                'notificaciones_activadas' => $expediente->notificaciones_activadas,
            ],
        ];
    }

    private function extraerMetadatosDocumento(Documento $documento): array
    {
        return [
            'version_mayor' => $documento->version_mayor ?? 1,
            'version_menor' => $documento->version_menor ?? 0,
            'tipologia_documental_id' => $documento->tipologia_documental_id,
            'formato' => $documento->formato,
            'tamano_bytes' => $documento->tamano_bytes,
            'fecha_documento' => $documento->fecha_documento?->toISOString(),
            'fecha_captura' => $documento->fecha_captura?->toISOString(),
            'fecha_creacion' => $documento->created_at?->toISOString(),
            'fecha_modificacion' => $documento->updated_at?->toISOString(),
            'firmado_digitalmente' => $documento->firmado_digitalmente ?? false,
            'total_firmas' => $documento->total_firmas ?? 0,
        ];
    }

    private function generarPalabrasClave(string $texto): array
    {
        // Limpiar y normalizar texto
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-záéíóúñü\s]/', ' ', $texto);
        
        // Dividir en palabras
        $palabras = array_filter(explode(' ', $texto));
        
        // Filtrar palabras vacías y muy cortas
        $stopWords = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'los', 'las'];
        
        $palabrasUtiles = array_filter($palabras, function($palabra) use ($stopWords) {
            return strlen($palabra) >= 3 && !in_array($palabra, $stopWords);
        });
        
        // Obtener palabras más frecuentes (máximo 10)
        $frecuencias = array_count_values($palabrasUtiles);
        arsort($frecuencias);
        
        return array_keys(array_slice($frecuencias, 0, 10, true));
    }

    private function evaluarEstadoConservacion(Expediente $expediente): string
    {
        $edad = $expediente->fecha_apertura?->diffInYears(now()) ?? 0;
        
        if ($edad < 1) return 'excelente';
        if ($edad < 5) return 'bueno';
        if ($edad < 10) return 'regular';
        if ($edad < 20) return 'malo';
        
        return 'critico';
    }

    private function evaluarEstadoConservacionDocumento(Documento $documento): string
    {
        $edad = $documento->created_at?->diffInYears(now()) ?? 0;
        
        // Considerar también el tamaño y formato
        $formatoDigital = in_array(strtolower($documento->extension ?? ''), ['pdf', 'docx', 'xlsx']);
        
        if ($edad < 1 && $formatoDigital) return 'excelente';
        if ($edad < 3 && $formatoDigital) return 'bueno';
        if ($edad < 7) return 'regular';
        if ($edad < 15) return 'malo';
        
        return 'critico';
    }

    private function calcularFoliosExpediente(Expediente $expediente): int
    {
        // Como no existe numero_paginas, calculamos una estimación basada en el tamaño
        // Asumiendo ~300 KB promedio por página en PDF
        $totalBytes = $expediente->documentos()->sum('tamano_bytes') ?? 0;
        return $totalBytes > 0 ? max(1, intval($totalBytes / 300000)) : 0;
    }

    private function esInformacionVital(Expediente $expediente): bool
    {
        // Lógica para determinar si un expediente contiene información vital
        return $expediente->prioridad === 'alta' || 
               stripos($expediente->titulo, 'contrato') !== false ||
               stripos($expediente->titulo, 'legal') !== false;
    }

    private function esValorHistorico(Expediente $expediente): bool
    {
        // Determinar valor histórico basado en edad y tipo
        $edad = $expediente->fecha_apertura?->diffInYears(now()) ?? 0;
        return $edad >= 10 || $expediente->estado === 'archivado';
    }

    private function esDocumentoVital(Documento $documento): bool
    {
        return stripos($documento->titulo, 'contrato') !== false ||
               stripos($documento->titulo, 'acuerdo') !== false ||
               stripos($documento->titulo, 'convenio') !== false ||
               $documento->firmado_digitalmente;
    }

    private function esDocumentoHistorico(Documento $documento): bool
    {
        $edad = $documento->created_at?->diffInYears(now()) ?? 0;
        return $edad >= 5;
    }

    private function formatearTamaño(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function registrarAuditoria(IndiceElectronico $indice, User $usuario, string $accion, array $datosAnteriores = null): void
    {
        try {
            \App\Models\PistaAuditoria::registrar($indice, $accion, [
                'descripcion' => "Índice electrónico para {$indice->tipo_entidad} '{$indice->titulo}' - {$accion}",
                'datos_anteriores' => $datosAnteriores,
                'usuario_id' => $usuario->id,
            ]);
        } catch (\Exception $e) {
            // Si falla la auditoría, solo logueamos el error sin interrumpir el proceso
            Log::warning("Error registrando auditoría para índice {$indice->id}: " . $e->getMessage());
        }
    }
}
