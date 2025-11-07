<?php

namespace App\Http\Controllers;

use App\Models\CCD;
use App\Models\CCDNivel;
use App\Services\CCDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CCDController extends Controller
{
    protected $ccdService;

    public function __construct(CCDService $ccdService)
    {
        $this->ccdService = $ccdService;
    }

    /**
     * Mostrar listado de CCDs
     */
    public function index(Request $request): Response
    {
        $query = CCD::with(['creador', 'niveles'])
            ->withCount('niveles');

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

        $ccds = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        // Opciones para el formulario de creación
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
            'padres_disponibles' => [], // Se puede poblar con CCDs existentes si es necesario
        ];

        return Inertia::render('admin/ccd/index', [
            'ccds' => $ccds,
            'filters' => $request->only(['estado', 'search']),
            'estadisticas' => $this->getEstadisticasGenerales(),
            'opciones' => $opciones,
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
            'padres_disponibles' => [],
        ];

        return Inertia::render('admin/ccd/create', [
            'opciones' => $opciones,
        ]);
    }

    /**
     * Almacenar nuevo CCD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cuadros_clasificacion,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'vocabulario_controlado' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $ccd = $this->ccdService->crear($validated, $request->user());

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', 'CCD creado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear CCD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear CCD: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar CCD específico con estructura jerárquica
     */
    public function show(CCD $ccd): Response
    {
        $ccd->load([
            'niveles',
            'vocabularios',
            'versiones.modificador',
            'creador',
            'aprobador'
        ]);

        return Inertia::render('admin/ccd/show', [
            'ccd' => $ccd,
            'estructura' => $this->ccdService->obtenerEstructuraJerarquica($ccd),
            'estadisticas' => $ccd->getEstadisticas(),
            'errores_validacion' => $ccd->validar(),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(CCD $ccd): Response
    {
        return Inertia::render('admin/ccd/edit', [
            'ccd' => $ccd,
        ]);
    }

    /**
     * Actualizar CCD
     */
    public function update(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cuadros_clasificacion,codigo,' . $ccd->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'vocabulario_controlado' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $this->ccdService->actualizar($ccd, $validated, $request->user());

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'CCD actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar CCD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar CCD
     */
    public function aprobar(CCD $ccd, Request $request)
    {
        try {
            // Validar estructura antes de aprobar
            $errores = $ccd->validar();
            if (!empty($errores)) {
                return back()->with('error', 'No se puede aprobar el CCD: ' . implode(', ', $errores));
            }

            $this->ccdService->aprobar($ccd, $request->user());

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'CCD aprobado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al aprobar CCD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al aprobar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Archivar CCD
     */
    public function archivar(CCD $ccd)
    {
        try {
            $ccd->archivar();

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', 'CCD archivado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al archivar CCD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al archivar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:20',
            'cambios' => 'required|string',
        ]);

        try {
            $this->ccdService->crearVersion(
                $ccd,
                $validated['version'],
                $validated['cambios'],
                $request->user()
            );

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'Nueva versión creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al crear versión: ' . $e->getMessage());
        }
    }

    /**
     * Agregar nivel al CCD
     */
    public function agregarNivel(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:ccd_niveles,id',
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_nivel' => 'required|string|in:fondo,seccion,subseccion,serie,subserie',
            'orden' => 'nullable|integer',
            'palabras_clave' => 'nullable|array',
        ]);

        try {
            $nivel = $this->ccdService->agregarNivel($ccd, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Nivel agregado exitosamente',
                'nivel' => $nivel->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar nivel', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar nivel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar nivel
     */
    public function actualizarNivel(Request $request, CCDNivel $nivel)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_nivel' => 'required|string|in:fondo,seccion,subseccion,serie,subserie',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean',
            'palabras_clave' => 'nullable|array',
        ]);

        try {
            $nivel->update($validated);
            $nivel->actualizarRuta();

            return response()->json([
                'success' => true,
                'message' => 'Nivel actualizado exitosamente',
                'nivel' => $nivel->fresh()->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar nivel', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar nivel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar nivel
     */
    public function eliminarNivel(CCDNivel $nivel)
    {
        try {
            if (!$nivel->esHoja()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un nivel que tiene hijos',
                ], 400);
            }

            $nivel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nivel eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar nivel', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nivel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mover nivel
     */
    public function moverNivel(Request $request, CCDNivel $nivel)
    {
        $validated = $request->validate([
            'nuevo_padre_id' => 'nullable|exists:ccd_niveles,id',
            'orden' => 'required|integer',
        ]);

        try {
            $nuevoPadre = $validated['nuevo_padre_id'] 
                ? CCDNivel::find($validated['nuevo_padre_id']) 
                : null;

            if (!$nivel->moverA($nuevoPadre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede mover el nivel a esa posición',
                ], 400);
            }

            $nivel->orden = $validated['orden'];
            $nivel->save();

            return response()->json([
                'success' => true,
                'message' => 'Nivel movido exitosamente',
                'nivel' => $nivel->fresh()->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mover nivel', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al mover nivel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estructura jerárquica completa
     */
    public function getEstructura(CCD $ccd)
    {
        try {
            $estructura = $this->ccdService->obtenerEstructuraJerarquica($ccd);

            return response()->json([
                'success' => true,
                'estructura' => $estructura,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estructura', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estructura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar CCD
     */
    public function destroy(CCD $ccd)
    {
        if ($ccd->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar CCDs en estado borrador');
        }

        if ($ccd->niveles()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un CCD que tiene niveles asociados');
        }

        try {
            $ccd->delete();

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', 'CCD eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar CCD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales(): array
    {
        return [
            'total' => CCD::count(),
            'activos' => CCD::where('estado', 'activo')->count(),
            'borradores' => CCD::where('estado', 'borrador')->count(),
            'vigentes' => CCD::where('estado', 'activo')
                ->whereDate('fecha_vigencia_inicio', '<=', now())
                ->where(function($q) {
                    $q->whereNull('fecha_vigencia_fin')
                      ->orWhereDate('fecha_vigencia_fin', '>=', now());
                })
                ->count(),
        ];
    }
}
