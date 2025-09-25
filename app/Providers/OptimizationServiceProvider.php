<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class OptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Configuraciones de optimización
        $this->app->singleton('optimization.config', function () {
            return config('optimization');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureDatabase();
        $this->configureCaching();
        $this->configureViews();
        $this->configureResponses();
        $this->configureQueryLogging();
    }

    /**
     * Configurar optimizaciones de base de datos
     */
    private function configureDatabase(): void
    {
        // Configurar límites de memoria para consultas pesadas
        DB::listen(function ($query) {
            if ($query->time > 2000) { // Más de 2 segundos
                \Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });

        // Configurar timeouts para operaciones específicas
        if (app()->environment('production')) {
            DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, 60);
        }
    }

    /**
     * Configurar estrategias de caché
     */
    private function configureCaching(): void
    {
        // Cache de configuraciones críticas
        Cache::macro('rememberForever', function ($key, $callback) {
            return Cache::rememberForever($key, $callback);
        });

        // Cache de datos de aplicación con TTL específico
        Cache::macro('appCache', function ($key, $callback, $ttl = null) {
            $ttl = $ttl ?? config('optimization.cache.default_ttl', 3600);
            return Cache::remember($key, $ttl, $callback);
        });

        // Warm critical caches on boot
        if (app()->environment('production')) {
            $this->warmCriticalCaches();
        }
    }

    /**
     * Configurar optimizaciones de vistas
     */
    private function configureViews(): void
    {
        // Compartir datos críticos con todas las vistas
        View::composer('*', function ($view) {
            // Solo en production para evitar overhead en desarrollo
            if (app()->environment('production')) {
                $view->with('app_version', config('app.version', '1.0.0'));
                $view->with('cache_version', md5(filemtime(config_path('app.php'))));
            }
        });

        // Precompilar vistas críticas en production
        if (app()->environment('production')) {
            $this->precompileCriticalViews();
        }
    }

    /**
     * Configurar optimizaciones de respuestas
     */
    private function configureResponses(): void
    {
        // Macro para respuestas cacheadas
        Response::macro('cached', function ($content, $minutes = 60) {
            return response($content)
                ->header('Cache-Control', "public, max-age=" . ($minutes * 60))
                ->header('Expires', now()->addMinutes($minutes)->format('D, d M Y H:i:s T'));
        });

        // Macro para respuestas con compresión
        Response::macro('compressed', function ($content) {
            if (function_exists('gzencode')) {
                return response(gzencode($content))
                    ->header('Content-Encoding', 'gzip')
                    ->header('Content-Type', 'application/json');
            }
            return response($content);
        });
    }

    /**
     * Configurar logging de consultas en desarrollo
     */
    private function configureQueryLogging(): void
    {
        if (app()->environment('local') && config('optimization.monitoring.log_slow_queries', true)) {
            DB::listen(function ($query) {
                $threshold = config('optimization.monitoring.slow_query_threshold', 2000);
                
                if ($query->time > $threshold) {
                    \Log::channel('performance')->warning('Slow Query', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'url' => request()->fullUrl(),
                        'user_id' => auth()->id(),
                    ]);
                }
            });
        }
    }

    /**
     * Precalentar cachés críticos
     */
    private function warmCriticalCaches(): void
    {
        // Cache de configuraciones críticas
        Cache::rememberForever('app.critical_config', function () {
            return [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
                'optimization_enabled' => true,
            ];
        });

        // Cache de rutas críticas (si no están ya cacheadas)
        if (!file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            \Artisan::call('route:cache');
        }
    }

    /**
     * Precompilar vistas críticas
     */
    private function precompileCriticalViews(): void
    {
        $criticalViews = [
            'app',
            'layouts.app',
            'auth.login',
            'dashboard',
        ];

        foreach ($criticalViews as $view) {
            try {
                if (view()->exists($view)) {
                    view($view)->render();
                }
            } catch (\Exception $e) {
                // Ignorar errores de precompilación
                \Log::debug("Could not precompile view {$view}: " . $e->getMessage());
            }
        }
    }
}
