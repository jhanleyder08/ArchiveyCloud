<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificacions';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensaje',
        'datos',
        'accion_url',
        'prioridad',
        'estado',
        'leida_en',
        'programada_para',
        'relacionado_id',
        'relacionado_tipo',
        'es_automatica',
        'creado_por',
        'expediente_id', // Para compatibilidad con el comando
        'documento_id',  // Para compatibilidad con el comando
        'leida',         // Para compatibilidad con el comando
        'archivada',     // Para compatibilidad con el comando
    ];

    protected $casts = [
        'datos' => 'array',
        'leida_en' => 'datetime',
        'programada_para' => 'datetime',
        'es_automatica' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'icono',
        'color_prioridad',
    ];

    /**
     * Relación con el usuario destinatario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el usuario creador
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación polimórfica con el objeto relacionado
     */
    public function relacionado(): MorphTo
    {
        return $this->morphTo('relacionado', 'relacionado_tipo', 'relacionado_id');
    }

    /**
     * Scope para notificaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeLeidas($query)
    {
        return $query->where('estado', 'leida');
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para notificaciones por prioridad
     */
    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para notificaciones de un usuario
     */
    public function scopeParaUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para notificaciones programadas que deben enviarse
     */
    public function scopeListasParaEnvio($query)
    {
        return $query->where('estado', 'pendiente')
                    ->where(function($q) {
                        $q->whereNull('programada_para')
                          ->orWhere('programada_para', '<=', Carbon::now());
                    });
    }

    /**
     * Marcar como leída
     */
    public function marcarComoLeida(): bool
    {
        return $this->update([
            'estado' => 'leida',
            'leida_en' => Carbon::now(),
        ]);
    }

    /**
     * Marcar como archivada
     */
    public function archivar(): bool
    {
        return $this->update(['estado' => 'archivada']);
    }

    /**
     * Obtener el ícono según el tipo de notificación
     */
    public function getIconoAttribute(): string
    {
        return match($this->tipo) {
            'expediente_vencido' => 'calendar-x',
            'expediente_proximo_vencer' => 'calendar-warning',
            'prestamo_vencido' => 'clock-x',
            'prestamo_proximo_vencer' => 'clock-warning',
            'disposicion_pendiente' => 'archive',
            'disposicion_aprobada' => 'check-circle',
            'documento_subido' => 'file-plus',
            'usuario_nuevo' => 'user-plus',
            'sistema' => 'settings',
            'seguridad' => 'shield-alert',
            default => 'bell',
        };
    }

    /**
     * Obtener el color según la prioridad
     */
    public function getColorPrioridadAttribute(): string
    {
        return match($this->prioridad) {
            'baja' => 'text-blue-600',
            'media' => 'text-yellow-600',
            'alta' => 'text-orange-600',
            'critica' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Verificar si la notificación está vencida para ser enviada
     */
    public function estaListaParaEnvio(): bool
    {
        if ($this->estado !== 'pendiente') {
            return false;
        }

        if ($this->programada_para === null) {
            return true;
        }

        return $this->programada_para <= Carbon::now();
    }

    /**
     * Crear notificación estática
     */
    public static function crear(array $datos): self
    {
        return self::create($datos);
    }

    /**
     * Crear notificación para múltiples usuarios
     */
    public static function crearParaUsuarios(array $userIds, array $datos): int
    {
        $notificaciones = [];
        foreach ($userIds as $userId) {
            $notificaciones[] = array_merge([
                'estado' => 'pendiente', // Valor por defecto explícito
            ], $datos, [
                'user_id' => $userId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return self::insert($notificaciones) ? count($notificaciones) : 0;
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public static function limpiarAntiguas(int $dias = 30): int
    {
        return self::where('estado', 'leida')
                  ->where('created_at', '<', Carbon::now()->subDays($dias))
                  ->delete();
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
