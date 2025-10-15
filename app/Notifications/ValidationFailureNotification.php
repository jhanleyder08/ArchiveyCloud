<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para fallos de validación
 */
class ValidationFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $tipoEntidad;
    protected int $entidadId;
    protected array $errores;
    protected array $contexto;

    public function __construct(string $tipoEntidad, int $entidadId, array $errores, array $contexto = [])
    {
        $this->tipoEntidad = $tipoEntidad;
        $this->entidadId = $entidadId;
        $this->errores = $errores;
        $this->contexto = $contexto;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Solo enviar email para errores críticos
        if (!empty($this->contexto['tipo']) && $this->contexto['tipo'] === 'critico') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $tipoTexto = match($this->tipoEntidad) {
            'documento' => 'documento',
            'expediente' => 'expediente',
            'serie' => 'serie documental',
            default => 'entidad'
        };

        return (new MailMessage)
                    ->subject('Error de Validación en SGDEA')
                    ->line("Se detectaron errores de validación en el {$tipoTexto} ID: {$this->entidadId}")
                    ->line('Errores encontrados:')
                    ->line('• ' . implode("\n• ", $this->errores))
                    ->action('Ver Detalles', url("/admin/{$this->tipoEntidad}s/{$this->entidadId}"))
                    ->line('Por favor, revise y corrija estos problemas lo antes posible.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'validation_failure',
            'titulo' => 'Error de Validación',
            'mensaje' => $this->generarMensaje(),
            'entidad' => [
                'tipo' => $this->tipoEntidad,
                'id' => $this->entidadId
            ],
            'errores' => $this->errores,
            'contexto' => $this->contexto,
            'url' => "/admin/{$this->tipoEntidad}s/{$this->entidadId}",
            'icono' => 'alert-triangle',
            'color' => 'red'
        ];
    }

    /**
     * Generar mensaje personalizado según el tipo de entidad
     */
    private function generarMensaje(): string
    {
        $tipoTexto = match($this->tipoEntidad) {
            'documento' => 'documento',
            'expediente' => 'expediente',
            'serie' => 'serie documental',
            default => 'entidad'
        };

        $cantidadErrores = count($this->errores);
        $pluralErrores = $cantidadErrores > 1 ? 'errores' : 'error';

        return "Se encontraron {$cantidadErrores} {$pluralErrores} de validación en el {$tipoTexto} #{$this->entidadId}";
    }
}
