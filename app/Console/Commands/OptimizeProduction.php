<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class OptimizeProduction extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'optimize:production 
                          {--force : Forzar optimización sin confirmación}
                          {--dry-run : Mostrar qué se ejecutaría sin hacer cambios}
                          {--skip-cache : Saltar optimización de caché}
                          {--skip-config : Saltar optimización de configuración}
                          {--skip-routes : Saltar optimización de rutas}
                          {--skip-views : Saltar optimización de vistas}
                          {--skip-database : Saltar optimización de base de datos}';

    /**
     * The console command description.
     */
    protected $description = 'Optimizar aplicación para entorno de producción con todas las mejores prácticas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Si no hay output (ejecutado desde HTTP), no mostrar info
        if (!$this->output->isDecorated() && php_sapi_name() !== 'cli') {
            return $this->handleNonInteractive();
        }

        $this->info('🚀 Iniciando optimización para producción de ArchiveyCloud...');
        $this->newLine();

        // Verificar modo dry-run
        if ($this->option('dry-run')) {
            $this->warn('🔍 Modo DRY-RUN activado - No se realizarán cambios reales');
            $this->newLine();
        }

        // Verificar entorno - Solo si no está en modo force
        if (app()->environment('local', 'development') && !$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Estás en entorno de desarrollo. ¿Continuar con la optimización?')) {
                $this->error('❌ Optimización cancelada.');
                return Command::FAILURE;
            }
        }

        $steps = $this->getOptimizationSteps();
        $completedSteps = 0;
        $totalSteps = count($steps);

        foreach ($steps as $step => $config) {
            if (isset($config['skip_option']) && $this->option($config['skip_option'])) {
                $this->warn("⏭️  Saltando: {$config['description']}");
                continue;
            }

            $this->info("📋 {$config['description']}...");
            
            try {
                if ($this->option('dry-run')) {
                    $this->line("   🔍 DRY-RUN: {$config['success_message']}");
                    $completedSteps++;
                } else {
                    $result = $this->{$config['method']}();
                    
                    if ($result) {
                        $this->line("   ✅ {$config['success_message']}");
                        $completedSteps++;
                    } else {
                        $this->error("   ❌ {$config['error_message']}");
                    }
                }
            } catch (\Exception $e) {
                if (!$this->option('dry-run')) {
                    $this->error("   ❌ Error: {$e->getMessage()}");
                    Log::error("OptimizeProduction error in {$step}: " . $e->getMessage(), [
                        'step' => $step,
                        'trace' => $e->getTraceAsString()
                    ]);
                } else {
                    $this->warn("   ⚠️  DRY-RUN: Would fail with: {$e->getMessage()}");
                }
            }
            
            $this->newLine();
        }

        // Resumen final
        $this->info('📊 Resumen de optimización:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Pasos completados', "{$completedSteps}/{$totalSteps}"],
                ['Porcentaje de éxito', round(($completedSteps / $totalSteps) * 100, 2) . '%'],
                ['Entorno', app()->environment()],
                ['Timestamp', now()->format('Y-m-d H:i:s')],
            ]
        );

        if ($completedSteps === $totalSteps) {
            $this->info('🎉 ¡Optimización completada exitosamente!');
            $this->warn('⚠️  Recuerda reiniciar los servicios web y workers de colas.');
            return Command::SUCCESS;
        } else {
            $this->error('⚠️  Optimización completada con errores. Revisa los logs para más detalles.');
            return Command::FAILURE;
        }
    }

    private function getOptimizationSteps(): array
    {
        return [
            'clear_caches' => [
                'description' => 'Limpiando cachés existentes',
                'method' => 'clearCaches',
                'success_message' => 'Cachés limpiados correctamente',
                'error_message' => 'Error al limpiar cachés',
                'skip_option' => 'skip-cache',
            ],
            'optimize_config' => [
                'description' => 'Optimizando configuración',
                'method' => 'optimizeConfig',
                'success_message' => 'Configuración optimizada y cacheada',
                'error_message' => 'Error al optimizar configuración',
                'skip_option' => 'skip-config',
            ],
            'optimize_routes' => [
                'description' => 'Optimizando rutas',
                'method' => 'optimizeRoutes',
                'success_message' => 'Rutas optimizadas y cacheadas',
                'error_message' => 'Error al optimizar rutas',
                'skip_option' => 'skip-routes',
            ],
            'optimize_views' => [
                'description' => 'Compilando vistas',
                'method' => 'optimizeViews',
                'success_message' => 'Vistas compiladas correctamente',
                'error_message' => 'Error al compilar vistas',
                'skip_option' => 'skip-views',
            ],
            'optimize_autoloader' => [
                'description' => 'Optimizando autoloader de Composer',
                'method' => 'optimizeAutoloader',
                'success_message' => 'Autoloader optimizado',
                'error_message' => 'Error al optimizar autoloader',
            ],
            'optimize_database' => [
                'description' => 'Optimizando base de datos',
                'method' => 'optimizeDatabase',
                'success_message' => 'Base de datos optimizada',
                'error_message' => 'Error al optimizar base de datos',
                'skip_option' => 'skip-database',
            ],
            'warm_caches' => [
                'description' => 'Precalentando cachés críticos',
                'method' => 'warmCaches',
                'success_message' => 'Cachés precalentados',
                'error_message' => 'Error al precalentar cachés',
                'skip_option' => 'skip-cache',
            ],
            'generate_manifest' => [
                'description' => 'Generando manifiesto de optimización',
                'method' => 'generateOptimizationManifest',
                'success_message' => 'Manifiesto generado',
                'error_message' => 'Error al generar manifiesto',
            ],
        ];
    }

    private function clearCaches(): bool
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Limpiar caché de aplicación específico
            Cache::flush();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing caches: ' . $e->getMessage());
            return false;
        }
    }

    private function optimizeConfig(): bool
    {
        try {
            Artisan::call('config:cache');
            return File::exists(base_path('bootstrap/cache/config.php'));
        } catch (\Exception $e) {
            Log::error('Error optimizing config: ' . $e->getMessage());
            return false;
        }
    }

    private function optimizeRoutes(): bool
    {
        try {
            Artisan::call('route:cache');
            return File::exists(base_path('bootstrap/cache/routes-v7.php'));
        } catch (\Exception $e) {
            Log::error('Error optimizing routes: ' . $e->getMessage());
            return false;
        }
    }

    private function optimizeViews(): bool
    {
        try {
            Artisan::call('view:cache');
            return File::exists(base_path('storage/framework/views'));
        } catch (\Exception $e) {
            Log::error('Error optimizing views: ' . $e->getMessage());
            return false;
        }
    }

    private function optimizeAutoloader(): bool
    {
        try {
            $process = new \Symfony\Component\Process\Process(
                ['composer', 'install', '--optimize-autoloader', '--no-dev'],
                base_path()
            );
            
            if (app()->environment('production')) {
                $process->run();
                return $process->isSuccessful();
            }
            
            // En desarrollo, solo optimizar sin --no-dev
            $process = new \Symfony\Component\Process\Process(
                ['composer', 'dump-autoload', '--optimize'],
                base_path()
            );
            $process->run();
            return $process->isSuccessful();
            
        } catch (\Exception $e) {
            Log::error('Error optimizing autoloader: ' . $e->getMessage());
            return false;
        }
    }

    private function optimizeDatabase(): bool
    {
        try {
            // Analizar tablas para optimizar índices
            $this->analyzeTableIndexes();
            
            // Optimizar tablas MySQL si es posible
            if (DB::getDriverName() === 'mysql') {
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = array_values((array) $table)[0];
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error optimizing database: ' . $e->getMessage());
            return false;
        }
    }

    private function analyzeTableIndexes(): void
    {
        $recommendedIndexes = config('optimization.database.recommended_indexes', []);
        
        foreach ($recommendedIndexes as $table => $columns) {
            try {
                // Verificar si los índices existen
                $indexes = DB::select("SHOW INDEX FROM `{$table}`");
                $existingIndexes = collect($indexes)->pluck('Column_name')->toArray();
                
                foreach ($columns as $column) {
                    if (!in_array($column, $existingIndexes)) {
                        $this->warn("   ⚠️  Índice recomendado faltante en {$table}.{$column}");
                    }
                }
            } catch (\Exception $e) {
                // Tabla no existe o error, continuar
                continue;
            }
        }
    }

    private function warmCaches(): bool
    {
        try {
            // Caché de configuración crítica
            $this->warmConfigCache();
            
            // Caché de datos frecuentemente accedidos
            $this->warmDataCache();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error warming caches: ' . $e->getMessage());
            return false;
        }
    }

    private function warmConfigCache(): void
    {
        // Caché configuraciones críticas
        $criticalConfigs = [
            'app',
            'database',
            'cache',
            'session',
            'queue',
            'optimization',
        ];

        foreach ($criticalConfigs as $config) {
            try {
                Cache::remember("config:{$config}", 3600, function() use ($config) {
                    return config($config);
                });
            } catch (\Exception $e) {
                Log::warning("Could not warm config cache for {$config}: " . $e->getMessage());
            }
        }
    }

    private function warmDataCache(): void
    {
        try {
            // Caché de usuarios activos
            Cache::remember('users:active_count', 1800, function() {
                return DB::table('users')->where('estado_cuenta', 'activo')->count();
            });

            // Caché de estadísticas básicas
            Cache::remember('stats:basic', 1800, function() {
                return [
                    'total_expedientes' => DB::table('expedientes')->count(),
                    'total_documentos' => DB::table('documentos')->count(),
                    'total_usuarios' => DB::table('users')->count(),
                ];
            });

        } catch (\Exception $e) {
            Log::warning('Could not warm data cache: ' . $e->getMessage());
        }
    }

    private function generateOptimizationManifest(): bool
    {
        try {
            $manifest = [
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'optimizations' => [
                    'config_cached' => File::exists(base_path('bootstrap/cache/config.php')),
                    'routes_cached' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
                    'views_cached' => File::exists(base_path('storage/framework/views')),
                    'autoloader_optimized' => true,
                ],
                'performance_settings' => [
                    'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                ],
                'cache_status' => [
                    'driver' => config('cache.default'),
                    'redis_available' => extension_loaded('redis'),
                ],
            ];

            File::put(
                base_path('storage/app/optimization-manifest.json'),
                json_encode($manifest, JSON_PRETTY_PRINT)
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error generating optimization manifest: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Manejo no interactivo (cuando se ejecuta desde HTTP)
     */
    protected function handleNonInteractive()
    {
        try {
            // Ejecutar optimizaciones básicas sin interacción
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            Cache::flush();
            
            Log::info('Optimización ejecutada desde contexto HTTP');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error en optimización no interactiva: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
