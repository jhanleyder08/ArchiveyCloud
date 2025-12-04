<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;
use App\Models\ApiTokenLog;
use Carbon\Carbon;

class ApiTokenAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return response()->json([
                'error' => 'Token API requerido',
                'message' => 'Debes proporcionar un token de autenticación válido'
            ], 401);
        }

        $apiToken = $this->validateToken($token);
        
        if (!$apiToken) {
            return response()->json([
                'error' => 'Token inválido',
                'message' => 'El token proporcionado no es válido'
            ], 401);
        }

        // Verificar estado del token
        $tokenStatus = $this->checkTokenStatus($apiToken);
        if ($tokenStatus !== 'valid') {
            return response()->json([
                'error' => 'Token no disponible',
                'message' => $this->getStatusMessage($tokenStatus)
            ], 401);
        }

        // Verificar IP si está restringida
        if (!$this->checkIpRestriction($apiToken, $request->ip())) {
            return response()->json([
                'error' => 'IP no autorizada',
                'message' => 'Tu IP no está autorizada para usar este token'
            ], 403);
        }

        // Verificar permiso específico si se proporciona
        if ($permission && !$apiToken->tienePermiso($permission)) {
            return response()->json([
                'error' => 'Permiso insuficiente',
                'message' => "No tienes permiso para: {$permission}"
            ], 403);
        }

        // Verificar límite de usos
        if ($apiToken->alcanzeLimiteUsos()) {
            return response()->json([
                'error' => 'Límite de usos alcanzado',
                'message' => 'El token ha alcanzado su límite de usos'
            ], 429);
        }

        // Establecer usuario autenticado
        if ($apiToken->usuario) {
            auth()->setUser($apiToken->usuario);
            $request->attributes->set('api_token', $apiToken);
            $request->attributes->set('api_user', $apiToken->usuario);
        }

        // Registrar el uso del token
        $startTime = microtime(true);
        
        $response = $next($request);
        
        // Calcular tiempo de respuesta
        $responseTime = (microtime(true) - $startTime) * 1000; // en milisegundos
        
        // Log del request
        $this->logApiRequest($apiToken, $request, $response, $responseTime);
        
        // Actualizar último uso del token
        $apiToken->update([
            'ultimo_uso' => now(),
            'ultima_ip' => $request->ip(),
            'usos_realizados' => $apiToken->usos_realizados + 1,
        ]);

        return $response;
    }

    /**
     * Extraer token del request
     */
    private function extractToken(Request $request): ?string
    {
        // Bearer token en header Authorization
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Token en query parameter
        if ($request->has('api_token')) {
            return $request->get('api_token');
        }

        // Token en header X-API-Token
        if ($request->hasHeader('X-API-Token')) {
            return $request->header('X-API-Token');
        }

        return null;
    }

    /**
     * Validar token y obtener instancia
     */
    private function validateToken(string $token): ?ApiToken
    {
        $hashedToken = hash('sha256', $token);
        
        return ApiToken::where('token', $hashedToken)
            ->with('usuario')
            ->first();
    }

    /**
     * Verificar estado del token
     */
    private function checkTokenStatus(ApiToken $token): string
    {
        if (!$token->activo) {
            return 'inactive';
        }

        if ($token->estaExpirado()) {
            return 'expired';
        }

        if ($token->alcanzeLimiteUsos()) {
            return 'limit_reached';
        }

        return 'valid';
    }

    /**
     * Verificar restricción de IP
     */
    private function checkIpRestriction(ApiToken $token, string $clientIp): bool
    {
        // Si no hay restricciones de IP, permitir todas
        if (!$token->ips_permitidas || empty($token->ips_permitidas)) {
            return true;
        }

        return in_array($clientIp, $token->ips_permitidas);
    }

    /**
     * Obtener mensaje de estado
     */
    private function getStatusMessage(string $status): string
    {
        return match($status) {
            'inactive' => 'El token está desactivado',
            'expired' => 'El token ha expirado',
            'limit_reached' => 'El token ha alcanzado su límite de usos',
            default => 'Token no válido'
        };
    }

    /**
     * Registrar el request en logs
     */
    private function logApiRequest(ApiToken $token, Request $request, Response $response, float $responseTime): void
    {
        try {
            ApiTokenLog::create([
                'api_token_id' => $token->id,
                'ruta' => $request->getPathInfo(),
                'metodo' => $request->getMethod(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'codigo_respuesta' => $response->getStatusCode(),
                'tiempo_respuesta' => round($responseTime / 1000, 3), // convertir a segundos
                'parametros' => [
                    'query' => $request->query->all(),
                    'content_length' => $request->header('Content-Length', 0),
                    'accept' => $request->header('Accept'),
                ],
            ]);
        } catch (\Exception $e) {
            // Silenciar errores de logging para no interrumpir la API
            \Log::warning('Error logging API request: ' . $e->getMessage());
        }
    }
}
