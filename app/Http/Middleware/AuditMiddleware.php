<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Auditoría
 * Registra todas las acciones importantes del sistema
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
     * Rutas sensibles que siempre se auditan
     */
    private const SENSITIVE_ROUTES = [
        'login', 'logout', 'password', 'usuarios', 'roles', 
        'permisos', 'workflows', 'documentos', 'expedientes'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Ejecutar request
        $response = $next($request);

        // Auditar solo si es necesario
        if ($this->shouldAudit($request)) {
            $this->audit($request, $response, $startTime);
        }

        return $response;
    }

    /**
     * Determinar si la request debe ser auditada
     */
    private function shouldAudit(Request $request): bool
    {
        // Auditar métodos importantes
        if (in_array($request->method(), self::AUDITABLE_ACTIONS)) {
            return true;
        }

        // Auditar rutas sensibles
        foreach (self::SENSITIVE_ROUTES as $route) {
            if (str_contains($request->path(), $route)) {
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
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $auditData = [
            // Información de la request
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            
            // Usuario
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            
            // Datos de la request (filtrados)
            'request_data' => $this->filterSensitiveData($request->all()),
            
            // Response
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            
            // Metadata
            'timestamp' => now()->toISOString(),
        ];

        // Log según el nivel de severidad
        if ($response->getStatusCode() >= 500) {
            Log::error('Audit - Server Error', $auditData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('Audit - Client Error', $auditData);
        } else {
            Log::info('Audit - Success', $auditData);
        }

        // Para operaciones críticas, también guardar en base de datos
        if ($this->isCriticalOperation($request)) {
            $this->saveToDatabase($auditData);
        }
    }

    /**
     * Filtrar datos sensibles
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_token',
            'secret', 'private_key', 'credit_card', 'cvv'
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***FILTERED***';
            }
        }

        return $data;
    }

    /**
     * Determinar si es una operación crítica
     */
    private function isCriticalOperation(Request $request): bool
    {
        $criticalRoutes = ['usuarios', 'roles', 'permisos', 'workflows'];
        $criticalMethods = ['DELETE'];

        foreach ($criticalRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return in_array($request->method(), $criticalMethods);
    }

    /**
     * Guardar en base de datos (placeholder)
     */
    private function saveToDatabase(array $auditData): void
    {
        // TODO: Implementar cuando se cree la tabla de auditoría
        // AuditLog::create($auditData);
    }
}
