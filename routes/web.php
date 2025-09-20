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

    // Ruta para extender sesión (actividad)
    Route::post('extend-session', function () {
        return response()->json(['status' => 'extended', 'time' => now()]);
    })->name('session.extend');

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
        
        // Gestión de Documentos routes
        Route::resource('documentos', App\Http\Controllers\Admin\AdminDocumentController::class);
        Route::get('documentos/upload/masivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'uploadMasivo'])->name('documentos.upload-masivo');
        Route::post('documentos/upload/masivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'procesarSubidaMasiva'])->name('documentos.procesar-masivo');
        Route::post('documentos/{documento}/version', [App\Http\Controllers\Admin\AdminDocumentController::class, 'crearVersion'])->name('documentos.crear-version');
        
        // Gestión de Expedientes routes
        Route::resource('expedientes', App\Http\Controllers\Admin\AdminExpedienteController::class);
        Route::get('expedientes/dashboard', [App\Http\Controllers\Admin\AdminExpedienteController::class, 'dashboard'])->name('expedientes.dashboard');
        Route::post('expedientes/{expediente}/cambiar-estado', [App\Http\Controllers\Admin\AdminExpedienteController::class, 'cambiarEstado'])->name('expedientes.cambiar-estado');
        Route::get('expedientes/{expediente}/exportar-directorio', [App\Http\Controllers\Admin\AdminExpedienteController::class, 'exportarDirectorio'])->name('expedientes.exportar-directorio');
        Route::get('expedientes/{expediente}/verificar-integridad', [App\Http\Controllers\Admin\AdminExpedienteController::class, 'verificarIntegridad'])->name('expedientes.verificar-integridad');
        
        // Sistema de Reportes y Estadísticas routes
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminReportController::class, 'index'])->name('index');
            Route::get('/dashboard', [App\Http\Controllers\Admin\AdminReportController::class, 'dashboard'])->name('dashboard');
            Route::get('/cumplimiento-normativo', [App\Http\Controllers\Admin\AdminReportController::class, 'cumplimientoNormativo'])->name('cumplimiento-normativo');
            Route::get('/productividad', [App\Http\Controllers\Admin\AdminReportController::class, 'productividad'])->name('productividad');
            Route::get('/almacenamiento', [App\Http\Controllers\Admin\AdminReportController::class, 'almacenamiento'])->name('almacenamiento');
            Route::post('/exportar', [App\Http\Controllers\Admin\AdminReportController::class, 'exportar'])->name('exportar');
        });
        
        // Sistema de Firmas Digitales routes
        Route::prefix('firmas')->name('firmas.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'dashboard'])->name('dashboard');
            Route::get('/documento/{documento}/firmar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'mostrarFormularioFirma'])->name('formulario');
            Route::post('/documento/{documento}/firmar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'firmarDocumento'])->name('firmar');
            Route::get('/documento/{documento}/verificar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'verificarFirmas'])->name('verificar');
            Route::get('/documento/{documento}/certificado', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'generarCertificado'])->name('certificado');
            Route::get('/firma/{firma}/verificar-especifica', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'verificarFirmaEspecifica'])->name('verificar-especifica');
        });
        
        // Sistema de Préstamos y Consultas routes
        Route::prefix('prestamos')->name('prestamos.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'store'])->name('store');
            Route::get('/{prestamo}', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'show'])->name('show');
            Route::put('/{prestamo}/devolver', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'devolver'])->name('devolver');
            Route::put('/{prestamo}/renovar', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'renovar'])->name('renovar');
            Route::get('/reportes/estadisticas', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'reportes'])->name('reportes');
            Route::get('/buscar/elementos', [App\Http\Controllers\Admin\AdminPrestamoController::class, 'buscar'])->name('buscar');
        });

        // Sistema de Disposición Final routes
        Route::prefix('disposiciones')->name('disposiciones.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'store'])->name('store');
            Route::get('/{disposicion}', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'show'])->name('show');
            
            // Workflow de disposiciones
            Route::put('/{disposicion}/enviar-revision', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'enviarRevision'])->name('enviar-revision');
            Route::put('/{disposicion}/aprobar', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'aprobar'])->name('aprobar');
            Route::put('/{disposicion}/rechazar', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'rechazar'])->name('rechazar');
            Route::put('/{disposicion}/ejecutar', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'ejecutar'])->name('ejecutar');
            
            // Reportes
            Route::get('/reportes/estadisticas', [App\Http\Controllers\Admin\AdminDisposicionController::class, 'reportes'])->name('reportes');
        });
        
        // Sistema de Notificaciones
        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            // Notificaciones del usuario
            Route::get('/', [App\Http\Controllers\Admin\NotificacionController::class, 'index'])->name('index');
            Route::get('/no-leidas', [App\Http\Controllers\Admin\NotificacionController::class, 'noLeidas'])->name('no-leidas');
            Route::patch('/{notificacion}/marcar-leida', [App\Http\Controllers\Admin\NotificacionController::class, 'marcarLeida'])->name('marcar-leida');
            Route::patch('/marcar-todas-leidas', [App\Http\Controllers\Admin\NotificacionController::class, 'marcarTodasLeidas'])->name('marcar-todas-leidas');
            Route::patch('/{notificacion}/archivar', [App\Http\Controllers\Admin\NotificacionController::class, 'archivar'])->name('archivar');
            Route::delete('/{notificacion}', [App\Http\Controllers\Admin\NotificacionController::class, 'destroy'])->name('destroy');
            
            // Panel administrativo (solo administradores)
            Route::get('/admin', [App\Http\Controllers\Admin\NotificacionController::class, 'admin'])->name('admin');
            Route::get('/crear', [App\Http\Controllers\Admin\NotificacionController::class, 'crear'])->name('crear');
            Route::post('/', [App\Http\Controllers\Admin\NotificacionController::class, 'store'])->name('store');
            Route::post('/limpiar-antiguas', [App\Http\Controllers\Admin\NotificacionController::class, 'limpiarAntiguas'])->name('limpiar-antiguas');
        });
        
        // Módulo de Retención y Disposición
        Route::prefix('retencion-disposicion')->name('retencion.')->group(function () {
            Route::get('/', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'index'])->name('index');
            Route::get('/{proceso}', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'show'])->name('show');
            Route::post('/crear', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'crearProceso'])->name('crear');
            
            // Acciones de disposición
            Route::post('/{proceso}/ejecutar-disposicion', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'ejecutarDisposicion'])->name('ejecutar-disposicion');
            Route::post('/{proceso}/aplazar', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'aplazarDisposicion'])->name('aplazar');
            Route::post('/{proceso}/reactivar', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'reactivarProceso'])->name('reactivar');
            Route::post('/{proceso}/bloquear-eliminacion', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'bloquearEliminacion'])->name('bloquear-eliminacion');
            Route::post('/{proceso}/desbloquear-eliminacion', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'desbloquearEliminacion'])->name('desbloquear-eliminacion');
            
            // Gestión de alertas
            Route::get('/alertas', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'gestionarAlertas'])->name('alertas');
            Route::post('/alertas/{alerta}/leer', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'marcarAlertaLeida'])->name('alerta.leer');
            Route::post('/alertas/{alerta}/atender', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'marcarAlertaAtendida'])->name('alerta.atender');
            
            // Reportes y procesos masivos
            Route::get('/reportes', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'reportes'])->name('reportes');
            Route::post('/procesar-masivo', [App\Http\Controllers\AdminRetencionDisposicionController::class, 'procesarActualizacionesMasivas'])->name('procesar-masivo');
        });
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
