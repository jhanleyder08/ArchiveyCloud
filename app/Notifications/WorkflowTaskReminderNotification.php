<?php

namespace App\Notifications;

use App\Models\WorkflowTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de recordatorio para tareas de workflow próximas a vencer
 */
class WorkflowTaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkflowTask $tarea;

    public function __construct(WorkflowTask $tarea)
    {
        $this->tarea = $tarea;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $instancia = $this->tarea->instancia;
        $horasRestantes = now()->diffInHours($this->tarea->fecha_limite);
        
        return (new MailMessage)
            ->subject('⏰ Recordatorio: Tarea de workflow próxima a vencer - SGDEA')
            ->greeting("Hola {$notifiable->name}")
            ->line("Tiene una tarea de workflow pendiente que vence en **{$horasRestantes} horas**.")
            ->line("**Workflow:** {$instancia->workflow->nombre}")
            ->line("**Tarea:** {$this->tarea->nombre}")
            ->line("**Código de seguimiento:** {$instancia->codigo_seguimiento}")
            ->line("**Fecha límite:** {$this->tarea->fecha_limite->format('d/m/Y H:i')}")
            ->line("**Prioridad:** " . ucfirst($this->tarea->prioridad))
            ->action('Ver Tarea', url("/admin/workflow/{$instancia->id}"))
            ->line('Por favor, complete esta tarea antes de la fecha límite para evitar escalamientos automáticos.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $instancia = $this->tarea->instancia;
        $horasRestantes = now()->diffInHours($this->tarea->fecha_limite);
        
        return [
            'tipo' => 'workflow_task_reminder',
            'titulo' => 'Tarea Próxima a Vencer',
            'mensaje' => "La tarea '{$this->tarea->nombre}' vence en {$horasRestantes} horas",
            'tarea' => [
                'id' => $this->tarea->id,
                'nombre' => $this->tarea->nombre,
                'tipo' => $this->tarea->tipo,
                'prioridad' => $this->tarea->prioridad,
                'fecha_limite' => $this->tarea->fecha_limite->toISOString()
            ],
            'workflow' => [
                'instancia_id' => $instancia->id,
                'codigo_seguimiento' => $instancia->codigo_seguimiento,
                'nombre' => $instancia->workflow->nombre,
                'entidad_tipo' => $instancia->entidad_tipo,
                'entidad_id' => $instancia->entidad_id
            ],
            'urgencia' => [
                'nivel' => $horasRestantes <= 4 ? 'critica' : ($horasRestantes <= 12 ? 'alta' : 'media'),
                'horas_restantes' => $horasRestantes
            ],
            'urls' => [
                'workflow' => "/admin/workflow/{$instancia->id}",
                'tarea' => "/admin/workflow/tarea/{$this->tarea->id}"
            ],
            'icono' => 'clock',
            'color' => $horasRestantes <= 4 ? 'red' : ($horasRestantes <= 12 ? 'orange' : 'yellow')
        ];
    }
}
