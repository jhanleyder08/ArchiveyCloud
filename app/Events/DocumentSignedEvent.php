<?php

namespace App\Events;

use App\Models\Documento;
use App\Models\FirmaDigital;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando un documento es firmado digitalmente
 */
class DocumentSignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Documento $documento;
    public FirmaDigital $firma;
    public User $firmante;
    public array $metadatos;

    public function __construct(Documento $documento, FirmaDigital $firma, User $firmante, array $metadatos = [])
    {
        $this->documento = $documento;
        $this->firma = $firma;
        $this->firmante = $firmante;
        $this->metadatos = $metadatos;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("documento.{$this->documento->id}"),
            new PrivateChannel("user.{$this->firmante->id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'documento_id' => $this->documento->id,
            'documento_nombre' => $this->documento->nombre,
            'firma_id' => $this->firma->id,
            'firmante' => $this->firmante->name,
            'tipo_firma' => $this->firma->tipo_firma,
            'fecha_firma' => $this->firma->fecha_firma->toISOString(),
            'metadatos' => $this->metadatos,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'document.signed';
    }
}
