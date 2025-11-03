<?php

namespace App\Events;

use App\Models\WorkflowTarea;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento: Tarea de Workflow Asignada
 */
class WorkflowTaskAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Constructor
     */
    public function __construct(
        public WorkflowTarea $tarea
    ) {}

    /**
     * Canal de broadcast
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->tarea->asignado_id),
            new Channel('workflows'),
        ];
    }

    /**
     * Nombre del evento en broadcast
     */
    public function broadcastAs(): string
    {
        return 'task.assigned';
    }

    /**
     * Datos a enviar
     */
    public function broadcastWith(): array
    {
        return [
            'tarea_id' => $this->tarea->id,
            'nombre' => $this->tarea->nombre,
            'descripcion' => $this->tarea->descripcion,
            'fecha_vencimiento' => $this->tarea->fecha_vencimiento?->toISOString(),
            'instancia_id' => $this->tarea->instancia_id,
            'asignado_id' => $this->tarea->asignado_id,
        ];
    }
}
