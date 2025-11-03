<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\DocumentoApiController;
use App\Http\Controllers\Api\ExpedienteApiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\DocumentoApiController as V1DocumentoApiController;
use App\Http\Controllers\Api\V1\ExpedienteApiController as V1ExpedienteApiController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check routes (públicas)
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/ping', [HealthController::class, 'ping']);

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes with Sanctum (para aplicación web)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Métricas detalladas (requiere autenticación)
    Route::get('/health/metrics', [HealthController::class, 'metrics']);
    
    // Dashboard Ejecutivo
    Route::prefix('dashboard')->group(function () {
        Route::get('/executive', [DashboardController::class, 'executive']);
        Route::post('/export', [DashboardController::class, 'export']);
    });
});

// API Externa protegida con tokens API
Route::middleware(['api.token'])->prefix('v1')->name('api.')->group(function () {
    
    // Documentos API
    Route::prefix('documentos')->name('documentos.')->group(function () {
        Route::get('/', [DocumentoApiController::class, 'index'])->name('index');
        Route::get('/estadisticas', [DocumentoApiController::class, 'estadisticas'])->name('estadisticas');
        Route::get('/{id}', [DocumentoApiController::class, 'show'])->name('show');
        Route::post('/', [DocumentoApiController::class, 'store'])->name('store');
        Route::put('/{id}', [DocumentoApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentoApiController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/download', [DocumentoApiController::class, 'download'])->name('download');
    });

    // Expedientes API
    Route::prefix('expedientes')->name('expedientes.')->group(function () {
        Route::get('/', [ExpedienteApiController::class, 'index'])->name('index');
        Route::get('/estadisticas', [ExpedienteApiController::class, 'estadisticas'])->name('estadisticas');
        Route::get('/{id}', [ExpedienteApiController::class, 'show'])->name('show');
        Route::post('/', [ExpedienteApiController::class, 'store'])->name('store');
        Route::put('/{id}', [ExpedienteApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpedienteApiController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/cerrar', [ExpedienteApiController::class, 'cerrar'])->name('cerrar');
        Route::post('/{id}/reabrir', [ExpedienteApiController::class, 'reabrir'])->name('reabrir');
        Route::get('/{id}/documentos', [ExpedienteApiController::class, 'documentos'])->name('documentos');
    });

    // Gestión de Tokens API (solo para administradores)
    Route::prefix('tokens')->name('tokens.')->group(function () {
        Route::get('/', [ApiTokenController::class, 'index'])->name('index');
        Route::get('/{id}', [ApiTokenController::class, 'show'])->name('show');
        Route::post('/', [ApiTokenController::class, 'store'])->name('store');
        Route::put('/{id}', [ApiTokenController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApiTokenController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/renovar', [ApiTokenController::class, 'renovar'])->name('renovar');
        Route::post('/{id}/revocar', [ApiTokenController::class, 'revocar'])->name('revocar');
        Route::get('/{id}/estadisticas', [ApiTokenController::class, 'estadisticas'])->name('estadisticas');
    });

    // Información de la API (versión legacy)
    Route::get('/info', [ApiController::class, 'info'])->name('info');
});
