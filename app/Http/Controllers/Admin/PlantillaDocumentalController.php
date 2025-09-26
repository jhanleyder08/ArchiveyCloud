<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantillaDocumental;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\User;
use App\Models\Documento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PlantillaDocumentalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
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
        
        $series = SerieDocumental::select('id', 'codigo', 'nombre')
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
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|in:memorando,oficio,resolucion,acta,informe,circular,comunicacion,otro',
            'tipo_documento' => 'nullable|string|max:100',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'subserie_documental_id' => 'nullable|exists:subseries_documentales,id',
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

        DB::beginTransaction();
        try {
            $plantilla = PlantillaDocumental::create($validated);

            DB::commit();

            return redirect()->route('admin.plantillas.show', $plantilla)
                ->with('success', 'Plantilla documental creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al crear la plantilla: ' . $e->getMessage());
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
     * Crear nueva versión de plantilla
     */
    public function crearVersion(PlantillaDocumental $plantilla)
    {
        $this->authorize('update', $plantilla);

        DB::beginTransaction();
        try {
            $nuevaVersion = $plantilla->replicate();
            $nuevaVersion->version = $plantilla->obtenerSiguienteVersion();
            $nuevaVersion->plantilla_padre_id = $plantilla->plantilla_padre_id ?: $plantilla->id;
            $nuevaVersion->estado = PlantillaDocumental::ESTADO_BORRADOR;
            $nuevaVersion->codigo = null; // Se generará automáticamente
            $nuevaVersion->save();

            DB::commit();

            return redirect()->route('admin.plantillas.edit', $nuevaVersion)
                ->with('success', "Nueva versión {$nuevaVersion->version} creada exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear nueva versión: ' . $e->getMessage());
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
}
