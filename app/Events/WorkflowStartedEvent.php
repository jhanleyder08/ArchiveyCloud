<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se inicia un workflow
 */
class WorkflowStartedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkflowInstance $instancia;
    public WorkflowTask $primeraTarea;

    public function __construct(WorkflowInstance $instancia, WorkflowTask $primeraTarea)
    {
        $this->instancia = $instancia;
        $this->primeraTarea = $primeraTarea;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workflow.{$this->instancia->id}"),
            new PrivateChannel("user.{$this->instancia->usuario_iniciador_id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'instancia_id' => $this->instancia->id,
            'codigo_seguimiento' => $this->instancia->codigo_seguimiento,
            'workflow_nombre' => $this->instancia->workflow->nombre,
            'entidad_tipo' => $this->instancia->entidad_tipo,
            'entidad_id' => $this->instancia->entidad_id,
            'primera_tarea' => [
                'id' => $this->primeraTarea->id,
                'nombre' => $this->primeraTarea->nombre,
                'tipo' => $this->primeraTarea->tipo
            ],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'workflow.started';
    }
}
