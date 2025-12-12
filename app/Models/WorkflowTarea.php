<?php

namespace App\Models;

use App\Notifications\TareaAsignadaNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Notification;

class WorkflowTarea extends Model
{
    use HasFactory;

    protected $table = 'workflow_tareas';

    protected static function boot()
    {
        parent::boot();

        // Enviar notificación cuando se crea una tarea (usando sistema de notificaciones existente)
        static::created(function ($tarea) {
            if ($tarea->asignado_type === 'App\\Models\\User' && $tarea->asignado_id) {
                try {
                    \App\Models\Notificacion::create([
                        'user_id' => $tarea->asignado_id,
                        'tipo' => 'tarea_asignada',
                        'titulo' => 'Nueva tarea asignada',
                        'mensaje' => "Se te ha asignado la tarea: {$tarea->nombre}",
                        'prioridad' => 'media',
                        'estado' => 'pendiente',
                        'accion_url' => "/admin/workflow/{$tarea->workflow_instancia_id}",
                        'datos' => [
                            'tarea_id' => $tarea->id,
                            'nombre' => $tarea->nombre,
                            'descripcion' => $tarea->descripcion,
                        ],
                        'es_automatica' => true,
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Error enviando notificación de tarea: " . $e->getMessage());
                }
            }
        });
    }

    protected $fillable = [
        'workflow_instancia_id',
        'paso_numero',
        'nombre',
        'descripcion',
        'tipo_asignacion',
        'asignado_id',
        'asignado_type',
        'fecha_vencimiento',
        'estado',
        'resultado',
        'observaciones',
        'usuario_completado_id',
        'fecha_completado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'datetime',
        'fecha_completado' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Instancia del workflow
     */
    public function instancia(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstancia::class, 'workflow_instancia_id');
    }

    /**
     * Asignado (usuario o rol)
     */
    public function asignado(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que completó la tarea
     */
    public function usuarioCompletado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_completado_id');
    }

    /**
     * Aprobar tarea
     */
    public function aprobar(int $usuarioId, string $observaciones = ''): bool
    {
        return $this->completar('aprobado', $usuarioId, $observaciones);
    }

    /**
     * Rechazar tarea
     */
    public function rechazar(int $usuarioId, string $observaciones): bool
    {
        return $this->completar('rechazado', $usuarioId, $observaciones);
    }

    /**
     * Completar tarea
     */
    public function completar(string $resultado, int $usuarioId, string $observaciones = ''): bool
    {
        $this->update([
            'estado' => 'completada',
            'resultado' => $resultado,
            'observaciones' => $observaciones,
            'usuario_completado_id' => $usuarioId,
            'fecha_completado' => now(),
        ]);

        // Actualizar instancia del workflow
        if ($resultado === 'aprobado') {
            $this->instancia->avanzar(['ultimo_aprobador' => $usuarioId]);
        } else {
            $this->instancia->rechazar($observaciones);
        }

        return true;
    }

    /**
     * Scope pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope asignadas a usuario
     */
    public function scopeAsignadasA($query, int $usuarioId)
    {
        return $query->where(function ($q) use ($usuarioId) {
            // Asignación directa al usuario
            $q->where(function ($subQ) use ($usuarioId) {
                $subQ->where('asignado_id', $usuarioId)
                     ->where('asignado_type', 'App\\Models\\User');
            });
            
            // O asignación a un rol que tenga el usuario
            $q->orWhere(function ($subQ) use ($usuarioId) {
                $subQ->where('asignado_type', 'App\\Models\\Role')
                     ->whereIn('asignado_id', function ($roleQuery) use ($usuarioId) {
                         $roleQuery->select('role_id')
                                   ->from('user_roles')
                                   ->where('user_id', $usuarioId);
                     });
            });
        });
    }

    /**
     * Scope vencidas
     */
    public function scopeVencidas($query)
    {
        return $query->where('estado', 'pendiente')
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now());
    }

    /**
     * Verificar si está vencida
     */
    public function estaVencida(): bool
    {
        return $this->estado === 'pendiente' 
            && $this->fecha_vencimiento 
            && $this->fecha_vencimiento->isPast();
    }

    /**
     * Días hasta vencimiento
     */
    public function diasHastaVencimiento(): ?int
    {
        if (!$this->fecha_vencimiento || $this->estado !== 'pendiente') {
            return null;
        }

        return now()->diffInDays($this->fecha_vencimiento, false);
    }
}
