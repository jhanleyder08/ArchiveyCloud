<?php

namespace App\Notifications;

use App\Models\WorkflowTarea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WorkflowTarea $tarea
    ) {}

    /**
     * Canales de entrega
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Representación para email
     */
    public function toMail(object $notifiable): MailMessage
    {
        $instancia = $this->tarea->instancia;
        $workflow = $instancia->workflow;
        
        $url = route('workflows.tareas.show', $this->tarea->id);

        return (new MailMessage)
            ->subject('Nueva tarea asignada: ' . $this->tarea->nombre)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Se te ha asignado una nueva tarea en el workflow **' . $workflow->nombre . '**.')
            ->line('**Tarea:** ' . $this->tarea->nombre)
            ->line('**Descripción:** ' . ($this->tarea->descripcion ?? 'Sin descripción'))
            ->when($this->tarea->fecha_vencimiento, function ($mail) {
                return $mail->line('**Vence:** ' . $this->tarea->fecha_vencimiento->format('d/m/Y H:i'));
            })
            ->action('Ver Tarea', $url)
            ->line('Por favor revisa y completa esta tarea a la brevedad.')
            ->salutation('Saludos, ' . config('app.name'));
    }

    /**
     * Representación para base de datos
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'tarea_asignada',
            'tarea_id' => $this->tarea->id,
            'workflow_id' => $this->tarea->instancia->workflow_id,
            'nombre' => $this->tarea->nombre,
            'descripcion' => $this->tarea->descripcion,
            'fecha_vencimiento' => $this->tarea->fecha_vencimiento?->toISOString(),
            'prioridad' => $this->getPrioridad(),
            'url' => route('workflows.tareas.show', $this->tarea->id),
        ];
    }

    /**
     * Calcular prioridad según días hasta vencimiento
     */
    private function getPrioridad(): string
    {
        if (!$this->tarea->fecha_vencimiento) {
            return 'normal';
        }

        $diasRestantes = now()->diffInDays($this->tarea->fecha_vencimiento, false);

        if ($diasRestantes < 0) {
            return 'vencida';
        } elseif ($diasRestantes <= 1) {
            return 'urgente';
        } elseif ($diasRestantes <= 3) {
            return 'alta';
        } else {
            return 'normal';
        }
    }
}
