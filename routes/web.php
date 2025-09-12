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

    // Sistema TRD (Tablas de Retención Documental)
    Route::prefix('trd')->name('trd.')->group(function () {
        Route::get('/', [App\Http\Controllers\TrdController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\TrdController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\TrdController::class, 'store'])->name('store');
        Route::get('/{trd}', [App\Http\Controllers\TrdController::class, 'show'])->name('show');
        Route::get('/{trd}/edit', [App\Http\Controllers\TrdController::class, 'edit'])->name('edit');
        Route::put('/{trd}', [App\Http\Controllers\TrdController::class, 'update'])->name('update');
        Route::delete('/{trd}', [App\Http\Controllers\TrdController::class, 'destroy'])->name('destroy');
        
        // Funcionalidades especiales
        Route::post('/{trd}/approve', [App\Http\Controllers\TrdController::class, 'approve'])->name('approve');
        Route::post('/{trd}/versions/{version}/restore', [App\Http\Controllers\TrdController::class, 'restoreVersion'])->name('restore-version');
        
        // Importación
        Route::get('/import/form', [App\Http\Controllers\TrdController::class, 'showImport'])->name('import.form');
        Route::post('/import', [App\Http\Controllers\TrdController::class, 'import'])->name('import');
    });

    // Administración
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', App\Http\Controllers\Admin\AdminUserController::class);
        Route::patch('users/{user}/toggle-status', [App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
