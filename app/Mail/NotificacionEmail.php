<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Notificacion;
use App\Models\User;

class NotificacionEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $notificacion;
    public $usuario;

    /**
     * Create a new message instance.
     */
    public function __construct(Notificacion $notificacion, User $usuario)
    {
        $this->notificacion = $notificacion;
        $this->usuario = $usuario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->notificacion->prioridad) {
            'critica' => '🚨 URGENTE: ' . $this->notificacion->titulo,
            'alta' => '⚠️ IMPORTANTE: ' . $this->notificacion->titulo,
            'media' => '📋 ' . $this->notificacion->titulo,
            default => '📝 ' . $this->notificacion->titulo
        };

        return new Envelope(
            subject: $subject,
            from: 'sistema@archiveycloud.com',
            replyTo: 'no-reply@archiveycloud.com'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion',
            with: [
                'notificacion' => $this->notificacion,
                'usuario' => $this->usuario,
                'urlAccion' => $this->notificacion->accion_url ? 
                    url($this->notificacion->accion_url) : 
                    route('admin.notificaciones.index'),
                'prioridadColor' => $this->getPrioridadColor(),
                'iconoPrioridad' => $this->getIconoPrioridad(),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Obtener color según prioridad
     */
    private function getPrioridadColor(): string
    {
        return match ($this->notificacion->prioridad) {
            'critica' => '#dc2626', // red-600
            'alta' => '#f97316',    // orange-500
            'media' => '#3b82f6',   // blue-500
            default => '#6b7280'    // gray-500
        };
    }

    /**
     * Obtener icono según prioridad
     */
    private function getIconoPrioridad(): string
    {
        return match ($this->notificacion->prioridad) {
            'critica' => '🚨',
            'alta' => '⚠️',
            'media' => '📋',
            default => '📝'
        };
    }
}
