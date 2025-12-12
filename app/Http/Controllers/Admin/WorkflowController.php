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
        $usuariosDisponibles = User::where('active', true)
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'cargo']);
        
        // Obtener IDs de documentos con workflow activo
        $documentosConWorkflowActivo = WorkflowInstancia::whereIn('estado', ['en_proceso', 'pausado'])
            ->where('entidad_type', 'App\\Models\\Documento')
            ->pluck('entidad_id')
            ->toArray();
        
        // Obtener documentos disponibles para workflow (sin workflow activo)
        $documentosDisponibles = Documento::with(['expediente:id,codigo,titulo'])
            ->whereNotIn('id', $documentosConWorkflowActivo)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'titulo', 'codigo_documento', 'expediente_id', 'created_at'])
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'nombre' => $doc->titulo,
                    'codigo' => $doc->codigo_documento ?? 'DOC-' . $doc->id,
                    'expediente' => $doc->expediente ? [
                        'numero' => $doc->expediente->codigo,
                        'titulo' => $doc->expediente->titulo
                    ] : null,
                    'fecha' => $doc->created_at?->format('d/m/Y') ?? ''
                ];
            });
        
        return Inertia::render('admin/workflow/create', [
            'entidad' => $entidad ? $this->formatearEntidad($entidad, $tipoEntidad) : null,
            'documento' => $entidad && $tipoEntidad === 'documento' ? $this->formatearEntidad($entidad, $tipoEntidad) : null,
            'tipo_entidad' => $tipoEntidad,
            'workflows_disponibles' => $workflowsDisponibles,
            'usuarios_disponibles' => $usuariosDisponibles,
            'documentos_disponibles' => $documentosDisponibles
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
            
            return redirect()->route('admin.workflow.show', $instancia)
                ->with('success', 'Workflow iniciado exitosamente');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withErrors(['error' => "Error al iniciar workflow: {$e->getMessage()}"]);
        }
    }

    /**
     * Mostrar detalles del workflow
     */
    public function show(WorkflowInstancia $instancia)
    {
        // Cargar relaciones necesarias
        $instancia->load(['workflow', 'tareas.asignado', 'usuarioIniciador', 'entidad']);
        
        // Verificar permisos del usuario
        $user = auth()->user();
        $puedeVer = $instancia->usuario_iniciador_id === $user->id || 
                   $instancia->tareas()->where(function ($q) use ($user) {
                       $q->where('asignado_id', $user->id)
                         ->where('asignado_type', 'App\\Models\\User');
                   })->exists();
        
        if (!$puedeVer) {
            abort(403, 'No tiene permisos para ver este workflow');
        }
        
        // Obtener entidad (documento/expediente)
        $entidad = $instancia->entidad;
        
        // Formatear datos para el frontend
        $workflowData = [
            'id' => $instancia->id,
            'estado' => $instancia->estado,
            'progreso' => $this->calcularProgreso($instancia),
            'prioridad' => $instancia->datos['prioridad'] ?? 'normal',
            'descripcion' => $instancia->workflow->descripcion ?? '',
            'fecha_solicitud' => $instancia->fecha_inicio?->toDateTimeString(),
            'fecha_vencimiento' => $instancia->datos['fecha_vencimiento'] ?? null,
            'esta_vencido' => false,
            'requiere_unanime' => $instancia->datos['requiere_unanime'] ?? false,
            'documento' => $entidad ? [
                'id' => $entidad->id,
                'nombre' => $entidad->titulo ?? $entidad->nombre ?? "Documento #{$entidad->id}",
                'codigo' => $entidad->codigo ?? '',
            ] : [
                'id' => $instancia->entidad_id,
                'nombre' => 'Documento no encontrado',
                'codigo' => '',
            ],
            'solicitante' => [
                'name' => $instancia->usuarioIniciador->name ?? 'N/A',
                'email' => $instancia->usuarioIniciador->email ?? '',
            ],
        ];
        
        // Obtener niveles de aprobación (tareas)
        $nivelesAprobacion = $instancia->tareas->map(function ($tarea, $index) {
            return [
                'nivel' => $index + 1,
                'usuario' => $tarea->asignado ? [
                    'id' => $tarea->asignado->id,
                    'name' => $tarea->asignado->name ?? $tarea->asignado->nombre ?? 'N/A',
                    'email' => $tarea->asignado->email ?? '',
                    'cargo' => $tarea->asignado->cargo ?? '',
                ] : null,
                'es_actual' => $tarea->estado === 'pendiente',
                'completado' => $tarea->estado === 'completada',
                'aprobacion' => $tarea->fecha_completado ? [
                    'accion' => $tarea->resultado ?? 'completado',
                    'comentarios' => $tarea->comentarios ?? '',
                    'fecha' => $tarea->fecha_completado->toDateTimeString(),
                    'tiempo_respuesta' => 0,
                ] : null,
            ];
        })->toArray();
        
        // Verificar si el usuario puede aprobar
        $puedeAprobar = $instancia->tareas()
            ->where('estado', 'pendiente')
            ->where('asignado_id', $user->id)
            ->where('asignado_type', 'App\\Models\\User')
            ->exists();
        
        return Inertia::render('admin/workflow/show', [
            'workflow' => $workflowData,
            'nivelesAprobacion' => $nivelesAprobacion,
            'puedeAprobar' => $puedeAprobar,
            'esSolicitante' => $instancia->usuario_iniciador_id === $user->id
        ]);
    }
    
    /**
     * Calcular progreso del workflow
     */
    private function calcularProgreso(WorkflowInstancia $instancia): int
    {
        $totalTareas = $instancia->tareas()->count();
        if ($totalTareas === 0) return 0;
        
        $tareasCompletadas = $instancia->tareas()->where('estado', 'completada')->count();
        return (int) round(($tareasCompletadas / $totalTareas) * 100);
    }
    
    /**
     * Mostrar formulario de aprobación
     */
    public function aprobar($workflow)
    {
        // Buscar instancia de workflow
        $instancia = WorkflowInstancia::with(['workflow', 'tareas.asignado', 'usuarioIniciador', 'entidad'])
            ->findOrFail($workflow);
        
        $user = auth()->user();
        
        // Verificar que tiene una tarea pendiente asignada
        $tareaPendiente = $instancia->tareas()
            ->where('estado', 'pendiente')
            ->where('asignado_id', $user->id)
            ->where('asignado_type', 'App\\Models\\User')
            ->first();
            
        if (!$tareaPendiente) {
            return redirect()->route('admin.workflow.show', $instancia->id)
                ->with('error', 'No tienes tareas pendientes para este workflow');
        }
        
        // Obtener entidad
        $entidad = $instancia->entidad;
        
        return Inertia::render('admin/workflow/aprobar', [
            'workflow' => [
                'id' => $instancia->id,
                'nombre' => $instancia->workflow->nombre,
                'descripcion' => $instancia->workflow->descripcion ?? '',
                'estado' => $instancia->estado,
                'prioridad' => $instancia->datos['prioridad'] ?? 'normal',
                'fecha_solicitud' => $instancia->fecha_inicio?->toDateTimeString(),
                'fecha_vencimiento' => $instancia->datos['fecha_vencimiento'] ?? now()->addDays(7)->toDateTimeString(),
                'documento' => $entidad ? [
                    'id' => $entidad->id,
                    'nombre' => $entidad->titulo ?? $entidad->nombre ?? "Documento #{$entidad->id}",
                    'codigo' => $entidad->codigo ?? '',
                    'url' => $entidad->ruta_archivo ?? null,
                ] : [
                    'id' => $instancia->entidad_id,
                    'nombre' => 'Documento no encontrado',
                    'codigo' => '',
                ],
                'solicitante' => $instancia->usuarioIniciador->name ?? 'N/A',
                'nivel_actual' => $instancia->paso_actual,
                'total_niveles' => $instancia->tareas()->count(),
            ],
            'tarea' => [
                'id' => $tareaPendiente->id,
                'nombre' => $tareaPendiente->nombre,
                'descripcion' => $tareaPendiente->descripcion,
            ],
        ]);
    }
    
    /**
     * Procesar decisión de aprobación
     */
    public function procesarAprobacion(Request $request, $workflow)
    {
        $request->validate([
            'accion' => 'required|in:aprobado,rechazado',
            'comentarios' => 'nullable|string|max:1000',
        ]);
        
        // Buscar instancia
        $instancia = WorkflowInstancia::with(['tareas'])
            ->findOrFail($workflow);
        
        $user = auth()->user();
        
        // Buscar tarea pendiente del usuario
        $tarea = $instancia->tareas()
            ->where('estado', 'pendiente')
            ->where('asignado_id', $user->id)
            ->where('asignado_type', 'App\\Models\\User')
            ->first();
            
        if (!$tarea) {
            return redirect()->back()
                ->withErrors(['error' => 'No tienes tareas pendientes para este workflow']);
        }
        
        DB::beginTransaction();
        
        try {
            // Actualizar tarea
            $tarea->update([
                'estado' => 'completada',
                'resultado' => $request->accion,
                'comentarios' => $request->comentarios,
                'fecha_completado' => now(),
            ]);
            
            // Si fue rechazado, cancelar el workflow
            if ($request->accion === 'rechazado') {
                $instancia->update([
                    'estado' => 'cancelado',
                    'fecha_finalizacion' => now(),
                    'resultado' => 'rechazado',
                ]);
            } else {
                // Verificar si hay más tareas pendientes
                $tareasPendientes = $instancia->tareas()
                    ->where('estado', 'pendiente')
                    ->count();
                    
                if ($tareasPendientes === 0) {
                    // Workflow completado
                    $instancia->update([
                        'estado' => 'finalizado',
                        'fecha_finalizacion' => now(),
                        'resultado' => 'aprobado',
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.workflow.show', $instancia->id)
                ->with('success', 'Decisión registrada correctamente');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withErrors(['error' => 'Error al procesar la aprobación: ' . $e->getMessage()]);
        }
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
