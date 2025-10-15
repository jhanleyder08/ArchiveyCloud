<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\TipologiaDocumental;
use App\Services\DocumentProcessingService;
use App\Services\BusinessRulesService;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controlador para Captura e Ingreso de Documentos
 * 
 * Implementa requerimientos:
 * REQ-CP-001: Definición de formatos permitidos
 * REQ-CP-002: Contenido multimedia
 * REQ-CP-007: Validación de formatos
 * REQ-CP-028: Conversión de formatos
 * REQ-CP-009/015: Subida masiva
 */
class AdminDocumentController extends Controller
{
    protected DocumentProcessingService $documentProcessor;
    protected BusinessRulesService $businessRules;

    public function __construct(DocumentProcessingService $documentProcessor, BusinessRulesService $businessRules)
    {
        $this->documentProcessor = $documentProcessor;
        $this->businessRules = $businessRules;
    }

    /**
     * REQ-CP-001: Formatos permitidos por categoría
     */
    const FORMATOS_PERMITIDOS = [
        'texto' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
        'imagen' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'webp'],
        'hoja_calculo' => ['xls', 'xlsx', 'csv', 'ods'],
        'presentacion' => ['ppt', 'pptx', 'odp'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
        'comprimido' => ['zip', 'rar', '7z', 'tar', 'gz']
    ];

    /**
     * REQ-CP-001: Tamaños máximos por tipo (en MB)
     */
    const TAMAÑOS_MAXIMOS = [
        'texto' => 50,
        'imagen' => 25,
        'hoja_calculo' => 100,
        'presentacion' => 200,
        'audio' => 500,
        'video' => 2048,
        'comprimido' => 1024
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Documento::query()->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('expediente_id')) {
            $query->where('expediente_id', $request->expediente_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('formato')) {
            $query->where('formato', $request->formato);
        }

        if ($request->filled('tipo_soporte')) {
            $query->where('tipo_soporte', $request->tipo_soporte);
        }

        // Paginación
        $documentos = $query->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => 0, // Documento::count(),
            'activos' => 0, // Simplificado por ahora
            'borradores' => 0, // Simplificado por ahora
            'archivados' => 0, // Simplificado por ahora
        ];

        // Opciones para filtros
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'aprobado', 'label' => 'Aprobado'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'archivado', 'label' => 'Archivado'],
            ],
            'soportes' => [
                ['value' => 'electronico', 'label' => 'Electrónico'],
                ['value' => 'fisico', 'label' => 'Físico'],
                ['value' => 'hibrido', 'label' => 'Híbrido'],
            ],
            'formatos_disponibles' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
            'expedientes_disponibles' => [], // Expediente::select('id', 'numero_expediente', 'titulo')->get(),
        ];

        return Inertia::render('admin/documentos/index', [
            'documentos' => $documentos,
            'stats' => $estadisticas,
            'expedientes' => $opciones['expedientes_disponibles'],
            'tipologias' => [],
            'filtros' => $request->only(['search', 'expediente_id', 'estado', 'formato', 'tipo_soporte'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $opciones = [
            'expedientes' => Expediente::with(['serie', 'subserie'])
                                     ->where('estado', '!=', 'cerrado')
                                     ->select('id', 'codigo', 'nombre', 'serie_id', 'subserie_id')
                                     ->get(),
            'tipologias' => TipologiaDocumental::where('activo', true)
                                              ->select('id', 'nombre', 'categoria', 'formato_archivo')
                                              ->get(),
            'estados' => [
                ['value' => Documento::ESTADO_BORRADOR, 'label' => 'Borrador'],
                ['value' => Documento::ESTADO_PENDIENTE, 'label' => 'Pendiente'],
                ['value' => Documento::ESTADO_ACTIVO, 'label' => 'Activo'],
            ],
            'tipos_soporte' => [
                ['value' => Documento::SOPORTE_ELECTRONICO, 'label' => 'Electrónico'],
                ['value' => Documento::SOPORTE_FISICO, 'label' => 'Físico'],
                ['value' => Documento::SOPORTE_HIBRIDO, 'label' => 'Híbrido'],
            ],
            'confidencialidad' => [
                ['value' => Documento::CONFIDENCIALIDAD_PUBLICA, 'label' => 'Pública'],
                ['value' => Documento::CONFIDENCIALIDAD_INTERNA, 'label' => 'Interna'],
                ['value' => Documento::CONFIDENCIALIDAD_CONFIDENCIAL, 'label' => 'Confidencial'],
                ['value' => Documento::CONFIDENCIALIDAD_RESERVADA, 'label' => 'Reservada'],
                ['value' => Documento::CONFIDENCIALIDAD_CLASIFICADA, 'label' => 'Clasificada'],
            ],
            'formatos_permitidos' => DocumentProcessingService::getFormatosSoportados(),
            'tamaños_maximos' => self::TAMAÑOS_MAXIMOS,
            'configuracion_multimedia' => $this->documentProcessor->getConfiguracionMultimedia(),
        ];

        return Inertia::render('admin/documentos/create', [
            'opciones' => $opciones
        ]);
    }

    /**
     * REQ-CP-007/028: Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'expediente_id' => 'required|exists:expedientes,id',
            'tipologia_id' => 'nullable|exists:tipologias_documentales,id',
            'tipo_documental' => 'nullable|string|max:100',
            'tipo_soporte' => 'required|in:' . implode(',', [
                Documento::SOPORTE_ELECTRONICO,
                Documento::SOPORTE_FISICO,
                Documento::SOPORTE_HIBRIDO
            ]),
            'estado' => 'required|in:' . implode(',', [
                Documento::ESTADO_BORRADOR,
                Documento::ESTADO_PENDIENTE,
                Documento::ESTADO_ACTIVO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Documento::CONFIDENCIALIDAD_PUBLICA,
                Documento::CONFIDENCIALIDAD_INTERNA,
                Documento::CONFIDENCIALIDAD_CONFIDENCIAL,
                Documento::CONFIDENCIALIDAD_RESERVADA,
                Documento::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'numero_folios' => 'nullable|integer|min:1',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'archivo' => 'nullable|file|max:' . (2048 * 1024), // 2GB máximo
            // Opciones de procesamiento
            'procesamiento' => 'nullable|array',
            'procesamiento.ocr' => 'nullable|boolean',
            'procesamiento.convertir' => 'nullable|boolean',
            'procesamiento.generar_miniatura' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        
        try {
            // REQ-VN-001: Validar estructura TRD/CCD antes de crear
            $expediente = null;
            if ($request->expediente_id) {
                $expediente = Expediente::with('serie')->find($request->expediente_id);
            }

            $validacionEstructura = $this->businessRules->validarEstructuraTRD([
                'serie_id' => $expediente ? $expediente->serie_id : null,
                'subserie_id' => $expediente ? $expediente->subserie_id : null,
                'tipologia_id' => $request->tipologia_id,
            ]);

            if (!$validacionEstructura['valido']) {
                throw new \Exception('Errores en estructura TRD: ' . implode(', ', $validacionEstructura['errores']));
            }

            // Mostrar advertencias de estructura si las hay
            if (!empty($validacionEstructura['advertencias'])) {
                session()->flash('trd_warnings', $validacionEstructura['advertencias']);
            }

            $documento = new Documento();
            $documento->fill($request->except(['archivo', 'procesamiento']));
            $documento->usuario_creador_id = auth()->id();
            
            // REQ-CP-007: Validación y procesamiento avanzado de archivo
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                
                // 1. Validación avanzada con el nuevo servicio
                $validacion = $this->documentProcessor->validarArchivo(
                    $archivo, 
                    $request->tipologia_id
                );
                
                if (!$validacion['valido']) {
                    throw new \Exception('Errores de validación: ' . implode(', ', $validacion['errores']));
                }
                
                // Mostrar advertencias si las hay
                if (!empty($validacion['advertencias'])) {
                    session()->flash('warnings', $validacion['advertencias']);
                }
                
                // 2. Procesar archivo con el servicio avanzado
                $procesamiento = $this->documentProcessor->procesarArchivo(
                    $archivo, 
                    $documento, 
                    $request->input('procesamiento', [])
                );
                
                if (!$procesamiento['success']) {
                    throw new \Exception('Error procesando el archivo');
                }
                
                // 3. Asignar datos del archivo procesado
                $documento->formato = strtolower($archivo->getClientOriginalExtension());
                $documento->tamaño = $archivo->getSize();
                $documento->ruta_archivo = $procesamiento['archivo_procesado'];
                $documento->hash_sha256 = $procesamiento['metadatos']['hash_sha256'] ?? null;
                
                // 4. Datos adicionales del procesamiento
                if ($procesamiento['ocr_texto']) {
                    $documento->contenido_ocr = $procesamiento['ocr_texto'];
                }
                
                if ($procesamiento['miniatura']) {
                    $documento->ruta_miniatura = $procesamiento['miniatura'];
                }
                
                // 5. Metadatos adicionales
                $documento->metadatos_archivo = json_encode([
                    'validacion' => $validacion,
                    'procesamiento' => $procesamiento['metadatos'],
                    'conversiones' => $procesamiento['conversiones'] ?? []
                ]);
            }
            
            $documento->save();

            // REQ-VN-003: Validar metadatos obligatorios
            $validacionMetadatos = $this->businessRules->validarMetadatosObligatorios($documento, 'documento');
            if (!empty($validacionMetadatos['advertencias'])) {
                session()->flash('metadata_warnings', $validacionMetadatos['advertencias']);
            }

            // REQ-VN-004: Validar integridad referencial
            $validacionIntegridad = $this->businessRules->validarIntegridadReferencial($documento, 'create');
            if (!$validacionIntegridad['valido']) {
                throw new \Exception('Errores de integridad: ' . implode(', ', $validacionIntegridad['errores']));
            }
            
            DB::commit();
            
            $mensaje = 'Documento creado exitosamente.';
            if (!empty($validacion['advertencias'] ?? [])) {
                $mensaje .= ' Nota: ' . implode(', ', $validacion['advertencias']);
            }
            
            return redirect()->route('admin.documentos.index')
                           ->with('success', $mensaje);
                           
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Error al crear documento: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Documento $documento)
    {
        $documento->load([
            'expediente.serie',
            'expediente.subserie', 
            'tipologia',
            'usuarioCreador',
            'usuarioModificador',
            'versiones',
            'firmas.usuario'
        ]);

        return Inertia::render('admin/documentos/show', [
            'documento' => [
                ...$documento->toArray(),
                'estadisticas' => $documento->getEstadisticas(),
                'puede_editar' => $this->puedeEditar($documento),
                'puede_eliminar' => $this->puedeEliminar($documento),
                'archivo_existe' => $documento->existe(),
                'url_descarga' => $documento->getUrlDescarga(),
                'integridad_verificada' => $documento->verificarIntegridad(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Documento $documento)
    {
        $documento->load(['expediente', 'tipologia']);
        
        $opciones = [
            'expedientes' => Expediente::with(['serie', 'subserie'])
                                     ->select('id', 'codigo', 'nombre', 'serie_id', 'subserie_id')
                                     ->get(),
            'tipologias' => TipologiaDocumental::where('activo', true)
                                              ->select('id', 'nombre', 'categoria', 'formato_archivo')
                                              ->get(),
            'estados' => [
                ['value' => Documento::ESTADO_BORRADOR, 'label' => 'Borrador'],
                ['value' => Documento::ESTADO_PENDIENTE, 'label' => 'Pendiente'],
                ['value' => Documento::ESTADO_APROBADO, 'label' => 'Aprobado'],
                ['value' => Documento::ESTADO_ACTIVO, 'label' => 'Activo'],
                ['value' => Documento::ESTADO_ARCHIVADO, 'label' => 'Archivado'],
            ],
            'tipos_soporte' => [
                ['value' => Documento::SOPORTE_ELECTRONICO, 'label' => 'Electrónico'],
                ['value' => Documento::SOPORTE_FISICO, 'label' => 'Físico'],
                ['value' => Documento::SOPORTE_HIBRIDO, 'label' => 'Híbrido'],
            ],
            'confidencialidad' => [
                ['value' => Documento::CONFIDENCIALIDAD_PUBLICA, 'label' => 'Pública'],
                ['value' => Documento::CONFIDENCIALIDAD_INTERNA, 'label' => 'Interna'],
                ['value' => Documento::CONFIDENCIALIDAD_CONFIDENCIAL, 'label' => 'Confidencial'],
                ['value' => Documento::CONFIDENCIALIDAD_RESERVADA, 'label' => 'Reservada'],
                ['value' => Documento::CONFIDENCIALIDAD_CLASIFICADA, 'label' => 'Clasificada'],
            ],
        ];

        return Inertia::render('admin/documentos/edit', [
            'documento' => $documento,
            'opciones' => $opciones
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Documento $documento)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'expediente_id' => 'required|exists:expedientes,id',
            'tipologia_id' => 'nullable|exists:tipologias_documentales,id',
            'tipo_documental' => 'nullable|string|max:100',
            'tipo_soporte' => 'required|in:' . implode(',', [
                Documento::SOPORTE_ELECTRONICO,
                Documento::SOPORTE_FISICO,
                Documento::SOPORTE_HIBRIDO
            ]),
            'estado' => 'required|in:' . implode(',', [
                Documento::ESTADO_BORRADOR,
                Documento::ESTADO_PENDIENTE,
                Documento::ESTADO_APROBADO,
                Documento::ESTADO_ACTIVO,
                Documento::ESTADO_ARCHIVADO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Documento::CONFIDENCIALIDAD_PUBLICA,
                Documento::CONFIDENCIALIDAD_INTERNA,
                Documento::CONFIDENCIALIDAD_CONFIDENCIAL,
                Documento::CONFIDENCIALIDAD_RESERVADA,
                Documento::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'numero_folios' => 'nullable|integer|min:1',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $documento->fill($request->all());
        $documento->usuario_modificador_id = auth()->id();
        $documento->save();

        return redirect()->route('admin.documentos.index')
                       ->with('success', 'Documento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Documento $documento)
    {
        if (!$this->puedeEliminar($documento)) {
            return redirect()->back()
                           ->withErrors(['error' => 'No tienes permisos para eliminar este documento.']);
        }

        $documento->delete();

        return redirect()->route('admin.documentos.index')
                       ->with('success', 'Documento eliminado exitosamente.');
    }

    /**
     * REQ-CP-015: Subida masiva de documentos
     */
    public function uploadMasivo()
    {
        return Inertia::render('admin/documentos/upload-masivo');
    }

    /**
     * REQ-CP-015: Procesar subida masiva mejorada con colas
     */
    public function procesarSubidaMasiva(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array|min:1|max:100', // Incrementado límite
            'archivos.*' => 'file|max:' . (2048 * 1024), // 2GB máximo
            'expediente_id' => 'required|exists:expedientes,id',
            'configuracion' => 'required|array',
            'configuracion.procesamiento_automatico' => 'boolean',
            'configuracion.usar_colas' => 'boolean',
            'configuracion.prioridad' => 'nullable|in:low,normal,high',
        ]);

        $resultados = [
            'documentos_creados' => [],
            'errores_validacion' => [],
            'procesamiento_cola' => 0,
            'procesamiento_inmediato' => 0
        ];

        $usarColas = $request->input('configuracion.usar_colas', true);
        $procesamientoAutomatico = $request->input('configuracion.procesamiento_automatico', true);
        $prioridad = $request->input('configuracion.prioridad', 'normal');

        DB::beginTransaction();
        
        try {
            foreach ($request->file('archivos') as $index => $archivo) {
                try {
                    // 1. Validación previa del archivo
                    $validacion = $this->documentProcessor->validarArchivo(
                        $archivo,
                        $request->input('configuracion.tipologia_id')
                    );

                    if (!$validacion['valido']) {
                        $resultados['errores_validacion'][] = [
                            'archivo' => $archivo->getClientOriginalName(),
                            'errores' => $validacion['errores']
                        ];
                        continue;
                    }

                    // 2. Crear documento básico
                    $documento = $this->crearDocumentoBasico($archivo, $request->all());
                    
                    if ($procesamientoAutomatico) {
                        if ($usarColas) {
                            // 3. Procesamiento en cola (recomendado para archivos grandes)
                            $this->despacharProcesamientoEnCola($documento, $request, $prioridad);
                            $resultados['procesamiento_cola']++;
                        } else {
                            // 4. Procesamiento inmediato (solo para archivos pequeños)
                            $this->procesarArchivoInmediato($documento, $archivo, $request);
                            $resultados['procesamiento_inmediato']++;
                        }
                    }

                    $resultados['documentos_creados'][] = [
                        'id' => $documento->id,
                        'codigo' => $documento->codigo,
                        'nombre' => $documento->nombre,
                        'archivo' => $archivo->getClientOriginalName(),
                        'estado_procesamiento' => $documento->estado_procesamiento
                    ];

                } catch (\Exception $e) {
                    $resultados['errores_validacion'][] = [
                        'archivo' => $archivo->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            $mensaje = sprintf(
                "Subida masiva completada: %d documentos creados, %d en cola de procesamiento, %d procesados inmediatamente, %d errores.",
                count($resultados['documentos_creados']),
                $resultados['procesamiento_cola'],
                $resultados['procesamiento_inmediato'],
                count($resultados['errores_validacion'])
            );
            
            return response()->json([
                'success' => true,
                'mensaje' => $mensaje,
                'resultados' => $resultados,
                'estadisticas' => [
                    'total_archivos' => count($request->file('archivos')),
                    'exitosos' => count($resultados['documentos_creados']),
                    'errores' => count($resultados['errores_validacion']),
                    'en_cola' => $resultados['procesamiento_cola']
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'mensaje' => 'Error en subida masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * REQ-CP-012: Crear nueva versión de documento
     */
    public function crearVersion(Request $request, Documento $documento)
    {
        $request->validate([
            'archivo' => 'required|file|max:' . (max(self::TAMAÑOS_MAXIMOS) * 1024),
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            $archivo = $request->file('archivo');
            $rutaArchivo = $this->guardarArchivo($archivo, $documento, true);
            
            $nuevaVersion = $documento->crearNuevaVersion($rutaArchivo, $request->observaciones);
            
            return response()->json([
                'success' => true,
                'mensaje' => 'Nueva versión creada exitosamente.',
                'version' => $nuevaVersion->version
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear versión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * REQ-CP-007: Validación de archivo en tiempo real (API)
     */
    public function validarArchivoApi(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file',
            'tipologia_id' => 'nullable|exists:tipologias_documentales,id'
        ]);

        try {
            $archivo = $request->file('archivo');
            $validacion = $this->documentProcessor->validarArchivo(
                $archivo,
                $request->tipologia_id
            );

            return response()->json([
                'success' => $validacion['valido'],
                'validacion' => $validacion,
                'archivo_info' => [
                    'nombre' => $archivo->getClientOriginalName(),
                    'tamaño' => $archivo->getSize(),
                    'tipo' => $archivo->getMimeType(),
                    'extension' => $archivo->getClientOriginalExtension()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error validando archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * REQ-CP-002: Obtener configuración de formatos multimedia
     */
    public function getConfiguracionFormatos()
    {
        return response()->json([
            'formatos_soportados' => DocumentProcessingService::getFormatosSoportados(),
            'configuracion_multimedia' => $this->documentProcessor->getConfiguracionMultimedia(),
            'tamaños_maximos' => self::TAMAÑOS_MAXIMOS
        ]);
    }

    /**
     * Métodos auxiliares
     */
    
    /**
     * REQ-CP-007: Validar formato de archivo
     */
    private function validarFormato($formato, $tipologiaId = null)
    {
        $todosLosFormatos = collect(self::FORMATOS_PERMITIDOS)->flatten()->toArray();
        
        if (!in_array($formato, $todosLosFormatos)) {
            throw new \Exception("Formato de archivo no permitido: {$formato}");
        }
        
        // Validar contra tipología si existe
        if ($tipologiaId) {
            $tipologia = TipologiaDocumental::find($tipologiaId);
            if ($tipologia && !empty($tipologia->formato_archivo)) {
                if (!in_array($formato, $tipologia->formato_archivo)) {
                    throw new \Exception("El formato {$formato} no está permitido para la tipología {$tipologia->nombre}");
                }
            }
        }
    }

    /**
     * Validar tamaño de archivo según tipo
     */
    private function validarTamaño($archivo, $formato)
    {
        $categoriaFormato = $this->getCategoriaFormato($formato);
        $tamañoMaximo = self::TAMAÑOS_MAXIMOS[$categoriaFormato] * 1024 * 1024; // Convertir a bytes
        
        if ($archivo->getSize() > $tamañoMaximo) {
            throw new \Exception("El archivo excede el tamaño máximo permitido para {$categoriaFormato}: " . self::TAMAÑOS_MAXIMOS[$categoriaFormato] . "MB");
        }
    }

    /**
     * Obtener categoría de formato
     */
    private function getCategoriaFormato($formato)
    {
        foreach (self::FORMATOS_PERMITIDOS as $categoria => $formatos) {
            if (in_array($formato, $formatos)) {
                return $categoria;
            }
        }
        return 'texto'; // Default
    }

    /**
     * Guardar archivo en storage
     */
    private function guardarArchivo($archivo, $documento, $esVersion = false)
    {
        $año = now()->format('Y');
        $mes = now()->format('m');
        $expediente = $documento->expediente_id ?? 'sin_expediente';
        
        $directorio = "documentos/{$año}/{$mes}/expediente_{$expediente}";
        
        if ($esVersion) {
            $directorio .= '/versiones';
        }
        
        return $archivo->store($directorio, 'public');
    }

    /**
     * Verificar si requiere miniatura
     */
    private function requiereMiniatura($formato)
    {
        $formatosConMiniatura = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'];
        return in_array(strtolower($formato), $formatosConMiniatura);
    }

    /**
     * Verificar permisos de edición
     */
    private function puedeEditar(Documento $documento)
    {
        $user = auth()->user();
        return $user->isAdmin() || 
               $documento->usuario_creador_id === $user->id ||
               $user->hasPermission('documentos.editar');
    }

    /**
     * Verificar permisos de eliminación
     */
    private function puedeEliminar(Documento $documento)
    {
        $user = auth()->user();
        
        // No se puede eliminar si tiene versiones
        if ($documento->versiones()->count() > 0) {
            return false;
        }
        
        return $user->isAdmin() || 
               ($documento->usuario_creador_id === $user->id && $documento->estado === Documento::ESTADO_BORRADOR) ||
               $user->hasPermission('documentos.eliminar');
    }

    /**
     * Obtener formatos disponibles para filtros
     */
    private function getFormatosDisponibles()
    {
        return Documento::select('formato')
                       ->distinct()
                       ->whereNotNull('formato')
                       ->pluck('formato')
                       ->map(function($formato) {
                           return ['value' => $formato, 'label' => strtoupper($formato)];
                       });
    }

    /**
     * Procesar archivo individual en subida masiva
     */
    private function procesarArchivoMasivo($archivo, $configuracion)
    {
        $formato = strtolower($archivo->getClientOriginalExtension());
        
        // Crear documento
        $documento = new Documento();
        $documento->nombre = $archivo->getClientOriginalName();
        $documento->expediente_id = $configuracion['expediente_id'];
        $documento->tipo_soporte = Documento::SOPORTE_ELECTRONICO;
        $documento->estado = $configuracion['configuracion']['estado_default'] ?? Documento::ESTADO_BORRADOR;
        $documento->confidencialidad = $configuracion['configuracion']['confidencialidad_default'] ?? Documento::CONFIDENCIALIDAD_INTERNA;
        $documento->usuario_creador_id = auth()->id();
        
        // Validar y procesar archivo
        $this->validarFormato($formato);
        $this->validarTamaño($archivo, $formato);
        
        $documento->formato = $formato;
        $documento->tamaño = $archivo->getSize();
        $documento->ruta_archivo = $this->guardarArchivo($archivo, $documento);
        
        $documento->save();
        
        return $documento;
    }

    /**
     * REQ-CP-012: Crear documento básico para procesamiento masivo
     */
    private function crearDocumentoBasico($archivo, $data): Documento
    {
        $documento = new Documento();
        $documento->nombre = $archivo->getClientOriginalName();
        $documento->expediente_id = $data['expediente_id'];
        $documento->tipologia_id = $data['configuracion']['tipologia_id'] ?? null;
        $documento->tipo_soporte = Documento::SOPORTE_ELECTRONICO;
        $documento->estado = $data['configuracion']['estado_default'] ?? Documento::ESTADO_BORRADOR;
        $documento->confidencialidad = $data['configuracion']['confidencialidad_default'] ?? Documento::CONFIDENCIALIDAD_INTERNA;
        $documento->usuario_creador_id = auth()->id();
        
        // Información del archivo
        $documento->formato = strtolower($archivo->getClientOriginalExtension());
        $documento->tamaño = $archivo->getSize();
        
        // Guardar archivo inmediatamente
        $documento->ruta_archivo = $this->guardarArchivo($archivo, $documento);
        
        // Estado de procesamiento inicial
        $documento->estado_procesamiento = Documento::PROCESAMIENTO_PENDIENTE;
        
        // Configuración de procesamiento
        $documento->configuracion_procesamiento = $data['configuracion']['procesamiento'] ?? [
            'ocr' => true,
            'convertir' => true,
            'generar_miniatura' => true
        ];
        
        $documento->save();
        
        return $documento;
    }

    /**
     * REQ-CP-012: Despachar procesamiento en cola con prioridades
     */
    private function despacharProcesamientoEnCola(Documento $documento, Request $request, string $prioridad): void
    {
        $opciones = $request->input('configuracion.procesamiento', []);
        
        // Mapear prioridad a cola específica
        $cola = match($prioridad) {
            'high' => 'high-priority',
            'low' => 'low-priority',
            default => 'default'
        };
        
        // Configurar delay según prioridad
        $delay = match($prioridad) {
            'high' => 0, // Inmediato
            'low' => 300, // 5 minutos
            default => 60 // 1 minuto
        };
        
        ProcessDocumentJob::dispatch($documento->id, $opciones, auth()->id())
            ->onQueue($cola)
            ->delay(now()->addSeconds($delay));
    }

    /**
     * REQ-CP-028: Procesamiento inmediato para archivos pequeños
     */
    private function procesarArchivoInmediato(Documento $documento, $archivo, Request $request): void
    {
        try {
            $documento->iniciarProcesamiento();
            
            $procesamiento = $this->documentProcessor->procesarArchivo(
                $archivo,
                $documento,
                $request->input('configuracion.procesamiento', [])
            );
            
            if ($procesamiento['success']) {
                // Actualizar con resultados
                $documento->update([
                    'hash_sha256' => $procesamiento['metadatos']['hash_sha256'] ?? null,
                    'contenido_ocr' => $procesamiento['ocr_texto'],
                    'ruta_miniatura' => $procesamiento['miniatura'],
                    'metadatos_archivo' => json_encode($procesamiento['metadatos'])
                ]);
                
                $documento->marcarProcesamientoCompletado();
            } else {
                $documento->marcarErrorProcesamiento('Error en procesamiento inmediato');
            }
            
        } catch (\Exception $e) {
            $documento->marcarErrorProcesamiento($e->getMessage());
        }
    }

    /**
     * REQ-CP-012: Obtener estado de procesamiento masivo
     */
    public function estadoProcesamientoMasivo(Request $request)
    {
        $request->validate([
            'documento_ids' => 'required|array',
            'documento_ids.*' => 'integer|exists:documentos,id'
        ]);

        $documentos = Documento::whereIn('id', $request->documento_ids)
            ->select('id', 'codigo', 'nombre', 'estado_procesamiento', 'error_procesamiento', 'fecha_procesamiento')
            ->get();

        $estadisticas = [
            'pendiente' => $documentos->where('estado_procesamiento', Documento::PROCESAMIENTO_PENDIENTE)->count(),
            'procesando' => $documentos->where('estado_procesamiento', Documento::PROCESAMIENTO_PROCESANDO)->count(),
            'completado' => $documentos->where('estado_procesamiento', Documento::PROCESAMIENTO_COMPLETADO)->count(),
            'error' => $documentos->where('estado_procesamiento', Documento::PROCESAMIENTO_ERROR)->count(),
            'fallido' => $documentos->where('estado_procesamiento', Documento::PROCESAMIENTO_FALLIDO)->count(),
        ];

        return response()->json([
            'documentos' => $documentos,
            'estadisticas' => $estadisticas,
            'completado' => $estadisticas['completado'] + $estadisticas['error'] + $estadisticas['fallido'],
            'total' => $documentos->count()
        ]);
    }

    /**
     * REQ-CP-012: Reenviar documentos con errores de procesamiento
     */
    public function reprocesarDocumentos(Request $request)
    {
        $request->validate([
            'documento_ids' => 'required|array',
            'documento_ids.*' => 'integer|exists:documentos,id',
            'prioridad' => 'nullable|in:low,normal,high'
        ]);

        $documentos = Documento::whereIn('id', $request->documento_ids)
            ->where('estado_procesamiento', 'error')
            ->get();

        $reprocesados = 0;
        foreach ($documentos as $documento) {
            try {
                $documento->update(['estado_procesamiento' => Documento::PROCESAMIENTO_PENDIENTE]);
                
                ProcessDocumentJob::dispatch(
                    $documento->id, 
                    $documento->getConfiguracionProcesamiento(),
                    auth()->id()
                )->onQueue($request->input('prioridad', 'normal') . '-priority');
                
                $reprocesados++;
            } catch (\Exception $e) {
                // Log error pero continúa
                \Log::error("Error reprocesando documento {$documento->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'mensaje' => "Se enviaron {$reprocesados} documentos para reprocesamiento.",
            'reprocesados' => $reprocesados
        ]);
    }
}
