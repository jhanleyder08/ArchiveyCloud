<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Ruta para extender sesión (actividad)
    Route::post('extend-session', function () {
        return response()->json(['status' => 'extended', 'time' => now()]);
    })->name('session.extend');

    // Soporte Técnico (Modal)
    Route::post('support', [App\Http\Controllers\SupportController::class, 'store'])->name('support.store');

    // Sistema de Búsqueda Avanzada (Elasticsearch)
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [App\Http\Controllers\SearchController::class, 'index'])->name('index');
        Route::post('/simple', [App\Http\Controllers\SearchController::class, 'search'])->name('simple');
        Route::post('/advanced', [App\Http\Controllers\SearchController::class, 'searchAdvanced'])->name('advanced');
        Route::get('/autocomplete', [App\Http\Controllers\SearchController::class, 'autocomplete'])->name('autocomplete');
        
        // REQ-BP-005: Nuevas rutas para búsqueda avanzada
        Route::post('/similar/{document}', [App\Http\Controllers\SearchController::class, 'searchSimilar'])->name('similar');
        Route::get('/stats', [App\Http\Controllers\SearchController::class, 'getSearchStats'])->name('stats');
        Route::post('/suggestions', [App\Http\Controllers\SearchController::class, 'searchWithSuggestions'])->name('suggestions');
    });

    // Sistema de Validaciones y Reglas de Negocio
    Route::prefix('validations')->name('validations.')->group(function () {
        Route::post('/estructura-trd', [App\Http\Controllers\ValidationController::class, 'validarEstructuraTRD'])->name('estructura-trd');
        Route::post('/reglas-expediente/{expediente}', [App\Http\Controllers\ValidationController::class, 'validarReglasExpediente'])->name('reglas-expediente');
        Route::post('/metadatos', [App\Http\Controllers\ValidationController::class, 'validarMetadatos'])->name('metadatos');
        Route::post('/integridad', [App\Http\Controllers\ValidationController::class, 'validarIntegridad'])->name('integridad');
        Route::post('/asistente', [App\Http\Controllers\ValidationController::class, 'generarAsistente'])->name('asistente');
        Route::post('/completa', [App\Http\Controllers\ValidationController::class, 'validacionCompleta'])->name('completa');
        Route::get('/options', [App\Http\Controllers\ValidationController::class, 'getValidationOptions'])->name('options');
    });

    // Autenticación de Dos Factores (MFA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/challenge', [App\Http\Controllers\TwoFactorChallengeController::class, 'show'])->name('challenge');
        Route::post('/verify', [App\Http\Controllers\TwoFactorChallengeController::class, 'verify'])->name('verify');
        Route::post('/resend', [App\Http\Controllers\TwoFactorChallengeController::class, 'resend'])->name('resend');
        
        Route::get('/settings', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'index'])->name('settings');
        Route::post('/enable', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'enable'])->name('enable');
        Route::post('/confirm', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'confirm'])->name('confirm');
        Route::post('/disable', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'disable'])->name('disable');
        Route::post('/recovery-codes/regenerate', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
        Route::get('/recovery-codes', [App\Http\Controllers\TwoFactorAuthenticationController::class, 'showRecoveryCodes'])->name('recovery-codes.show');
    });

    // OCR (Optical Character Recognition)
    Route::prefix('ocr')->name('ocr.')->group(function () {
        Route::post('/process/{documento}', [App\Http\Controllers\OCRController::class, 'process'])->name('process');
        Route::post('/batch', [App\Http\Controllers\OCRController::class, 'processBatch'])->name('batch');
        Route::get('/status/{documento}', [App\Http\Controllers\OCRController::class, 'status'])->name('status');
    });

    // Captura de Correos Electrónicos
    Route::prefix('email-accounts')->name('email-accounts.')->group(function () {
        Route::get('/', [App\Http\Controllers\EmailAccountController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\EmailAccountController::class, 'store'])->name('store');
        Route::patch('/{emailAccount}', [App\Http\Controllers\EmailAccountController::class, 'update'])->name('update');
        Route::delete('/{emailAccount}', [App\Http\Controllers\EmailAccountController::class, 'destroy'])->name('destroy');
        Route::post('/{emailAccount}/test', [App\Http\Controllers\EmailAccountController::class, 'testConnection'])->name('test');
        Route::post('/{emailAccount}/capture', [App\Http\Controllers\EmailAccountController::class, 'capture'])->name('capture');
        Route::get('/{emailAccount}/captures', [App\Http\Controllers\EmailAccountController::class, 'captures'])->name('captures');
        Route::post('/capture-all', [App\Http\Controllers\EmailAccountController::class, 'captureAll'])->name('capture-all');
    });

    // Administración
    Route::prefix('admin')->name('admin.')->group(function () {
        // Gestión de Usuarios - Protegido con permisos específicos
        Route::middleware('permission:usuarios.ver')->group(function () {
            Route::get('users', [App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [App\Http\Controllers\Admin\AdminUserController::class, 'show'])->name('users.show');
        });
        
        Route::middleware('permission:usuarios.crear')->group(function () {
            Route::get('users/create', [App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('users.create');
            Route::post('users', [App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('users.store');
        });
        
        Route::middleware('permission:usuarios.editar')->group(function () {
            Route::get('users/{user}/edit', [App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}', [App\Http\Controllers\Admin\AdminUserController::class, 'update']);
        });
        
        Route::middleware('permission:usuarios.activar')->group(function () {
            Route::patch('users/{user}/toggle-status', [App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        });
        
        Route::middleware('permission:usuarios.eliminar')->group(function () {
            Route::delete('users/{user}', [App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('users.destroy');
        });
        
        // Gestión de Roles y Permisos - Solo Super Administrador
        Route::middleware('can:manage-roles')->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('index');
            Route::put('/{role}/permissions', [App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('update-permissions');
            Route::patch('/{role}/toggle-status', [App\Http\Controllers\Admin\RoleController::class, 'toggleStatus'])->name('toggle-status');
        });
        
        // Gestión de Tablas de Retención Documental (TRD) - Protegido con permisos
        Route::prefix('trd')->name('trd.')->middleware('permission:trd.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminTRDController::class, 'index'])->name('index');
            Route::get('/{trd}', [App\Http\Controllers\Admin\AdminTRDController::class, 'show'])->name('show');
            
            Route::middleware('permission:trd.crear')->group(function () {
                Route::post('/', [App\Http\Controllers\Admin\AdminTRDController::class, 'store'])->name('store');
            });
            
            Route::middleware('permission:trd.editar')->group(function () {
                Route::get('/{trd}/edit', [App\Http\Controllers\Admin\AdminTRDController::class, 'edit'])->name('edit');
                Route::put('/{trd}', [App\Http\Controllers\Admin\AdminTRDController::class, 'update'])->name('update');
                Route::patch('/{trd}', [App\Http\Controllers\Admin\AdminTRDController::class, 'update']);
                Route::delete('/{trd}', [App\Http\Controllers\Admin\AdminTRDController::class, 'destroy'])->name('destroy');
                Route::patch('/{trd}/vigencia', [App\Http\Controllers\Admin\AdminTRDController::class, 'toggleVigencia'])->name('vigencia');
            });
            
            Route::middleware('permission:trd.crear')->group(function () {
                Route::post('/{trd}/duplicate', [App\Http\Controllers\Admin\AdminTRDController::class, 'duplicate'])->name('duplicate');
            });
        });
        
        // Gestión de Series Documentales - Protegido con permisos
        Route::middleware('permission:series.ver')->group(function () {
            Route::get('series', [App\Http\Controllers\Admin\AdminSeriesController::class, 'index'])->name('series.index');
            Route::get('series-dashboard', [App\Http\Controllers\Admin\AdminSeriesController::class, 'dashboard'])->name('series.dashboard');
            Route::get('series/export', [App\Http\Controllers\Admin\AdminSeriesController::class, 'export'])->name('series.export');
            Route::get('series/{serie}', [App\Http\Controllers\Admin\AdminSeriesController::class, 'show'])->name('series.show');
        });
        
        Route::middleware('permission:series.crear')->group(function () {
            Route::get('series/create', [App\Http\Controllers\Admin\AdminSeriesController::class, 'create'])->name('series.create');
            Route::post('series', [App\Http\Controllers\Admin\AdminSeriesController::class, 'store'])->name('series.store');
        });
        
        Route::middleware('permission:series.editar')->group(function () {
            Route::get('series/{serie}/edit', [App\Http\Controllers\Admin\AdminSeriesController::class, 'edit'])->name('series.edit');
            Route::put('series/{serie}', [App\Http\Controllers\Admin\AdminSeriesController::class, 'update'])->name('series.update');
            Route::patch('series/{serie}', [App\Http\Controllers\Admin\AdminSeriesController::class, 'update']);
            Route::delete('series/{serie}', [App\Http\Controllers\Admin\AdminSeriesController::class, 'destroy'])->name('series.destroy');
            Route::post('series/{serie}/duplicate', [App\Http\Controllers\Admin\AdminSeriesController::class, 'duplicate'])->name('series.duplicate');
            Route::patch('series/{serie}/toggle-active', [App\Http\Controllers\Admin\AdminSeriesController::class, 'toggleActive'])->name('series.toggle-active');
        });

        // Subseries Documentales - Protegido con permisos
        Route::middleware('permission:subseries.ver')->group(function () {
            Route::get('subseries', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'index'])->name('subseries.index');
            Route::get('subseries/export', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'export'])->name('subseries.export');
            Route::get('subseries/{subserie}', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'show'])->name('subseries.show');
        });
        
        Route::middleware('permission:subseries.crear')->group(function () {
            Route::get('subseries/create', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'create'])->name('subseries.create');
            Route::post('subseries', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'store'])->name('subseries.store');
        });
        
        Route::middleware('permission:subseries.editar')->group(function () {
            Route::get('subseries/{subserie}/edit', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'edit'])->name('subseries.edit');
            Route::put('subseries/{subserie}', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'update'])->name('subseries.update');
            Route::patch('subseries/{subserie}', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'update']);
            Route::delete('subseries/{subserie}', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'destroy'])->name('subseries.destroy');
            Route::post('subseries/{subserie}/duplicate', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'duplicate'])->name('subseries.duplicate');
            Route::patch('subseries/{subserie}/toggle-active', [App\Http\Controllers\Admin\AdminSubseriesController::class, 'toggleActive'])->name('subseries.toggle-active');
        });
        
        // Cuadros de Clasificación Documental (CCD) - Protegido con permisos
        Route::prefix('ccd')->name('ccd.')->middleware('permission:ccd.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\CCDController::class, 'index'])->name('index');
            
            Route::middleware('permission:ccd.crear')->group(function () {
                Route::get('/create', [App\Http\Controllers\CCDController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\CCDController::class, 'store'])->name('store');
            });
            
            Route::get('/{ccd}', [App\Http\Controllers\CCDController::class, 'show'])->name('show');
            
            Route::middleware('permission:ccd.editar')->group(function () {
                Route::get('/{ccd}/edit', [App\Http\Controllers\CCDController::class, 'edit'])->name('edit');
                Route::put('/{ccd}', [App\Http\Controllers\CCDController::class, 'update'])->name('update');
                Route::delete('/{ccd}', [App\Http\Controllers\CCDController::class, 'destroy'])->name('destroy');
                
                // Acciones especiales
                Route::post('/{ccd}/aprobar', [App\Http\Controllers\CCDController::class, 'aprobar'])->name('aprobar');
                Route::post('/{ccd}/archivar', [App\Http\Controllers\CCDController::class, 'archivar'])->name('archivar');
                Route::post('/{ccd}/version', [App\Http\Controllers\CCDController::class, 'crearVersion'])->name('version');
                
                // Gestión de niveles
                Route::post('/{ccd}/nivel', [App\Http\Controllers\CCDController::class, 'agregarNivel'])->name('agregarNivel');
                Route::put('/nivel/{nivel}', [App\Http\Controllers\CCDController::class, 'actualizarNivel'])->name('actualizarNivel');
                Route::delete('/nivel/{nivel}', [App\Http\Controllers\CCDController::class, 'eliminarNivel'])->name('eliminarNivel');
                Route::post('/nivel/{nivel}/mover', [App\Http\Controllers\CCDController::class, 'moverNivel'])->name('moverNivel');
            });
            
            // Estructura (accesible para todos con permiso ccd.ver)
            Route::get('/{ccd}/estructura', [App\Http\Controllers\CCDController::class, 'getEstructura'])->name('estructura');
        });
        
        // Gestión de Documentos - Protegido con permisos
        // IMPORTANTE: Rutas específicas ANTES que rutas con parámetros
        Route::middleware('permission:documentos.crear')->group(function () {
            Route::get('documentos/create', [App\Http\Controllers\Admin\AdminDocumentController::class, 'create'])->name('documentos.create');
            Route::post('documentos', [App\Http\Controllers\Admin\AdminDocumentController::class, 'store'])->name('documentos.store');
            Route::get('documentos/upload/masivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'uploadMasivo'])->name('documentos.upload-masivo');
            Route::post('documentos/upload/masivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'procesarSubidaMasiva'])->name('documentos.procesar-masivo');
        });
        
        // REQ-CP-007: Rutas de validación (sin parámetros)
        Route::post('documentos/validar-archivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'validarArchivoApi'])->name('documentos.validar-archivo');
        Route::get('documentos/configuracion-formatos', [App\Http\Controllers\Admin\AdminDocumentController::class, 'getConfiguracionFormatos'])->name('documentos.configuracion-formatos');
        
        Route::middleware('permission:documentos.ver')->group(function () {
            Route::get('documentos', [App\Http\Controllers\Admin\AdminDocumentController::class, 'index'])->name('documentos.index');
            Route::get('documentos/{documento}', [App\Http\Controllers\Admin\AdminDocumentController::class, 'show'])->name('documentos.show');
            Route::get('documentos/{documento}/preview', [App\Http\Controllers\Admin\AdminDocumentController::class, 'preview'])->name('documentos.preview');
            Route::get('documentos/{documento}/descargar', [App\Http\Controllers\Admin\AdminDocumentController::class, 'descargar'])->name('documentos.descargar');
        });
        
        Route::middleware('permission:documentos.editar')->group(function () {
            Route::get('documentos/{documento}/edit', [App\Http\Controllers\Admin\AdminDocumentController::class, 'edit'])->name('documentos.edit');
            Route::put('documentos/{documento}', [App\Http\Controllers\Admin\AdminDocumentController::class, 'update'])->name('documentos.update');
            Route::patch('documentos/{documento}', [App\Http\Controllers\Admin\AdminDocumentController::class, 'update']);
            Route::post('documentos/{documento}/version', [App\Http\Controllers\Admin\AdminDocumentController::class, 'crearVersion'])->name('documentos.crear-version');
        });
        
        Route::middleware('permission:documentos.eliminar')->group(function () {
            Route::delete('documentos/{documento}', [App\Http\Controllers\Admin\AdminDocumentController::class, 'destroy'])->name('documentos.destroy');
        });
        
        // REQ-CP-012: Nuevas rutas para procesamiento masivo avanzado
        Route::post('documentos/estado-procesamiento-masivo', [App\Http\Controllers\Admin\AdminDocumentController::class, 'estadoProcesamientoMasivo'])->name('documentos.estado-procesamiento-masivo');
        Route::post('documentos/reprocesar', [App\Http\Controllers\Admin\AdminDocumentController::class, 'reprocesarDocumentos'])->name('documentos.reprocesar');

        // REQ-FD-001: Sistema de Firmas Digitales
        Route::prefix('firmas')->name('firmas.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'index'])->name('index');
            Route::get('documento/{documento}/firmar', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'firmarDocumento'])->name('documento.firmar');
            Route::post('documento/{documento}/procesar', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'procesarFirma'])->name('documento.procesar');
            Route::post('contrafirma/{firma}', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'procesarContrafirma'])->name('contrafirma');
            Route::post('validar/{firma}', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'validarFirma'])->name('validar');
            Route::get('detalle/{firma}', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'mostrarFirma'])->name('detalle');
            Route::get('descargar/{firma}', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'descargarArchivoFirmado'])->name('descargar');
        });

        // REQ-CD-001: Gestión de Certificados Digitales  
        Route::prefix('certificados')->name('certificados.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'gestionCertificados'])->name('index');
            Route::post('importar', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'importarCertificado'])->name('importar');
            Route::post('verificar/{certificado}', [App\Http\Controllers\Admin\DigitalSignatureController::class, 'verificarCertificado'])->name('verificar');
        });

        // REQ-WF-001: Sistema de Workflow y Flujos de Trabajo - Protegido con permisos
        Route::prefix('workflow')->name('workflow.')->middleware('permission:workflow.gestionar')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\WorkflowController::class, 'index'])->name('index');
            Route::get('create', [App\Http\Controllers\Admin\WorkflowController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\WorkflowController::class, 'store'])->name('store');
            Route::get('{instancia}', [App\Http\Controllers\Admin\WorkflowController::class, 'show'])->name('show');
            Route::post('tarea/{tarea}/completar', [App\Http\Controllers\Admin\WorkflowController::class, 'completarTarea'])->name('completar-tarea');
            Route::post('{instancia}/cancelar', [App\Http\Controllers\Admin\WorkflowController::class, 'cancelar'])->name('cancelar');
            Route::get('reportes/dashboard', [App\Http\Controllers\Admin\WorkflowController::class, 'reportes'])->name('reportes');
        });
        
        // Gestión de Expedientes - Protegido con permisos
        Route::prefix('expedientes')->name('expedientes.')->middleware('permission:expedientes.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\ExpedienteController::class, 'index'])->name('index');
            
            // IMPORTANTE: Rutas estáticas ANTES de rutas con parámetros
            Route::middleware('permission:expedientes.crear')->group(function () {
                Route::get('/create', [App\Http\Controllers\ExpedienteController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\ExpedienteController::class, 'store'])->name('store');
            });
            
            Route::middleware('permission:expedientes.editar')->group(function () {
                Route::get('/{expediente}/edit', [App\Http\Controllers\ExpedienteController::class, 'edit'])->name('edit');
                Route::put('/{expediente}', [App\Http\Controllers\ExpedienteController::class, 'update'])->name('update');
                Route::delete('/{expediente}', [App\Http\Controllers\ExpedienteController::class, 'destroy'])->name('destroy');
                
                // Acciones especiales
                Route::post('/{expediente}/cambiar-estado', [App\Http\Controllers\ExpedienteController::class, 'cambiarEstado'])->name('cambiarEstado');
                Route::post('/{expediente}/cerrar', [App\Http\Controllers\ExpedienteController::class, 'cerrar'])->name('cerrar');
                Route::post('/{expediente}/agregar-documento', [App\Http\Controllers\ExpedienteController::class, 'agregarDocumento'])->name('agregarDocumento');
                Route::post('/{expediente}/transferencia', [App\Http\Controllers\ExpedienteController::class, 'crearTransferencia'])->name('crearTransferencia');
                Route::get('/{expediente}/verificar-integridad', [App\Http\Controllers\ExpedienteController::class, 'verificarIntegridad'])->name('verificarIntegridad');
            });
            
            // Ruta show al final (después de rutas estáticas)
            Route::get('/{expediente}', [App\Http\Controllers\ExpedienteController::class, 'show'])->name('show');
        });
        
        // Reportes y estadísticas - Protegido con permisos
        Route::group(['prefix' => 'reportes', 'middleware' => 'permission:reportes.ver'], function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminReportController::class, 'index'])->name('reportes.index');
            Route::get('/dashboard', [App\Http\Controllers\Admin\AdminReportController::class, 'dashboard'])->name('reportes.dashboard');
            Route::get('/cumplimiento-normativo', [App\Http\Controllers\Admin\AdminReportController::class, 'cumplimientoNormativo'])->name('reportes.cumplimiento-normativo');
            Route::get('/productividad', [App\Http\Controllers\Admin\AdminReportController::class, 'productividad'])->name('reportes.productividad');
            Route::get('/almacenamiento', [App\Http\Controllers\Admin\AdminReportController::class, 'almacenamiento'])->name('reportes.almacenamiento');
            
            Route::middleware('permission:reportes.exportar')->group(function () {
                Route::get('/exportar/{tipo}', [App\Http\Controllers\Admin\AdminReportController::class, 'exportar'])->name('reportes.exportar');
            });
        });

        // Plantillas Documentales - Protegido con permisos
        Route::middleware('permission:plantillas.ver')->group(function () {
            Route::get('plantillas', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'index'])->name('plantillas.index');
            Route::get('plantillas/{plantilla}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'show'])->name('plantillas.show');
        });
        
        Route::middleware('permission:plantillas.crear')->group(function () {
            Route::get('plantillas/create', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'create'])->name('plantillas.create');
            Route::post('plantillas', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'store'])->name('plantillas.store');
        });
        
        Route::middleware('permission:plantillas.editar')->group(function () {
            Route::get('plantillas/{plantilla}/edit', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'edit'])->name('plantillas.edit');
            Route::put('plantillas/{plantilla}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'update'])->name('plantillas.update');
            Route::patch('plantillas/{plantilla}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'update']);
            Route::delete('plantillas/{plantilla}', [App\Http\Controllers\Admin\PlantillaDocumentalController::class, 'destroy'])->name('plantillas.destroy');
        });
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
        
        // Sistema de Préstamos y Consultas - Protegido con permisos
        Route::prefix('prestamos')->name('prestamos.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PrestamoController::class, 'index'])->name('index');
            Route::get('/reportes', [App\Http\Controllers\Admin\PrestamoController::class, 'reportes'])->name('reportes');
            Route::get('/reportes/pdf', [App\Http\Controllers\Admin\PrestamoController::class, 'exportarReportesPDF'])->name('reportes.pdf');
            Route::get('/create', [App\Http\Controllers\Admin\PrestamoController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\PrestamoController::class, 'store'])->name('store');
            Route::get('/{prestamo}', [App\Http\Controllers\Admin\PrestamoController::class, 'show'])->name('show');
            Route::get('/{prestamo}/edit', [App\Http\Controllers\Admin\PrestamoController::class, 'edit'])->name('edit');
            Route::put('/{prestamo}', [App\Http\Controllers\Admin\PrestamoController::class, 'update'])->name('update');
            Route::delete('/{prestamo}', [App\Http\Controllers\Admin\PrestamoController::class, 'destroy'])->name('destroy');
            Route::put('/{prestamo}/devolver', [App\Http\Controllers\Admin\PrestamoController::class, 'devolver'])->name('devolver');
            Route::put('/{prestamo}/renovar', [App\Http\Controllers\Admin\PrestamoController::class, 'renovar'])->name('renovar');
        });

        // Sistema de Disposición Final - Protegido con permisos
        Route::prefix('disposiciones')->name('disposiciones.')->middleware('permission:disposiciones.ver')->group(function () {
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
        
        // Dashboard Ejecutivo Unificado - Protegido con permisos
        Route::prefix('dashboard-ejecutivo')->name('dashboard-ejecutivo.')->middleware('permission:administracion.dashboard.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'index'])->name('index');
            Route::get('/datos-grafico', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'datosGrafico'])->name('datos-grafico');
            Route::get('/exportar-pdf', [App\Http\Controllers\Admin\DashboardEjecutivoController::class, 'exportarPDF'])->name('exportar-pdf');
        });

        // Sistema de Optimización y Monitoreo - Protegido con permisos
        Route::prefix('optimizacion')->name('optimizacion.')->middleware('permission:administracion.configuracion.gestionar')->group(function () {
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

        // Sistema de Notificaciones - Protegido con permisos
        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            // Notificaciones del usuario (accesibles para todos)
            Route::get('/', [App\Http\Controllers\Admin\NotificacionController::class, 'index'])->name('index');
            Route::get('/no-leidas', [App\Http\Controllers\Admin\NotificacionController::class, 'noLeidas'])->name('no-leidas');
            Route::patch('/{notificacion}/marcar-leida', [App\Http\Controllers\Admin\NotificacionController::class, 'marcarLeida'])->name('marcar-leida');
            Route::patch('/marcar-todas-leidas', [App\Http\Controllers\Admin\NotificacionController::class, 'marcarTodasLeidas'])->name('marcar-todas-leidas');
            Route::patch('/{notificacion}/archivar', [App\Http\Controllers\Admin\NotificacionController::class, 'archivar'])->name('archivar');
            Route::delete('/{notificacion}', [App\Http\Controllers\Admin\NotificacionController::class, 'destroy'])->name('destroy');
            
            // Panel administrativo - Requiere permiso de gestión
            Route::middleware('permission:notificaciones.gestionar')->group(function () {
                Route::get('/admin', [App\Http\Controllers\Admin\NotificacionController::class, 'admin'])->name('admin');
                Route::get('/crear', [App\Http\Controllers\Admin\NotificacionController::class, 'crear'])->name('crear');
                Route::post('/', [App\Http\Controllers\Admin\NotificacionController::class, 'store'])->name('store');
                Route::post('/limpiar-antiguas', [App\Http\Controllers\Admin\NotificacionController::class, 'limpiarAntiguas'])->name('limpiar-antiguas');
            });
        });
        
        // Sistema de Índices Electrónicos - Protegido con permisos
        Route::prefix('indices')->name('indices.')->middleware('permission:indices.ver')->group(function () {
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

        
        // Servicios externos (Email, SMS) - Protegido con permisos
        Route::prefix('servicios-externos')->name('servicios-externos.')->middleware('permission:administracion.configuracion.gestionar')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'index'])->name('index');
            Route::get('/testing', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testing'])->name('testing');
            Route::post('/test-email', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testEmail'])->name('test-email');
            Route::post('/test-sms', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'testSms'])->name('test-sms');
            Route::get('/estadisticas', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'estadisticas'])->name('estadisticas');
            Route::get('/configuracion', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'configuracion'])->name('configuracion');
            Route::post('/configuracion', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'actualizarConfiguracion'])->name('actualizar-configuracion');
            Route::post('/forzar-resumenes', [App\Http\Controllers\Admin\ServiciosExternosController::class, 'forzarResumenes'])->name('forzar-resumenes');
        });

        // Sistema de Firmas Digitales Avanzado - Protegido con permisos
        Route::prefix('firmas')->name('firmas.')->middleware('permission:firmas.gestionar')->group(function () {
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
        
        // Sistema de API Tokens - Protegido con permisos
        Route::prefix('api-tokens')->name('api-tokens.')->middleware('permission:api.gestionar')->group(function () {
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

        // Sistema de Certificados Digitales PKI - Protegido con permisos
        Route::prefix('certificados')->name('certificados.')->middleware('permission:certificados.gestionar')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'store'])->name('store');
            Route::get('/{certificado}', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'show'])->name('show');
            Route::post('/{certificado}/revocar', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'revocar'])->name('revocar');
            Route::post('/{certificado}/renovar', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'renovar'])->name('renovar');
            Route::get('/{certificado}/descargar/{formato?}', [App\Http\Controllers\Admin\CertificadoDigitalController::class, 'descargar'])->name('descargar');
        });

        // Sistema de Auditoría y Trazabilidad Avanzada - Protegido con permisos
        Route::prefix('auditoria')->name('auditoria.')->middleware('permission:auditoria.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'index'])->name('index');
            Route::get('/analytics', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'analytics'])->name('analytics');
            Route::get('/patrones', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'patrones'])->name('patrones');
            Route::get('/{auditoria}', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'show'])->name('show');
            Route::get('/api/metricas', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'metricas'])->name('metricas');
            
            Route::middleware('permission:auditoria.exportar')->group(function () {
                Route::post('/reporte', [App\Http\Controllers\Admin\AuditoriaAvanzadaController::class, 'reporte'])->name('reporte');
            });
        });

        // Sistema de Configuración Avanzada - Protegido con permisos
        Route::prefix('configuracion')->name('configuracion.')->middleware('permission:administracion.configuracion.gestionar')->group(function () {
            // Dashboard principal
            Route::get('/', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'index'])->name('index');
            
            // Actualización de configuraciones
            Route::put('/{clave}', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'actualizar'])->name('actualizar');
            
            // Branding y personalización
            Route::get('/branding', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'branding'])->name('branding');
            Route::post('/branding/upload', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'subirArchivoBranding'])->name('branding.upload');
            
            // Configuración por roles
            Route::get('/roles', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'roles'])->name('roles');
            Route::put('/roles/{role}', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'actualizarConfiguracionRol'])->name('roles.update');
            Route::put('/roles/update', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'actualizarConfiguracionesRoles'])->name('roles.update-batch');
            
            // Branding adicional
            Route::put('/branding/update', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'actualizarBranding'])->name('branding.update');
            Route::delete('/branding/logo/{tipo}', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'eliminarLogo'])->name('branding.logo.delete');
            
            // Mantenimiento del sistema
            Route::get('/mantenimiento', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'mantenimiento'])->name('mantenimiento');
            Route::post('/mantenimiento/comando', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'ejecutarComando'])->name('mantenimiento.comando');
            
            // Import/Export configuraciones
            Route::get('/exportar', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'exportar'])->name('exportar');
            Route::post('/importar', [App\Http\Controllers\Admin\AdminConfiguracionController::class, 'importar'])->name('importar');
        });

        // Sistema de Migración y Importación de Datos - Protegido con permisos
        Route::prefix('importaciones')->name('importaciones.')->middleware('permission:importacion.gestionar')->group(function () {
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
