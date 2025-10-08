<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Services\ExpedienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ExpedienteController extends Controller
{
    protected $expedienteService;

    public function __construct(ExpedienteService $expedienteService)
    {
        $this->expedienteService = $expedienteService;
    }

    /**
     * Mostrar listado de expedientes
     */
    public function index(Request $request): Response
    {
        $query = Expediente::with(['serie', 'subserie', 'responsable'])
            ->withCount('documentos');

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('serie_id')) {
            $query->where('serie_id', $request->serie_id);
        }

        if ($request->has('tipo')) {
            $query->where('tipo_expediente', $request->tipo);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->search . '%')
                  ->orWhere('titulo', 'like', '%' . $request->search . '%');
            });
        }

        $expedientes = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Expedientes/Index', [
            'expedientes' => $expedientes,
            'filters' => $request->only(['estado', 'serie_id', 'tipo', 'search']),
            'estadisticas' => $this->getEstadisticasGenerales(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Expedientes/Create', [
            'series' => \App\Models\SerieDocumental::where('activo', true)->get(),
            'dependencias' => \App\Models\Dependencia::all(),
        ]);
    }

    /**
     * Almacenar nuevo expediente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:100|unique:expedientes,codigo',
            'titulo' => 'required|string|max:500',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'tipo_expediente' => 'required|in:administrativo,contable,juridico,tecnico,historico,personal',
            'nivel_acceso' => 'required|in:publico,restringido,confidencial,reservado',
            'fecha_apertura' => 'nullable|date',
            'responsable_id' => 'required|exists:users,id',
            'dependencia_id' => 'nullable|exists:dependencias,id',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'palabras_clave' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $expediente = $this->expedienteService->crear($validated, $request->user());

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente creado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear expediente', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear expediente: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar expediente específico
     */
    public function show(Expediente $expediente): Response
    {
        $expediente->load([
            'serie',
            'subserie',
            'responsable',
            'dependencia',
            'documentos',
            'creador',
        ]);

        return Inertia::render('Admin/Expedientes/Show', [
            'expediente' => $expediente,
            'estadisticas' => $this->expedienteService->getEstadisticas($expediente),
            'integridad' => $this->expedienteService->verificarIntegridad($expediente),
            'historial' => \DB::table('expediente_historial')
                ->where('expediente_id', $expediente->id)
                ->orderBy('fecha_cambio', 'desc')
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Expediente $expediente): Response
    {
        if ($expediente->cerrado) {
            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('error', 'No se puede editar un expediente cerrado');
        }

        return Inertia::render('Admin/Expedientes/Edit', [
            'expediente' => $expediente,
            'series' => \App\Models\SerieDocumental::where('activo', true)->get(),
            'dependencias' => \App\Models\Dependencia::all(),
        ]);
    }

    /**
     * Actualizar expediente
     */
    public function update(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:100|unique:expedientes,codigo,' . $expediente->id,
            'titulo' => 'required|string|max:500',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'tipo_expediente' => 'required|in:administrativo,contable,juridico,tecnico,historico,personal',
            'nivel_acceso' => 'required|in:publico,restringido,confidencial,reservado',
            'responsable_id' => 'required|exists:users,id',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'palabras_clave' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $this->expedienteService->actualizar($expediente, $validated, $request->user());

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar expediente', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado del expediente
     */
    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'estado' => 'required|in:en_tramite,activo,semiactivo,inactivo,historico,en_transferencia,transferido',
            'observaciones' => 'required|string',
        ]);

        try {
            $this->expedienteService->cambiarEstado(
                $expediente,
                $validated['estado'],
                $validated['observaciones'],
                $request->user()
            );

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Estado cambiado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar estado: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar expediente
     */
    public function cerrar(Expediente $expediente, Request $request)
    {
        try {
            $this->expedienteService->cerrar($expediente, $request->user());

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente cerrado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cerrar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Agregar documento al expediente
     */
    public function agregarDocumento(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'orden' => 'nullable|integer',
            'motivo' => 'nullable|string',
            'es_principal' => 'nullable|boolean',
        ]);

        try {
            $this->expedienteService->agregarDocumento(
                $expediente,
                $validated['documento_id'],
                $validated,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento agregado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar documento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear transferencia
     */
    public function crearTransferencia(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'tipo_transferencia' => 'required|in:archivo_gestion_a_central,archivo_central_a_historico,transferencia_entre_dependencias',
            'destino_dependencia_id' => 'required|exists:dependencias,id',
            'ubicacion_destino' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $transferencia = $this->expedienteService->crearTransferencia(
                $expediente,
                $validated,
                $request->user()
            );

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Transferencia creada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear transferencia: ' . $e->getMessage());
        }
    }

    /**
     * Verificar integridad
     */
    public function verificarIntegridad(Expediente $expediente)
    {
        try {
            $resultado = $this->expedienteService->verificarIntegridad($expediente);

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar integridad: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar expediente
     */
    public function destroy(Expediente $expediente)
    {
        if ($expediente->cerrado) {
            return back()->with('error', 'No se puede eliminar un expediente cerrado');
        }

        if ($expediente->numero_documentos > 0) {
            return back()->with('error', 'No se puede eliminar un expediente con documentos asociados');
        }

        try {
            $expediente->delete();

            return redirect()
                ->route('admin.expedientes.index')
                ->with('success', 'Expediente eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales(): array
    {
        return [
            'total_expedientes' => Expediente::count(),
            'en_tramite' => Expediente::where('estado', 'en_tramite')->count(),
            'activos' => Expediente::where('estado', 'activo')->count(),
            'cerrados' => Expediente::where('cerrado', true)->count(),
            'por_tipo' => Expediente::selectRaw('tipo_expediente, COUNT(*) as total')
                ->groupBy('tipo_expediente')
                ->pluck('total', 'tipo_expediente')
                ->toArray(),
        ];
    }
}
