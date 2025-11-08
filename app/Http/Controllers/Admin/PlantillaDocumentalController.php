<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantillaDocumental;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\User;
use App\Models\Documento;
use App\Services\PlantillaEditorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PlantillaDocumentalController extends Controller
{
    protected PlantillaEditorService $editorService;

    public function __construct(PlantillaEditorService $editorService)
    {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->editorService = $editorService;
    }

    /**
     * Dashboard principal de plantillas
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['categoria', 'estado', 'es_publica', 'serie_documental_id', 'buscar']);
        
        $plantillas = PlantillaDocumental::query()
            ->with(['usuarioCreador:id,name', 'serieDocumental:id,codigo,nombre'])
            ->when($filtros['categoria'] ?? null, function ($query, $categoria) {
                $query->where('categoria', $categoria);
            })
            ->when($filtros['estado'] ?? null, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->when(isset($filtros['es_publica']), function ($query) use ($filtros) {
                $query->where('es_publica', $filtros['es_publica']);
            })
            ->when($filtros['serie_documental_id'] ?? null, function ($query, $serieId) {
                $query->where('serie_documental_id', $serieId);
            })
            ->when($filtros['buscar'] ?? null, function ($query, $termino) {
                $query->buscar($termino);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        try {
            $estadisticas = PlantillaDocumental::obtenerEstadisticas();
        } catch (\Exception $e) {
            \Log::error('Error getting plantilla estadisticas: ' . $e->getMessage());
            $estadisticas = null;
        }
        
        // Asegurar que estadisticas tenga estructura completa y válida
        $estadisticasDefault = [
            'total' => 0,
            'activas' => 0,
            'borradores' => 0,
            'publicas' => 0,
            'por_categoria' => [],
            'mas_usadas' => []
        ];
        
        if (!$estadisticas || !is_array($estadisticas)) {
            $estadisticas = $estadisticasDefault;
        } else {
            // Asegurar que todas las claves existen
            $estadisticas = array_merge($estadisticasDefault, $estadisticas);
        }
        
        $series = SerieDocumental::with('subseries')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('admin/plantillas/index', [
            'plantillas' => $plantillas,
            'estadisticas' => $estadisticas,
            'series' => $series,
            'filtros' => $filtros,
            'categorias' => [
                'memorando' => 'Memorando',
                'oficio' => 'Oficio',
                'resolucion' => 'Resolución',
                'acta' => 'Acta',
                'informe' => 'Informe',
                'circular' => 'Circular',
                'comunicacion' => 'Comunicación',
                'otro' => 'Otro'
            ],
            'estados' => [
                'borrador' => 'Borrador',
                'revision' => 'En Revisión',
                'activa' => 'Activa',
                'archivada' => 'Archivada',
                'obsoleta' => 'Obsoleta'
            ]
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $series = SerieDocumental::with('subseries')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('admin/plantillas/create', [
            'series' => $series,
            'categorias' => [
                'memorando' => 'Memorando',
                'oficio' => 'Oficio',
                'resolucion' => 'Resolución',
                'acta' => 'Acta',
                'informe' => 'Informe',
                'circular' => 'Circular',
                'comunicacion' => 'Comunicación',
                'otro' => 'Otro'
            ]
        ]);
    }

    /**
     * Almacenar nueva plantilla
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Store method called', ['request_data' => $request->all()]);
            
            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string|max:1000',
                'categoria' => 'required|in:memorando,oficio,resolucion,acta,informe,circular,comunicacion,otro',
                'tipo_documento' => 'nullable|string|max:100',
                'serie_documental_id' => 'nullable|string',
                'subserie_documental_id' => 'nullable|string',
                'contenido_html' => 'nullable|string',
                'campos_variables' => 'nullable|array',
                'campos_variables.*.nombre' => 'required|string|max:50',
                'campos_variables.*.tipo' => 'required|in:texto,numero,fecha,email,url,textarea,select,checkbox',
                'campos_variables.*.etiqueta' => 'required|string|max:100',
                'campos_variables.*.requerido' => 'boolean',
                'campos_variables.*.valor_defecto' => 'nullable|string',
                'metadatos_predefinidos' => 'nullable|array',
                'configuracion_formato' => 'nullable|array',
                'es_publica' => 'boolean',
                'tags' => 'nullable|array',
                'observaciones' => 'nullable|string|max:500'
            ]);

            \Log::info('Validation passed', ['validated' => $validated]);

            // Limpiar valores vacíos y convertir a null si están vacíos
            if (empty($validated['serie_documental_id']) || $validated['serie_documental_id'] === '' || $validated['serie_documental_id'] === 'null') {
                $validated['serie_documental_id'] = null;
            } else {
                // Convertir a entero y validar existencia
                $serieId = (int)$validated['serie_documental_id'];
                if ($serieId > 0) {
                    // Validar que existe antes de asignar
                    if (!\App\Models\SerieDocumental::where('id', $serieId)->exists()) {
                        return back()
                            ->withInput()
                            ->withErrors(['serie_documental_id' => 'La serie documental seleccionada no existe.']);
                    }
                    $validated['serie_documental_id'] = $serieId;
                } else {
                    $validated['serie_documental_id'] = null;
                }
            }

            if (empty($validated['subserie_documental_id']) || $validated['subserie_documental_id'] === '' || $validated['subserie_documental_id'] === 'null') {
                $validated['subserie_documental_id'] = null;
            } else {
                // Convertir a entero y validar existencia
                $subserieId = (int)$validated['subserie_documental_id'];
                if ($subserieId > 0) {
                    // Validar que existe antes de asignar
                    if (!\App\Models\SubserieDocumental::where('id', $subserieId)->exists()) {
                        return back()
                            ->withInput()
                            ->withErrors(['subserie_documental_id' => 'La subserie documental seleccionada no existe.']);
                    }
                    $validated['subserie_documental_id'] = $subserieId;
                } else {
                    $validated['subserie_documental_id'] = null;
                }
            }

            DB::beginTransaction();
            try {
                // Asignar valores por defecto antes de crear
                $validated['usuario_creador_id'] = Auth::id();
                $validated['estado'] = PlantillaDocumental::ESTADO_BORRADOR;
                $validated['version'] = 1.0;
                
                // Generar código único antes de crear (sin usar ID)
                $prefijo = 'PLT';
                $categoria = strtoupper(substr($validated['categoria'], 0, 3));
                $ultimoId = PlantillaDocumental::withTrashed()->max('id') ?? 0;
                
                // Asegurar que el código sea único
                do {
                    $numero = str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);
                    $codigo = "{$prefijo}-{$categoria}-{$numero}-V1";
                    $ultimoId++;
                } while (PlantillaDocumental::withTrashed()->where('codigo', $codigo)->exists());
                
                $validated['codigo'] = $codigo;

                \Log::info('Creando plantilla con datos:', $validated);
                
                $plantilla = PlantillaDocumental::create($validated);

                DB::commit();

                \Log::info('Plantilla creada exitosamente:', ['id' => $plantilla->id, 'codigo' => $plantilla->codigo]);

                return redirect()->route('admin.plantillas.index')
                    ->with('success', 'Plantilla documental creada exitosamente.');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error al crear plantilla:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $validated ?? []
                ]);
                
                return back()
                    ->withInput()
                    ->with('error', 'Error al crear la plantilla: ' . $e->getMessage());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error:', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Unexpected error in store method:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()
                ->withInput()
                ->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar plantilla específica
     */
    public function show(PlantillaDocumental $plantilla)
    {
        $plantilla->load([
            'usuarioCreador:id,name,email',
            'serieDocumental:id,codigo,nombre',
            'subserieDocumental:id,codigo,nombre',
            'plantillaPadre:id,nombre,version',
            'versiones' => function ($query) {
                $query->orderBy('version', 'desc');
            }
        ]);

        $documentosGenerados = $plantilla->documentosGenerados()
            ->with(['expediente:id,numero_expediente,titulo', 'usuarioCreador:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $estadisticasUso = [
            'documentos_generados' => $plantilla->documentosGenerados()->count(),
            'documentos_ultimo_mes' => $plantilla->documentosGenerados()
                ->where('created_at', '>=', Carbon::now()->subMonth())
                ->count(),
            'usuarios_utilizan' => $plantilla->documentosGenerados()
                ->distinct('usuario_creador_id')
                ->count(),
            'version_actual' => $plantilla->version,
            'es_version_reciente' => $plantilla->esVersionMasReciente()
        ];

        return Inertia::render('admin/plantillas/show', [
            'plantilla' => $plantilla,
            'documentos_generados' => $documentosGenerados,
            'estadisticas_uso' => $estadisticasUso,
            'puede_editar' => $plantilla->puedeSerEditadaPor(auth()->user()),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(PlantillaDocumental $plantilla)
    {
        $this->authorize('update', $plantilla);

        $plantilla->load(['serieDocumental', 'subserieDocumental']);
        
        $series = SerieDocumental::with('subseries')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('admin/plantillas/edit', [
            'plantilla' => $plantilla,
            'series' => $series,
            'categorias' => [
                'memorando' => 'Memorando',
                'oficio' => 'Oficio',
                'resolucion' => 'Resolución',
                'acta' => 'Acta',
                'informe' => 'Informe',
                'circular' => 'Circular',
                'comunicacion' => 'Comunicación',
                'otro' => 'Otro'
            ]
        ]);
    }

    /**
     * Actualizar plantilla
     */
    public function update(Request $request, PlantillaDocumental $plantilla)
    {
        $this->authorize('update', $plantilla);

        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|in:memorando,oficio,resolucion,acta,informe,circular,comunicacion,otro',
            'tipo_documento' => 'nullable|string|max:100',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'subserie_documental_id' => 'nullable|exists:subseries_documentales,id',
            'contenido_html' => 'nullable|string',
            'campos_variables' => 'nullable|array',
            'metadatos_predefinidos' => 'nullable|array',
            'configuracion_formato' => 'nullable|array',
            'es_publica' => 'boolean',
            'tags' => 'nullable|array',
            'observaciones' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $plantilla->update($validated);

            DB::commit();

            return redirect()->route('admin.plantillas.show', $plantilla)
                ->with('success', 'Plantilla actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la plantilla: ' . $e->getMessage());
        }
    }


    /**
     * Cambiar estado de plantilla
     */
    public function cambiarEstado(Request $request, PlantillaDocumental $plantilla)
    {
        $this->authorize('update', $plantilla);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,revision,activa,archivada,obsoleta',
            'observaciones' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $plantilla->update([
                'estado' => $validated['estado'],
                'observaciones' => $validated['observaciones'] ?? $plantilla->observaciones
            ]);

            DB::commit();

            return back()->with('success', 'Estado de la plantilla actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cambiar estado: ' . $e->getMessage());
        }
    }

    /**
     * Generar documento desde plantilla
     */
    public function generarDocumento(Request $request, PlantillaDocumental $plantilla)
    {
        $validated = $request->validate([
            'variables' => 'required|array',
            'expediente_id' => 'nullable|exists:expedientes,id',
            'nombre_documento' => 'required|string|max:200'
        ]);

        DB::beginTransaction();
        try {
            $contenidoProcesado = $plantilla->procesarContenidoConVariables($validated['variables']);

            // Crear archivo HTML temporal
            $nombreArchivo = $validated['nombre_documento'] . '.html';
            $rutaArchivo = 'documentos/generados/' . date('Y/m/') . $nombreArchivo;
            
            Storage::put($rutaArchivo, $contenidoProcesado);

            // Crear registro de documento
            $documento = Documento::create([
                'nombre' => $validated['nombre_documento'],
                'descripcion' => "Documento generado desde plantilla: {$plantilla->nombre}",
                'expediente_id' => $validated['expediente_id'],
                'plantilla_id' => $plantilla->id,
                'tipo_documental' => $plantilla->tipo_documento,
                'formato' => 'html',
                'ruta_archivo' => $rutaArchivo,
                'tamaño' => Storage::size($rutaArchivo),
                'hash_integridad' => hash_file('sha256', Storage::path($rutaArchivo)),
                'usuario_creador_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Documento generado exitosamente.',
                'documento_id' => $documento->id,
                'url_descarga' => route('admin.documentos.download', $documento)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al generar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Previsualizar plantilla con variables
     */
    public function previsualizar(Request $request, PlantillaDocumental $plantilla)
    {
        $validated = $request->validate([
            'variables' => 'array'
        ]);

        $contenidoProcesado = $plantilla->procesarContenidoConVariables($validated['variables'] ?? []);

        return response()->json([
            'contenido' => $contenidoProcesado
        ]);
    }

    /**
     * Duplicar plantilla
     */
    public function duplicar(PlantillaDocumental $plantilla)
    {
        DB::beginTransaction();
        try {
            $nuevaPlantilla = $plantilla->replicate();
            $nuevaPlantilla->nombre = $plantilla->nombre . ' (Copia)';
            $nuevaPlantilla->codigo = null; // Se generará automáticamente
            $nuevaPlantilla->estado = PlantillaDocumental::ESTADO_BORRADOR;
            $nuevaPlantilla->usuario_creador_id = auth()->id();
            $nuevaPlantilla->plantilla_padre_id = null;
            $nuevaPlantilla->version = 1.0;
            $nuevaPlantilla->save();

            DB::commit();

            return redirect()->route('admin.plantillas.edit', $nuevaPlantilla)
                ->with('success', 'Plantilla duplicada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al duplicar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar plantilla
     */
    public function destroy(PlantillaDocumental $plantilla)
    {
        $this->authorize('delete', $plantilla);

        DB::beginTransaction();
        try {
            // Verificar si tiene documentos generados
            if ($plantilla->documentosGenerados()->count() > 0) {
                return back()->with('error', 'No se puede eliminar una plantilla que tiene documentos generados.');
            }

            $plantilla->delete();

            DB::commit();

            return redirect()->route('admin.plantillas.index')
                ->with('success', 'Plantilla eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Obtener subseries por serie (AJAX)
     */
    public function obtenerSubseries(Request $request)
    {
        $serieId = $request->get('serie_id');
        
        $subseries = SubserieDocumental::where('serie_id', $serieId)
            ->select('id', 'codigo', 'nombre')
            ->orderBy('codigo')
            ->get();

        return response()->json($subseries);
    }

    /**
     * Estadísticas de uso de plantillas
     */
    public function estadisticas()
    {
        $estadisticas = PlantillaDocumental::obtenerEstadisticas();
        
        $usoMensual = DB::table('documentos')
            ->join('plantillas_documentales', 'documentos.plantilla_id', '=', 'plantillas_documentales.id')
            ->selectRaw('YEAR(documentos.created_at) as año, MONTH(documentos.created_at) as mes, COUNT(*) as total')
            ->whereNotNull('documentos.plantilla_id')
            ->where('documentos.created_at', '>=', Carbon::now()->subYear())
            ->groupBy('año', 'mes')
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return Inertia::render('admin/plantillas/estadisticas', [
            'estadisticas' => $estadisticas,
            'uso_mensual' => $usoMensual
        ]);
    }

    /**
     * Editor avanzado de plantillas
     */
    public function editor(PlantillaDocumental $plantilla = null)
    {
        $datos = [
            'plantilla' => $plantilla,
            'categorias' => $this->obtenerCategorias(),
            'series' => SerieDocumental::select('id', 'codigo', 'nombre')->orderBy('codigo')->get(),
            'tipos_campo' => [
                ['value' => 'text', 'label' => 'Texto'],
                ['value' => 'number', 'label' => 'Número'],
                ['value' => 'date', 'label' => 'Fecha'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'tel', 'label' => 'Teléfono'],
                ['value' => 'textarea', 'label' => 'Área de texto'],
                ['value' => 'select', 'label' => 'Lista desplegable'],
                ['value' => 'checkbox', 'label' => 'Casilla de verificación']
            ]
        ];

        if ($plantilla) {
            $datos['estadisticas_uso'] = $this->editorService->obtenerEstadisticasUso($plantilla);
        }

        return Inertia::render('admin/plantillas/editor', $datos);
    }

    /**
     * Crear plantilla desde documento
     */
    public function crearDesdeDocumento(Request $request)
    {
        $validated = $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string',
            'tags' => 'nullable|array'
        ]);

        try {
            $documento = Documento::findOrFail($validated['documento_id']);
            
            $plantilla = $this->editorService->crearPlantillaDesdeDocumento($documento, $validated);

            return redirect()->route('admin.plantillas.editor', $plantilla)
                ->with('success', 'Plantilla creada exitosamente desde documento.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Validar estructura de plantilla
     */
    public function validarEstructura(Request $request)
    {
        $validated = $request->validate([
            'contenido_html' => 'required|string'
        ]);

        $validacion = $this->editorService->validarEstructura($validated['contenido_html']);

        return response()->json($validacion);
    }

    /**
     * Crear nueva versión de plantilla
     */
    public function crearVersion(PlantillaDocumental $plantilla, Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'contenido_html' => 'nullable|string',
            'contenido_json' => 'nullable|array',
            'campos_variables' => 'nullable|array',
            'metadatos_predefinidos' => 'nullable|array',
            'configuracion_formato' => 'nullable|array',
            'tags' => 'nullable|array'
        ]);

        try {
            $nuevaPlantilla = $this->editorService->crearNuevaVersion($plantilla, $validated);

            return redirect()->route('admin.plantillas.show', $nuevaPlantilla)
                ->with('success', 'Nueva versión creada exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Exportar plantilla
     */
    public function exportar(PlantillaDocumental $plantilla, string $formato = 'json')
    {
        try {
            $contenido = $this->editorService->exportarPlantilla($plantilla, $formato);
            
            $extension = $formato;
            $mimeType = $this->obtenerMimeType($formato);
            $nombreArchivo = "plantilla_{$plantilla->codigo}.{$extension}";

            return response($contenido)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Importar plantilla
     */
    public function importar(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:json,html,xml|max:10240', // 10MB max
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'es_publica' => 'boolean'
        ]);

        try {
            $archivo = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $rutaArchivo = $archivo->store('temp/plantillas');

            $metadatos = [
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? '',
                'categoria' => $validated['categoria'],
                'serie_documental_id' => $validated['serie_documental_id'] ?? null,
                'es_publica' => $validated['es_publica'] ?? false
            ];

            $plantilla = $this->editorService->importarPlantilla($rutaArchivo, $extension, $metadatos);

            // Limpiar archivo temporal
            Storage::delete($rutaArchivo);

            return redirect()->route('admin.plantillas.show', $plantilla)
                ->with('success', 'Plantilla importada exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Aplicar plantilla para generar documento
     */
    public function aplicarPlantilla(Request $request)
    {
        $validated = $request->validate([
            'plantilla_id' => 'required|exists:plantillas_documentales,id',
            'datos' => 'required|array',
            'nombre_documento' => 'required|string|max:255',
            'expediente_id' => 'nullable|exists:expedientes,id'
        ]);

        try {
            $plantilla = PlantillaDocumental::findOrFail($validated['plantilla_id']);
            
            $contenidoGenerado = $this->editorService->aplicarPlantilla($plantilla, $validated['datos']);

            // Crear documento temporal para preview o guardado
            $documento = [
                'nombre' => $validated['nombre_documento'],
                'contenido' => $contenidoGenerado,
                'plantilla_id' => $plantilla->id,
                'expediente_id' => $validated['expediente_id'] ?? null
            ];

            return response()->json([
                'success' => true,
                'documento' => $documento,
                'contenido_generado' => $contenidoGenerado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener documentos disponibles para crear plantilla
     */
    public function obtenerDocumentosDisponibles()
    {
        $documentos = Documento::whereDoesntHave('plantillaGenerada')
            ->select('id', 'nombre', 'tipo_mime', 'created_at')
            ->with('expediente:id,codigo,nombre')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($documentos);
    }

    /**
     * Obtener categorías disponibles
     */
    private function obtenerCategorias(): array
    {
        return [
            ['value' => PlantillaDocumental::CATEGORIA_MEMORANDO, 'label' => 'Memorando'],
            ['value' => PlantillaDocumental::CATEGORIA_OFICIO, 'label' => 'Oficio'],
            ['value' => PlantillaDocumental::CATEGORIA_RESOLUCION, 'label' => 'Resolución'],
            ['value' => PlantillaDocumental::CATEGORIA_ACTA, 'label' => 'Acta'],
            ['value' => PlantillaDocumental::CATEGORIA_INFORME, 'label' => 'Informe'],
            ['value' => PlantillaDocumental::CATEGORIA_CIRCULAR, 'label' => 'Circular'],
            ['value' => PlantillaDocumental::CATEGORIA_COMUNICACION, 'label' => 'Comunicación'],
            ['value' => PlantillaDocumental::CATEGORIA_OTRO, 'label' => 'Otro']
        ];
    }

    /**
     * Obtener MIME type por formato
     */
    private function obtenerMimeType(string $formato): string
    {
        $mimeTypes = [
            'json' => 'application/json',
            'html' => 'text/html',
            'xml' => 'application/xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        return $mimeTypes[$formato] ?? 'application/octet-stream';
    }
}
