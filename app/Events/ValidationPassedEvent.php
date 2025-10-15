<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando una validación pasa exitosamente
 */
class ValidationPassedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tipoEntidad;
    public int $entidadId;
    public array $advertencias;
    public ?int $usuarioId;
    public int $puntuacionCalidad;
    public array $contexto;

    public function __construct(
        string $tipoEntidad,
        int $entidadId,
        array $advertencias = [],
        ?int $usuarioId = null,
        int $puntuacionCalidad = 0,
        array $contexto = []
    ) {
        $this->tipoEntidad = $tipoEntidad;
        $this->entidadId = $entidadId;
        $this->advertencias = $advertencias;
        $this->usuarioId = $usuarioId ?? auth()->id();
        $this->puntuacionCalidad = $puntuacionCalidad;
        $this->contexto = $contexto;
    }
}
