<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SolicitudFirma extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitudes_firma';

    protected $fillable = [
        'documento_id',
        'solicitante_id',
        'titulo',
        'descripcion',
        'tipo_flujo',
        'prioridad',
        'fecha_limite',
        'estado',
        'configuracion_flujo',
        'metadata_solicitud',
        'completada_en',
        'cancelada_en',
        'razon_cancelacion'
    ];

    protected $casts = [
        'fecha_limite' => 'datetime',
        'completada_en' => 'datetime',
        'cancelada_en' => 'datetime',
        'configuracion_flujo' => 'array',
        'metadata_solicitud' => 'array'
    ];

    protected $dates = [
        'fecha_limite',
        'completada_en',
        'cancelada_en',
        'deleted_at'
    ];

    // Estados de la solicitud
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';
    const ESTADO_VENCIDA = 'vencida';

    // Tipos de flujo
    const FLUJO_SECUENCIAL = 'secuencial';
    const FLUJO_PARALELO = 'paralelo';
    const FLUJO_MIXTO = 'mixto';

    // Prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';

    /**
     * Relación con el documento a firmar
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    /**
     * Relación con el usuario solicitante
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * Relación con los firmantes de la solicitud
     */
    public function firmantes(): HasMany
    {
        return $this->hasMany(FirmanteSolicitud::class, 'solicitud_id');
    }

    /**
     * Relación con las firmas realizadas
     */
    public function firmas(): HasMany
    {
        return $this->hasMany(FirmaDigital::class, 'solicitud_firma_id');
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para solicitudes en proceso
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado', self::ESTADO_EN_PROCESO);
    }

    /**
     * Scope para solicitudes completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /**
     * Scope para solicitudes vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->where('fecha_limite', '<', now())
                    ->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO]);
    }

    /**
     * Scope para solicitudes próximas a vencer
     */
    public function scopeProximasAVencer($query, $horas = 24)
    {
        return $query->whereBetween('fecha_limite', [
                        now(),
                        now()->addHours($horas)
                    ])
                    ->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO]);
    }

    /**
     * Scope para solicitudes de un usuario específico
     */
    public function scopeDelSolicitante($query, $usuarioId)
    {
        return $query->where('solicitante_id', $usuarioId);
    }

    /**
     * Scope para solicitudes donde el usuario debe firmar
     */
    public function scopeParaFirmar($query, $usuarioId)
    {
        return $query->whereHas('firmantes', function ($q) use ($usuarioId) {
            $q->where('usuario_id', $usuarioId)
              ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE);
        });
    }

    /**
     * Verificar si la solicitud está vencida
     */
    public function getVencidaAttribute(): bool
    {
        return $this->fecha_limite && 
               $this->fecha_limite < now() && 
               in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO]);
    }

    /**
     * Verificar si está próxima a vencer
     */
    public function getProximaAVencerAttribute(): bool
    {
        if (!$this->fecha_limite || $this->vencida) {
            return false;
        }

        $horasRestantes = now()->diffInHours($this->fecha_limite, false);
        return $horasRestantes <= 24 && $horasRestantes > 0;
    }

    /**
     * Obtener progreso de firmas
     */
    public function getProgresoAttribute(): array
    {
        $totalFirmantes = $this->firmantes()->count();
        $firmasCompletadas = $this->firmantes()->where('estado', FirmanteSolicitud::ESTADO_FIRMADO)->count();
        $firmasPendientes = $this->firmantes()->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)->count();
        $firmasRechazadas = $this->firmantes()->where('estado', FirmanteSolicitud::ESTADO_RECHAZADO)->count();

        return [
            'total' => $totalFirmantes,
            'completadas' => $firmasCompletadas,
            'pendientes' => $firmasPendientes,
            'rechazadas' => $firmasRechazadas,
            'porcentaje' => $totalFirmantes > 0 ? ($firmasCompletadas / $totalFirmantes) * 100 : 0
        ];
    }

    /**
     * Obtener siguiente firmante en el flujo
     */
    public function getSiguienteFirmanteAttribute(): ?FirmanteSolicitud
    {
        if ($this->tipo_flujo === self::FLUJO_PARALELO) {
            // En flujo paralelo, todos pueden firmar simultáneamente
            return $this->firmantes()
                        ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                        ->orderBy('orden')
                        ->first();
        }

        // En flujo secuencial, solo el siguiente en orden
        return $this->firmantes()
                    ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                    ->orderBy('orden')
                    ->first();
    }

    /**
     * Verificar si un usuario puede firmar ahora
     */
    public function puedeFiremar(User $usuario): bool
    {
        $firmante = $this->firmantes()
                        ->where('usuario_id', $usuario->id)
                        ->first();

        if (!$firmante || $firmante->estado !== FirmanteSolicitud::ESTADO_PENDIENTE) {
            return false;
        }

        if ($this->estado !== self::ESTADO_EN_PROCESO && $this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        if ($this->vencida) {
            return false;
        }

        // En flujo paralelo, puede firmar siempre
        if ($this->tipo_flujo === self::FLUJO_PARALELO) {
            return true;
        }

        // En flujo secuencial, verificar que es su turno
        $siguienteFirmante = $this->siguiente_firmante;
        return $siguienteFirmante && $siguienteFirmante->id === $firmante->id;
    }

    /**
     * Iniciar el proceso de firma
     */
    public function iniciar(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        $this->update(['estado' => self::ESTADO_EN_PROCESO]);

        // Notificar a los firmantes según el tipo de flujo
        if ($this->tipo_flujo === self::FLUJO_PARALELO) {
            // Notificar a todos los firmantes
            $this->firmantes()->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                             ->get()
                             ->each(function ($firmante) {
                                 $firmante->notificar();
                             });
        } else {
            // Notificar solo al primer firmante
            $primerFirmante = $this->firmantes()
                                  ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
                                  ->orderBy('orden')
                                  ->first();
            if ($primerFirmante) {
                $primerFirmante->notificar();
            }
        }

        return true;
    }

    /**
     * Completar la solicitud
     */
    public function completar(): bool
    {
        $progreso = $this->progreso;
        
        if ($progreso['pendientes'] > 0) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_COMPLETADA,
            'completada_en' => now()
        ]);

        // Notificar al solicitante
        $this->notificarComplecion();

        return true;
    }

    /**
     * Cancelar la solicitud
     */
    public function cancelar(string $razon = 'No especificada'): bool
    {
        if ($this->estado === self::ESTADO_COMPLETADA) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'cancelada_en' => now(),
            'razon_cancelacion' => $razon
        ]);

        // Notificar a todos los firmantes pendientes
        $this->firmantes()
             ->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)
             ->get()
             ->each(function ($firmante) {
                 $firmante->notificarCancelacion();
             });

        return true;
    }

    /**
     * Marcar como vencida
     */
    public function marcarVencida(): bool
    {
        if (!$this->vencida) {
            return false;
        }

        $this->update(['estado' => self::ESTADO_VENCIDA]);

        // Notificar vencimiento
        $this->notificarVencimiento();

        return true;
    }

    /**
     * Obtener estadísticas de la solicitud
     */
    public function obtenerEstadisticas(): array
    {
        $firmantes = $this->firmantes;
        
        return [
            'total_firmantes' => $firmantes->count(),
            'firmado' => $firmantes->where('estado', FirmanteSolicitud::ESTADO_FIRMADO)->count(),
            'pendiente' => $firmantes->where('estado', FirmanteSolicitud::ESTADO_PENDIENTE)->count(),
            'rechazado' => $firmantes->where('estado', FirmanteSolicitud::ESTADO_RECHAZADO)->count(),
            'tiempo_promedio_firma' => $this->calcularTiempoPromedioFirma(),
            'dias_transcurridos' => $this->created_at->diffInDays(now()),
            'tiempo_restante' => $this->fecha_limite ? now()->diffForHumans($this->fecha_limite) : null
        ];
    }

    /**
     * Calcular tiempo promedio de firma
     */
    private function calcularTiempoPromedioFirma(): ?float
    {
        $firmasCompletadas = $this->firmantes()
                                 ->where('estado', FirmanteSolicitud::ESTADO_FIRMADO)
                                 ->whereNotNull('firmado_en')
                                 ->get();

        if ($firmasCompletadas->isEmpty()) {
            return null;
        }

        $tiempoTotal = $firmasCompletadas->sum(function ($firmante) {
            return $firmante->created_at->diffInHours($firmante->firmado_en);
        });

        return $tiempoTotal / $firmasCompletadas->count();
    }

    /**
     * Notificar compleción de la solicitud
     */
    private function notificarComplecion(): void
    {
        // Implementar notificación al solicitante
        // Por ejemplo, usando el sistema de notificaciones existente
    }

    /**
     * Notificar vencimiento de la solicitud
     */
    private function notificarVencimiento(): void
    {
        // Implementar notificación de vencimiento
        // Por ejemplo, usando el sistema de notificaciones existente
    }
}
