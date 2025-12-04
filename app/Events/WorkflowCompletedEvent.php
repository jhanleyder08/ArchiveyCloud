<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se completa un workflow
 */
class WorkflowCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkflowInstance $instancia;

    public function __construct(WorkflowInstance $instancia)
    {
        $this->instancia = $instancia;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workflow.{$this->instancia->id}"),
            new PrivateChannel("user.{$this->instancia->usuario_iniciador_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'instancia_id' => $this->instancia->id,
            'codigo_seguimiento' => $this->instancia->codigo_seguimiento,
            'workflow_nombre' => $this->instancia->workflow->nombre,
            'fecha_completado' => $this->instancia->fecha_completado->toISOString(),
            'duracion_horas' => $this->instancia->fecha_inicio->diffInHours($this->instancia->fecha_completado),
            'timestamp' => now()->toISOString()
        ];
    }

    public function broadcastAs(): string
    {
        return 'workflow.completed';
    }
}
