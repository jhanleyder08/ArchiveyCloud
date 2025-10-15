<?php

namespace App\Notifications;

use App\Models\Documento;
use App\Models\FirmaDigital;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para documento firmado digitalmente
 */
class DocumentSignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Documento $documento;
    protected FirmaDigital $firma;
    protected bool $esFirmante;

    public function __construct(Documento $documento, FirmaDigital $firma, bool $esFirmante = true)
    {
        $this->documento = $documento;
        $this->firma = $firma;
        $this->esFirmante = $esFirmante;
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
        $subject = $this->esFirmante 
            ? 'Documento firmado exitosamente - SGDEA'
            : 'Nuevo documento firmado - SGDEA';

        $greeting = $this->esFirmante
            ? 'Ha firmado exitosamente un documento'
            : 'Se ha firmado un nuevo documento';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hola {$notifiable->name}")
            ->line($greeting)
            ->line("**Documento:** {$this->documento->nombre}")
            ->line("**Expediente:** {$this->documento->expediente->nombre}")
            ->line("**Firmante:** {$this->firma->usuario->name}")
            ->line("**Tipo de firma:** {$this->firma->tipo_firma}")
            ->line("**Fecha de firma:** {$this->firma->fecha_firma->format('d/m/Y H:i:s')}")
            ->action('Ver Documento', url("/admin/documentos/{$this->documento->id}"))
            ->action('Ver Firma', url("/admin/firmas/detalle/{$this->firma->id}"))
            ->line('Esta firma digital garantiza la autenticidad e integridad del documento.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'document_signed',
            'titulo' => $this->esFirmante ? 'Documento Firmado' : 'Nuevo Documento Firmado',
            'mensaje' => $this->generarMensaje(),
            'documento' => [
                'id' => $this->documento->id,
                'nombre' => $this->documento->nombre,
                'expediente' => $this->documento->expediente ? $this->documento->expediente->nombre : null
            ],
            'firma' => [
                'id' => $this->firma->id,
                'tipo' => $this->firma->tipo_firma,
                'firmante' => $this->firma->usuario->name,
                'fecha' => $this->firma->fecha_firma->toISOString()
            ],
            'urls' => [
                'documento' => "/admin/documentos/{$this->documento->id}",
                'firma' => "/admin/firmas/detalle/{$this->firma->id}"
            ],
            'icono' => 'file-signature',
            'color' => 'green'
        ];
    }

    private function generarMensaje(): string
    {
        if ($this->esFirmante) {
            return "Ha firmado exitosamente el documento '{$this->documento->nombre}' con firma {$this->firma->tipo_firma}";
        } else {
            return "{$this->firma->usuario->name} ha firmado el documento '{$this->documento->nombre}' con firma {$this->firma->tipo_firma}";
        }
    }
}
