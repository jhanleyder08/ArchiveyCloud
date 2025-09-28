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
        
        // Reportes y estadísticas
        Route::group(['prefix' => 'reportes'], function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminReportController::class, 'index'])->name('reportes.index');
            Route::get('/dashboard', [App\Http\Controllers\Admin\AdminReportController::class, 'dashboard'])->name('reportes.dashboard');
            Route::get('/cumplimiento-normativo', [App\Http\Controllers\Admin\AdminReportController::class, 'cumplimientoNormativo'])->name('reportes.cumplimiento-normativo');
            Route::get('/productividad', [App\Http\Controllers\Admin\AdminReportController::class, 'productividad'])->name('reportes.productividad');
            Route::get('/almacenamiento', [App\Http\Controllers\Admin\AdminReportController::class, 'almacenamiento'])->name('reportes.almacenamiento');
            Route::get('/exportar/{tipo}', [App\Http\Controllers\Admin\AdminReportController::class, 'exportar'])->name('reportes.exportar');
        });

        // Plantillas Documentales routes
        Route::resource('plantillas', App\Http\Controllers\Admin\PlantillaDocumentalController::class);
        Route::group(['prefix' => 'plantillas'], function () {
            // Editor avanzado
            Route::get('/editor/{plantilla?}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'editor'])->name('plantillas.editor');
            
            // Rutas específicas de plantillas
            Route::post('/{plantilla}/version', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'crearVersion'])->name('plantillas.crear-version');
            Route::patch('/{plantilla}/estado', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'cambiarEstado'])->name('plantillas.cambiar-estado');
            Route::post('/{plantilla}/generar', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'generarDocumento'])->name('plantillas.generar-documento');
            Route::post('/{plantilla}/previsualizar', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'previsualizar'])->name('plantillas.previsualizar');
            Route::post('/{plantilla}/duplicar', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'duplicar'])->name('plantillas.duplicar');
            Route::get('/{plantilla}/exportar/{formato}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'exportar'])->name('plantillas.exportar');
            
            // Funcionalidades avanzadas
            Route::post('/crear-desde-documento', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'crearDesdeDocumento'])->name('plantillas.crear-desde-documento');
            Route::post('/importar', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'importar'])->name('plantillas.importar');
            Route::post('/aplicar', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'aplicarPlantilla'])->name('plantillas.aplicar');
            Route::post('/validar-estructura', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'validarEstructura'])->name('plantillas.validar-estructura');
            
            // AJAX routes
            Route::get('/subseries/por-serie', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'obtenerSubseries'])->name('plantillas.subseries');
            Route::get('/estadisticas/dashboard', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'estadisticas'])->name('plantillas.estadisticas');
            Route::get('/documentos-disponibles', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'obtenerDocumentosDisponibles'])->name('plantillas.documentos-disponibles');
        });

        // Sistema de Firmas Digitales Básico (DESHABILITADO - Conflicto con FirmaDigitalAvanzadaController)
        // Route::prefix('firmas-basico')->name('firmas-basico.')->group(function () {
        //     Route::get('/dashboard', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'dashboard'])->name('dashboard');
        //     Route::get('/documento/{documento}/firmar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'mostrarFormularioFirma'])->name('formulario');
        //     Route::post('/documento/{documento}/firmar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'firmarDocumento'])->name('firmar');
        //     Route::get('/documento/{documento}/verificar', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'verificarFirmas'])->name('verificar');
        //     Route::get('/documento/{documento}/certificado', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'generarCertificado'])->name('certificado');
        //     Route::get('/firma/{firma}/verificar-especifica', [App\Http\Controllers\Admin\FirmaDigitalController::class, 'verificarFirmaEspecifica'])->name('verificar-especifica');
        // });
        
        // Sistema de Workflow/Aprobaciones routes
        Route::prefix('workflow')->name('workflow.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\WorkflowController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\WorkflowController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\WorkflowController::class, 'store'])->name('store');
            Route::get('/{workflow}', [App\Http\Controllers\Admin\WorkflowController::class, 'show'])->name('show');
            Route::get('/{workflow}/aprobar', [App\Http\Controllers\Admin\WorkflowController::class, 'aprobar'])->name('aprobar');
            Route::post('/{workflow}/procesar', [App\Http\Controllers\Admin\WorkflowController::class, 'procesarAprobacion'])->name('procesar');
            Route::post('/{workflow}/delegar', [App\Http\Controllers\Admin\WorkflowController::class, 'delegar'])->name('delegar');
            Route::post('/{workflow}/cancelar', [App\Http\Controllers\Admin\WorkflowController::class, 'cancelar'])->name('cancelar');
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
        
        // Dashboard Ejecutivo Unificado
        Route::prefix('dashboard-ejecutivo')->name('dashboard-ejecutivo.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'index'])->name('index');
            Route::get('/datos-grafico', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'datosGrafico'])->name('datos-grafico');
            Route::post('/exportar-pdf', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'exportarPDF'])->name('exportar-pdf');
        });

        // Sistema de Optimización y Monitoreo
        Route::prefix('optimizacion')->name('optimizacion.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\OptimizacionController::class, 'index'])->name('index');
            Route::get('/cache', [App\Http\Controllers\Admin\OptimizacionController::class, 'cache'])->name('cache');
            Route::post('/cache/warmup', [App\Http\Controllers\Admin\OptimizacionController::class, 'cacheWarmup'])->name('cache.warmup');
            Route::post('/cache/flush', [App\Http\Controllers\Admin\OptimizacionController::class, 'cacheFlush'])->name('cache.flush');
            Route::get('/backups', [App\Http\Controllers\Admin\OptimizacionController::class, 'backups'])->name('backups');
            Route::post('/backups/create', [App\Http\Controllers\Admin\OptimizacionController::class, 'createBackup'])->name('backups.create');
            Route::get('/monitoring', [App\Http\Controllers\Admin\OptimizacionController::class, 'monitoring'])->name('monitoring');
            Route::post('/run-optimization', [App\Http\Controllers\Admin\OptimizacionController::class, 'runOptimization'])->name('run-optimization');
            Route::get('/system-status', [App\Http\Controllers\Admin\OptimizacionController::class, 'getSystemStatusApi'])->name('system-status');
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
        
        // Sistema de Índices Electrónicos
        Route::prefix('indices')->name('indices.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'index'])->name('index');
            Route::get('/{indice}', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'show'])->name('show');
            Route::delete('/{indice}', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'destroy'])->name('destroy');
            
            // Gestión de índices
            Route::post('/regenerar', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'regenerar'])->name('regenerar');
            Route::post('/indexar-entidad', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'indexarEntidad'])->name('indexar-entidad');
            Route::patch('/{indice}/actualizar', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'actualizar'])->name('actualizar');
            
            // Exportación y estadísticas
            Route::post('/exportar', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'exportar'])->name('exportar');
            Route::get('/estadisticas/dashboard', [App\Http\Controllers\Admin\IndiceElectronicoController::class, 'estadisticas'])->name('estadisticas');
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
        
        // Servicios externos (Email, SMS)
        Route::prefix('servicios-externos')->name('servicios-externos.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'index'])->name('index');
            Route::get('/testing', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testing'])->name('testing');
            Route::post('/test-email', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testEmail'])->name('test-email');
            Route::post('/test-sms', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testSms'])->name('test-sms');
            Route::get('/estadisticas', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'estadisticas'])->name('estadisticas');
            Route::get('/configuracion', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'configuracion'])->name('configuracion');
            Route::post('/configuracion', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'actualizarConfiguracion'])->name('actualizar-configuracion');
            Route::post('/forzar-resumenes', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'forzarResumenes'])->name('forzar-resumenes');
        });

        // Sistema de Firmas Digitales Avanzado
        Route::prefix('firmas')->name('firmas.')->group(function () {
            // Dashboard principal
            Route::get('/', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'dashboard'])->name('dashboard-alt');
            
            // Gestión de certificados digitales
            Route::get('/certificados', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'certificados'])->name('certificados');
            
            // Solicitudes de firma múltiple
            Route::get('/solicitudes', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'solicitudes'])->name('solicitudes');
            Route::get('/solicitudes/crear', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'crearSolicitud'])->name('solicitudes.crear');
            Route::post('/solicitudes', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'almacenarSolicitud'])->name('solicitudes.store');
            Route::get('/solicitudes/{solicitud}', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'verSolicitud'])->name('solicitud');
            Route::post('/solicitudes/{solicitud}/firmar', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'firmarDocumento'])->name('solicitud.firmar');
            Route::post('/solicitudes/{solicitud}/rechazar', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'rechazarFirma'])->name('solicitud.rechazar');
            Route::post('/solicitudes/{solicitud}/cancelar', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'cancelarSolicitud'])->name('solicitud.cancelar');
            
            // Verificación de firmas
            Route::get('/verificar/{documento}', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'verificarFirmas'])->name('verificar');
            
            // API endpoints
            Route::get('/api/certificados', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'apiCertificados'])->name('api.certificados');
            Route::get('/api/estadisticas', [App\Http\Controllers\Admin\FirmaDigitalAvanzadaController::class, 'apiEstadisticas'])->name('api.estadisticas');
        });
        
        // Sistema de API Tokens
        Route::prefix('api-tokens')->name('api-tokens.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ApiTokenController::class, 'index'])->name('index');
            Route::get('/dashboard', [App\Http\Controllers\Admin\ApiTokenController::class, 'dashboard'])->name('dashboard');
            Route::get('/create', [App\Http\Controllers\Admin\ApiTokenController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\ApiTokenController::class, 'store'])->name('store');
            Route::get('/{apiToken}', [App\Http\Controllers\Admin\ApiTokenController::class, 'show'])->name('show');
            Route::get('/{apiToken}/edit', [App\Http\Controllers\Admin\ApiTokenController::class, 'edit'])->name('edit');
            Route::put('/{apiToken}', [App\Http\Controllers\Admin\ApiTokenController::class, 'update'])->name('update');
            Route::delete('/{apiToken}', [App\Http\Controllers\Admin\ApiTokenController::class, 'destroy'])->name('destroy');
            Route::post('/{apiToken}/revocar', [App\Http\Controllers\Admin\ApiTokenController::class, 'revocar'])->name('revocar');
            Route::post('/{apiToken}/renovar', [App\Http\Controllers\Admin\ApiTokenController::class, 'renovar'])->name('renovar');
        });

        // Sistema de Certificados Digitales PKI
        Route::prefix('certificados')->name('certificados.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'store'])->name('store');
            Route::get('/{certificado}', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'show'])->name('show');
            Route::post('/{certificado}/revocar', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'revocar'])->name('revocar');
            Route::post('/{certificado}/renovar', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'renovar'])->name('renovar');
            Route::get('/{certificado}/descargar/{formato?}', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'descargar'])->name('descargar');
        });

        // Sistema de Auditoría y Trazabilidad Avanzada
        Route::prefix('auditoria')->name('auditoria.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'index'])->name('index');
            Route::get('/analytics', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'analytics'])->name('analytics');
            Route::get('/patrones', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'patrones'])->name('patrones');
            Route::get('/{auditoria}', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'show'])->name('show');
            Route::post('/reporte', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'reporte'])->name('reporte');
            Route::get('/api/metricas', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'metricas'])->name('metricas');
        });

        // Sistema de Migración y Importación de Datos
        Route::prefix('importaciones')->name('importaciones.')->group(function () {
            // Dashboard principal
            Route::get('/', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'dashboard'])->name('dashboard');
            
            // Gestión de importaciones
            Route::get('/listado', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'index'])->name('index');
            Route::get('/crear', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'create'])->name('crear');
            Route::post('/', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'store'])->name('store');
            Route::get('/{importacion}', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'show'])->name('ver');
            Route::delete('/{importacion}', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'destroy'])->name('destroy');
            
            // Acciones de procesamiento
            Route::post('/{importacion}/procesar', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'procesar'])->name('procesar');
            Route::post('/{importacion}/cancelar', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'cancelar'])->name('cancelar');
            
            // Descargas
            Route::get('/{importacion}/descargar/original', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'descargarOriginal'])->name('descargar.original');
            Route::get('/{importacion}/descargar/errores', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'descargarErrores'])->name('descargar.errores');
            Route::get('/{importacion}/descargar/procesado', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'descargarProcesado'])->name('descargar.procesado');
            
            // API endpoints
            Route::get('/{importacion}/progreso', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'progreso'])->name('progreso');
            Route::get('/api/estadisticas', [App\Http\Controllers\Admin\ImportacionDatosController::class, 'apiEstadisticas'])->name('api.estadisticas');
        });

        // Ruta de prueba
        Route::get('test-simple', function () {
            return Inertia::render('test-simple');
        })->name('test-simple');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
