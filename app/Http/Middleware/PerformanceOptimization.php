<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceOptimization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Aplicar optimizaciones antes del procesamiento
        $this->applyPreProcessingOptimizations($request);

        $response = $next($request);

        // Aplicar optimizaciones después del procesamiento
        $this->applyPostProcessingOptimizations($request, $response, $startTime, $startMemory);

        return $response;
    }

    /**
     * Aplicar optimizaciones antes del procesamiento de la request
     */
    private function applyPreProcessingOptimizations(Request $request): void
    {
        // Establecer headers de caché para assets estáticos
        if ($this->isStaticAsset($request)) {
            $this->setStaticAssetHeaders($request);
        }

        // Comprimir output si es posible
        if ($this->shouldCompressResponse($request)) {
            if (!ob_start('ob_gzhandler')) {
                ob_start();
            }
        }

        // Aplicar límites de memoria para requests específicas
        $this->applyMemoryLimits($request);
    }

    /**
     * Aplicar optimizaciones después del procesamiento de la request
     */
    private function applyPostProcessingOptimizations(
        Request $request, 
        Response $response, 
        float $startTime, 
        int $startMemory
    ): void {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos
        $memoryUsed = $endMemory - $startMemory;

        // Agregar headers de performance
        $response->headers->set('X-Response-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));
        $response->headers->set('X-Memory-Peak', $this->formatBytes(memory_get_peak_usage(true)));

        // Headers de seguridad
        $this->addSecurityHeaders($response);

        // Headers de caché
        $this->addCacheHeaders($request, $response);

        // Log de performance si es necesario
        $this->logPerformanceMetrics($request, $executionTime, $memoryUsed);

        // Cleanup de memoria para requests grandes
        if ($memoryUsed > 50 * 1024 * 1024) { // 50MB
            gc_collect_cycles();
        }
    }

    /**
     * Determinar si la request es para un asset estático
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
        
        foreach ($staticExtensions as $extension) {
            if (str_ends_with($path, '.' . $extension)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Establecer headers para assets estáticos
     */
    private function setStaticAssetHeaders(Request $request): void
    {
        // Headers de caché agresivo para assets estáticos
        $maxAge = 31536000; // 1 año
        
        header("Cache-Control: public, max-age={$maxAge}, immutable");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Pragma: cache');
    }

    /**
     * Determinar si se debe comprimir la respuesta
     */
    private function shouldCompressResponse(Request $request): bool
    {
        // No comprimir si ya está comprimido
        if (ob_get_level() > 0) {
            return false;
        }

        // No comprimir para requests específicas
        if ($request->is('api/*/download') || $request->is('*/export/*')) {
            return false;
        }

        // Comprimir solo si el cliente lo soporta
        $acceptEncoding = $request->header('Accept-Encoding', '');
        return strpos($acceptEncoding, 'gzip') !== false;
    }

    /**
     * Aplicar límites de memoria específicos por tipo de request
     */
    private function applyMemoryLimits(Request $request): void
    {
        $path = $request->path();
        
        // Límites específicos por tipo de operación
        if (str_contains($path, 'reportes') || str_contains($path, 'export')) {
            // Reportes pueden necesitar más memoria
            ini_set('memory_limit', config('optimization.performance.memory_limit', '512M'));
            ini_set('max_execution_time', config('optimization.performance.reports.timeout', 600));
        } elseif (str_contains($path, 'upload') || str_contains($path, 'documentos')) {
            // Uploads necesitan más memoria y tiempo
            ini_set('memory_limit', '256M');
            ini_set('max_execution_time', '300');
        }
    }

    /**
     * Agregar headers de seguridad
     */
    private function addSecurityHeaders(Response $response): void
    {
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        // Solo agregar HSTS en HTTPS
        if (request()->isSecure()) {
            $securityHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        foreach ($securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }
    }

    /**
     * Agregar headers de caché apropiados
     */
    private function addCacheHeaders(Request $request, Response $response): void
    {
        // No cachear si hay errores
        if ($response->getStatusCode() >= 400) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            return;
        }

        $path = $request->path();

        // Caché específico por tipo de contenido
        if (str_contains($path, 'api/')) {
            // API responses con caché corto
            $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 minutos
        } elseif (str_contains($path, 'reportes') || str_contains($path, 'estadisticas')) {
            // Reportes con caché medio
            $response->headers->set('Cache-Control', 'public, max-age=1800'); // 30 minutos
        } elseif ($this->isStaticAsset($request)) {
            // Assets estáticos con caché largo (ya manejado en setStaticAssetHeaders)
            return;
        } else {
            // Páginas dinámicas con caché corto
            $response->headers->set('Cache-Control', 'public, max-age=60'); // 1 minuto
        }

        // ETag para validación de caché
        $etag = md5($response->getContent());
        $response->headers->set('ETag', '"' . $etag . '"');

        // Verificar If-None-Match para 304 Not Modified
        if ($request->header('If-None-Match') === '"' . $etag . '"') {
            $response->setStatusCode(304);
            $response->setContent('');
        }
    }

    /**
     * Log de métricas de performance
     */
    private function logPerformanceMetrics(Request $request, float $executionTime, int $memoryUsed): void
    {
        $threshold = config('optimization.monitoring.performance_thresholds.page_load', 3000);
        
        // Solo log si excede el threshold o si tracking está habilitado
        if ($executionTime > $threshold || config('optimization.monitoring.track_performance', false)) {
            $context = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ];

            if ($executionTime > $threshold) {
                Log::warning('Slow request detected', $context);
            } else {
                Log::info('Performance metrics', $context);
            }

            // Guardar métricas en caché para dashboard de monitoring
            $this->storePerformanceMetrics($context);
        }
    }

    /**
     * Almacenar métricas de performance para monitoring
     */
    private function storePerformanceMetrics(array $metrics): void
    {
        try {
            $key = 'performance_metrics:' . date('Y-m-d-H');
            $currentMetrics = Cache::get($key, []);
            $currentMetrics[] = $metrics;
            
            // Mantener solo las últimas 100 métricas por hora
            if (count($currentMetrics) > 100) {
                $currentMetrics = array_slice($currentMetrics, -100);
            }
            
            Cache::put($key, $currentMetrics, 3600); // 1 hora
        } catch (\Exception $e) {
            Log::error('Error storing performance metrics: ' . $e->getMessage());
        }
    }

    /**
     * Formatear bytes en formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
