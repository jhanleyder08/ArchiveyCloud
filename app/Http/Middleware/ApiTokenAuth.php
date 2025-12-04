<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiToken;
use Carbon\Carbon;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si es una ruta de API
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Obtener el token del header Authorization
        $token = $this->extractTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de acceso requerido',
                'error' => 'MISSING_TOKEN',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Buscar el token en la base de datos
        $apiToken = ApiToken::where('token', hash('sha256', $token))
            ->where('activo', true)
            ->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token de acceso inválido',
                'error' => 'INVALID_TOKEN',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Verificar si el token ha expirado
        if ($apiToken->fecha_expiracion && Carbon::parse($apiToken->fecha_expiracion)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de acceso expirado',
                'error' => 'EXPIRED_TOKEN',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Verificar límites de uso si están configurados
        if ($apiToken->limite_usos && $apiToken->usos_realizados >= $apiToken->limite_usos) {
            return response()->json([
                'success' => false,
                'message' => 'Límite de usos del token excedido',
                'error' => 'TOKEN_LIMIT_EXCEEDED',
                'timestamp' => now()->toISOString(),
            ], 429);
        }

        // Verificar IP permitidas si están configuradas
        if ($apiToken->ips_permitidas && !empty($apiToken->ips_permitidas)) {
            $clientIp = $request->ip();
            if (!in_array($clientIp, $apiToken->ips_permitidas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso no autorizado desde esta IP',
                    'error' => 'IP_NOT_ALLOWED',
                    'timestamp' => now()->toISOString(),
                ], 403);
            }
        }

        // Verificar permisos/scopes si están configurados
        $requiredScopes = $this->getRequiredScopes($request);
        if (!empty($requiredScopes) && !$this->hasRequiredScopes($apiToken, $requiredScopes)) {
            return response()->json([
                'success' => false,
                'message' => 'Permisos insuficientes para esta operación',
                'error' => 'INSUFFICIENT_PERMISSIONS',
                'required_scopes' => $requiredScopes,
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        // Autenticar al usuario asociado al token
        if ($apiToken->usuario) {
            Auth::setUser($apiToken->usuario);
        }

        // Incrementar contador de usos
        $apiToken->increment('usos_realizados');
        $apiToken->update([
            'ultimo_uso' => now(),
            'ultima_ip' => $request->ip(),
        ]);

        // Agregar información del token al request para uso posterior
        $request->merge([
            'api_token' => $apiToken,
            'api_token_id' => $apiToken->id,
        ]);

        // Registrar el uso del token
        $this->logTokenUsage($apiToken, $request);

        return $next($request);
    }

    /**
     * Extraer token del request
     */
    private function extractTokenFromRequest(Request $request): ?string
    {
        // Primero intentar obtener del header Authorization
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Intentar obtener del header X-API-TOKEN
        $apiTokenHeader = $request->header('X-API-TOKEN');
        if ($apiTokenHeader) {
            return $apiTokenHeader;
        }

        // Intentar obtener del query parameter
        return $request->query('api_token');
    }

    /**
     * Obtener scopes requeridos para la ruta actual
     */
    private function getRequiredScopes(Request $request): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $routeName = $route->getName();
        $method = $request->method();

        // Mapeo de rutas a permisos requeridos
        $scopeMap = [
            // Documentos
            'api.documentos.index' => ['documentos:read'],
            'api.documentos.show' => ['documentos:read'],
            'api.documentos.store' => ['documentos:write'],
            'api.documentos.update' => ['documentos:write'],
            'api.documentos.destroy' => ['documentos:delete'],
            'api.documentos.download' => ['documentos:read'],

            // Expedientes
            'api.expedientes.index' => ['expedientes:read'],
            'api.expedientes.show' => ['expedientes:read'],
            'api.expedientes.store' => ['expedientes:write'],
            'api.expedientes.update' => ['expedientes:write'],
            'api.expedientes.destroy' => ['expedientes:delete'],
            'api.expedientes.cerrar' => ['expedientes:write'],
            'api.expedientes.reabrir' => ['expedientes:write'],

            // Usuarios
            'api.usuarios.index' => ['usuarios:read'],
            'api.usuarios.show' => ['usuarios:read'],
            'api.usuarios.store' => ['usuarios:write'],
            'api.usuarios.update' => ['usuarios:write'],

            // Auditoría
            'api.auditoria.index' => ['auditoria:read'],
            'api.auditoria.show' => ['auditoria:read'],

            // Firmas digitales
            'api.firmas.index' => ['firmas:read'],
            'api.firmas.store' => ['firmas:write'],
            'api.firmas.validar' => ['firmas:read'],
        ];

        return $scopeMap[$routeName] ?? [];
    }

    /**
     * Verificar si el token tiene los scopes requeridos
     */
    private function hasRequiredScopes(ApiToken $token, array $requiredScopes): bool
    {
        if (empty($requiredScopes)) {
            return true;
        }

        $tokenScopes = $token->permisos ?? [];

        // Si el token tiene el scope 'admin' o '*', tiene todos los permisos
        if (in_array('admin', $tokenScopes) || in_array('*', $tokenScopes)) {
            return true;
        }

        // Verificar cada scope requerido
        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $tokenScopes)) {
                // Verificar wildcards (ej: documentos:* incluye documentos:read)
                $scopeParts = explode(':', $requiredScope);
                $wildcardScope = $scopeParts[0] . ':*';
                
                if (!in_array($wildcardScope, $tokenScopes)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Registrar uso del token
     */
    private function logTokenUsage(ApiToken $token, Request $request): void
    {
        try {
            // Crear registro de uso del token
            \DB::table('api_token_logs')->insert([
                'api_token_id' => $token->id,
                'ruta' => $request->path(),
                'metodo' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'parametros' => json_encode($request->query()),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log del error pero no interrumpir la ejecución
            \Log::error('Error al registrar uso de token API: ' . $e->getMessage());
        }
    }
}
