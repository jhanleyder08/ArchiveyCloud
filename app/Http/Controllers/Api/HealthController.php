<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    /**
     * Health check completo del sistema
     */
    public function check(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [];
        $overallStatus = 'healthy';

        // Verificar base de datos
        $checks['database'] = $this->checkDatabase();
        
        // Verificar caché
        $checks['cache'] = $this->checkCache();
        
        // Verificar Redis
        $checks['redis'] = $this->checkRedis();
        
        // Verificar sistema de archivos
        $checks['storage'] = $this->checkStorage();
        
        // Verificar colas
        $checks['queue'] = $this->checkQueue();
        
        // Verificar servicios externos
        $checks['external_services'] = $this->checkExternalServices();
        
        // Verificar performance
        $checks['performance'] = $this->checkPerformance();

        // Determinar estado general
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            } elseif ($check['status'] === 'degraded' && $overallStatus === 'healthy') {
                $overallStatus = 'degraded';
            }
        }

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);

        // Convertir checks a formato array para el frontend
        $checksArray = [];
        foreach ($checks as $name => $check) {
            $checksArray[] = [
                'name' => ucfirst(str_replace('_', ' ', $name)),
                'status' => $check['status'] === 'healthy' ? 'ok' : $check['status'],
                'message' => $check['message'] ?? null,
                'response_time' => isset($check['response_time']) ? (float) str_replace('ms', '', $check['response_time']) : null,
            ];
        }

        return response()->json([
            'status' => $overallStatus === 'healthy' ? 'ok' : $overallStatus,
            'timestamp' => now()->toISOString(),
            'response_time' => $responseTime . 'ms',
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checksArray,
            'system_info' => $this->getSystemInfo(),
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Health check rápido para load balancer
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Métricas de performance del sistema
     */
    public function metrics(): JsonResponse
    {
        return response()->json([
            'timestamp' => now()->toISOString(),
            'metrics' => [
                'memory' => [
                    'used' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                    'limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
                ],
                'database' => $this->getDatabaseMetrics(),
                'cache' => $this->getCacheMetrics(),
                'storage' => $this->getStorageMetrics(),
                'performance' => $this->getPerformanceMetrics(),
            ],
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test básico de conexión
            DB::connection()->getPdo();
            
            // Test de escritura/lectura
            $testValue = 'health_check_' . time();
            DB::statement("SELECT '{$testValue}' as test");
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Verificar conexiones activas
            $connections = $this->getDatabaseConnections();
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime . 'ms',
                'driver' => DB::getDriverName(),
                'active_connections' => $connections,
                'message' => 'Database is accessible',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Database connection failed',
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_cache_' . time();
            $testValue = 'test_value_' . time();
            
            // Test de escritura
            Cache::put($testKey, $testValue, 60);
            
            // Test de lectura
            $retrieved = Cache::get($testKey);
            
            // Limpiar test
            Cache::forget($testKey);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($retrieved !== $testValue) {
                throw new \Exception('Cache read/write mismatch');
            }
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime . 'ms',
                'driver' => config('cache.default'),
                'message' => 'Cache is working correctly',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Cache system failed',
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            if (!extension_loaded('redis')) {
                return [
                    'status' => 'degraded',
                    'message' => 'Redis extension not loaded',
                ];
            }

            $startTime = microtime(true);
            $redis = Redis::connection();
            
            // Test básico
            $testKey = 'health_check_redis_' . time();
            $testValue = 'test_value_' . time();
            
            $redis->set($testKey, $testValue, 'EX', 60);
            $retrieved = $redis->get($testKey);
            $redis->del($testKey);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($retrieved !== $testValue) {
                throw new \Exception('Redis read/write mismatch');
            }
            
            // Obtener info del servidor Redis
            $info = $redis->info();
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime . 'ms',
                'version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'message' => 'Redis is working correctly',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Redis connection failed',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $publicPath = public_path();
            
            // Verificar permisos de escritura
            if (!is_writable($storagePath)) {
                throw new \Exception('Storage directory is not writable');
            }
            
            // Test de escritura
            $testFile = $storagePath . '/app/health_check_' . time() . '.tmp';
            $testContent = 'health check test content';
            
            File::put($testFile, $testContent);
            $retrieved = File::get($testFile);
            File::delete($testFile);
            
            if ($retrieved !== $testContent) {
                throw new \Exception('File read/write mismatch');
            }
            
            // Obtener información de espacio en disco
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $status = 'healthy';
            if ($usedPercentage > 90) {
                $status = 'unhealthy';
            } elseif ($usedPercentage > 80) {
                $status = 'degraded';
            }
            
            return [
                'status' => $status,
                'free_space' => $this->formatBytes($freeSpace),
                'total_space' => $this->formatBytes($totalSpace),
                'used_percentage' => $usedPercentage . '%',
                'writable' => is_writable($storagePath),
                'message' => $status === 'healthy' ? 'Storage is healthy' : 'Storage space is running low',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Storage system failed',
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            
            // Verificar configuración de colas
            if ($connection === 'sync') {
                return [
                    'status' => 'degraded',
                    'connection' => $connection,
                    'message' => 'Using sync queue driver (not recommended for production)',
                ];
            }
            
            // Para Redis/Database, verificar conexión
            $queueInfo = [];
            
            if ($connection === 'redis') {
                $redis = Redis::connection('default');
                $queueInfo['pending_jobs'] = $redis->llen('queues:default');
            } elseif ($connection === 'database') {
                $queueInfo['pending_jobs'] = DB::table('jobs')->count();
                $queueInfo['failed_jobs'] = DB::table('failed_jobs')->count();
            }
            
            return [
                'status' => 'healthy',
                'connection' => $connection,
                'info' => $queueInfo,
                'message' => 'Queue system is operational',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Queue system failed',
            ];
        }
    }

    private function checkExternalServices(): array
    {
        $services = [];
        $overallStatus = 'healthy';
        
        // Verificar servicio de email
        try {
            $mailConfig = config('mail');
            $services['email'] = [
                'status' => 'healthy',
                'driver' => $mailConfig['default'],
                'message' => 'Email service configured',
            ];
        } catch (Throwable $e) {
            $services['email'] = [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
            $overallStatus = 'degraded';
        }
        
        // Verificar configuración de SMS
        if (config('services.twilio.sid')) {
            $services['sms'] = [
                'status' => 'healthy',
                'provider' => 'twilio',
                'message' => 'SMS service configured',
            ];
        } else {
            $services['sms'] = [
                'status' => 'degraded',
                'message' => 'SMS service not configured',
            ];
            if ($overallStatus === 'healthy') {
                $overallStatus = 'degraded';
            }
        }
        
        return [
            'status' => $overallStatus,
            'services' => $services,
        ];
    }

    private function checkPerformance(): array
    {
        $metrics = $this->getPerformanceMetrics();
        
        $status = 'healthy';
        
        // Verificar uso de memoria
        $memoryUsagePercent = ($metrics['memory_used'] / $metrics['memory_limit']) * 100;
        if ($memoryUsagePercent > 90) {
            $status = 'unhealthy';
        } elseif ($memoryUsagePercent > 80) {
            $status = 'degraded';
        }
        
        // Verificar cache hit ratio
        if (isset($metrics['cache_hit_ratio']) && $metrics['cache_hit_ratio'] < 70) {
            $status = $status === 'healthy' ? 'degraded' : $status;
        }
        
        return [
            'status' => $status,
            'metrics' => $metrics,
            'message' => $status === 'healthy' ? 'Performance is optimal' : 'Performance issues detected',
        ];
    }

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

    private function getDatabaseConnections(): int
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                $result = DB::select('SHOW STATUS WHERE variable_name = "Threads_connected"');
                return (int) $result[0]->Value;
            }
            return 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function getDatabaseMetrics(): array
    {
        try {
            return [
                'connections' => $this->getDatabaseConnections(),
                'driver' => DB::getDriverName(),
                'read_only' => false, // Podría implementarse
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getCacheMetrics(): array
    {
        try {
            $driver = config('cache.default');
            $metrics = ['driver' => $driver];
            
            if ($driver === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                $metrics['keyspace_hits'] = $info['keyspace_hits'] ?? 0;
                $metrics['keyspace_misses'] = $info['keyspace_misses'] ?? 0;
                
                $total = $metrics['keyspace_hits'] + $metrics['keyspace_misses'];
                $metrics['hit_ratio'] = $total > 0 ? round(($metrics['keyspace_hits'] / $total) * 100, 2) : 0;
            }
            
            return $metrics;
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getStorageMetrics(): array
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        
        return [
            'free_space' => $freeSpace,
            'total_space' => $totalSpace,
            'used_space' => $totalSpace - $freeSpace,
            'usage_percentage' => round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2),
        ];
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'memory_used' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'execution_time' => microtime(true) - LARAVEL_START,
            'included_files' => count(get_included_files()),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
        ];
    }

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
