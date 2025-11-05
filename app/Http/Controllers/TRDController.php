<?php

namespace App\Http\Controllers;

use App\Models\TRD;
use App\Services\TRDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TRDController extends Controller
{
    protected $trdService;

    public function __construct(TRDService $trdService)
    {
        $this->trdService = $trdService;
    }

    /**
     * Mostrar listado de TRDs
     */
    public function index(Request $request): Response
    {
        $query = TRD::with(['creador', 'series'])
            ->withCount('series');

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('codigo', 'like', '%' . $request->search . '%');
            });
        }

        $trds = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('admin/trd/index', [
            'trds' => $trds,
            'filters' => $request->only(['estado', 'search']),
            'estadisticas' => $this->trdService->obtenerEstadisticasGenerales(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        return Inertia::render('admin/trd/create');
    }

    /**
     * Almacenar nueva TRD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:trds,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'metadata' => 'nullable|array',
        ]);

        try {
            $trd = $this->trdService->crear($validated, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear TRD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear TRD: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar TRD específica
     */
    public function show(TRD $trd): Response
    {
        $trd->load([
            'series.subseries',
            'series.retenciones',
            'versiones.modificador',
            'creador',
            'aprobador'
        ]);

        return Inertia::render('admin/trd/show', [
            'trd' => $trd,
            'estadisticas' => $trd->getEstadisticas(),
            'errores_validacion' => $this->trdService->validar($trd),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(TRD $trd): Response
    {
        return Inertia::render('admin/trd/edit', [
            'trd' => $trd,
        ]);
    }

    /**
     * Actualizar TRD
     */
    public function update(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:trds,codigo,' . $trd->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'metadata' => 'nullable|array',
        ]);

        try {
            $trd = $this->trdService->actualizar($trd, $validated, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar TRD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar TRD
     */
    public function aprobar(TRD $trd, Request $request)
    {
        try {
            // Validar estructura antes de aprobar
            $errores = $this->trdService->validar($trd);
            if (!empty($errores)) {
                return back()->with('error', 'No se puede aprobar la TRD: ' . implode(', ', $errores));
            }

            $trd = $this->trdService->aprobar($trd, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD aprobada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al aprobar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al aprobar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Archivar TRD
     */
    public function archivar(TRD $trd)
    {
        try {
            $trd->archivar();

            return redirect()
                ->route('admin.trd.index')
                ->with('success', 'TRD archivada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al archivar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al archivar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:20',
            'cambios' => 'required|string',
        ]);

        try {
            $this->trdService->crearVersion(
                $trd,
                $validated['version'],
                $validated['cambios'],
                $request->user()
            );

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'Nueva versión creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al crear versión: ' . $e->getMessage());
        }
    }

    /**
     * Agregar serie a TRD
     */
    public function agregarSerie(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'dependencia' => 'nullable|string|max:255',
            'orden' => 'nullable|integer',
        ]);

        try {
            $serie = $this->trdService->agregarSerie($trd, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Serie agregada exitosamente',
                'serie' => $serie,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar serie', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar serie: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importar TRD desde XML
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xml|max:10240', // 10MB max
        ]);

        try {
            $archivo = $request->file('archivo');
            $ruta = $archivo->store('trd_importaciones', 'local');

            $importacion = $this->trdService->importarDesdeXML(
                storage_path('app/' . $ruta),
                $request->user()
            );

            return redirect()
                ->route('admin.trd.show', $importacion->trd_id)
                ->with('success', 'TRD importada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al importar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al importar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Exportar TRD a XML
     */
    public function exportar(TRD $trd)
    {
        try {
            $xml = $this->trdService->exportarAXML($trd);

            $nombreArchivo = 'TRD_' . $trd->codigo . '_v' . $trd->version . '.xml';

            return response($xml, 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
        } catch (\Exception $e) {
            Log::error('Error al exportar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al exportar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar TRD
     */
    public function destroy(TRD $trd)
    {
        // Solo se puede eliminar si está en borrador y no tiene datos asociados
        if ($trd->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar TRDs en estado borrador');
        }

        if ($trd->series()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una TRD que tiene series asociadas');
        }

        try {
            $trd->delete();

            return redirect()
                ->route('admin.trd.index')
                ->with('success', 'TRD eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar TRD: ' . $e->getMessage());
        }
    }
}
