<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Modelo para Procesos de Retención y Disposición del SGDEA
 * 
 * Implementa requerimientos:
 * - REQ-RD-001: Gestión de tiempos de retención
 * - REQ-RD-002: Auditoría y trazabilidad
 * - REQ-RD-003: Propagación de cambios
 * - REQ-RD-005: Acciones de disposición
 * - REQ-RD-006: Cálculo automático de fechas
 * - REQ-RD-007: Sistema de alertas
 * - REQ-RD-008: Gestión de aplazamientos
 * - REQ-RD-009: Integridad referencial
 */
class ProcesoRetencionDisposicion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'procesos_retencion_disposicion';

    protected $fillable = [
        'codigo_proceso',
        'documento_id',
        'expediente_id',
        'tipo_entidad',
        'trd_id',
        'serie_documental_id',
        'subserie_documental_id',
        'fecha_creacion_documento',
        'periodo_retencion_archivo_gestion',
        'periodo_retencion_archivo_central',
        'fecha_vencimiento_gestion',
        'fecha_vencimiento_central',
        'fecha_alerta_previa',
        'estado',
        'accion_disposicion',
        'aplazado',
        'fecha_aplazamiento',
        'razon_aplazamiento',
        'fecha_fin_aplazamiento',
        'usuario_aplazamiento',
        'alertas_activas',
        'dias_alerta_previa',
        'canales_notificacion',
        'ultima_alerta_enviada',
        'hash_integridad',
        'bloqueado_eliminacion',
        'razon_bloqueo',
        'metadatos_adicionales',
        'observaciones',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha_creacion_documento' => 'date',
        'fecha_vencimiento_gestion' => 'date',
        'fecha_vencimiento_central' => 'date',
        'fecha_alerta_previa' => 'date',
        'fecha_aplazamiento' => 'date',
        'fecha_fin_aplazamiento' => 'date',
        'aplazado' => 'boolean',
        'alertas_activas' => 'boolean',
        'bloqueado_eliminacion' => 'boolean',
        'canales_notificacion' => 'array',
        'metadatos_adicionales' => 'array',
        'ultima_alerta_enviada' => 'datetime'
    ];

    // Estados del proceso de retención
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_ALERTA_PREVIA = 'alerta_previa';
    const ESTADO_VENCIDO = 'vencido';
    const ESTADO_EN_DISPOSICION = 'en_disposicion';
    const ESTADO_TRANSFERIDO = 'transferido';
    const ESTADO_ELIMINADO = 'eliminado';
    const ESTADO_CONSERVADO = 'conservado';
    const ESTADO_APLAZADO = 'aplazado';
    const ESTADO_SUSPENDIDO = 'suspendido';

    // Acciones de disposición
    const ACCION_CONSERVACION_PERMANENTE = 'conservacion_permanente';
    const ACCION_ELIMINACION = 'eliminacion';
    const ACCION_TRANSFERENCIA_HISTORICO = 'transferencia_historico';
    const ACCION_SELECCION_DOCUMENTAL = 'seleccion_documental';
    const ACCION_MICROFILMACION = 'microfilmacion';
    const ACCION_DIGITALIZACION_PERMANENTE = 'digitalizacion_permanente';

    protected static function boot()
    {
        parent::boot();

        // REQ-RD-001: Generar código único del proceso
        static::creating(function ($proceso) {
            if (empty($proceso->codigo_proceso)) {
                $proceso->codigo_proceso = 'RET-' . now()->format('Y') . '-' . str_pad(static::count() + 1, 8, '0', STR_PAD_LEFT);
            }

            // REQ-RD-006: Calcular fechas automáticamente si no están definidas
            if (empty($proceso->fecha_vencimiento_gestion) || empty($proceso->fecha_vencimiento_central)) {
                $proceso->calcularFechasRetencion();
            }

            // REQ-RD-009: Generar hash de integridad
            $proceso->generarHashIntegridad();
        });

        // REQ-RD-002: Registrar cambios en auditoría
        static::updating(function ($proceso) {
            $proceso->generarHashIntegridad();
        });

        static::updated(function ($proceso) {
            $proceso->registrarCambioAuditoria();
        });
    }

    /**
     * Relaciones
     */
    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function trd()
    {
        return $this->belongsTo(TablaRetencionDocumental::class, 'trd_id');
    }

    public function serieDocumental()
    {
        return $this->belongsTo(SerieDocumental::class);
    }

    public function subserieDocumental()
    {
        return $this->belongsTo(SubserieDocumental::class);
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usuarioModificador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function usuarioAplazamiento()
    {
        return $this->belongsTo(User::class, 'usuario_aplazamiento');
    }

    public function historialAcciones()
    {
        return $this->hasMany(HistorialAccionDisposicion::class, 'proceso_retencion_id');
    }

    public function alertas()
    {
        return $this->hasMany(AlertaRetencion::class, 'proceso_retencion_id');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', self::ESTADO_VENCIDO);
    }

    public function scopeProximosVencer($query, $dias = 30)
    {
        return $query->where('fecha_alerta_previa', '<=', now()->addDays($dias))
                    ->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeRequierenAtencion($query)
    {
        return $query->whereIn('estado', [self::ESTADO_VENCIDO, self::ESTADO_ALERTA_PREVIA]);
    }

    /**
     * REQ-RD-006: Calcular fechas de retención automáticamente
     */
    public function calcularFechasRetencion()
    {
        if (!$this->trd || !$this->fecha_creacion_documento) {
            return false;
        }

        $fechaCreacion = Carbon::parse($this->fecha_creacion_documento);

        // Calcular fecha de vencimiento en archivo de gestión
        $this->fecha_vencimiento_gestion = $fechaCreacion->copy()
            ->addYears($this->periodo_retencion_archivo_gestion ?? 0);

        // Calcular fecha de vencimiento en archivo central
        $this->fecha_vencimiento_central = $this->fecha_vencimiento_gestion->copy()
            ->addYears($this->periodo_retencion_archivo_central ?? 0);

        // Calcular fecha de alerta previa
        $diasAlerta = $this->dias_alerta_previa ?? 30;
        $this->fecha_alerta_previa = $this->fecha_vencimiento_gestion->copy()
            ->subDays($diasAlerta);

        return true;
    }

    /**
     * REQ-RD-007: Verificar si requiere alerta
     */
    public function requiereAlerta(): bool
    {
        if (!$this->alertas_activas || $this->estado !== self::ESTADO_ACTIVO) {
            return false;
        }

        $now = now();
        
        // Verificar si se acerca el vencimiento
        if ($this->fecha_alerta_previa && $now >= $this->fecha_alerta_previa) {
            return true;
        }

        // Verificar si ya está vencido
        if ($this->fecha_vencimiento_gestion && $now >= $this->fecha_vencimiento_gestion) {
            return true;
        }

        return false;
    }

    /**
     * REQ-RD-005: Ejecutar acción de disposición
     */
    public function ejecutarDisposicion(string $accion, User $usuario, string $observaciones = null): bool
    {
        $estadoAnterior = $this->estado;

        try {
            switch ($accion) {
                case self::ACCION_CONSERVACION_PERMANENTE:
                    $this->estado = self::ESTADO_CONSERVADO;
                    $this->accion_disposicion = $accion;
                    break;

                case self::ACCION_ELIMINACION:
                    if ($this->bloqueado_eliminacion) {
                        throw new \Exception('El documento está bloqueado para eliminación: ' . $this->razon_bloqueo);
                    }
                    $this->estado = self::ESTADO_ELIMINADO;
                    $this->accion_disposicion = $accion;
                    break;

                case self::ACCION_TRANSFERENCIA_HISTORICO:
                    $this->estado = self::ESTADO_TRANSFERIDO;
                    $this->accion_disposicion = $accion;
                    break;

                default:
                    throw new \Exception('Acción de disposición no válida: ' . $accion);
            }

            $this->updated_by = $usuario->id;
            $this->save();

            // Registrar en auditoría
            $this->registrarAccionAuditoria('disposicion_ejecutada', [
                'accion_disposicion' => $accion,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $this->estado,
                'observaciones' => $observaciones
            ], $usuario);

            return true;

        } catch (\Exception $e) {
            // Log error
            \Log::error('Error ejecutando disposición: ' . $e->getMessage(), [
                'proceso_id' => $this->id,
                'accion' => $accion,
                'usuario' => $usuario->id
            ]);
            return false;
        }
    }

    /**
     * REQ-RD-008: Aplazar disposición
     */
    public function aplazarDisposicion(Carbon $fechaFin, string $razon, User $usuario): bool
    {
        $this->aplazado = true;
        $this->fecha_aplazamiento = now();
        $this->fecha_fin_aplazamiento = $fechaFin;
        $this->razon_aplazamiento = $razon;
        $this->usuario_aplazamiento = $usuario->id;
        $this->estado = self::ESTADO_APLAZADO;
        $this->updated_by = $usuario->id;

        $saved = $this->save();

        if ($saved) {
            $this->registrarAccionAuditoria('aplazamiento', [
                'fecha_fin_aplazamiento' => $fechaFin->format('Y-m-d'),
                'razon' => $razon
            ], $usuario);
        }

        return $saved;
    }

    /**
     * REQ-RD-003: Actualizar estados basado en fechas
     */
    public function actualizarEstado(): bool
    {
        $estadoAnterior = $this->estado;
        $now = now();

        // Si está aplazado, verificar si el aplazamiento ha terminado
        if ($this->aplazado && $this->fecha_fin_aplazamiento && $now >= $this->fecha_fin_aplazamiento) {
            $this->aplazado = false;
            $this->estado = self::ESTADO_ACTIVO;
        }

        // Solo actualizar estados si está activo o en alerta previa
        if (!in_array($this->estado, [self::ESTADO_ACTIVO, self::ESTADO_ALERTA_PREVIA])) {
            return false;
        }

        // Verificar vencimiento
        if ($this->fecha_vencimiento_gestion && $now >= $this->fecha_vencimiento_gestion) {
            $this->estado = self::ESTADO_VENCIDO;
        }
        // Verificar alerta previa
        elseif ($this->fecha_alerta_previa && $now >= $this->fecha_alerta_previa) {
            $this->estado = self::ESTADO_ALERTA_PREVIA;
        }

        if ($this->estado !== $estadoAnterior) {
            $this->save();
            
            $this->registrarAccionAuditoria('cambio_estado_automatico', [
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $this->estado,
                'fecha_verificacion' => $now->format('Y-m-d H:i:s')
            ]);

            return true;
        }

        return false;
    }

    /**
     * REQ-RD-009: Generar hash de integridad
     */
    public function generarHashIntegridad()
    {
        $datos = [
            'codigo_proceso' => $this->codigo_proceso,
            'tipo_entidad' => $this->tipo_entidad,
            'entidad_id' => $this->documento_id ?? $this->expediente_id,
            'trd_id' => $this->trd_id,
            'fechas_retencion' => [
                'gestion' => $this->fecha_vencimiento_gestion?->format('Y-m-d'),
                'central' => $this->fecha_vencimiento_central?->format('Y-m-d')
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s')
        ];

        $this->hash_integridad = hash('sha256', json_encode($datos, JSON_SORT_KEYS));
    }

    /**
     * REQ-RD-009: Verificar integridad
     */
    public function verificarIntegridad(): bool
    {
        $hashActual = $this->hash_integridad;
        $this->generarHashIntegridad();
        
        return $hashActual === $this->hash_integridad;
    }

    /**
     * REQ-RD-002: Registrar acción en auditoría
     */
    private function registrarAccionAuditoria(string $tipoAccion, array $datosAdicionales = [], User $usuario = null)
    {
        $usuario = $usuario ?? auth()->user();
        
        if (!$usuario) return;

        HistorialAccionDisposicion::create([
            'proceso_retencion_id' => $this->id,
            'tipo_accion' => $tipoAccion,
            'estado_anterior' => $this->getOriginal('estado'),
            'estado_nuevo' => $this->estado,
            'descripcion_accion' => $this->generarDescripcionAccion($tipoAccion, $datosAdicionales),
            'datos_adicionales' => $datosAdicionales,
            'fecha_accion' => now(),
            'usuario_accion' => $usuario->id,
            'ip_origen' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * REQ-RD-002: Registrar cambio general en auditoría
     */
    private function registrarCambioAuditoria()
    {
        if ($this->isDirty()) {
            $cambios = $this->getDirty();
            unset($cambios['updated_at'], $cambios['hash_integridad']);
            
            if (!empty($cambios)) {
                $this->registrarAccionAuditoria('modificacion', [
                    'campos_modificados' => array_keys($cambios),
                    'valores_anteriores' => array_intersect_key($this->getOriginal(), $cambios),
                    'valores_nuevos' => $cambios
                ]);
            }
        }
    }

    /**
     * Generar descripción legible de la acción
     */
    private function generarDescripcionAccion(string $tipoAccion, array $datos = []): string
    {
        switch ($tipoAccion) {
            case 'creacion_proceso':
                return 'Proceso de retención y disposición creado automáticamente';
            case 'calculo_fechas':
                return 'Fechas de retención calculadas automáticamente';
            case 'cambio_estado_automatico':
                return "Estado cambiado automáticamente de '{$datos['estado_anterior']}' a '{$datos['estado_nuevo']}'";
            case 'aplazamiento':
                return "Disposición aplazada hasta {$datos['fecha_fin_aplazamiento']}. Razón: {$datos['razon']}";
            case 'disposicion_ejecutada':
                return "Acción de disposición ejecutada: {$datos['accion_disposicion']}";
            case 'modificacion':
                return 'Información del proceso modificada: ' . implode(', ', $datos['campos_modificados']);
            default:
                return "Acción ejecutada: {$tipoAccion}";
        }
    }

    /**
     * Obtener entidad relacionada (documento o expediente)
     */
    public function getEntidadRelacionadaAttribute()
    {
        return $this->tipo_entidad === 'documento' ? $this->documento : $this->expediente;
    }

    /**
     * Obtener días restantes hasta vencimiento
     */
    public function getDiasHastaVencimientoAttribute(): int
    {
        if (!$this->fecha_vencimiento_gestion) return -1;
        
        return now()->diffInDays($this->fecha_vencimiento_gestion, false);
    }

    /**
     * Verificar si el proceso está crítico (vencido o por vencer pronto)
     */
    public function getEsCriticoAttribute(): bool
    {
        return in_array($this->estado, [self::ESTADO_VENCIDO, self::ESTADO_ALERTA_PREVIA]) ||
               $this->dias_hasta_vencimiento <= 7;
    }
}
