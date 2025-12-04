<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando una validación falla
 */
class ValidationFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tipoEntidad;
    public int $entidadId;
    public array $errores;
    public array $advertencias;
    public ?int $usuarioId;
    public array $contexto;

    public function __construct(
        string $tipoEntidad,
        int $entidadId,
        array $errores,
        array $advertencias = [],
        ?int $usuarioId = null,
        array $contexto = []
    ) {
        $this->tipoEntidad = $tipoEntidad;
        $this->entidadId = $entidadId;
        $this->errores = $errores;
        $this->advertencias = $advertencias;
        $this->usuarioId = $usuarioId ?? auth()->id();
        $this->contexto = $contexto;
    }
}
