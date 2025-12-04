<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheManagement extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:manage 
                          {action : Acción a realizar (warmup, flush, info, stats)}
                          {--user-id= : ID de usuario para operaciones específicas}
                          {--document-id= : ID de documento para operaciones específicas}
                          {--pattern= : Patrón para operaciones de limpieza}';

    /**
     * The console command description.
     */
    protected $description = 'Gestionar sistema de caché de ArchiveyCloud';

    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'warmup' => $this->warmupCache(),
            'flush' => $this->flushCache(),
            'info' => $this->showCacheInfo(),
            'stats' => $this->showCacheStats(),
            'forget-user' => $this->forgetUserCache(),
            'forget-document' => $this->forgetDocumentCache(),
            'forget-pattern' => $this->forgetByPattern(),
            default => $this->error("Acción '{$action}' no válida. Use: warmup, flush, info, stats, forget-user, forget-document, forget-pattern")
        };
    }

    private function warmupCache(): int
    {
        $this->info('🔥 Precalentando cachés críticos...');
        $this->newLine();

        $startTime = microtime(true);
        $results = $this->cacheService->warmupCriticalCaches();
        $endTime = microtime(true);

        if (isset($results['error'])) {
            $this->error('❌ Error durante el precalentamiento: ' . $results['error']);
            return Command::FAILURE;
        }

        $this->info('✅ Cachés precalentados exitosamente');
        $this->line('   Tiempo: ' . round(($endTime - $startTime) * 1000, 2) . 'ms');
        $this->newLine();

        // Mostrar resumen
        $this->table(
            ['Caché', 'Estado'],
            [
                ['Estadísticas del sistema', isset($results['system_statistics']) ? '✅ Cargado' : '❌ Error'],
                ['Datos TRD', isset($results['trd_data']) ? '✅ Cargado' : '❌ Error'],
                ['Servicios externos', isset($results['external_services']) ? '✅ Cargado' : '❌ Error'],
            ]
        );

        return Command::SUCCESS;
    }

    private function flushCache(): int
    {
        if (!$this->confirm('¿Estás seguro de que deseas limpiar TODO el caché?')) {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->info('🧹 Limpiando todo el caché...');

        $startTime = microtime(true);
        $success = $this->cacheService->flush();
        $endTime = microtime(true);

        if ($success) {
            $this->info('✅ Caché limpiado exitosamente');
            $this->line('   Tiempo: ' . round(($endTime - $startTime) * 1000, 2) . 'ms');
        } else {
            $this->error('❌ Error al limpiar el caché');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showCacheInfo(): int
    {
        $this->info('📊 Información del sistema de caché');
        $this->newLine();

        $info = $this->cacheService->getCacheInfo();

        if ($info['status'] === 'error') {
            $this->error('❌ Error obteniendo información del caché: ' . $info['error']);
            return Command::FAILURE;
        }

        $data = [
            ['Driver', $info['driver']],
            ['Estado', $info['status']],
        ];

        if (isset($info['redis'])) {
            $data[] = ['--- Redis Info ---', ''];
            $data[] = ['Versión Redis', $info['redis']['version']];
            $data[] = ['Clientes conectados', $info['redis']['connected_clients']];
            $data[] = ['Memoria usada', $info['redis']['used_memory']];
            $data[] = ['Cache hits', number_format($info['redis']['keyspace_hits'])];
            $data[] = ['Cache misses', number_format($info['redis']['keyspace_misses'])];
            $data[] = ['Hit ratio', $info['redis']['hit_ratio'] . '%'];
        }

        $this->table(['Métrica', 'Valor'], $data);

        return Command::SUCCESS;
    }

    private function showCacheStats(): int
    {
        $this->info('📈 Estadísticas de uso del caché');
        $this->newLine();

        // Verificar si existen los cachés críticos
        $cacheKeys = [
            'system_statistics' => 'Estadísticas del sistema',
            'trd_active_data' => 'Datos TRD activos',
            'external_services_config' => 'Configuración servicios externos',
        ];

        $stats = [];
        foreach ($cacheKeys as $key => $description) {
            $exists = Cache::has($key);
            $stats[] = [
                $description,
                $exists ? '✅ En caché' : '❌ No cacheado',
                $exists ? 'N/A' : 'Considerar warmup'
            ];
        }

        $this->table(['Caché', 'Estado', 'Recomendación'], $stats);

        // Mostrar configuración TTL
        $this->newLine();
        $this->info('⏰ Configuración de TTL (segundos)');
        
        $ttlConfig = config('optimization.cache.ttl', []);
        $ttlData = [];
        foreach ($ttlConfig as $type => $seconds) {
            $ttlData[] = [
                str_replace('_', ' ', ucfirst($type)),
                $seconds,
                $this->formatDuration($seconds)
            ];
        }

        $this->table(['Tipo de datos', 'TTL (segundos)', 'Duración'], $ttlData);

        return Command::SUCCESS;
    }

    private function forgetUserCache(): int
    {
        $userId = $this->option('user-id');
        
        if (!$userId) {
            $this->error('❌ Debe proporcionar --user-id para esta operación');
            return Command::FAILURE;
        }

        $this->info("🗑️  Limpiando caché del usuario {$userId}...");
        
        $this->cacheService->forgetUserCache((int) $userId);
        
        $this->info('✅ Caché del usuario limpiado exitosamente');
        return Command::SUCCESS;
    }

    private function forgetDocumentCache(): int
    {
        $documentId = $this->option('document-id');
        
        if (!$documentId) {
            $this->error('❌ Debe proporcionar --document-id para esta operación');
            return Command::FAILURE;
        }

        $this->info("🗑️  Limpiando caché del documento {$documentId}...");
        
        $this->cacheService->forgetDocumentCache((int) $documentId);
        
        $this->info('✅ Caché del documento limpiado exitosamente');
        return Command::SUCCESS;
    }

    private function forgetByPattern(): int
    {
        $pattern = $this->option('pattern');
        
        if (!$pattern) {
            $this->error('❌ Debe proporcionar --pattern para esta operación');
            return Command::FAILURE;
        }

        $this->info("🗑️  Limpiando caché con patrón '{$pattern}'...");
        
        $deleted = $this->cacheService->forgetByPattern($pattern);
        
        if ($deleted > 0) {
            $this->info("✅ {$deleted} claves eliminadas exitosamente");
        } else {
            $this->warn('⚠️  No se encontraron claves que coincidan con el patrón');
        }

        return Command::SUCCESS;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
    }
}
