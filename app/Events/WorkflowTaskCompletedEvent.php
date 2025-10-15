<?php

namespace App\Events;

use App\Models\WorkflowTask;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se completa una tarea de workflow
 */
class WorkflowTaskCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkflowTask $tarea;
    public User $usuario;

    public function __construct(WorkflowTask $tarea, User $usuario)
    {
        $this->tarea = $tarea;
        $this->usuario = $usuario;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workflow.{$this->tarea->instancia_id}"),
            new PrivateChannel("user.{$this->tarea->instancia->usuario_iniciador_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'tarea_id' => $this->tarea->id,
            'tarea_nombre' => $this->tarea->nombre,
            'instancia_id' => $this->tarea->instancia_id,
            'completado_por' => $this->usuario->name,
            'resultado' => $this->tarea->resultado,
            'fecha_completado' => $this->tarea->fecha_completado->toISOString(),
            'timestamp' => now()->toISOString()
        ];
    }

    public function broadcastAs(): string
    {
        return 'workflow.task.completed';
    }
}
