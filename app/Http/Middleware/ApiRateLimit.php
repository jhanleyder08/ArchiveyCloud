<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Solo aplicar rate limiting a rutas de API
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Obtener configuración de rate limiting
        $config = $this->getRateLimitConfig($request);
        
        // Generar clave única para el rate limiting
        $key = $this->generateRateLimitKey($request);
        
        // Verificar límite general
        if ($this->checkGeneralRateLimit($key, $config['general'])) {
            return $this->rateLimitExceededResponse('general');
        }

        // Verificar límite por token (si aplica)
        if ($request->has('api_token_id')) {
            $tokenKey = "api_token:" . $request->get('api_token_id');
            if ($this->checkTokenRateLimit($tokenKey, $config['token'])) {
                return $this->rateLimitExceededResponse('token');
            }
        }

        // Verificar límite por endpoint específico
        $endpointKey = $key . ':' . $this->getEndpointIdentifier($request);
        if ($this->checkEndpointRateLimit($endpointKey, $request, $config['endpoint'])) {
            return $this->rateLimitExceededResponse('endpoint');
        }

        // Registrar la petición
        $this->recordRequest($key, $request);

        $response = $next($request);

        // Agregar headers informativos sobre rate limiting
        return $this->addRateLimitHeaders($response, $key, $config);
    }

    /**
     * Obtener configuración de rate limiting
     */
    private function getRateLimitConfig(Request $request): array
    {
        // Configuración base
        $config = [
            'general' => [
                'max_attempts' => 1000, // 1000 requests
                'decay_minutes' => 60,  // por hora
            ],
            'token' => [
                'max_attempts' => 5000, // 5000 requests  
                'decay_minutes' => 60,  // por hora
            ],
            'endpoint' => [
                'max_attempts' => 100,  // 100 requests
                'decay_minutes' => 60,  // por hora por endpoint
            ]
        ];

        // Configuraciones específicas por endpoint
        $endpointConfigs = [
            'GET' => [
                'max_attempts' => 200,
                'decay_minutes' => 60,
            ],
            'POST' => [
                'max_attempts' => 50,
                'decay_minutes' => 60,
            ],
            'PUT' => [
                'max_attempts' => 50,
                'decay_minutes' => 60,
            ],
            'DELETE' => [
                'max_attempts' => 20,
                'decay_minutes' => 60,
            ],
            // Endpoints específicos con límites más estrictos
            'POST:/api/documentos' => [
                'max_attempts' => 10,
                'decay_minutes' => 60,
            ],
            'POST:/api/expedientes' => [
                'max_attempts' => 20,
                'decay_minutes' => 60,
            ],
        ];

        // Aplicar configuración específica si existe
        $method = $request->method();
        $path = $request->path();
        $endpointKey = $method . ':/' . $path;

        if (isset($endpointConfigs[$endpointKey])) {
            $config['endpoint'] = $endpointConfigs[$endpointKey];
        } elseif (isset($endpointConfigs[$method])) {
            $config['endpoint'] = $endpointConfigs[$method];
        }

        return $config;
    }

    /**
     * Generar clave para rate limiting
     */
    private function generateRateLimitKey(Request $request): string
    {
        // Usar IP como base
        $ip = $request->ip();
        
        // Si hay token de API, usarlo para identificar al cliente
        if ($request->has('api_token_id')) {
            return 'api_rate_limit:token:' . $request->get('api_token_id');
        }

        // Usar IP si no hay token
        return 'api_rate_limit:ip:' . $ip;
    }

    /**
     * Obtener identificador del endpoint
     */
    private function getEndpointIdentifier(Request $request): string
    {
        return $request->method() . ':' . $request->path();
    }

    /**
     * Verificar límite general
     */
    private function checkGeneralRateLimit(string $key, array $config): bool
    {
        $generalKey = $key . ':general';
        
        return RateLimiter::tooManyAttempts(
            $generalKey, 
            $config['max_attempts']
        );
    }

    /**
     * Verificar límite por token
     */
    private function checkTokenRateLimit(string $tokenKey, array $config): bool
    {
        return RateLimiter::tooManyAttempts(
            $tokenKey, 
            $config['max_attempts']
        );
    }

    /**
     * Verificar límite por endpoint
     */
    private function checkEndpointRateLimit(string $endpointKey, Request $request, array $config): bool
    {
        return RateLimiter::tooManyAttempts(
            $endpointKey, 
            $config['max_attempts']
        );
    }

    /**
     * Registrar la petición en el rate limiter
     */
    private function recordRequest(string $key, Request $request): void
    {
        $config = $this->getRateLimitConfig($request);
        
        // Registrar para límite general
        RateLimiter::hit(
            $key . ':general', 
            $config['general']['decay_minutes'] * 60
        );

        // Registrar para límite por token
        if ($request->has('api_token_id')) {
            $tokenKey = "api_token:" . $request->get('api_token_id');
            RateLimiter::hit(
                $tokenKey, 
                $config['token']['decay_minutes'] * 60
            );
        }

        // Registrar para límite por endpoint
        $endpointKey = $key . ':' . $this->getEndpointIdentifier($request);
        RateLimiter::hit(
            $endpointKey, 
            $config['endpoint']['decay_minutes'] * 60
        );
    }

    /**
     * Agregar headers de rate limiting
     */
    private function addRateLimitHeaders(Response $response, string $key, array $config): Response
    {
        $generalKey = $key . ':general';
        
        $remaining = RateLimiter::remaining(
            $generalKey, 
            $config['general']['max_attempts']
        );
        
        $resetTime = RateLimiter::availableIn($generalKey);
        
        $response->headers->set('X-RateLimit-Limit', $config['general']['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($resetTime)->timestamp);
        
        // Headers adicionales
        $response->headers->set('X-RateLimit-Window', $config['general']['decay_minutes'] . ' minutes');
        
        return $response;
    }

    /**
     * Respuesta cuando se excede el rate limit
     */
    private function rateLimitExceededResponse(string $type): Response
    {
        $messages = [
            'general' => 'Demasiadas peticiones. Límite general excedido.',
            'token' => 'Demasiadas peticiones para este token de API.',
            'endpoint' => 'Demasiadas peticiones a este endpoint específico.',
        ];

        $response = [
            'success' => false,
            'message' => $messages[$type] ?? 'Límite de peticiones excedido',
            'error' => 'RATE_LIMIT_EXCEEDED',
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'retry_after' => 60, // segundos
        ];

        return response()->json($response, 429)
            ->header('Retry-After', 60)
            ->header('X-RateLimit-Exceeded', $type);
    }

    /**
     * Limpiar contadores de rate limiting (para comandos de mantenimiento)
     */
    public static function clearRateLimits(?string $identifier = null): void
    {
        if ($identifier) {
            // Limpiar para un identificador específico
            $keys = [
                "api_rate_limit:token:{$identifier}",
                "api_rate_limit:ip:{$identifier}",
            ];
            
            foreach ($keys as $key) {
                RateLimiter::clear($key . ':general');
                RateLimiter::clear($key);
            }
        } else {
            // Limpiar todos los rate limits (usar con cuidado)
            Cache::tags(['rate_limiter'])->flush();
        }
    }

    /**
     * Obtener estadísticas de rate limiting
     */
    public static function getRateLimitStats(string $identifier): array
    {
        $key = "api_rate_limit:token:{$identifier}";
        
        return [
            'general' => [
                'remaining' => RateLimiter::remaining($key . ':general', 1000),
                'available_in' => RateLimiter::availableIn($key . ':general'),
            ],
            'token' => [
                'remaining' => RateLimiter::remaining($key, 5000),
                'available_in' => RateLimiter::availableIn($key),
            ],
        ];
    }
}
