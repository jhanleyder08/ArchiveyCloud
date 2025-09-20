<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\TipologiaDocumental;
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
            'formatos_permitidos' => self::FORMATOS_PERMITIDOS,
            'tamaños_maximos' => self::TAMAÑOS_MAXIMOS,
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
            'archivo' => 'nullable|file|max:' . (max(self::TAMAÑOS_MAXIMOS) * 1024), // Max en KB
        ]);

        DB::beginTransaction();
        
        try {
            $documento = new Documento();
            $documento->fill($request->except(['archivo']));
            $documento->usuario_creador_id = auth()->id();
            
            // REQ-CP-007: Procesar archivo si se subió
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $formato = strtolower($archivo->getClientOriginalExtension());
                
                // Validar formato
                $this->validarFormato($formato, $request->tipologia_id);
                
                // Validar tamaño según tipo
                $this->validarTamaño($archivo, $formato);
                
                // Guardar archivo
                $rutaArchivo = $this->guardarArchivo($archivo, $documento);
                
                $documento->formato = $formato;
                $documento->tamaño = $archivo->getSize();
                $documento->ruta_archivo = $rutaArchivo;
            }
            
            $documento->save();
            
            // Generar miniatura si es necesario
            if ($documento->ruta_archivo && $this->requiereMiniatura($documento->formato)) {
                $documento->generarMiniatura();
            }
            
            DB::commit();
            
            return redirect()->route('admin.documentos.index')
                           ->with('success', 'Documento creado exitosamente.');
                           
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
     * REQ-CP-015: Procesar subida masiva
     */
    public function procesarSubidaMasiva(Request $request)
    {
        $request->validate([
            'archivos' => 'required|array|min:1|max:50',
            'archivos.*' => 'file|max:' . (max(self::TAMAÑOS_MAXIMOS) * 1024),
            'expediente_id' => 'required|exists:expedientes,id',
            'configuracion' => 'required|array',
        ]);

        $resultados = [
            'exitosos' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        DB::beginTransaction();
        
        try {
            foreach ($request->file('archivos') as $archivo) {
                try {
                    $documento = $this->procesarArchivoMasivo($archivo, $request->all());
                    $resultados['exitosos']++;
                    $resultados['detalles'][] = [
                        'archivo' => $archivo->getClientOriginalName(),
                        'estado' => 'exitoso',
                        'codigo' => $documento->codigo
                    ];
                } catch (\Exception $e) {
                    $resultados['errores']++;
                    $resultados['detalles'][] = [
                        'archivo' => $archivo->getClientOriginalName(),
                        'estado' => 'error',
                        'mensaje' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'mensaje' => "Procesados {$resultados['exitosos']} archivos exitosamente, {$resultados['errores']} errores.",
                'resultados' => $resultados
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
}
