<?php

namespace App\Events;

use App\Models\FirmaDigital;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando falla la validación de una firma digital
 */
class SignatureValidationFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public FirmaDigital $firma;
    public array $erroresValidacion;
    public string $tipoFallo;
    public ?int $usuarioId;

    public function __construct(
        FirmaDigital $firma, 
        array $erroresValidacion, 
        string $tipoFallo = 'validation_error',
        ?int $usuarioId = null
    ) {
        $this->firma = $firma;
        $this->erroresValidacion = $erroresValidacion;
        $this->tipoFallo = $tipoFallo;
        $this->usuarioId = $usuarioId ?? auth()->id();
    }
}
