<?php

namespace App\Notifications;

use App\Models\Documento;
use App\Models\WorkflowInstancia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoAprobadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Documento $documento,
        public WorkflowInstancia $instancia,
        public string $aprobadoPor
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('documentos.show', $this->documento->id);

        return (new MailMessage)
            ->subject('Documento Aprobado: ' . $this->documento->nombre)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu documento ha sido **aprobado** exitosamente.')
            ->line('**Documento:** ' . $this->documento->nombre)
            ->line('**Código:** ' . $this->documento->codigo)
            ->line('**Aprobado por:** ' . $this->aprobadoPor)
            ->action('Ver Documento', $url)
            ->line('El documento está ahora disponible en el sistema.')
            ->success()
            ->salutation('Saludos, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'documento_aprobado',
            'documento_id' => $this->documento->id,
            'documento_nombre' => $this->documento->nombre,
            'documento_codigo' => $this->documento->codigo,
            'aprobado_por' => $this->aprobadoPor,
            'workflow_id' => $this->instancia->workflow_id,
            'url' => route('documentos.show', $this->documento->id),
        ];
    }
}
