<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class WorkflowDocumento extends Model
{
    use HasFactory;

    protected $table = 'workflow_documentos';

    protected $fillable = [
        'documento_id',
        'estado',
        'solicitante_id',
        'revisor_actual_id',
        'aprobador_final_id',
        'niveles_aprobacion',
        'nivel_actual',
        'requiere_aprobacion_unanime',
        'fecha_solicitud',
        'fecha_asignacion',
        'fecha_aprobacion',
        'fecha_rechazo',
        'fecha_vencimiento',
        'descripcion_solicitud',
        'comentarios_finales',
        'prioridad',
        'metadata'
    ];

    protected $casts = [
        'niveles_aprobacion' => 'array',
        'metadata' => 'array',
        'requiere_aprobacion_unanime' => 'boolean',
        'fecha_solicitud' => 'datetime',
        'fecha_asignacion' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'fecha_rechazo' => 'datetime',
        'fecha_vencimiento' => 'datetime',
    ];

    // Estados del workflow
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_REVISION = 'en_revision';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';

    // Prioridades
    const PRIORIDAD_CRITICA = 1;
    const PRIORIDAD_ALTA = 2;
    const PRIORIDAD_MEDIA = 3;
    const PRIORIDAD_BAJA = 4;

    /**
     * Relación con el documento
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    /**
     * Relación con el solicitante
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * Relación con el revisor actual
     */
    public function revisorActual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_actual_id');
    }

    /**
     * Relación con el aprobador final
     */
    public function aprobadorFinal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_final_id');
    }

    /**
     * Relación con las aprobaciones
     */
    public function aprobaciones(): HasMany
    {
        return $this->hasMany(AprobacionWorkflow::class, 'workflow_documento_id');
    }

    /**
     * Scope para workflows pendientes
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_REVISION]);
    }

    /**
     * Scope para workflows vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_vencimiento', '<', now())
                    ->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_REVISION]);
    }

    /**
     * Scope para workflows de un usuario específico
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('revisor_actual_id', $usuarioId)
                    ->orWhere('solicitante_id', $usuarioId)
                    ->orWhereJsonContains('niveles_aprobacion', $usuarioId);
    }

    /**
     * Scope por prioridad
     */
    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Obtener el siguiente aprobador en el flujo
     */
    public function getSiguienteAprobadorAttribute()
    {
        if ($this->nivel_actual < count($this->niveles_aprobacion)) {
            $siguienteAprobadorId = $this->niveles_aprobacion[$this->nivel_actual];
            return User::find($siguienteAprobadorId);
        }
        return null;
    }

    /**
     * Verificar si el workflow está vencido
     */
    public function getEstaVencidoAttribute()
    {
        return $this->fecha_vencimiento && 
               $this->fecha_vencimiento->isPast() && 
               in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_REVISION]);
    }

    /**
     * Obtener el progreso del workflow en porcentaje
     */
    public function getProgresoAttribute()
    {
        if (empty($this->niveles_aprobacion)) {
            return 0;
        }

        if ($this->estado === self::ESTADO_APROBADO) {
            return 100;
        }

        if ($this->estado === self::ESTADO_RECHAZADO) {
            return 0;
        }

        return ($this->nivel_actual / count($this->niveles_aprobacion)) * 100;
    }

    /**
     * Obtener etiqueta de prioridad
     */
    public function getEtiquetaPrioridadAttribute()
    {
        $prioridades = [
            self::PRIORIDAD_CRITICA => 'Crítica',
            self::PRIORIDAD_ALTA => 'Alta',
            self::PRIORIDAD_MEDIA => 'Media',
            self::PRIORIDAD_BAJA => 'Baja',
        ];

        return $prioridades[$this->prioridad] ?? 'Media';
    }

    /**
     * Verificar si un usuario puede aprobar en el nivel actual
     */
    public function puedeAprobar(User $usuario): bool
    {
        if ($this->estado !== self::ESTADO_EN_REVISION) {
            return false;
        }

        if (empty($this->niveles_aprobacion) || $this->nivel_actual >= count($this->niveles_aprobacion)) {
            return false;
        }

        $aprobadorActualId = $this->niveles_aprobacion[$this->nivel_actual];
        return $usuario->id == $aprobadorActualId;
    }
}
