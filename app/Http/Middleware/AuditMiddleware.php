<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PistaAuditoria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Auditoría
 * Registra todas las acciones importantes del sistema incluyendo navegación
 */
class AuditMiddleware
{
    /**
     * Acciones que deben ser auditadas
     */
    private const AUDITABLE_ACTIONS = [
        'POST', 'PUT', 'PATCH', 'DELETE'
    ];

    /**
     * Rutas sensibles que siempre se auditan (incluyendo GET)
     */
    private const SENSITIVE_ROUTES = [
        'login', 'logout', 'password', 'usuarios', 'users', 'roles', 
        'permisos', 'workflows', 'documentos', 'expedientes', 'auditoria',
        'trd', 'series', 'subseries', 'ccd', 'reportes', 'firmas',
        'prestamos', 'transferencias', 'disposiciones', 'configuracion'
    ];

    /**
     * Rutas a excluir de la auditoría
     */
    private const EXCLUDED_ROUTES = [
        'api/', 'sanctum/', '_debugbar', 'livewire', 'broadcasting',
        'extend-session', 'assets/', 'build/', 'favicon', '.js', '.css',
        '.png', '.jpg', '.svg', '.ico', 'notifications/conteo'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Ejecutar request
        $response = $next($request);

        // Auditar si es necesario y el usuario está autenticado
        if (auth()->check() && $this->shouldAudit($request, $response)) {
            $this->audit($request, $response, $startTime);
        }

        return $response;
    }

    /**
     * Determinar si la request debe ser auditada
     */
    private function shouldAudit(Request $request, Response $response): bool
    {
        $path = $request->path();

        // Excluir rutas no relevantes
        foreach (self::EXCLUDED_ROUTES as $excluded) {
            if (str_contains($path, $excluded)) {
                return false;
            }
        }

        // Solo auditar respuestas exitosas para navegación
        if ($request->method() === 'GET' && $response->getStatusCode() >= 400) {
            return false;
        }

        // Auditar métodos de modificación (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), self::AUDITABLE_ACTIONS)) {
            return true;
        }

        // Auditar navegación a rutas sensibles (GET)
        if ($request->method() === 'GET') {
            foreach (self::SENSITIVE_ROUTES as $route) {
                if (str_contains($path, $route)) {
                    return true;
                }
            }
            
            // Auditar acceso al dashboard
            if ($path === 'dashboard' || str_starts_with($path, 'admin/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar la auditoría
     */
    private function audit(Request $request, Response $response, float $startTime): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $routeName = $request->route()?->getName();
            $path = $request->path();
            $method = $request->method();

            // Determinar la acción basada en el método y ruta
            $accion = $this->determinarAccion($method, $path, $routeName);
            $descripcion = $this->generarDescripcion($method, $path, $routeName);
            $modulo = $this->determinarModulo($path);

            // Guardar en base de datos
            PistaAuditoria::create([
                'fecha_hora' => now(),
                'usuario_id' => auth()->id(),
                'evento' => $descripcion,
                'tabla_afectada' => $modulo,
                'registro_id' => $this->extraerRegistroId($request),
                'operacion' => $this->mapearOperacion($method),
                'valores_anteriores' => null,
                'valores_nuevos' => $method !== 'GET' ? $this->filterSensitiveData($request->all()) : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'sesion_id' => session()->getId(),
                'modulo' => $modulo,
                'accion_detalle' => $descripcion,
                'resultado' => $response->getStatusCode() < 400 ? 'exitoso' : 'fallido',
                'observaciones' => "Duración: {$duration}ms | Status: {$response->getStatusCode()}",
                'entidad_type' => $modulo,
                'entidad_id' => $this->extraerRegistroId($request),
                'accion' => $accion,
                'descripcion' => $descripcion,
                'contexto_adicional' => [
                    'url' => $request->fullUrl(),
                    'route_name' => $routeName,
                    'duration_ms' => $duration,
                    'status_code' => $response->getStatusCode(),
                    'referer' => $request->header('referer')
                ]
            ]);

            // Log para depuración
            if (config('app.debug')) {
                Log::debug('Audit registered', [
                    'user' => auth()->user()->email,
                    'action' => $accion,
                    'path' => $path,
                    'method' => $method
                ]);
            }

        } catch (\Exception $e) {
            // No interrumpir la request si falla la auditoría
            Log::error('Error en auditoría: ' . $e->getMessage(), [
                'path' => $request->path(),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * Determinar la acción basada en el método HTTP
     */
    private function determinarAccion(string $method, string $path, ?string $routeName): string
    {
        // Acciones específicas por ruta
        if (str_contains($path, 'login')) return 'login';
        if (str_contains($path, 'logout')) return 'logout';

        // Acciones por método HTTP
        return match($method) {
            'GET' => 'leer',
            'POST' => str_contains($path, 'login') ? 'login' : 'crear',
            'PUT', 'PATCH' => 'actualizar',
            'DELETE' => 'eliminar',
            default => 'otro'
        };
    }

    /**
     * Generar descripción legible de la acción
     */
    private function generarDescripcion(string $method, string $path, ?string $routeName): string
    {
        $usuario = auth()->user()->name ?? 'Usuario';
        
        // Descripciones específicas por ruta
        $descripciones = [
            'dashboard' => "Accedió al panel principal",
            'admin/users' => "Accedió a gestión de usuarios",
            'admin/documentos' => "Accedió a gestión de documentos",
            'admin/expedientes' => "Accedió a gestión de expedientes",
            'admin/auditoria' => "Accedió a registros de auditoría",
            'admin/trd' => "Accedió a Tablas de Retención Documental",
            'admin/series' => "Accedió a Series Documentales",
            'admin/subseries' => "Accedió a Subseries Documentales",
            'admin/ccd' => "Accedió a Cuadros de Clasificación",
            'admin/reportes' => "Accedió a Reportes",
            'admin/roles' => "Accedió a gestión de roles",
            'admin/firmas' => "Accedió a Firmas Digitales",
            'admin/prestamos' => "Accedió a Préstamos",
            'admin/workflow' => "Accedió a Flujos de Trabajo",
            'admin/transferencias' => "Accedió a Transferencias",
            'admin/disposiciones' => "Accedió a Disposiciones Finales",
            'admin/configuracion' => "Accedió a Configuración",
            'admin/servicios-externos' => "Accedió a Servicios Externos",
        ];

        // Buscar coincidencia
        foreach ($descripciones as $ruta => $desc) {
            if (str_contains($path, $ruta)) {
                $accionVerbo = match($method) {
                    'POST' => 'Creó registro en',
                    'PUT', 'PATCH' => 'Actualizó registro en',
                    'DELETE' => 'Eliminó registro en',
                    default => $desc
                };
                
                if ($method === 'GET') {
                    return $desc;
                }
                
                return str_replace('Accedió a', $accionVerbo, $desc);
            }
        }

        // Descripción genérica
        return match($method) {
            'GET' => "Navegó a: /{$path}",
            'POST' => "Creó registro en: /{$path}",
            'PUT', 'PATCH' => "Actualizó registro en: /{$path}",
            'DELETE' => "Eliminó registro en: /{$path}",
            default => "Acción {$method} en: /{$path}"
        };
    }

    /**
     * Determinar el módulo basado en la ruta
     */
    private function determinarModulo(string $path): string
    {
        $modulos = [
            'users' => 'Usuarios',
            'documentos' => 'Documentos',
            'expedientes' => 'Expedientes',
            'auditoria' => 'Auditoría',
            'trd' => 'TRD',
            'series' => 'Series',
            'subseries' => 'Subseries',
            'ccd' => 'CCD',
            'reportes' => 'Reportes',
            'roles' => 'Roles',
            'firmas' => 'Firmas',
            'prestamos' => 'Préstamos',
            'workflow' => 'Workflow',
            'transferencias' => 'Transferencias',
            'disposiciones' => 'Disposiciones',
            'configuracion' => 'Configuración',
            'servicios-externos' => 'Servicios Externos',
            'notificaciones' => 'Notificaciones',
            'dashboard' => 'Dashboard',
        ];

        foreach ($modulos as $key => $nombre) {
            if (str_contains($path, $key)) {
                return $nombre;
            }
        }

        return 'SGDEA';
    }

    /**
     * Extraer ID del registro de la ruta
     */
    private function extraerRegistroId(Request $request): ?int
    {
        $parameters = $request->route()?->parameters() ?? [];
        
        foreach ($parameters as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
            if (is_object($value) && property_exists($value, 'id')) {
                return $value->id;
            }
        }

        return null;
    }

    /**
     * Mapear método HTTP a operación
     */
    private function mapearOperacion(string $method): string
    {
        return match($method) {
            'GET' => 'read',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'other'
        };
    }

    /**
     * Filtrar datos sensibles
     */
    private function filterSensitiveData(array $data): ?array
    {
        if (empty($data)) {
            return null;
        }

        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_token',
            'secret', 'private_key', 'credit_card', 'cvv', '_token'
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***FILTERED***';
            }
        }

        return $data;
    }
}
