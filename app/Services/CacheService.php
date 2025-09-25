<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Configuraciones de TTL por tipo de datos
     */
    private array $ttlConfig;

    public function __construct()
    {
        $this->ttlConfig = config('optimization.cache.ttl', [
            'user_permissions' => 1800,
            'document_metadata' => 3600,
            'trd_data' => 7200,
            'workflow_states' => 900,
            'statistics' => 1800,
            'notifications' => 300,
            'external_services' => 600,
        ]);
    }

    /**
     * Cache de permisos de usuario
     */
    public function getUserPermissions(int $userId): array
    {
        return Cache::remember(
            "user_permissions:{$userId}",
            $this->ttlConfig['user_permissions'],
            function () use ($userId) {
                return \App\Models\User::with('role.permissions')
                    ->find($userId)
                    ?->role
                    ?->permissions
                    ?->pluck('name')
                    ?->toArray() ?? [];
            }
        );
    }

    /**
     * Cache de metadatos de documentos
     */
    public function getDocumentMetadata(int $documentId): ?array
    {
        return Cache::remember(
            "document_metadata:{$documentId}",
            $this->ttlConfig['document_metadata'],
            function () use ($documentId) {
                $document = \App\Models\Documento::with(['expediente'])->find($documentId);

                if (!$document) {
                    return null;
                }

                return [
                    'id' => $document->id,
                    'codigo' => $document->codigo,
                    'nombre' => $document->nombre,
                    'descripcion' => $document->descripcion,
                    'fecha_creacion' => $document->fecha_creacion,
                    'tamaño' => $document->tamaño,
                    'formato' => $document->formato,
                    'hash_integridad' => $document->hash_integridad,
                    'expediente' => [
                        'id' => $document->expediente->id ?? null,
                        'numero_expediente' => $document->expediente->numero_expediente ?? null,
                    ],
                ];
            }
        );
    }

    /**
     * Cache de datos TRD
     */
    public function getTRDData(): array
    {
        return Cache::remember(
            'trd_active_data',
            $this->ttlConfig['trd_data'],
            function () {
                return \App\Models\TablaRetencionDocumental::where('estado', 'aprobada')
                    ->with(['series', 'subseries'])
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Cache de estados de workflows
     */
    public function getWorkflowStates(int $userId): array
    {
        return Cache::remember(
            "workflow_states:{$userId}",
            $this->ttlConfig['workflow_states'],
            function () use ($userId) {
                return [
                    'pending_approval' => \App\Models\WorkflowDocumento::where('estado', 'pendiente')
                        ->whereHas('aprobacionesWorkflow', function ($query) use ($userId) {
                            $query->where('usuario_id', $userId)
                                  ->where('estado', 'pendiente');
                        })
                        ->count(),
                    'my_requests' => \App\Models\WorkflowDocumento::where('solicitante_id', $userId)
                        ->whereIn('estado', ['pendiente', 'en_revision'])
                        ->count(),
                ];
            }
        );
    }

    /**
     * Cache de estadísticas del sistema
     */
    public function getSystemStatistics(): array
    {
        return Cache::remember(
            'system_statistics',
            $this->ttlConfig['statistics'],
            function () {
                return [
                    'total_documents' => \App\Models\Documento::count(),
                    'total_expedientes' => \App\Models\Expediente::count(),
                    'total_users' => \App\Models\User::where('active', true)->count(),
                    'storage_used' => \App\Models\Documento::sum('tamaño') ?? 0,
                    'documents_this_month' => \App\Models\Documento::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                    'expedientes_by_state' => \App\Models\Expediente::groupBy('estado_ciclo_vida')
                        ->selectRaw('estado_ciclo_vida, count(*) as count')
                        ->pluck('count', 'estado_ciclo_vida')
                        ->toArray(),
                ];
            }
        );
    }

    /**
     * Cache de notificaciones críticas
     */
    public function getCriticalNotifications(int $userId): array
    {
        return Cache::remember(
            "critical_notifications:{$userId}",
            $this->ttlConfig['notifications'],
            function () use ($userId) {
                return \App\Models\Notificacion::where('user_id', $userId)
                    ->where('estado', 'pendiente')
                    ->where('prioridad', 'critica')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Cache de configuración de servicios externos
     */
    public function getExternalServicesConfig(): array
    {
        return Cache::remember(
            'external_services_config',
            $this->ttlConfig['external_services'],
            function () {
                return [
                    'email_enabled' => !empty(config('mail.mailers.smtp.host')),
                    'sms_enabled' => !empty(config('services.twilio.sid')),
                    'backup_enabled' => config('optimization.backup.enabled', false),
                    'queue_driver' => config('queue.default'),
                    'cache_driver' => config('cache.default'),
                ];
            }
        );
    }

    /**
     * Invalidar cache específico
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Error forgetting cache key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidar cache por patrón
     */
    public function forgetByPattern(string $pattern): int
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    return Redis::del($keys);
                }
                return 0;
            } else {
                // Para otros drivers, no se puede hacer pattern matching
                Log::warning("Pattern cache invalidation only supported with Redis driver");
                return 0;
            }
        } catch (\Exception $e) {
            Log::error("Error forgetting cache pattern {$pattern}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Invalidar cache de usuario
     */
    public function forgetUserCache(int $userId): void
    {
        $patterns = [
            "user_permissions:{$userId}",
            "workflow_states:{$userId}",
            "critical_notifications:{$userId}",
        ];

        foreach ($patterns as $pattern) {
            $this->forget($pattern);
        }
    }

    /**
     * Invalidar cache de documento
     */
    public function forgetDocumentCache(int $documentId): void
    {
        $this->forget("document_metadata:{$documentId}");
        $this->forget('system_statistics'); // Las estadísticas cambian cuando se modifica un documento
    }

    /**
     * Invalidar cache de estadísticas
     */
    public function forgetStatisticsCache(): void
    {
        $this->forget('system_statistics');
    }

    /**
     * Invalidar cache de TRD
     */
    public function forgetTRDCache(): void
    {
        $this->forget('trd_active_data');
    }

    /**
     * Obtener información del estado del cache
     */
    public function getCacheInfo(): array
    {
        try {
            $info = [
                'driver' => config('cache.default'),
                'status' => 'healthy',
            ];

            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $redisInfo = $redis->info();
                
                $info['redis'] = [
                    'version' => $redisInfo['redis_version'] ?? 'unknown',
                    'connected_clients' => $redisInfo['connected_clients'] ?? 0,
                    'used_memory' => $redisInfo['used_memory_human'] ?? 'unknown',
                    'keyspace_hits' => $redisInfo['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $redisInfo['keyspace_misses'] ?? 0,
                ];

                $total = $info['redis']['keyspace_hits'] + $info['redis']['keyspace_misses'];
                $info['redis']['hit_ratio'] = $total > 0 ? 
                    round(($info['redis']['keyspace_hits'] / $total) * 100, 2) : 0;
            }

            return $info;
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Limpiar todo el cache
     */
    public function flush(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error("Error flushing cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Precalentar cachés críticos
     */
    public function warmupCriticalCaches(): array
    {
        $results = [];
        
        try {
            // Precalentar estadísticas del sistema
            $results['system_statistics'] = $this->getSystemStatistics();
            
            // Precalentar datos TRD
            $results['trd_data'] = $this->getTRDData();
            
            // Precalentar configuración de servicios externos
            $results['external_services'] = $this->getExternalServicesConfig();
            
            Log::info('Critical caches warmed up successfully');
            
        } catch (\Exception $e) {
            Log::error('Error warming up critical caches: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
}
