<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\DisposicionFinal;
use App\Models\SubserieDocumental;
use App\Models\TRDTiempoRetencion;
use App\Models\CCDNivel;
use App\Models\PistaAuditoria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para gestión automática de disposiciones finales
 * 
 * Este servicio detecta expedientes que cumplen su tiempo de retención
 * según la configuración de la TRD y genera/sugiere disposiciones automáticas.
 * 
 * Flujo:
 * TRD → TRDTiempoRetencion → CCDNivel (Serie/Subserie) → Expediente → DisposicionFinal
 */
class DisposicionAutomaticaService
{
    // Mapeo de códigos TRD a tipos de disposición del sistema
    const MAPEO_DISPOSICION = [
        'CT' => 'conservacion_permanente',      // Conservación Total
        'E'  => 'eliminacion_controlada',       // Eliminación
        'D'  => 'digitalizacion',               // Digitalización
        'S'  => 'transferencia_historica',      // Selección (Transferencia)
        'M'  => 'microfilmacion',               // Microfilmación
    ];

    /**
     * Obtener expedientes que requieren disposición final
     * Basado en tiempos de retención de la TRD
     * 
     * @param int $diasAnticipacion Días de anticipación para alertas
     * @return Collection
     */
    public function getExpedientesParaDisposicion(int $diasAnticipacion = 30): Collection
    {
        $hoy = Carbon::now();
        $fechaLimite = $hoy->copy()->addDays($diasAnticipacion);

        // Expedientes sin disposición final activa (cerrados o con fecha de eliminación próxima)
        $expedientes = Expediente::with(['serie', 'subserie', 'responsable', 'disposicionFinal'])
            ->where(function ($query) {
                // Sin disposición o con disposición rechazada/cancelada
                $query->whereDoesntHave('disposicionFinal')
                    ->orWhereHas('disposicionFinal', function ($q) {
                        $q->whereIn('estado', [
                            DisposicionFinal::ESTADO_RECHAZADO,
                            DisposicionFinal::ESTADO_CANCELADO
                        ]);
                    });
            })
            ->where(function ($query) use ($fechaLimite) {
                // Fecha de eliminación próxima o vencida
                $query->whereNotNull('fecha_eliminacion')
                    ->where('fecha_eliminacion', '<=', $fechaLimite);
            })
            ->get();

        // Enriquecer con información de retención
        return $expedientes->map(function ($expediente) use ($hoy) {
            $expediente->info_retencion = $this->calcularInfoRetencion($expediente);
            $expediente->dias_para_disposicion = $expediente->fecha_eliminacion 
                ? $hoy->diffInDays($expediente->fecha_eliminacion, false) 
                : null;
            $expediente->ya_vencido = $expediente->fecha_eliminacion && $expediente->fecha_eliminacion < $hoy;
            $expediente->tipo_disposicion_sugerido = $this->obtenerTipoDisposicionSugerido($expediente);
            return $expediente;
        });
    }

    /**
     * Obtener estadísticas de disposiciones pendientes por TRD
     * 
     * @return array
     */
    public function getEstadisticasDisposicionesPendientes(): array
    {
        $hoy = Carbon::now();
        
        // Expedientes vencidos sin disposición
        $vencidosSinDisposicion = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->where('fecha_eliminacion', '<', $hoy)
            ->count();

        // Próximos a vencer (30 días)
        $proximosVencer30 = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->whereBetween('fecha_eliminacion', [$hoy, $hoy->copy()->addDays(30)])
            ->count();

        // Próximos a vencer (60 días)
        $proximosVencer60 = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->whereBetween('fecha_eliminacion', [$hoy->copy()->addDays(31), $hoy->copy()->addDays(60)])
            ->count();

        // Próximos a vencer (90 días)
        $proximosVencer90 = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->whereBetween('fecha_eliminacion', [$hoy->copy()->addDays(61), $hoy->copy()->addDays(90)])
            ->count();

        // Total de expedientes con fecha de eliminación configurada (para mostrar información)
        $totalConFechaEliminacion = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->count();

        // Por tipo de disposición (según subserie)
        $porTipoDisposicion = Expediente::whereDoesntHave('disposicionFinal')
            ->whereNotNull('fecha_eliminacion')
            ->where('fecha_eliminacion', '<=', $hoy->copy()->addDays(90))
            ->with('subserie')
            ->get()
            ->groupBy(function ($exp) {
                return $exp->disposicion_final ?? $exp->subserie->disposicion_final ?? 'no_definido';
            })
            ->map->count();

        return [
            'vencidos_sin_disposicion' => $vencidosSinDisposicion,
            'proximos_30_dias' => $proximosVencer30,
            'proximos_60_dias' => $proximosVencer60,
            'proximos_90_dias' => $proximosVencer90,
            'total_pendientes' => $vencidosSinDisposicion + $proximosVencer30 + $proximosVencer60 + $proximosVencer90,
            'total_con_fecha_eliminacion' => $totalConFechaEliminacion,
            'por_tipo_disposicion' => $porTipoDisposicion,
        ];
    }

    /**
     * Generar disposiciones automáticas para expedientes vencidos
     * 
     * @param array $expedienteIds IDs de expedientes a procesar (vacío = todos los vencidos)
     * @param int|null $responsableId ID del usuario responsable
     * @return array Resultados del proceso
     */
    public function generarDisposicionesAutomaticas(array $expedienteIds = [], ?int $responsableId = null): array
    {
        $resultados = [
            'exitosos' => 0,
            'fallidos' => 0,
            'omitidos' => 0,
            'detalles' => [],
        ];

        $query = Expediente::with(['subserie', 'serie'])
            ->whereDoesntHave('disposicionFinal', function ($q) {
                $q->whereNotIn('estado', [
                    DisposicionFinal::ESTADO_RECHAZADO,
                    DisposicionFinal::ESTADO_CANCELADO
                ]);
            })
            ->whereNotNull('fecha_eliminacion')
            ->where('fecha_eliminacion', '<=', Carbon::now());

        if (!empty($expedienteIds)) {
            $query->whereIn('id', $expedienteIds);
        }

        $expedientes = $query->get();

        foreach ($expedientes as $expediente) {
            try {
                $resultado = $this->crearDisposicionParaExpediente($expediente, $responsableId);
                
                if ($resultado['success']) {
                    $resultados['exitosos']++;
                    $resultados['detalles'][] = [
                        'expediente_id' => $expediente->id,
                        'expediente_codigo' => $expediente->codigo,
                        'status' => 'creado',
                        'disposicion_id' => $resultado['disposicion_id'],
                        'tipo_disposicion' => $resultado['tipo_disposicion'],
                    ];
                } else {
                    $resultados['omitidos']++;
                    $resultados['detalles'][] = [
                        'expediente_id' => $expediente->id,
                        'expediente_codigo' => $expediente->codigo,
                        'status' => 'omitido',
                        'razon' => $resultado['razon'],
                    ];
                }
            } catch (\Exception $e) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'expediente_id' => $expediente->id,
                    'expediente_codigo' => $expediente->codigo ?? 'N/A',
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    /**
     * Crear disposición para un expediente específico
     * 
     * @param Expediente $expediente
     * @param int|null $responsableId
     * @return array
     */
    public function crearDisposicionParaExpediente(Expediente $expediente, ?int $responsableId = null): array
    {
        // Verificar que no tenga disposición activa
        $disposicionExistente = $expediente->disposicionFinal()
            ->whereNotIn('estado', [
                DisposicionFinal::ESTADO_RECHAZADO,
                DisposicionFinal::ESTADO_CANCELADO
            ])
            ->first();

        if ($disposicionExistente) {
            return [
                'success' => false,
                'razon' => 'Ya tiene disposición activa: ' . $disposicionExistente->estado,
            ];
        }

        // Obtener tipo de disposición desde la configuración
        $tipoDisposicion = $this->obtenerTipoDisposicionSugerido($expediente);

        if (!$tipoDisposicion) {
            return [
                'success' => false,
                'razon' => 'No se pudo determinar el tipo de disposición desde la TRD',
            ];
        }

        // Crear la disposición
        $disposicion = DB::transaction(function () use ($expediente, $tipoDisposicion, $responsableId) {
            $disposicion = DisposicionFinal::create([
                'expediente_id' => $expediente->id,
                'documento_id' => null,
                'responsable_id' => $responsableId ?? $expediente->responsable_id ?? auth()->id(),
                'tipo_disposicion' => $tipoDisposicion,
                'estado' => DisposicionFinal::ESTADO_PENDIENTE,
                'fecha_vencimiento_retencion' => $expediente->fecha_eliminacion,
                'fecha_propuesta' => Carbon::now(),
                'justificacion' => $this->generarJustificacionAutomatica($expediente),
                'observaciones' => 'Disposición generada automáticamente según configuración de TRD',
                'metadata_proceso' => [
                    'generado_automaticamente' => true,
                    'fecha_generacion' => Carbon::now()->toISOString(),
                    'basado_en' => [
                        'subserie' => $expediente->subserie->nombre ?? null,
                        'serie' => $expediente->serie->nombre ?? null,
                        'disposicion_configurada' => $expediente->disposicion_final,
                        'tiempo_retencion_gestion' => $expediente->anos_archivo_gestion,
                        'tiempo_retencion_central' => $expediente->anos_archivo_central,
                    ],
                ],
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'disposicion_automatica',
                'accion' => 'crear',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Disposición generada automáticamente para expediente: {$expediente->codigo}",
                'valores_nuevos' => json_encode([
                    'expediente_id' => $expediente->id,
                    'tipo_disposicion' => $tipoDisposicion,
                    'basado_en_trd' => true,
                ]),
            ]);

            return $disposicion;
        });

        return [
            'success' => true,
            'disposicion_id' => $disposicion->id,
            'tipo_disposicion' => $tipoDisposicion,
        ];
    }

    /**
     * Obtener tipo de disposición sugerido basado en TRD
     * 
     * @param Expediente $expediente
     * @return string|null
     */
    public function obtenerTipoDisposicionSugerido(Expediente $expediente): ?string
    {
        // Prioridad 1: Disposición configurada directamente en el expediente
        if ($expediente->disposicion_final) {
            return $this->normalizarTipoDisposicion($expediente->disposicion_final);
        }

        // Prioridad 2: Disposición de la subserie
        if ($expediente->subserie && $expediente->subserie->disposicion_final) {
            return $this->normalizarTipoDisposicion($expediente->subserie->disposicion_final);
        }

        // Prioridad 3: Buscar en TRDTiempoRetencion si existe relación con CCDNivel
        if ($expediente->ccd_nivel_id) {
            $tiempoRetencion = TRDTiempoRetencion::where('ccd_nivel_id', $expediente->ccd_nivel_id)
                ->first();
            
            if ($tiempoRetencion && $tiempoRetencion->disposicion_final) {
                return self::MAPEO_DISPOSICION[$tiempoRetencion->disposicion_final] ?? null;
            }
        }

        // Prioridad 4: Buscar por subserie en CCDNivel
        if ($expediente->subserie_id) {
            $ccdNivel = CCDNivel::where('tipo_nivel', CCDNivel::TIPO_SUBSERIE)
                ->where('nombre', 'LIKE', '%' . ($expediente->subserie->nombre ?? '') . '%')
                ->first();
            
            if ($ccdNivel) {
                $tiempoRetencion = TRDTiempoRetencion::where('ccd_nivel_id', $ccdNivel->id)->first();
                if ($tiempoRetencion && $tiempoRetencion->disposicion_final) {
                    return self::MAPEO_DISPOSICION[$tiempoRetencion->disposicion_final] ?? null;
                }
            }
        }

        // Prioridad 5: Usar disposición por defecto según flags de subserie
        if ($expediente->subserie) {
            $subserie = $expediente->subserie;
            
            if ($subserie->disposicion_ct) return 'conservacion_permanente';
            if ($subserie->disposicion_e) return 'eliminacion_controlada';
            if ($subserie->disposicion_d) return 'digitalizacion';
            if ($subserie->disposicion_s) return 'transferencia_historica';
        }

        return null;
    }

    /**
     * Normalizar tipo de disposición a formato del sistema
     * 
     * @param string $disposicion
     * @return string
     */
    private function normalizarTipoDisposicion(string $disposicion): string
    {
        // Si ya es un código TRD (CT, E, D, S, M)
        if (isset(self::MAPEO_DISPOSICION[$disposicion])) {
            return self::MAPEO_DISPOSICION[$disposicion];
        }

        // Si ya está en formato largo
        $formatosValidos = [
            'conservacion_permanente',
            'eliminacion_controlada',
            'transferencia_historica',
            'digitalizacion',
            'microfilmacion',
        ];

        // Mapeo de formatos alternativos
        $mapeoAlternativo = [
            'conservacion_total' => 'conservacion_permanente',
            'eliminacion' => 'eliminacion_controlada',
            'seleccion' => 'transferencia_historica',
            'transferencia' => 'transferencia_historica',
        ];

        $disposicionLower = strtolower($disposicion);

        if (in_array($disposicionLower, $formatosValidos)) {
            return $disposicionLower;
        }

        return $mapeoAlternativo[$disposicionLower] ?? 'conservacion_permanente';
    }

    /**
     * Calcular información de retención del expediente
     * 
     * @param Expediente $expediente
     * @return array
     */
    private function calcularInfoRetencion(Expediente $expediente): array
    {
        $fechaCierre = $expediente->fecha_cierre;
        $anosGestion = $expediente->anos_archivo_gestion ?? 0;
        $anosCentral = $expediente->anos_archivo_central ?? 0;

        $fechaFinGestion = $fechaCierre ? $fechaCierre->copy()->addYears($anosGestion) : null;
        $fechaFinCentral = $fechaFinGestion ? $fechaFinGestion->copy()->addYears($anosCentral) : null;

        return [
            'fecha_cierre' => $fechaCierre?->format('Y-m-d'),
            'anos_archivo_gestion' => $anosGestion,
            'anos_archivo_central' => $anosCentral,
            'anos_total_retencion' => $anosGestion + $anosCentral,
            'fecha_fin_archivo_gestion' => $fechaFinGestion?->format('Y-m-d'),
            'fecha_fin_archivo_central' => $fechaFinCentral?->format('Y-m-d'),
            'disposicion_configurada' => $expediente->disposicion_final,
            'origen_disposicion' => $this->determinarOrigenDisposicion($expediente),
        ];
    }

    /**
     * Determinar de dónde viene la configuración de disposición
     * 
     * @param Expediente $expediente
     * @return string
     */
    private function determinarOrigenDisposicion(Expediente $expediente): string
    {
        if ($expediente->disposicion_final) {
            return 'expediente';
        }

        if ($expediente->subserie && $expediente->subserie->disposicion_final) {
            return 'subserie';
        }

        if ($expediente->ccd_nivel_id) {
            $tiempoRetencion = TRDTiempoRetencion::where('ccd_nivel_id', $expediente->ccd_nivel_id)->first();
            if ($tiempoRetencion) {
                return 'trd_tiempo_retencion';
            }
        }

        return 'no_definido';
    }

    /**
     * Generar justificación automática para la disposición
     * 
     * @param Expediente $expediente
     * @return string
     */
    private function generarJustificacionAutomatica(Expediente $expediente): string
    {
        $partes = [];
        
        $partes[] = "Disposición generada automáticamente según la Tabla de Retención Documental (TRD).";
        
        if ($expediente->fecha_cierre) {
            $partes[] = "Expediente cerrado el " . $expediente->fecha_cierre->format('d/m/Y') . ".";
        }
        
        $tiempoTotal = ($expediente->anos_archivo_gestion ?? 0) + ($expediente->anos_archivo_central ?? 0);
        if ($tiempoTotal > 0) {
            $partes[] = "Tiempo de retención configurado: {$tiempoTotal} años ({$expediente->anos_archivo_gestion} en gestión + {$expediente->anos_archivo_central} en central).";
        }
        
        if ($expediente->fecha_eliminacion) {
            $partes[] = "Fecha de cumplimiento de retención: " . $expediente->fecha_eliminacion->format('d/m/Y') . ".";
        }
        
        $origen = $this->determinarOrigenDisposicion($expediente);
        $origenes = [
            'expediente' => 'configuración del expediente',
            'subserie' => 'subserie documental',
            'trd_tiempo_retencion' => 'TRD - Tiempos de Retención',
            'no_definido' => 'configuración por defecto',
        ];
        $partes[] = "Tipo de disposición basado en: " . ($origenes[$origen] ?? $origen) . ".";

        return implode(' ', $partes);
    }

    /**
     * Obtener resumen de configuración TRD para un expediente
     * 
     * @param Expediente $expediente
     * @return array
     */
    public function getResumenConfiguracionTRD(Expediente $expediente): array
    {
        $info = $this->calcularInfoRetencion($expediente);
        $tipoSugerido = $this->obtenerTipoDisposicionSugerido($expediente);

        return [
            'expediente' => [
                'id' => $expediente->id,
                'codigo' => $expediente->codigo,
                'titulo' => $expediente->titulo,
            ],
            'serie' => $expediente->serie ? [
                'id' => $expediente->serie->id,
                'codigo' => $expediente->serie->codigo,
                'nombre' => $expediente->serie->nombre,
            ] : null,
            'subserie' => $expediente->subserie ? [
                'id' => $expediente->subserie->id,
                'codigo' => $expediente->subserie->codigo,
                'nombre' => $expediente->subserie->nombre,
                'disposicion_final' => $expediente->subserie->disposicion_final,
            ] : null,
            'retencion' => $info,
            'tipo_disposicion_sugerido' => $tipoSugerido,
            'tipo_disposicion_label' => $this->getLabelTipoDisposicion($tipoSugerido),
        ];
    }

    /**
     * Obtener label legible del tipo de disposición
     */
    private function getLabelTipoDisposicion(?string $tipo): string
    {
        $labels = [
            'conservacion_permanente' => 'Conservación Permanente',
            'eliminacion_controlada' => 'Eliminación Controlada',
            'transferencia_historica' => 'Transferencia Histórica',
            'digitalizacion' => 'Digitalización',
            'microfilmacion' => 'Microfilmación',
        ];

        return $labels[$tipo] ?? 'No definido';
    }
}
