<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use App\Models\WorkflowTarea;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\User;
use App\Services\WorkflowEngineService;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Controlador para gestión de workflows y flujos de trabajo
 */
class WorkflowController extends Controller
{
    protected WorkflowEngineService $workflowEngine;
    protected ApprovalWorkflowService $approvalService;

    public function __construct(
        WorkflowEngineService $workflowEngine,
        ApprovalWorkflowService $approvalService
    ) {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->workflowEngine = $workflowEngine;
        $this->approvalService = $approvalService;
    }

    /**
     * Dashboard de workflows del usuario
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Obtener tareas pendientes de aprobación
        $tareasPendientes = $this->approvalService->obtenerAprobacionesPendientes($user, [
            'prioridad' => $request->get('prioridad'),
            'tipo_entidad' => $request->get('tipo_entidad')
        ]);
        
        // Obtener instancias iniciadas por el usuario
        $instanciasUsuario = WorkflowInstancia::where('usuario_iniciador_id', $user->id)
            ->with(['workflow', 'tareas.asignado'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($instancia) {
                $tareaActual = $instancia->tareas()->where('estado', 'pendiente')->first();
                return [
                    'id' => $instancia->id,
                    'codigo_seguimiento' => $instancia->id, // Usar ID como código si no existe
                    'workflow_nombre' => $instancia->workflow->nombre,
                    'entidad_tipo' => $instancia->entidad_type,
                    'entidad_id' => $instancia->entidad_id,
                    'estado' => $instancia->estado,
                    'progreso' => $this->calcularProgreso($instancia),
                    'fecha_inicio' => $instancia->fecha_inicio,
                    'fecha_limite' => null, // No existe en el modelo
                    'tarea_actual' => $tareaActual ? [
                        'nombre' => $tareaActual->nombre,
                        'asignado_a' => $tareaActual->asignado?->name ?? 'Sin asignar'
                    ] : null
                ];
            });
        
        // Estadísticas del usuario
        $estadisticas = [
            'tareas_pendientes' => count($tareasPendientes),
            'instancias_activas' => WorkflowInstancia::where('usuario_iniciador_id', $user->id)
                ->where('estado', 'en_proceso')->count(),
            'completadas_mes' => WorkflowInstancia::where('usuario_iniciador_id', $user->id)
                ->where('estado', 'completado')
                ->whereMonth('fecha_finalizacion', now()->month)->count(),
            'promedio_duracion' => $this->calcularPromedioTiempo($user->id)
        ];
        
        return Inertia::render('admin/workflow/index', [
            'tareas_pendientes' => $tareasPendientes,
            'instancias_usuario' => $instanciasUsuario,
            'estadisticas' => $estadisticas,
            'workflows_disponibles' => $this->obtenerWorkflowsDisponibles()
        ]);
    }

    /**
     * Mostrar formulario para iniciar workflow
     */
    public function create(Request $request)
    {
        $entidad = null;
        $tipoEntidad = $request->get('tipo_entidad', 'documento');
        
        if ($request->has('entidad_id')) {
            $entidad = $tipoEntidad === 'expediente' 
                ? Expediente::with(['serie'])->findOrFail($request->entidad_id)
                : Documento::with(['expediente'])->findOrFail($request->entidad_id);
        }
        
        // Obtener workflows disponibles
        $workflowsDisponibles = Workflow::where('activo', true)
            ->where('tipo_entidad', $tipoEntidad)
            ->get(['id', 'nombre', 'descripcion', 'tipo_entidad']);
        
        // Obtener usuarios para asignación
        $usuariosDisponibles = User::where('activo', true)
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'cargo']);
        
        return Inertia::render('admin/workflow/create', [
            'entidad' => $entidad ? $this->formatearEntidad($entidad, $tipoEntidad) : null,
            'tipo_entidad' => $tipoEntidad,
            'workflows_disponibles' => $workflowsDisponibles,
            'usuarios_disponibles' => $usuariosDisponibles
        ]);
    }

    /**
     * Iniciar nuevo workflow
     */
    public function store(Request $request)
    {
        $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'entidad_tipo' => ['required', Rule::in(['documento', 'expediente'])],
            'entidad_id' => 'required|integer',
            'datos_iniciales' => 'array',
            'prioridad' => ['required', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'dias_limite' => 'nullable|integer|min:1|max:30',
            'comentarios' => 'nullable|string|max:1000'
        ]);
        
        DB::beginTransaction();
        
        try {
            $workflow = Workflow::findOrFail($request->workflow_id);
            
            // Obtener entidad
            $entidad = $request->entidad_tipo === 'expediente'
                ? Expediente::findOrFail($request->entidad_id)
                : Documento::findOrFail($request->entidad_id);
            
            // Datos iniciales
            $datosIniciales = array_merge(
                $request->datos_iniciales ?? [],
                [
                    'prioridad' => $request->prioridad,
                    'dias_limite' => $request->dias_limite,
                    'comentarios_inicial' => $request->comentarios
                ]
            );
            
            // Iniciar workflow
            $instancia = $this->workflowEngine->iniciarWorkflow(
                $workflow,
                $entidad,
                auth()->user(),
                $datosIniciales
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Workflow iniciado exitosamente',
                'instancia' => [
                    'id' => $instancia->id,
                    'codigo_seguimiento' => $instancia->codigo_seguimiento,
                    'url' => route('admin.workflow.show', $instancia)
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => "Error al iniciar workflow: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Mostrar detalles del workflow
     */
    public function show(WorkflowInstancia $instancia)
    {
        // Obtener estado completo del workflow
        $estadoWorkflow = $this->workflowEngine->obtenerEstadoWorkflow($instancia);
        
        // Verificar permisos del usuario
        $user = auth()->user();
        $puedeVer = $instancia->usuario_iniciador_id === $user->id || 
                   $instancia->tareas()->whereHas('asignado', function ($q) use ($user) {
                       $q->where('id', $user->id);
                   })->exists();
        
        if (!$puedeVer) {
            abort(403, 'No tiene permisos para ver este workflow');
        }
        
        return Inertia::render('admin/workflow/show', [
            'workflow' => $estadoWorkflow,
            'puede_completar_tarea' => $this->usuarioPuedeCompletarTareaActual($instancia, $user),
            'es_iniciador' => $instancia->usuario_iniciador_id === $user->id
        ]);
    }
    
    /**
     * Completar tarea de workflow
     */
    public function completarTarea(Request $request, WorkflowTarea $tarea)
    {
        $request->validate([
            'decision' => 'nullable|string',
            'comentarios' => 'nullable|string|max:1000',
            'datos_resultado' => 'array',
            'adjuntos' => 'array'
        ]);
        
        DB::beginTransaction();
        
        try {
            $user = auth()->user();
            
            // Validar que el usuario puede completar la tarea
            if ($tarea->asignado_type !== 'App\\Models\\User' || $tarea->asignado_id !== $user->id) {
                throw new \Exception('No tiene permisos para completar esta tarea');
            }
            
            $datosComplecion = [
                'decision' => $request->decision,
                'comentarios' => $request->comentarios,
                'datos_resultado' => $request->datos_resultado ?? [],
                'adjuntos' => $request->adjuntos ?? []
            ];
            
            // Procesar según tipo de tarea
            if ($tarea->tipo_asignacion === 'aprobacion') {
                $siguienteTarea = $this->approvalService->procesarDecisionAprobacion(
                    $tarea,
                    $user,
                    $request->decision ?? 'aprobar',
                    $datosComplecion
                );
            } else {
                $siguienteTarea = $this->workflowEngine->completarTarea(
                    $tarea,
                    $user,
                    $datosComplecion
                );
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Tarea completada exitosamente',
                'siguiente_tarea' => $siguienteTarea ? [
                    'id' => $siguienteTarea->id,
                    'nombre' => $siguienteTarea->nombre
                ] : null,
                'workflow_completado' => !$siguienteTarea
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => "Error completando tarea: {$e->getMessage()}"
            ], 500);
        }
    }
    
    /**
     * Cancelar workflow
     */
    public function cancelar(Request $request, WorkflowInstancia $instancia)
    {
        $request->validate([
            'motivo' => 'required|string|max:500'
        ]);
        
        try {
            $user = auth()->user();
            
            // Solo el iniciador o un admin puede cancelar
            if ($instancia->usuario_iniciador_id !== $user->id && !$user->hasRole('admin')) {
                throw new \Exception('No tiene permisos para cancelar este workflow');
            }
            
            $this->workflowEngine->cancelarWorkflow($instancia, $user, $request->motivo);
            
            return response()->json([
                'success' => true,
                'message' => 'Workflow cancelado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error cancelando workflow: {$e->getMessage()}"
            ], 500);
        }
    }
    
    /**
     * Obtener reportes de workflow
     */
    public function reportes(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo_entidad' => 'nullable|string',
            'estado' => 'nullable|string'
        ]);
        
        $reporte = $this->approvalService->generarReporteAprobaciones([
            'fecha_inicio' => $request->fecha_inicio ? \Carbon\Carbon::parse($request->fecha_inicio) : now()->subMonth(),
            'fecha_fin' => $request->fecha_fin ? \Carbon\Carbon::parse($request->fecha_fin) : now(),
            'tipo_entidad' => $request->tipo_entidad,
            'estado' => $request->estado
        ]);
        
        return Inertia::render('admin/workflow/reportes', [
            'reporte' => $reporte,
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'tipo_entidad', 'estado'])
        ]);
    }
    
    // Métodos auxiliares
    private function calcularProgreso(WorkflowInstancia $instancia): float
    {
        $totalTareas = $instancia->tareas()->count();
        $tareasCompletadas = $instancia->tareas()->whereIn('estado', ['completada', 'cancelada'])->count();
        
        return $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100, 2) : 0;
    }
    
    private function calcularPromedioTiempo(int $usuarioId): float
    {
        $instanciasCompletadas = WorkflowInstancia::where('usuario_iniciador_id', $usuarioId)
            ->where('estado', 'completado')
            ->whereNotNull('fecha_finalizacion')
            ->get();
        
        if ($instanciasCompletadas->isEmpty()) {
            return 0;
        }
        
        $tiempoTotal = $instanciasCompletadas->sum(function ($instancia) {
            return $instancia->fecha_inicio->diffInHours($instancia->fecha_finalizacion);
        });
        
        return round($tiempoTotal / $instanciasCompletadas->count(), 2);
    }
    
    private function obtenerWorkflowsDisponibles(): array
    {
        return Workflow::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion', 'tipo_entidad'])
            ->toArray();
    }
    
    private function formatearEntidad($entidad, string $tipo): array
    {
        if ($tipo === 'expediente') {
            return [
                'id' => $entidad->id,
                'nombre' => $entidad->nombre,
                'codigo' => $entidad->codigo,
                'serie' => $entidad->serie?->nombre,
                'estado' => $entidad->estado
            ];
        } else {
            return [
                'id' => $entidad->id,
                'nombre' => $entidad->nombre,
                'formato' => $entidad->formato,
                'expediente' => $entidad->expediente?->nombre,
                'serie' => $entidad->expediente?->serie?->nombre
            ];
        }
    }
    
    private function usuarioPuedeCompletarTareaActual(WorkflowInstancia $instancia, User $user): bool
    {
        $tareaActual = $instancia->tareas()->where('estado', 'pendiente')->first();
        if (!$tareaActual) {
            return false;
        }
        
        return $tareaActual->asignado_type === 'App\\Models\\User' && 
               $tareaActual->asignado_id === $user->id;
    }
}
