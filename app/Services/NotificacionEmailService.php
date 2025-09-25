<?php

namespace App\Services;

use App\Mail\NotificacionEmail;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificacionEmailService
{
    /**
     * Enviar notificación por email
     */
    public function enviarNotificacion(Notificacion $notificacion): bool
    {
        try {
            $usuario = $notificacion->user;
            
            if (!$usuario || !$usuario->email) {
                Log::warning('Notificación sin usuario o email válido', [
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Verificar si el usuario ha deshabilitado emails
            if (!$this->usuarioPuedeRecibirEmails($usuario)) {
                Log::info('Usuario ha deshabilitado notificaciones por email', [
                    'user_id' => $usuario->id,
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Verificar throttling (no más de 5 emails por hora por usuario)
            if (!$this->verificarThrottling($usuario)) {
                Log::warning('Usuario excedió límite de emails por hora', [
                    'user_id' => $usuario->id,
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Enviar email
            Mail::to($usuario->email)->send(new NotificacionEmail($notificacion, $usuario));

            // Registrar envío exitoso
            $this->registrarEnvio($usuario, $notificacion);

            Log::info('Email de notificación enviado exitosamente', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
                'notificacion_id' => $notificacion->id,
                'tipo' => $notificacion->tipo,
                'prioridad' => $notificacion->prioridad
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error enviando email de notificación', [
                'error' => $e->getMessage(),
                'notificacion_id' => $notificacion->id,
                'user_id' => $notificacion->user_id ?? null
            ]);

            return false;
        }
    }

    /**
     * Enviar notificaciones masivas por email
     */
    public function enviarNotificacionesMasivas(array $notificaciones): array
    {
        $resultados = [
            'exitosas' => 0,
            'fallidas' => 0,
            'omitidas' => 0,
            'detalles' => []
        ];

        foreach ($notificaciones as $notificacion) {
            if ($notificacion instanceof Notificacion) {
                $enviado = $this->enviarNotificacion($notificacion);
                
                if ($enviado) {
                    $resultados['exitosas']++;
                } else {
                    $resultados['fallidas']++;
                }

                $resultados['detalles'][] = [
                    'notificacion_id' => $notificacion->id,
                    'usuario' => $notificacion->user->email ?? 'N/A',
                    'enviado' => $enviado
                ];
            } else {
                $resultados['omitidas']++;
            }
        }

        Log::info('Envío masivo de emails completado', $resultados);

        return $resultados;
    }

    /**
     * Verificar si el usuario puede recibir emails
     */
    private function usuarioPuedeRecibirEmails(User $usuario): bool
    {
        // Aquí podrías verificar preferencias del usuario
        // Por ahora asumimos que todos pueden recibir emails
        return true;
    }

    /**
     * Verificar throttling de emails
     */
    private function verificarThrottling(User $usuario): bool
    {
        $cacheKey = "email_throttle_user_{$usuario->id}";
        $enviosUltimaHora = Cache::get($cacheKey, 0);

        // Máximo 5 emails por hora por usuario
        if ($enviosUltimaHora >= 5) {
            return false;
        }

        return true;
    }

    /**
     * Registrar envío de email
     */
    private function registrarEnvio(User $usuario, Notificacion $notificacion): void
    {
        $cacheKey = "email_throttle_user_{$usuario->id}";
        $envios = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $envios + 1, 3600); // 1 hora

        // Registrar en log de auditoría
        Log::channel('mail')->info('Email enviado', [
            'user_id' => $usuario->id,
            'email' => $usuario->email,
            'notificacion_id' => $notificacion->id,
            'tipo' => $notificacion->tipo,
            'prioridad' => $notificacion->prioridad,
            'timestamp' => now()
        ]);
    }

    /**
     * Enviar email de resumen diario
     */
    public function enviarResumenDiario(User $usuario): bool
    {
        try {
            $notificacionesPendientes = Notificacion::where('user_id', $usuario->id)
                ->where('estado', 'pendiente')
                ->where('created_at', '>=', now()->subDay())
                ->get();

            if ($notificacionesPendientes->isEmpty()) {
                return true; // No hay notificaciones pendientes
            }

            // Crear notificación de resumen
            $resumen = new Notificacion([
                'user_id' => $usuario->id,
                'tipo' => 'resumen_diario',
                'titulo' => 'Resumen diario de notificaciones',
                'mensaje' => "Tienes {$notificacionesPendientes->count()} notificaciones pendientes del último día.",
                'prioridad' => 'media',
                'estado' => 'pendiente',
                'es_automatica' => true,
                'datos' => [
                    'total_pendientes' => $notificacionesPendientes->count(),
                    'criticas' => $notificacionesPendientes->where('prioridad', 'critica')->count(),
                    'altas' => $notificacionesPendientes->where('prioridad', 'alta')->count(),
                    'medias' => $notificacionesPendientes->where('prioridad', 'media')->count(),
                ]
            ]);

            return $this->enviarNotificacion($resumen);

        } catch (\Exception $e) {
            Log::error('Error enviando resumen diario', [
                'error' => $e->getMessage(),
                'user_id' => $usuario->id
            ]);

            return false;
        }
    }

    /**
     * Obtener estadísticas de envío de emails
     */
    public function obtenerEstadisticas(): array
    {
        $hoy = now()->startOfDay();
        
        return [
            'emails_hoy' => $this->contarEmailsEnviados($hoy, now()),
            'emails_semana' => $this->contarEmailsEnviados(now()->startOfWeek(), now()),
            'emails_mes' => $this->contarEmailsEnviados(now()->startOfMonth(), now()),
            'usuarios_activos_email' => $this->contarUsuariosActivosEmail(),
            'tipos_mas_enviados' => $this->obtenerTiposMasEnviados(),
        ];
    }

    /**
     * Contar emails enviados en un período
     */
    private function contarEmailsEnviados($desde, $hasta): int
    {
        // Esto sería mejor implementado con una tabla de logs de emails
        // Por ahora simulamos con notificaciones
        return Notificacion::whereBetween('created_at', [$desde, $hasta])
            ->where('es_automatica', true)
            ->count();
    }

    /**
     * Contar usuarios que han recibido emails
     */
    private function contarUsuariosActivosEmail(): int
    {
        return User::whereHas('notificaciones', function ($query) {
            $query->where('created_at', '>=', now()->subWeek());
        })->count();
    }

    /**
     * Obtener tipos de notificación más enviados
     */
    private function obtenerTiposMasEnviados(): array
    {
        return Notificacion::where('created_at', '>=', now()->subMonth())
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'tipo' => $item->tipo,
                    'total' => $item->total,
                    'nombre' => ucwords(str_replace('_', ' ', $item->tipo))
                ];
            })
            ->toArray();
    }
}
