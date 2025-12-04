<?php

namespace App\Http\Controllers;

use App\Models\PlantillaDocumento;
use App\Models\SerieDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PlantillaDocumentoController extends Controller
{
    /**
     * Lista de plantillas
     */
    public function index(Request $request)
    {
        $query = PlantillaDocumento::with(['serieDocumental', 'usuarioCreador'])
            ->activas();

        // Filtrar por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Filtrar por tipo
        if ($request->filled('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%');
        }

        // Solo públicas o del usuario
        if (!$request->user()->hasRole('admin')) {
            $query->where(function ($q) use ($request) {
                $q->where('es_publica', true)
                  ->orWhere('usuario_creador_id', $request->user()->id);
            });
        }

        $plantillas = $query->orderBy('nombre')
            ->paginate(20)
            ->through(fn($plantilla) => [
                'id' => $plantilla->id,
                'nombre' => $plantilla->nombre,
                'descripcion' => $plantilla->descripcion,
                'categoria' => $plantilla->categoria,
                'tipo_documento' => $plantilla->tipo_documento,
                'es_publica' => $plantilla->es_publica,
                'version' => $plantilla->version,
                'tags' => $plantilla->tags,
                'serie_documental' => $plantilla->serieDocumental?->nombre,
                'usuario_creador' => $plantilla->usuarioCreador->name,
                'created_at' => $plantilla->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Plantillas/Index', [
            'plantillas' => $plantillas,
            'filtros' => $request->only(['categoria', 'tipo_documento', 'search']),
            'categorias' => $this->getCategorias(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return Inertia::render('Plantillas/Create', [
            'seriesDocumentales' => SerieDocumental::activas()
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'categorias' => $this->getCategorias(),
        ]);
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_documento' => 'nullable|string|max:100',
            'categoria' => 'required|string|max:50',
            'contenido_html' => 'nullable|string',
            'contenido_json' => 'nullable|array',
            'campos_variables' => 'nullable|array',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'subserie_documental_id' => 'nullable|exists:subseries_documentales,id',
            'metadatos_predefinidos' => 'nullable|array',
            'es_publica' => 'boolean',
            'tags' => 'nullable|array',
        ]);

        $validated['usuario_creador_id'] = Auth::id();

        $plantilla = PlantillaDocumento::create($validated);

        return redirect()
            ->route('plantillas.show', $plantilla)
            ->with('success', 'Plantilla creada exitosamente');
    }

    /**
     * Mostrar plantilla
     */
    public function show(PlantillaDocumento $plantilla)
    {
        $plantilla->load(['serieDocumental', 'subserieDocumental', 'usuarioCreador']);

        return Inertia::render('Plantillas/Show', [
            'plantilla' => [
                'id' => $plantilla->id,
                'nombre' => $plantilla->nombre,
                'descripcion' => $plantilla->descripcion,
                'categoria' => $plantilla->categoria,
                'tipo_documento' => $plantilla->tipo_documento,
                'contenido_html' => $plantilla->contenido_html,
                'contenido_json' => $plantilla->contenido_json,
                'campos_variables' => $plantilla->campos_variables,
                'serie_documental' => $plantilla->serieDocumental,
                'subserie_documental' => $plantilla->subserieDocumental,
                'metadatos_predefinidos' => $plantilla->metadatos_predefinidos,
                'es_publica' => $plantilla->es_publica,
                'activa' => $plantilla->activa,
                'version' => $plantilla->version,
                'tags' => $plantilla->tags,
                'usuario_creador' => $plantilla->usuarioCreador->name,
                'created_at' => $plantilla->created_at->format('d/m/Y H:i'),
                'updated_at' => $plantilla->updated_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(PlantillaDocumento $plantilla)
    {
        $this->authorize('update', $plantilla);

        return Inertia::render('Plantillas/Edit', [
            'plantilla' => $plantilla,
            'seriesDocumentales' => SerieDocumental::activas()
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'categorias' => $this->getCategorias(),
        ]);
    }

    /**
     * Actualizar plantilla
     */
    public function update(Request $request, PlantillaDocumento $plantilla)
    {
        $this->authorize('update', $plantilla);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_documento' => 'nullable|string|max:100',
            'categoria' => 'required|string|max:50',
            'contenido_html' => 'nullable|string',
            'contenido_json' => 'nullable|array',
            'campos_variables' => 'nullable|array',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'subserie_documental_id' => 'nullable|exists:subseries_documentales,id',
            'metadatos_predefinidos' => 'nullable|array',
            'es_publica' => 'boolean',
            'activa' => 'boolean',
            'tags' => 'nullable|array',
        ]);

        $plantilla->update($validated);
        $plantilla->incrementarVersion();

        return redirect()
            ->route('plantillas.show', $plantilla)
            ->with('success', 'Plantilla actualizada exitosamente');
    }

    /**
     * Eliminar plantilla
     */
    public function destroy(PlantillaDocumento $plantilla)
    {
        $this->authorize('delete', $plantilla);

        $plantilla->delete();

        return redirect()
            ->route('plantillas.index')
            ->with('success', 'Plantilla eliminada exitosamente');
    }

    /**
     * Duplicar plantilla
     */
    public function duplicate(PlantillaDocumento $plantilla)
    {
        $nuevaPlantilla = $plantilla->duplicar([
            'usuario_creador_id' => Auth::id(),
            'es_publica' => false,
        ]);

        return redirect()
            ->route('plantillas.edit', $nuevaPlantilla)
            ->with('success', 'Plantilla duplicada exitosamente');
    }

    /**
     * Usar plantilla (vista previa con variables)
     */
    public function preview(Request $request, PlantillaDocumento $plantilla)
    {
        $variables = $request->input('variables', []);
        
        $contenidoRenderizado = $plantilla->renderizar($variables);

        return response()->json([
            'success' => true,
            'contenido' => $contenidoRenderizado,
            'campos_faltantes' => $this->obtenerCamposFaltantes($plantilla, $variables),
        ]);
    }

    /**
     * Crear documento desde plantilla
     */
    public function createDocument(Request $request, PlantillaDocumento $plantilla)
    {
        $variables = $request->validate([
            'variables' => 'required|array',
            'variables.*' => 'required',
        ])['variables'];

        if (!$plantilla->validarVariables($variables)) {
            return back()->withErrors([
                'variables' => 'Faltan variables requeridas para esta plantilla'
            ]);
        }

        // Crear documento con la plantilla
        $contenido = $plantilla->renderizar($variables);

        // Aquí iría la lógica para crear el documento
        // Por ahora retornamos la vista de creación con los datos pre-cargados
        
        return redirect()
            ->route('documentos.create', [
                'plantilla_id' => $plantilla->id,
                'contenido' => $contenido,
                'metadatos' => $plantilla->metadatos_predefinidos,
            ])
            ->with('success', 'Documento creado desde plantilla');
    }

    /**
     * Obtener categorías disponibles
     */
    private function getCategorias(): array
    {
        return [
            'general' => 'General',
            'contrato' => 'Contratos',
            'oficio' => 'Oficios',
            'memorando' => 'Memorandos',
            'acta' => 'Actas',
            'informe' => 'Informes',
            'carta' => 'Cartas',
            'circular' => 'Circulares',
            'resolucion' => 'Resoluciones',
            'certificado' => 'Certificados',
        ];
    }

    /**
     * Obtener campos faltantes
     */
    private function obtenerCamposFaltantes(PlantillaDocumento $plantilla, array $variables): array
    {
        $camposFaltantes = [];
        
        foreach ($plantilla->campos_variables ?? [] as $campo) {
            if (($campo['requerido'] ?? false) && !isset($variables[$campo['nombre']])) {
                $camposFaltantes[] = $campo['nombre'];
            }
        }

        return $camposFaltantes;
    }
}
