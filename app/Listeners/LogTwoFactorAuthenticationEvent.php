<?php

namespace App\Listeners;

use App\Events\TwoFactorAuthenticationEvent;
use App\Models\PistaAuditoria;
use Illuminate\Support\Facades\Log;

class LogTwoFactorAuthenticationEvent
{
    /**
     * Handle the event.
     */
    public function handle(TwoFactorAuthenticationEvent $event): void
    {
        // Registrar en PistaAuditoria si existe
        if (class_exists(PistaAuditoria::class)) {
            try {
                PistaAuditoria::registrar($event->user, $event->action, [
                    'descripcion' => $this->getDescripcion($event),
                    'method' => $event->method,
                    'success' => $event->success,
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                ]);
            } catch (\Exception $e) {
                Log::warning('Error registrando auditoría 2FA: ' . $e->getMessage());
            }
        }

        // Registrar en log de Laravel
        $logLevel = $event->success ? 'info' : 'warning';
        Log::$logLevel('2FA Event', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'action' => $event->action,
            'method' => $event->method,
            'success' => $event->success,
            'ip' => $event->ipAddress,
        ]);
    }

    /**
     * Obtener descripción del evento
     */
    protected function getDescripcion(TwoFactorAuthenticationEvent $event): string
    {
        $userName = $event->user->name;
        $method = $event->method ? " con método {$event->method}" : '';
        $status = $event->success ? 'exitoso' : 'fallido';

        return match($event->action) {
            '2fa_enabled' => "2FA habilitado{$method} para usuario {$userName}",
            '2fa_disabled' => "2FA deshabilitado para usuario {$userName}",
            '2fa_verified' => "Verificación 2FA {$status} para usuario {$userName}{$method}",
            '2fa_recovery_codes_regenerated' => "Códigos de recuperación regenerados para usuario {$userName}",
            '2fa_code_sent' => "Código 2FA enviado{$method} a usuario {$userName}",
            'recovery_code_used' => "Código de recuperación usado por usuario {$userName}",
            default => "Evento 2FA: {$event->action} - Usuario {$userName}",
        };
    }
}
