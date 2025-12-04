<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use App\Models\WorkflowTarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller para Workflows
 * REQ-CP-011: APIs de Interoperabilidad
 */
class WorkflowController extends Controller
{
    /**
     * Listar todos los workflows
     */
    public function index(Request $request): JsonResponse
    {
        $query = Workflow::with('creador');

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('tipo_entidad')) {
            $query->where('tipo_entidad', $request->tipo_entidad);
        }

        // Búsqueda
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        // Paginación
        $workflows = $query->paginate($request->get('per_page', 15));

        return response()->json($workflows);
    }

    /**
     * Crear nuevo workflow
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_entidad' => 'required|string|max:255',
            'pasos' => 'required|array|min:1',
            'pasos.*.nombre' => 'required|string',
            'pasos.*.tipo_asignacion' => 'required|in:usuario,rol',
            'configuracion' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workflow = Workflow::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo_entidad' => $request->tipo_entidad,
                'pasos' => $request->pasos,
                'configuracion' => $request->configuracion ?? [],
                'activo' => $request->get('activo', true),
                'usuario_creador_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Workflow creado exitosamente',
                'data' => $workflow->load('creador')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver detalles de un workflow
     */
    public function show(int $id): JsonResponse
    {
        $workflow = Workflow::with(['creador', 'instancias' => function($q) {
            $q->latest()->limit(10);
        }])->find($id);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $workflow
        ]);
    }

    /**
     * Actualizar workflow
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $workflow = Workflow::find($id);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'pasos' => 'sometimes|required|array|min:1',
            'configuracion' => 'nullable|array',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workflow->update($request->only([
                'nombre', 'descripcion', 'pasos', 'configuracion', 'activo'
            ]));

            return response()->json([
                'message' => 'Workflow actualizado exitosamente',
                'data' => $workflow->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar workflow
     */
    public function destroy(int $id): JsonResponse
    {
        $workflow = Workflow::find($id);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        // Verificar si tiene instancias activas
        if ($workflow->instancias()->whereIn('estado', ['pendiente', 'en_progreso'])->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un workflow con instancias activas'
            ], 409);
        }

        try {
            $workflow->delete();

            return response()->json([
                'message' => 'Workflow eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar una instancia de workflow
     */
    public function iniciar(Request $request, int $workflowId): JsonResponse
    {
        $workflow = Workflow::find($workflowId);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        if (!$workflow->activo) {
            return response()->json([
                'message' => 'El workflow no está activo'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'entidad_id' => 'required|integer',
            'datos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $instancia = $workflow->iniciar(
                entidadId: $request->entidad_id,
                usuarioId: auth()->id(),
                datos: $request->datos ?? []
            );

            return response()->json([
                'message' => 'Workflow iniciado exitosamente',
                'data' => $instancia->load(['workflow', 'usuario', 'tareas'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al iniciar workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar instancias de un workflow
     */
    public function instancias(Request $request, int $workflowId): JsonResponse
    {
        $workflow = Workflow::find($workflowId);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        $query = $workflow->instancias()->with(['usuario', 'tareas']);

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $instancias = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($instancias);
    }

    /**
     * Ver detalles de una instancia
     */
    public function verInstancia(int $instanciaId): JsonResponse
    {
        $instancia = WorkflowInstancia::with([
            'workflow',
            'usuario',
            'tareas.asignado',
            'tareas.usuarioCompletado'
        ])->find($instanciaId);

        if (!$instancia) {
            return response()->json([
                'message' => 'Instancia no encontrada'
            ], 404);
        }

        return response()->json([
            'data' => $instancia
        ]);
    }

    /**
     * Aprobar tarea
     */
    public function aprobarTarea(Request $request, int $tareaId): JsonResponse
    {
        $tarea = WorkflowTarea::with('instancia')->find($tareaId);

        if (!$tarea) {
            return response()->json([
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        if ($tarea->estado !== 'pendiente') {
            return response()->json([
                'message' => 'La tarea no está pendiente'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tarea->aprobar(
                usuarioId: auth()->id(),
                observaciones: $request->observaciones
            );

            return response()->json([
                'message' => 'Tarea aprobada exitosamente',
                'data' => $tarea->fresh(['instancia', 'usuarioCompletado'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aprobar tarea',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar tarea
     */
    public function rechazarTarea(Request $request, int $tareaId): JsonResponse
    {
        $tarea = WorkflowTarea::with('instancia')->find($tareaId);

        if (!$tarea) {
            return response()->json([
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        if ($tarea->estado !== 'pendiente') {
            return response()->json([
                'message' => 'La tarea no está pendiente'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tarea->rechazar(
                usuarioId: auth()->id(),
                motivo: $request->motivo
            );

            return response()->json([
                'message' => 'Tarea rechazada',
                'data' => $tarea->fresh(['instancia', 'usuarioCompletado'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar tarea',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mis tareas pendientes
     */
    public function misTareas(Request $request): JsonResponse
    {
        $query = WorkflowTarea::with(['instancia.workflow'])
            ->where('asignado_type', 'App\Models\User')
            ->where('asignado_id', auth()->id())
            ->where('estado', 'pendiente');

        // Ordenar por vencimiento
        $query->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC');

        $tareas = $query->paginate($request->get('per_page', 15));

        return response()->json($tareas);
    }

    /**
     * Estadísticas de workflows
     */
    public function estadisticas(int $workflowId): JsonResponse
    {
        $workflow = Workflow::find($workflowId);

        if (!$workflow) {
            return response()->json([
                'message' => 'Workflow no encontrado'
            ], 404);
        }

        $stats = [
            'total_instancias' => $workflow->instancias()->count(),
            'instancias_por_estado' => $workflow->instancias()
                ->select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'tareas_pendientes' => $workflow->instancias()
                ->whereHas('tareas', function($q) {
                    $q->where('estado', 'pendiente');
                })->count(),
            'tiempo_promedio_completado' => $workflow->instancias()
                ->where('estado', 'completado')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, fecha_finalizacion)) as promedio')
                ->value('promedio'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
