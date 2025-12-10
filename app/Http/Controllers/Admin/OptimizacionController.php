<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OptimizacionController extends Controller
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Dashboard principal de optimización
     */
    public function index(): Response
    {
        return Inertia::render('admin/optimizacion/Index', [
            'systemStatus' => $this->getSystemStatus(),
            'optimizationHistory' => $this->getOptimizationHistory(),
            'cacheStatistics' => $this->getCacheStatistics(),
            'healthStatus' => $this->getHealthStatus(),
        ]);
    }

    /**
     * Panel de gestión de caché
     */
    public function cache(): Response
    {
        return Inertia::render('admin/optimizacion/Cache', [
            'cacheInfo' => $this->cacheService->getCacheInfo(),
            'cacheStats' => $this->getCacheDetailedStats(),
            'ttlConfig' => config('optimization.cache.ttl', []),
        ]);
    }

    /**
     * Panel de gestión de backups
     */
    public function backups(): Response
    {
        return Inertia::render('admin/optimizacion/Backups', [
            'backupHistory' => $this->getBackupHistory(),
            'storageInfo' => $this->getStorageInfo(),
            'backupConfig' => $this->getBackupConfig(),
        ]);
    }

    /**
     * Panel de health checks
     */
    public function monitoring(): Response
    {
        return Inertia::render('admin/optimizacion/Monitoring', [
            'healthChecks' => $this->getDetailedHealthChecks(),
            'performanceMetrics' => $this->getPerformanceMetrics(),
            'systemInfo' => $this->getSystemInfo(),
        ]);
    }

    /**
     * Ejecutar optimización desde web
     */
    public function runOptimization(Request $request): JsonResponse
    {
        try {
            $options = [];
            
            if ($request->boolean('dry_run')) {
                $options[] = '--dry-run';
            }
            
            if ($request->boolean('skip_cache')) {
                $options[] = '--skip-cache';
            }
            
            if ($request->boolean('skip_config')) {
                $options[] = '--skip-config';
            }
            
            if ($request->boolean('skip_routes')) {
                $options[] = '--skip-routes';
            }
            
            if ($request->boolean('force')) {
                $options[] = '--force';
            }

            // Ejecutar comando de optimización
            $exitCode = Artisan::call('optimize:production', $options);
            $output = Artisan::output();

            // Registrar en historial
            $this->recordOptimization([
                'options' => $options,
                'exit_code' => $exitCode,
                'output' => $output,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => $exitCode === 0 ? 'Optimización ejecutada exitosamente' : 'Error durante la optimización',
            ]);

        } catch (\Exception $e) {
            Log::error('Error running optimization: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gestión de caché - Warmup
     */
    public function cacheWarmup(): JsonResponse
    {
        try {
            $startTime = microtime(true);
            $results = $this->cacheService->warmupCriticalCaches();
            $endTime = microtime(true);

            return response()->json([
                'success' => !isset($results['error']),
                'message' => isset($results['error']) ? 
                    'Error durante warmup: ' . $results['error'] : 
                    'Cachés precalentados exitosamente',
                'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante warmup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gestión de caché - Flush
     */
    public function cacheFlush(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'all');
            
            switch ($type) {
                case 'all':
                    $success = $this->cacheService->flush();
                    $message = 'Todos los cachés limpiados';
                    break;
                    
                case 'user':
                    $userId = $request->input('user_id');
                    if (!$userId) {
                        return response()->json(['success' => false, 'message' => 'ID de usuario requerido'], 400);
                    }
                    $this->cacheService->forgetUserCache((int) $userId);
                    $success = true;
                    $message = "Caché del usuario {$userId} limpiado";
                    break;
                    
                case 'pattern':
                    $pattern = $request->input('pattern');
                    if (!$pattern) {
                        return response()->json(['success' => false, 'message' => 'Patrón requerido'], 400);
                    }
                    $deleted = $this->cacheService->forgetByPattern($pattern);
                    $success = true;
                    $message = "{$deleted} claves eliminadas con patrón '{$pattern}'";
                    break;
                    
                default:
                    return response()->json(['success' => false, 'message' => 'Tipo de limpieza no válido'], 400);
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante limpieza: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear backup desde web
     */
    public function createBackup(Request $request): JsonResponse
    {
        try {
            $options = [
                '--type' => $request->input('type', 'full'),
            ];
            
            if ($request->boolean('compress')) {
                $options['--compress'] = true;
            }
            
            if ($request->boolean('cleanup')) {
                $options['--cleanup'] = true;
            }
            
            if ($request->has('retention_days')) {
                $options['--retention-days'] = $request->input('retention_days');
            }
            
            if ($request->boolean('dry_run')) {
                $options['--dry-run'] = true;
            }

            $exitCode = Artisan::call('backup:create', $options);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => $exitCode === 0 ? 'Backup creado exitosamente' : 'Error durante backup',
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating backup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estado del sistema en tiempo real
     */
    public function getSystemStatusApi(): JsonResponse
    {
        return response()->json([
            'system_status' => $this->getSystemStatus(),
            'health_status' => $this->getHealthStatus(),
            'cache_stats' => $this->getCacheStatistics(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Estado general del sistema
     */
    private function getSystemStatus(): array
    {
        return [
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'optimization_enabled' => config('optimization.enabled', false),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'redis_available' => extension_loaded('redis'),
            'uptime' => $this->getSystemUptime(),
        ];
    }

    /**
     * Historial de optimizaciones
     */
    private function getOptimizationHistory(): array
    {
        $historyFile = storage_path('app/optimization-history.json');
        
        if (!File::exists($historyFile)) {
            return [];
        }
        
        $history = json_decode(File::get($historyFile), true) ?? [];
        
        // Retornar últimas 10 optimizaciones
        return array_slice(array_reverse($history), 0, 10);
    }

    /**
     * Estadísticas de caché
     */
    private function getCacheStatistics(): array
    {
        $info = $this->cacheService->getCacheInfo();
        
        $stats = [
            'driver' => $info['driver'],
            'status' => $info['status'],
            'hit_ratio' => 0,
            'memory_usage' => 'N/A',
            'keys_count' => 'N/A',
        ];
        
        if (isset($info['redis'])) {
            $stats['hit_ratio'] = $info['redis']['hit_ratio'] ?? 0;
            $stats['memory_usage'] = $info['redis']['used_memory'] ?? 'N/A';
            $stats['keys_count'] = ($info['redis']['keyspace_hits'] ?? 0) + ($info['redis']['keyspace_misses'] ?? 0);
        }
        
        return $stats;
    }

    /**
     * Estado de health checks
     */
    private function getHealthStatus(): array
    {
        try {
            // Llamar directamente al controlador en lugar de hacer HTTP request
            $healthController = app(\App\Http\Controllers\Api\HealthController::class);
            $response = $healthController->check();
            
            // Aceptar tanto 200 (ok) como 503 (degraded)
            if (in_array($response->status(), [200, 503])) {
                $data = json_decode($response->getContent(), true);
                return [
                    'status' => $data['status'] ?? 'unknown',
                    'response_time' => $data['response_time'] ?? 'N/A',
                    'checks' => $data['checks'] ?? [],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Health check failed: ' . $e->getMessage());
        }
        
        return [
            'status' => 'error',
            'response_time' => 'N/A',
            'checks' => [],
        ];
    }

    /**
     * Estadísticas detalladas de caché
     */
    private function getCacheDetailedStats(): array
    {
        $cacheKeys = [
            'system_statistics' => 'Estadísticas del sistema',
            'trd_active_data' => 'Datos TRD activos',
            'external_services_config' => 'Configuración servicios externos',
        ];

        $stats = [];
        foreach ($cacheKeys as $key => $description) {
            $exists = Cache::has($key);
            $stats[] = [
                'key' => $key,
                'description' => $description,
                'exists' => $exists,
                'status' => $exists ? 'cached' : 'not_cached',
            ];
        }

        return $stats;
    }

    /**
     * Historial de backups
     */
    private function getBackupHistory(): array
    {
        $historyFile = storage_path('backups/backup-history.json');
        
        if (!File::exists($historyFile)) {
            return [];
        }
        
        $history = json_decode(File::get($historyFile), true) ?? [];
        
        // Retornar últimos 20 backups
        return array_slice(array_reverse($history), 0, 20);
    }

    /**
     * Información de almacenamiento
     */
    private function getStorageInfo(): array
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        
        return [
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'used_space' => $totalSpace - $freeSpace,
            'usage_percentage' => round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2),
            'free_space_formatted' => $this->formatBytes($freeSpace),
            'total_space_formatted' => $this->formatBytes($totalSpace),
            'used_space_formatted' => $this->formatBytes($totalSpace - $freeSpace),
        ];
    }

    /**
     * Configuración de backup
     */
    private function getBackupConfig(): array
    {
        return [
            'retention_days' => config('optimization.backup.retention_days', 30),
            'enabled' => config('optimization.backup.enabled', true),
            'compression' => config('optimization.backup.compression', true),
            'types' => ['full', 'database', 'files'],
        ];
    }

    /**
     * Health checks detallados
     */
    private function getDetailedHealthChecks(): array
    {
        $healthStatus = $this->getHealthStatus();
        return $healthStatus['checks'] ?? [];
    }

    /**
     * Métricas de performance
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'memory_used' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'execution_time' => microtime(true) - LARAVEL_START,
            'included_files' => count(get_included_files()),
            'database_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
        ];
    }

    /**
     * Información del sistema
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'os' => PHP_OS,
            'architecture' => php_uname('m'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * Registrar optimización en historial
     */
    private function recordOptimization(array $data): void
    {
        $historyFile = storage_path('app/optimization-history.json');
        
        $history = [];
        if (File::exists($historyFile)) {
            $history = json_decode(File::get($historyFile), true) ?? [];
        }
        
        $history[] = array_merge($data, [
            'timestamp' => now()->toISOString(),
            'success' => $data['exit_code'] === 0,
        ]);
        
        // Mantener solo los últimos 50 registros
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        File::put($historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    /**
     * Obtener uptime del sistema
     */
    private function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A (Windows)';
        }
        
        try {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Parsear límite de memoria
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }

    /**
     * Formatear bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
