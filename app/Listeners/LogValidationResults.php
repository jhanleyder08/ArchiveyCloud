<?php

namespace App\Listeners;

use App\Events\ValidationFailedEvent;
use App\Events\ValidationPassedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para registrar resultados de validaciones
 */
class LogValidationResults implements ShouldQueue
{
    /**
     * Handle validation failed events
     */
    public function handleValidationFailed(ValidationFailedEvent $event): void
    {
        Log::warning('Validación fallida', [
            'tipo_entidad' => $event->tipoEntidad,
            'entidad_id' => $event->entidadId,
            'usuario_id' => $event->usuarioId,
            'errores' => $event->errores,
            'advertencias' => $event->advertencias,
            'contexto' => $event->contexto,
            'timestamp' => now()->toISOString()
        ]);

        // Log específico por tipo de entidad
        Log::channel('validations')->error("Validación fallida - {$event->tipoEntidad} ID:{$event->entidadId}", [
            'errores' => $event->errores,
            'usuario' => $event->usuarioId
        ]);
    }

    /**
     * Handle validation passed events
     */
    public function handleValidationPassed(ValidationPassedEvent $event): void
    {
        if (!empty($event->advertencias) || $event->puntuacionCalidad < 70) {
            Log::info('Validación pasada con advertencias', [
                'tipo_entidad' => $event->tipoEntidad,
                'entidad_id' => $event->entidadId,
                'usuario_id' => $event->usuarioId,
                'advertencias' => $event->advertencias,
                'puntuacion_calidad' => $event->puntuacionCalidad,
                'contexto' => $event->contexto,
                'timestamp' => now()->toISOString()
            ]);
        }

        // Log de éxito en canal específico
        if ($event->puntuacionCalidad >= 90) {
            Log::channel('validations')->info("Validación excelente - {$event->tipoEntidad} ID:{$event->entidadId}", [
                'puntuacion' => $event->puntuacionCalidad,
                'usuario' => $event->usuarioId
            ]);
        }
    }
}
