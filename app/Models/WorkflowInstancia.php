<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstancia extends Model
{
    use HasFactory;

    protected $table = 'workflow_instancias';

    protected $fillable = [
        'workflow_id',
        'entidad_id',
        'entidad_type',
        'usuario_iniciador_id',
        'paso_actual',
        'estado',
        'datos',
        'fecha_inicio',
        'fecha_finalizacion',
        'resultado',
    ];

    protected $casts = [
        'datos' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instancia) {
            if (!$instancia->fecha_inicio) {
                $instancia->fecha_inicio = now();
            }
        });
    }

    /**
     * Workflow asociado
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Entidad relacionada (documento, expediente, etc.)
     */
    public function entidad(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario iniciador
     */
    public function usuarioIniciador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_iniciador_id');
    }

    /**
     * Tareas del workflow
     */
    public function tareas(): HasMany
    {
        return $this->hasMany(WorkflowTarea::class);
    }

    /**
     * Avanzar al siguiente paso
     */
    public function avanzar(array $datos = []): bool
    {
        $siguientePaso = $this->paso_actual + 1;
        $workflow = $this->workflow;

        if ($siguientePaso >= $workflow->getTotalPasos()) {
            return $this->finalizar('aprobado');
        }

        $this->update([
            'paso_actual' => $siguientePaso,
            'datos' => array_merge($this->datos ?? [], $datos),
        ]);

        // Crear tarea para el siguiente paso
        $this->crearTareaParaPaso($siguientePaso);

        return true;
    }

    /**
     * Rechazar y finalizar
     */
    public function rechazar(string $motivo = ''): bool
    {
        return $this->finalizar('rechazado', $motivo);
    }

    /**
     * Finalizar workflow
     */
    public function finalizar(string $resultado, string $observaciones = ''): bool
    {
        $this->update([
            'estado' => 'finalizado',
            'resultado' => $resultado,
            'fecha_finalizacion' => now(),
            'datos' => array_merge($this->datos ?? [], [
                'observaciones_finales' => $observaciones,
            ]),
        ]);

        // Cerrar todas las tareas pendientes
        $this->tareas()->where('estado', 'pendiente')->update([
            'estado' => 'cancelada',
        ]);

        return true;
    }

    /**
     * Crear tarea para un paso
     */
    protected function crearTareaParaPaso(int $indicePaso): void
    {
        $paso = $this->workflow->getPaso($indicePaso);
        
        if (!$paso) {
            return;
        }

        WorkflowTarea::create([
            'workflow_instancia_id' => $this->id,
            'paso_numero' => $indicePaso,
            'nombre' => $paso['nombre'] ?? 'Tarea',
            'descripcion' => $paso['descripcion'] ?? '',
            'tipo_asignacion' => $paso['tipo_asignacion'] ?? 'usuario',
            'asignado_id' => $paso['asignado_id'] ?? null,
            'asignado_type' => $paso['asignado_type'] ?? 'App\\Models\\User',
            'fecha_vencimiento' => $this->calcularFechaVencimiento($paso),
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Calcular fecha de vencimiento
     */
    protected function calcularFechaVencimiento(array $paso): ?\DateTime
    {
        $diasVencimiento = $paso['dias_vencimiento'] ?? null;
        
        if ($diasVencimiento) {
            return now()->addDays($diasVencimiento);
        }

        return null;
    }

    /**
     * Scope por estado
     */
    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope en proceso
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado', 'en_proceso');
    }

    /**
     * Scope finalizados
     */
    public function scopeFinalizados($query)
    {
        return $query->where('estado', 'finalizado');
    }
}
