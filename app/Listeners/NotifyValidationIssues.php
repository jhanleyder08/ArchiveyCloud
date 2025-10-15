<?php

namespace App\Listeners;

use App\Events\ValidationFailedEvent;
use App\Models\User;
use App\Notifications\ValidationFailureNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para notificar sobre problemas de validación
 */
class NotifyValidationIssues implements ShouldQueue
{
    /**
     * Handle validation failed events
     */
    public function handle(ValidationFailedEvent $event): void
    {
        // Solo notificar errores críticos (no advertencias)
        if (empty($event->errores)) {
            return;
        }

        // Obtener usuario que realizó la acción
        $usuario = null;
        if ($event->usuarioId) {
            $usuario = User::find($event->usuarioId);
        }

        // Notificar al usuario que creó la entidad
        if ($usuario) {
            $usuario->notify(new ValidationFailureNotification(
                $event->tipoEntidad,
                $event->entidadId,
                $event->errores,
                $event->contexto
            ));
        }

        // Notificar a administradores si es un error crítico de integridad
        $erroresCriticos = array_filter($event->errores, function ($error) {
            return str_contains(strtolower($error), 'integridad') || 
                   str_contains(strtolower($error), 'referencial') ||
                   str_contains(strtolower($error), 'duplicado');
        });

        if (!empty($erroresCriticos)) {
            $administradores = User::role('admin')->get();
            Notification::send($administradores, new ValidationFailureNotification(
                $event->tipoEntidad,
                $event->entidadId,
                $erroresCriticos,
                array_merge($event->contexto, ['tipo' => 'critico'])
            ));
        }
    }
}
