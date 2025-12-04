<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para Historial de Acciones de Disposición del SGDEA
 * 
 * Implementa requerimientos:
 * - REQ-RD-002: Auditoría y trazabilidad (pistas inmutables)
 * - REQ-RD-008: Registro de acciones especiales
 */
class HistorialAccionDisposicion extends Model
{
    use HasFactory;

    protected $table = 'historial_acciones_disposicion';

    protected $fillable = [
        'proceso_retencion_id',
        'tipo_accion',
        'estado_anterior',
        'estado_nuevo',
        'descripcion_accion',
        'datos_adicionales',
        'fecha_accion',
        'usuario_accion',
        'ip_origen',
        'user_agent',
        'hash_accion'
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'fecha_accion' => 'datetime'
    ];

    // Tipos de acciones auditables
    const TIPO_CREACION_PROCESO = 'creacion_proceso';
    const TIPO_CALCULO_FECHAS = 'calculo_fechas';
    const TIPO_ENVIO_ALERTA = 'envio_alerta';
    const TIPO_CAMBIO_ESTADO = 'cambio_estado';
    const TIPO_APLAZAMIENTO = 'aplazamiento';
    const TIPO_TRANSFERENCIA = 'transferencia';
    const TIPO_ELIMINACION = 'eliminacion';
    const TIPO_CONSERVACION = 'conservacion';
    const TIPO_SUSPENSION = 'suspension';
    const TIPO_REACTIVACION = 'reactivacion';
    const TIPO_MODIFICACION_FECHAS = 'modificacion_fechas';
    const TIPO_BLOQUEO = 'bloqueo';
    const TIPO_DESBLOQUEO = 'desbloqueo';

    protected static function boot()
    {
        parent::boot();

        // REQ-RD-002: Generar hash inmutable para garantizar integridad
        static::creating(function ($historial) {
            $historial->generarHashInmutable();
        });
    }

    /**
     * Relaciones
     */
    public function procesoRetencion()
    {
        return $this->belongsTo(ProcesoRetencionDisposicion::class, 'proceso_retencion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_accion');
    }

    /**
     * Scopes
     */
    public function scopePorTipoAccion($query, string $tipo)
    {
        return $query->where('tipo_accion', $tipo);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        $query->where('fecha_accion', '>=', $fechaInicio);
        
        if ($fechaFin) {
            $query->where('fecha_accion', '<=', $fechaFin);
        }
        
        return $query;
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_accion', $usuarioId);
    }

    /**
     * REQ-RD-002: Generar hash inmutable para garantizar integridad de auditoría
     */
    public function generarHashInmutable()
    {
        $datos = [
            'proceso_retencion_id' => $this->proceso_retencion_id,
            'tipo_accion' => $this->tipo_accion,
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'descripcion_accion' => $this->descripcion_accion,
            'fecha_accion' => $this->fecha_accion?->format('Y-m-d H:i:s'),
            'usuario_accion' => $this->usuario_accion,
            'ip_origen' => $this->ip_origen,
            'timestamp' => now()->timestamp
        ];

        $this->hash_accion = hash('sha256', json_encode($datos, JSON_SORT_KEYS));
    }

    /**
     * REQ-RD-002: Verificar integridad del registro de auditoría
     */
    public function verificarIntegridad(): bool
    {
        $hashActual = $this->hash_accion;
        $this->generarHashInmutable();
        
        return $hashActual === $this->hash_accion;
    }

    /**
     * Obtener descripción legible del cambio de estado
     */
    public function getDescripcionCambioEstadoAttribute(): string
    {
        if ($this->estado_anterior && $this->estado_nuevo && $this->estado_anterior !== $this->estado_nuevo) {
            return "Estado cambió de '{$this->estado_anterior}' a '{$this->estado_nuevo}'";
        }
        
        return $this->descripcion_accion;
    }

    /**
     * Verificar si es una acción crítica que requiere atención especial
     */
    public function getEsCriticaAttribute(): bool
    {
        return in_array($this->tipo_accion, [
            self::TIPO_ELIMINACION,
            self::TIPO_TRANSFERENCIA,
            self::TIPO_CONSERVACION,
            self::TIPO_BLOQUEO
        ]);
    }

    /**
     * Obtener icono representativo de la acción
     */
    public function getIconoAccionAttribute(): string
    {
        return match($this->tipo_accion) {
            self::TIPO_CREACION_PROCESO => 'plus-circle',
            self::TIPO_CALCULO_FECHAS => 'calendar',
            self::TIPO_ENVIO_ALERTA => 'bell',
            self::TIPO_CAMBIO_ESTADO => 'arrow-right',
            self::TIPO_APLAZAMIENTO => 'pause-circle',
            self::TIPO_TRANSFERENCIA => 'send',
            self::TIPO_ELIMINACION => 'trash-2',
            self::TIPO_CONSERVACION => 'archive',
            self::TIPO_SUSPENSION => 'stop-circle',
            self::TIPO_REACTIVACION => 'play-circle',
            self::TIPO_MODIFICACION_FECHAS => 'edit-3',
            self::TIPO_BLOQUEO => 'lock',
            self::TIPO_DESBLOQUEO => 'unlock',
            default => 'info'
        };
    }

    /**
     * Obtener color representativo de la acción para UI
     */
    public function getColorAccionAttribute(): string
    {
        return match($this->tipo_accion) {
            self::TIPO_CREACION_PROCESO => 'green',
            self::TIPO_CALCULO_FECHAS => 'blue',
            self::TIPO_ENVIO_ALERTA => 'yellow',
            self::TIPO_CAMBIO_ESTADO => 'indigo',
            self::TIPO_APLAZAMIENTO => 'orange',
            self::TIPO_TRANSFERENCIA => 'purple',
            self::TIPO_ELIMINACION => 'red',
            self::TIPO_CONSERVACION => 'emerald',
            self::TIPO_SUSPENSION => 'gray',
            self::TIPO_REACTIVACION => 'green',
            self::TIPO_MODIFICACION_FECHAS => 'amber',
            self::TIPO_BLOQUEO => 'red',
            self::TIPO_DESBLOQUEO => 'green',
            default => 'gray'
        };
    }
}
