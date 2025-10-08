<?php

use App\Http\Middleware\ApiPermissionMiddleware;
use App\Http\Middleware\ApiTokenAuthentication;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PerformanceOptimization;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackUserActivity;
use App\Http\Middleware\TwoFactorAuthentication;
use App\Http\Middleware\VerifyUserActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            PerformanceOptimization::class, // Optimización de performance
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TrackUserActivity::class, // Trackear actividad del usuario
            VerifyUserActive::class, // Verificar usuarios activos en todas las rutas web
        ]);

        // Registrar middleware de roles y API
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'user.active' => VerifyUserActive::class,
            'track.activity' => TrackUserActivity::class,
            'performance' => PerformanceOptimization::class,
            'api.token' => ApiTokenAuthentication::class,
            'api.permission' => ApiPermissionMiddleware::class,
            'two-factor' => TwoFactorAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
