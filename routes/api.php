<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check routes (públicas)
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/ping', [HealthController::class, 'ping']);

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Métricas detalladas (requiere autenticación)
    Route::get('/health/metrics', [HealthController::class, 'metrics']);
});
