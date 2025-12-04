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
        $query = Documento::with(['expediente', 'tipologia', 'usuarioCreador'])->orderBy('created_at', 'desc');

        // Filtros - usando campos correctos de la BD
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_documento', 'LIKE', "%{$search}%")
                  ->orWhere('titulo', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('expediente_id')) {
            $query->where('expediente_id', $request->expediente_id);
        }

        if ($request->filled('formato')) {
            $query->where('formato', $request->formato);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === 'true' || $request->activo === '1');
        }

        // Paginación
        $documentos = $query->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => Documento::count(),
            'activos' => Documento::where('activo', true)->count(),
            'borradores' => Documento::where('activo', false)->count(),
            'procesados' => Documento::where('estado_procesamiento', 'completado')->count(),
        ];

        // Obtener expedientes y tipologías para filtros
        $expedientes = Expediente::select('id', 'codigo', 'titulo')->get();
        $tipologias = TipologiaDocumental::where('activa', true)->select('id', 'nombre', 'categoria')->get();

        return Inertia::render('admin/documentos/index', [
            'documentos' => $documentos,
            'stats' => $estadisticas,
            'expedientes' => $expedientes,
            'tipologias' => $tipologias,
            'filtros' => $request->only(['search', 'expediente_id', 'formato', 'activo'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $opciones = [
            'expedientes' => Expediente::with(['serie', 'subserie'])
                                     ->where('cerrado', false)
                                     ->select('id', 'codigo', 'titulo', 'serie_id', 'subserie_id')
                                     ->get(),
            'tipologias' => TipologiaDocumental::where('activa', true)
                                              ->select('id', 'nombre', 'categoria', 'formatos_aceptados')
                                              ->get(),
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
            'tipo_soporte' => 'nullable|string',
            'activo' => 'nullable|boolean',
            'confidencialidad' => 'nullable|string',
            'archivo' => 'required|file|max:' . (2048 * 1024), // 2GB máximo - REQUERIDO
            'procesamiento' => 'nullable|array',
        ]);

        DB::beginTransaction();
        
        try {
            // Validar que se haya subido un archivo
            if (!$request->hasFile('archivo')) {
                return redirect()->back()
                               ->withInput()
                               ->withErrors(['archivo' => 'El archivo es requerido.']);
            }

            $documento = new Documento();
            // Generar código único
            $year = date('Y');
            $lastDoc = Documento::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
            $nextNum = $lastDoc ? (intval(substr($lastDoc->codigo_documento ?? '0000', -4)) + 1) : 1;
            $documento->codigo_documento = 'DOC-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            
            // Mapear campos del formulario a campos de la BD
            $documento->titulo = $request->nombre;
            $documento->descripcion = $request->descripcion ?? '';
            $documento->expediente_id = $request->expediente_id;
            $documento->tipologia_documental_id = $request->tipologia_id ?: 1; // Usar tipología por defecto
            $documento->productor_id = auth()->id(); // CAMPO REQUERIDO
            $documento->created_by = auth()->id();
            $documento->activo = $request->activo ?? true;
            $documento->version_mayor = 1;
            $documento->version_menor = 0;
            $documento->fecha_documento = now();
            $documento->fecha_captura = now();
            
            // Procesar archivo (REQUERIDO)
            $archivo = $request->file('archivo');
            $documento->formato = strtolower($archivo->getClientOriginalExtension());
            $documento->tamano_bytes = $archivo->getSize();
            $documento->nombre_archivo = $archivo->getClientOriginalName();
            
            // Guardar archivo
            $path = $archivo->store('documentos/' . date('Y/m'), 'public');
            // El path se guarda en metadatos por ahora ya que no hay columna ruta_archivo
            $documento->metadatos_archivo = json_encode(['ruta' => $path]);
            $documento->hash_sha256 = hash_file('sha256', $archivo->getRealPath());
            
            $documento->save();
            
            DB::commit();
            
            return redirect()->route('admin.documentos.index')
                           ->with('success', 'Documento creado exitosamente.');
                           
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creando documento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
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
            'usuarioModificador'
        ]);

        // Generar URL directa del archivo
        $urlDirecta = null;
        if ($documento->metadatos_archivo) {
            $metadatos = json_decode($documento->metadatos_archivo, true);
            $ruta = $metadatos['ruta'] ?? null;
            if ($ruta) {
                // Intentar primero con storage público
                $urlDirecta = asset('storage/' . $ruta);
                
                // Si no funciona, usar URL directa al public
                $publicPath = str_replace('storage/app/public/', '', $ruta);
                $urlAlternativa = asset($publicPath);
                
                // Verificar si el archivo existe en public
                if (file_exists(public_path($publicPath))) {
                    $urlDirecta = $urlAlternativa;
                }
            }
        }

        return Inertia::render('admin/documentos/show', [
            'documento' => [
                ...$documento->toArray(),
                'estadisticas' => $documento->getEstadisticas(),
                'puede_editar' => $this->puedeEditar($documento),
                'puede_eliminar' => $this->puedeEliminar($documento),
                'archivo_existe' => $documento->existe(),
                'url_descarga' => $urlDirecta ?: $documento->getUrlDescarga(),
                'url_directa' => $urlDirecta,
                'integridad_verificada' => $documento->verificarIntegridad(),
            ]
        ]);
    }

    /**
     * Preview del documento
     */
    public function preview(Documento $documento)
    {
        if (!$documento->existe()) {
            abort(404, 'Archivo no encontrado');
        }

        $metadatos = json_decode($documento->metadatos_archivo, true);
        $ruta = $metadatos['ruta'] ?? null;
        
        if (!$ruta) {
            abort(404, 'Ruta de archivo no encontrada');
        }

        $path = storage_path('app/public/' . $ruta);
        
        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en el sistema');
        }

        $mimeType = $this->getMimeType($documento->formato);
        
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $documento->nombre_archivo . '"',
            'X-Frame-Options' => 'SAMEORIGIN'
        ]);
    }

    /**
     * Descargar documento
     */
    public function descargar(Documento $documento)
    {
        if (!$documento->existe()) {
            abort(404, 'Archivo no encontrado');
        }

        $path = storage_path('app/public/' . json_decode($documento->metadatos_archivo, true)['ruta']);
        
        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en el sistema');
        }

        return response()->download($path, $documento->nombre_archivo);
    }

    /**
     * Obtener MIME type por extensión
     */
    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
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
            'tipologias' => TipologiaDocumental::where('activa', true)
                                              ->select('id', 'nombre', 'categoria', 'formatos_aceptados')
                                              ->get(),
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
            'activo' => 'nullable|boolean',
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
        
        // Por ahora permitir eliminar (versiones no implementadas)
        // if ($documento->versiones()->count() > 0) {
        //     return false;
        // }
        
        return $user->isAdmin() || 
               ($documento->usuario_creador_id === $user->id && !$documento->activo) ||
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
        $documento->activo = $configuracion['configuracion']['activo_default'] ?? false;
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
        $documento->activo = $data['configuracion']['activo_default'] ?? false;
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
