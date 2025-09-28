<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\DocumentoApiController;
use App\Http\Controllers\Api\V1\ExpedienteApiController;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
|
| Rutas de la API v1 de ArchiveyCloud para integraciones externas.
| Todas las rutas están protegidas por el middleware 'api.token'.
|
*/

Route::prefix('v1')->middleware(['api.token'])->group(function () {
    
    // Información general del sistema
    Route::get('/info', [ApiController::class, 'info'])
        ->name('api.v1.info');
    
    // Gestión de Documentos
    Route::prefix('documentos')->group(function () {
        Route::get('/', [DocumentoApiController::class, 'index'])
            ->middleware('api.permission:documentos:read')
            ->name('api.v1.documentos.index');
            
        Route::post('/', [DocumentoApiController::class, 'store'])
            ->middleware('api.permission:documentos:write')
            ->name('api.v1.documentos.store');
            
        Route::get('/{id}', [DocumentoApiController::class, 'show'])
            ->middleware('api.permission:documentos:read')
            ->name('api.v1.documentos.show');
            
        Route::put('/{id}', [DocumentoApiController::class, 'update'])
            ->middleware('api.permission:documentos:write')
            ->name('api.v1.documentos.update');
            
        Route::delete('/{id}', [DocumentoApiController::class, 'destroy'])
            ->middleware('api.permission:documentos:delete')
            ->name('api.v1.documentos.destroy');
            
        Route::get('/{id}/download', [DocumentoApiController::class, 'download'])
            ->middleware('api.permission:documentos:read')
            ->name('api.v1.documentos.download');
    });
    
    // Gestión de Expedientes
    Route::prefix('expedientes')->group(function () {
        Route::get('/', [ExpedienteApiController::class, 'index'])
            ->middleware('api.permission:expedientes:read')
            ->name('api.v1.expedientes.index');
            
        Route::post('/', [ExpedienteApiController::class, 'store'])
            ->middleware('api.permission:expedientes:write')
            ->name('api.v1.expedientes.store');
            
        Route::get('/{id}', [ExpedienteApiController::class, 'show'])
            ->middleware('api.permission:expedientes:read')
            ->name('api.v1.expedientes.show');
            
        Route::put('/{id}', [ExpedienteApiController::class, 'update'])
            ->middleware('api.permission:expedientes:write')
            ->name('api.v1.expedientes.update');
            
        Route::delete('/{id}', [ExpedienteApiController::class, 'destroy'])
            ->middleware('api.permission:expedientes:delete')
            ->name('api.v1.expedientes.destroy');
            
        Route::get('/{id}/documentos', [ExpedienteApiController::class, 'documentos'])
            ->middleware('api.permission:expedientes:read')
            ->name('api.v1.expedientes.documentos');
    });
    
});
