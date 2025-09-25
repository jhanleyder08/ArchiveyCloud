<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificacionSmsService
{
    private $apiUrl;
    private $apiKey;
    private $sender;

    public function __construct()
    {
        // Configuración del servicio SMS (Twilio, AWS SNS, etc.)
        // Por ahora usamos configuración simulada
        $this->apiUrl = config('services.sms.api_url', 'https://api.textlocal.in/send/');
        $this->apiKey = config('services.sms.api_key', 'demo_key');
        $this->sender = config('services.sms.sender', 'ArchiveyCloud');
    }

    /**
     * Enviar SMS de notificación (solo para notificaciones críticas)
     */
    public function enviarSms(Notificacion $notificacion): bool
    {
        try {
            // Solo enviar SMS para notificaciones críticas
            if ($notificacion->prioridad !== 'critica') {
                return true; // No es crítica, no se envía SMS
            }

            $usuario = $notificacion->user;
            
            if (!$usuario || !$usuario->telefono) {
                Log::warning('Notificación crítica sin usuario o teléfono válido', [
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Verificar si el usuario ha habilitado SMS
            if (!$this->usuarioPuedeRecibirSms($usuario)) {
                Log::info('Usuario ha deshabilitado notificaciones por SMS', [
                    'user_id' => $usuario->id,
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Verificar throttling (no más de 3 SMS por día por usuario)
            if (!$this->verificarThrottling($usuario)) {
                Log::warning('Usuario excedió límite de SMS por día', [
                    'user_id' => $usuario->id,
                    'notificacion_id' => $notificacion->id
                ]);
                return false;
            }

            // Preparar mensaje
            $mensaje = $this->prepararMensajeSms($notificacion);

            // Enviar SMS
            $enviado = $this->enviarSmsApi($usuario->telefono, $mensaje);

            if ($enviado) {
                // Registrar envío exitoso
                $this->registrarEnvio($usuario, $notificacion);

                Log::info('SMS enviado exitosamente', [
                    'user_id' => $usuario->id,
                    'telefono' => $this->enmascararTelefono($usuario->telefono),
                    'notificacion_id' => $notificacion->id,
                    'tipo' => $notificacion->tipo
                ]);
            }

            return $enviado;

        } catch (\Exception $e) {
            Log::error('Error enviando SMS', [
                'error' => $e->getMessage(),
                'notificacion_id' => $notificacion->id,
                'user_id' => $notificacion->user_id ?? null
            ]);

            return false;
        }
    }

    /**
     * Preparar mensaje SMS (máximo 160 caracteres)
     */
    private function prepararMensajeSms(Notificacion $notificacion): string
    {
        $icono = match ($notificacion->prioridad) {
            'critica' => '🚨',
            'alta' => '⚠️',
            default => '📋'
        };

        $base = "{$icono} ArchiveyCloud: {$notificacion->titulo}";
        
        // Si el mensaje base es muy largo, lo truncamos
        if (strlen($base) > 140) {
            $base = substr($base, 0, 137) . '...';
        }
        
        // Agregar información de acceso si hay espacio
        $urlCorta = " Ver: " . config('app.url') . "/admin";
        
        if (strlen($base . $urlCorta) <= 160) {
            $base .= $urlCorta;
        }

        return $base;
    }

    /**
     * Enviar SMS a través de la API
     */
    private function enviarSmsApi(string $telefono, string $mensaje): bool
    {
        // En un entorno real, aquí se haría la llamada a la API real
        // Por ejemplo, Twilio, AWS SNS, TextLocal, etc.
        
        // Simulación para desarrollo
        if (config('app.env') === 'local') {
            Log::info('SMS simulado (desarrollo)', [
                'telefono' => $this->enmascararTelefono($telefono),
                'mensaje' => $mensaje,
                'longitud' => strlen($mensaje)
            ]);
            return true;
        }

        try {
            // Ejemplo de implementación con TextLocal
            $response = Http::asForm()->post($this->apiUrl, [
                'apikey' => $this->apiKey,
                'numbers' => $this->formatearTelefono($telefono),
                'message' => $mensaje,
                'sender' => $this->sender,
                'test' => config('app.env') !== 'production'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && $data['status'] === 'success';
            }

            Log::error('Error en respuesta de API SMS', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Error llamando API SMS', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Verificar si el usuario puede recibir SMS
     */
    private function usuarioPuedeRecibirSms(User $usuario): bool
    {
        // Verificar si el usuario ha habilitado SMS en sus preferencias
        // Por ahora asumimos que todos los usuarios con teléfono pueden recibir SMS críticos
        return !empty($usuario->telefono);
    }

    /**
     * Verificar throttling de SMS (máximo 3 por día)
     */
    private function verificarThrottling(User $usuario): bool
    {
        $cacheKey = "sms_throttle_user_{$usuario->id}";
        $enviosDia = Cache::get($cacheKey, 0);

        // Máximo 3 SMS por día por usuario
        if ($enviosDia >= 3) {
            return false;
        }

        return true;
    }

    /**
     * Registrar envío de SMS
     */
    private function registrarEnvio(User $usuario, Notificacion $notificacion): void
    {
        $cacheKey = "sms_throttle_user_{$usuario->id}";
        $envios = Cache::get($cacheKey, 0);
        
        // Cache por 24 horas
        Cache::put($cacheKey, $envios + 1, 86400);

        // Registrar en log de auditoría
        Log::channel('sms')->info('SMS enviado', [
            'user_id' => $usuario->id,
            'telefono' => $this->enmascararTelefono($usuario->telefono),
            'notificacion_id' => $notificacion->id,
            'tipo' => $notificacion->tipo,
            'timestamp' => now()
        ]);
    }

    /**
     * Formatear teléfono para la API
     */
    private function formatearTelefono(string $telefono): string
    {
        // Limpiar el teléfono y asegurar formato internacional
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);
        
        // Si no empieza con +, asumir código de país Colombia (+57)
        if (!str_starts_with($telefono, '+')) {
            $telefono = '+57' . ltrim($telefono, '0');
        }

        return $telefono;
    }

    /**
     * Enmascarar teléfono para logs
     */
    private function enmascararTelefono(string $telefono): string
    {
        if (strlen($telefono) <= 4) {
            return str_repeat('*', strlen($telefono));
        }

        return substr($telefono, 0, 3) . str_repeat('*', strlen($telefono) - 6) . substr($telefono, -3);
    }

    /**
     * Obtener estadísticas de SMS
     */
    public function obtenerEstadisticas(): array
    {
        return [
            'sms_hoy' => $this->contarSmsEnviados(now()->startOfDay(), now()),
            'sms_semana' => $this->contarSmsEnviados(now()->startOfWeek(), now()),
            'sms_mes' => $this->contarSmsEnviados(now()->startOfMonth(), now()),
            'usuarios_con_telefono' => User::whereNotNull('telefono')->count(),
            'throttling_activo' => $this->contarUsuariosConThrottling(),
        ];
    }

    /**
     * Contar SMS enviados en un período
     */
    private function contarSmsEnviados($desde, $hasta): int
    {
        // Esto sería mejor implementado con una tabla de logs de SMS
        // Por ahora simulamos contando notificaciones críticas
        return Notificacion::whereBetween('created_at', [$desde, $hasta])
            ->where('prioridad', 'critica')
            ->where('es_automatica', true)
            ->count();
    }

    /**
     * Contar usuarios con throttling activo
     */
    private function contarUsuariosConThrottling(): int
    {
        // En una implementación real, esto consultaría Redis/Cache
        // Por simplicidad, retornamos 0
        return 0;
    }

    /**
     * Probar configuración SMS
     */
    public function probarConfiguracion(string $telefono = null): array
    {
        $resultado = [
            'configuracion_ok' => true,
            'api_disponible' => false,
            'mensaje_prueba_enviado' => false,
            'detalles' => []
        ];

        // Verificar configuración
        if (empty($this->apiKey) || $this->apiKey === 'demo_key') {
            $resultado['configuracion_ok'] = false;
            $resultado['detalles'][] = 'API Key no configurada';
        }

        if (empty($this->apiUrl)) {
            $resultado['configuracion_ok'] = false;
            $resultado['detalles'][] = 'URL de API no configurada';
        }

        // Si hay configuración, probar conectividad
        if ($resultado['configuracion_ok'] && config('app.env') !== 'local') {
            try {
                $response = Http::timeout(10)->get($this->apiUrl);
                $resultado['api_disponible'] = $response->status() < 500;
                
                if (!$resultado['api_disponible']) {
                    $resultado['detalles'][] = 'API no disponible (status: ' . $response->status() . ')';
                }
            } catch (\Exception $e) {
                $resultado['api_disponible'] = false;
                $resultado['detalles'][] = 'Error conectando con API: ' . $e->getMessage();
            }
        } else {
            $resultado['api_disponible'] = true; // En local siempre está "disponible"
        }

        // Si se proporciona teléfono, enviar mensaje de prueba
        if ($telefono && $resultado['configuracion_ok']) {
            $notificacionPrueba = new Notificacion([
                'tipo' => 'prueba_sms',
                'titulo' => 'Prueba SMS ArchiveyCloud',
                'mensaje' => 'Este es un mensaje de prueba del sistema de notificaciones SMS.',
                'prioridad' => 'critica',
            ]);

            $usuarioPrueba = (object)[
                'id' => 0,
                'telefono' => $telefono,
                'name' => 'Usuario Prueba'
            ];

            $notificacionPrueba->user = $usuarioPrueba;
            $resultado['mensaje_prueba_enviado'] = $this->enviarSms($notificacionPrueba);
            
            if ($resultado['mensaje_prueba_enviado']) {
                $resultado['detalles'][] = 'Mensaje de prueba enviado exitosamente';
            } else {
                $resultado['detalles'][] = 'Error enviando mensaje de prueba';
            }
        }

        return $resultado;
    }
}
