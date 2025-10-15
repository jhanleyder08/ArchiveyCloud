<?php

namespace App\Events;

use App\Models\CertificadoDigital;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando un certificado está próximo a vencer
 */
class CertificateExpiringEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CertificadoDigital $certificado;
    public int $diasRestantes;
    public string $nivelUrgencia;

    public function __construct(CertificadoDigital $certificado, int $diasRestantes)
    {
        $this->certificado = $certificado;
        $this->diasRestantes = $diasRestantes;
        $this->nivelUrgencia = $this->determinarNivelUrgencia($diasRestantes);
    }

    private function determinarNivelUrgencia(int $dias): string
    {
        if ($dias <= 7) return 'critica';
        if ($dias <= 15) return 'alta';
        if ($dias <= 30) return 'media';
        return 'baja';
    }
}
