<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchOptimizationService;
use Illuminate\Console\Command;

/**
 * Comando para optimizar Elasticsearch
 */
class OptimizeElasticsearch extends Command
{
    protected $signature = 'elasticsearch:optimize 
                           {--index=documentos_sgdea : Nombre del índice a optimizar}
                           {--full : Ejecutar optimización completa}
                           {--create-index : Solo crear índice optimizado}
                           {--create-template : Solo crear template}
                           {--setup-aliases : Solo configurar aliases}
                           {--stats : Mostrar estadísticas del índice}';

    protected $description = 'Optimizar índices de Elasticsearch para el SGDEA';

    protected ElasticsearchOptimizationService $optimizationService;

    public function __construct(ElasticsearchOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    public function handle(): int
    {
        $this->info('🔍 Iniciando optimización de Elasticsearch para SGDEA...');

        try {
            if ($this->option('stats')) {
                return $this->showStats();
            }

            if ($this->option('full')) {
                return $this->runFullOptimization();
            }

            if ($this->option('create-index')) {
                return $this->createIndex();
            }

            if ($this->option('create-template')) {
                return $this->createTemplate();
            }

            if ($this->option('setup-aliases')) {
                return $this->setupAliases();
            }

            // Por defecto, mostrar opciones
            $this->showOptions();
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }
    }

    private function showOptions(): void
    {
        $this->info('Opciones disponibles:');
        $this->line('');
        $this->line('  --full              Ejecutar optimización completa');
        $this->line('  --create-index      Crear índice optimizado');
        $this->line('  --create-template   Crear template de índice');
        $this->line('  --setup-aliases     Configurar aliases');
        $this->line('  --stats             Mostrar estadísticas');
        $this->line('');
        $this->info('Ejemplo: php artisan elasticsearch:optimize --full');
    }

    private function runFullOptimization(): int
    {
        $this->info('🚀 Ejecutando optimización completa...');
        
        $results = $this->optimizationService->runFullOptimization();
        
        $this->line('');
        $this->info('📊 Resultados:');
        
        if ($results['index_created']) {
            $this->info('✅ Índice optimizado creado');
        } else {
            $this->warn('⚠️  No se pudo crear el índice optimizado');
        }
        
        if ($results['template_created']) {
            $this->info('✅ Template de índice creado');
        } else {
            $this->warn('⚠️  No se pudo crear el template');
        }
        
        if ($results['aliases_configured']) {
            $this->info('✅ Aliases configurados');
        } else {
            $this->warn('⚠️  No se pudieron configurar los aliases');
        }
        
        if ($results['optimization_completed']) {
            $this->info('✅ Optimización del índice completada');
        } else {
            $this->warn('⚠️  No se pudo completar la optimización');
        }

        if (!empty($results['errors'])) {
            $this->line('');
            $this->error('❌ Errores encontrados:');
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
            return 1;
        }

        $this->line('');
        $this->info('🎉 Optimización completa finalizada exitosamente');
        return 0;
    }

    private function createIndex(): int
    {
        $this->info('📝 Creando índice optimizado...');
        
        if ($this->optimizationService->createOptimizedDocumentIndex()) {
            $this->info('✅ Índice optimizado creado exitosamente');
            return 0;
        } else {
            $this->error('❌ Error creando índice optimizado');
            return 1;
        }
    }

    private function createTemplate(): int
    {
        $this->info('📋 Creando template de índice...');
        
        if ($this->optimizationService->createDocumentTemplate()) {
            $this->info('✅ Template creado exitosamente');
            return 0;
        } else {
            $this->error('❌ Error creando template');
            return 1;
        }
    }

    private function setupAliases(): int
    {
        $this->info('🔗 Configurando aliases...');
        
        if ($this->optimizationService->setupIndexAliases()) {
            $this->info('✅ Aliases configurados exitosamente');
            return 0;
        } else {
            $this->error('❌ Error configurando aliases');
            return 1;
        }
    }

    private function showStats(): int
    {
        $indexName = $this->option('index');
        $this->info("📈 Estadísticas del índice: {$indexName}");
        
        $stats = $this->optimizationService->getIndexStats($indexName);
        
        if (empty($stats)) {
            $this->warn('⚠️  No se pudieron obtener las estadísticas');
            return 1;
        }

        $this->line('');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Estado de salud', $this->formatHealth($stats['health'] ?? 'unknown')],
                ['Documentos totales', number_format($stats['documents'] ?? 0)],
                ['Tamaño del índice', $this->formatBytes($stats['size'] ?? 0)],
                ['Shards activos', $stats['shards'] ?? 0],
                ['Segmentos', $stats['segments'] ?? 0],
                ['Consultas de búsqueda', number_format($stats['search_queries'] ?? 0)],
                ['Operaciones de indexación', number_format($stats['indexing_operations'] ?? 0)],
            ]
        );

        return 0;
    }

    private function formatHealth(string $health): string
    {
        return match($health) {
            'green' => '🟢 Verde (Saludable)',
            'yellow' => '🟡 Amarillo (Advertencia)',
            'red' => '🔴 Rojo (Crítico)',
            default => '⚫ Desconocido'
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = floor(log($bytes, 1024));
        
        return round($bytes / (1024 ** $unitIndex), 2) . ' ' . $units[$unitIndex];
    }
}
