<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Soporte Técnico (Modal)
    Route::post('support', [App\Http\Controllers\SupportController::class, 'store'])->name('support.store');

    // Administración
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', App\Http\Controllers\Admin\AdminUserController::class);
        Route::patch('users/{user}/toggle-status', [App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // Gestión de Tablas de Retención Documental (TRD)
        Route::resource('trd', App\Http\Controllers\Admin\AdminTRDController::class);
        Route::post('trd/{trd}/duplicate', [App\Http\Controllers\Admin\AdminTRDController::class, 'duplicate'])->name('trd.duplicate');
        Route::patch('trd/{trd}/vigencia', [App\Http\Controllers\Admin\AdminTRDController::class, 'toggleVigencia'])->name('trd.toggle-vigencia');
        Route::get('trd/{trd}/export', [App\Http\Controllers\Admin\AdminTRDController::class, 'export'])->name('trd.export');
        
        // Gestión de Series Documentales
        Route::resource('series', App\Http\Controllers\Admin\AdminSeriesController::class);
        Route::post('series/{serie}/duplicate', [App\Http\Controllers\Admin\AdminSeriesController::class, 'duplicate'])->name('series.duplicate');
        Route::patch('series/{serie}/toggle-active', [App\Http\Controllers\Admin\AdminSeriesController::class, 'toggleActive'])->name('series.toggle-active');
        Route::get('series/export/{format?}', [App\Http\Controllers\Admin\AdminSeriesController::class, 'export'])->name('series.export');

        // Subseries Documentales routes
        Route::resource('subseries', App\Http\Controllers\Admin\AdminSubseriesController::class);
        Route::post('subseries/{subserie}/duplicate', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'duplicate'])->name('subseries.duplicate');
        Route::patch('subseries/{subserie}/toggle-active', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'toggleActive'])->name('subseries.toggle-active');
        Route::get('subseries/export/{format?}', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'export'])->name('subseries.export');
        
        // Cuadros de Clasificación Documental (CCD) routes
        Route::resource('ccd', App\Http\Controllers\Admin\AdminCCDController::class);
        Route::post('ccd/{ccd}/duplicate', [App\Http\Controllers\Admin\AdminCCDController::class, 'duplicate'])->name('ccd.duplicate');
        Route::patch('ccd/{ccd}/toggle-active', [App\Http\Controllers\Admin\AdminCCDController::class, 'toggleActive'])->name('ccd.toggle-active');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
