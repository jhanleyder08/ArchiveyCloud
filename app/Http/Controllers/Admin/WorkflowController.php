<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDocumento;
use App\Models\Documento;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    protected $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->workflowService = $workflowService;
    }

    /**
     * Dashboard de workflows del usuario
     */
    public function index(Request $request)
    {
        $usuarioId = auth()->id();

        // Workflows pendientes de aprobación del usuario
        $workflowsPendientes = $this->workflowService->getWorkflowsPendientes($usuarioId);

        // Workflows solicitados por el usuario
        $workflowsSolicitados = WorkflowDocumento::with(['documento', 'revisorActual', 'aprobadorFinal'])
            ->where('solicitante_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'documento' => [
                        'id' => $workflow->documento->id,
                        'nombre' => $workflow->documento->titulo,
                        'codigo' => $workflow->documento->codigo_documento
                    ],
                    'estado' => $workflow->estado,
                    'prioridad' => $workflow->etiqueta_prioridad,
                    'revisor_actual' => $workflow->revisorActual?->name,
                    'fecha_solicitud' => $workflow->fecha_solicitud,
                    'progreso' => $workflow->progreso,
                    'esta_vencido' => $workflow->esta_vencido
                ];
            });

        // Estadísticas
        $estadisticas = [
            'pendientes_aprobacion' => count($workflowsPendientes),
            'solicitados_activos' => WorkflowDocumento::where('solicitante_id', $usuarioId)
                ->whereIn('estado', [WorkflowDocumento::ESTADO_PENDIENTE, WorkflowDocumento::ESTADO_EN_REVISION])
                ->count(),
            'aprobados_mes' => WorkflowDocumento::where('solicitante_id', $usuarioId)
                ->where('estado', WorkflowDocumento::ESTADO_APROBADO)
                ->whereMonth('fecha_aprobacion', now()->month)
                ->count(),
            'rechazados_mes' => WorkflowDocumento::where('solicitante_id', $usuarioId)
                ->where('estado', WorkflowDocumento::ESTADO_RECHAZADO)
                ->whereMonth('fecha_rechazo', now()->month)
                ->count(),
        ];

        return Inertia::render('admin/workflow/index', [
            'workflowsPendientes' => $workflowsPendientes,
            'workflowsSolicitados' => $workflowsSolicitados,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Mostrar formulario para iniciar workflow
     */
    public function create(Request $request)
    {
        $documento = null;
        if ($request->has('documento_id')) {
            $documento = Documento::with(['expediente'])->findOrFail($request->documento_id);
        }

        // Obtener usuarios disponibles para aprobación
        $usuariosDisponibles = User::where('estado_cuenta', 'activo')
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'cargo']);

        return Inertia::render('admin/workflow/create', [
            'documento' => $documento ? [
                'id' => $documento->id,
                'nombre' => $documento->titulo,
                'codigo' => $documento->codigo_documento,
                'expediente' => $documento->expediente ? [
                    'numero' => $documento->expediente->numero_expediente,
                    'titulo' => $documento->expediente->titulo
                ] : null
            ] : null,
            'usuariosDisponibles' => $usuariosDisponibles,
        ]);
    }

    /**
     * Iniciar nuevo workflow
     */
    public function store(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'aprobadores' => 'required|array|min:1',
            'aprobadores.*' => 'exists:users,id',
            'descripcion' => 'nullable|string|max:1000',
            'prioridad' => 'required|integer|in:1,2,3,4',
            'requiere_unanime' => 'boolean',
            'dias_vencimiento' => 'required|integer|min:1|max:30'
        ]);

        try {
            $documento = Documento::findOrFail($request->documento_id);

            $datos = [
                'aprobadores' => $request->aprobadores,
                'descripcion' => $request->descripcion,
                'prioridad' => $request->prioridad,
                'requiere_unanime' => $request->requiere_unanime ?? false,
                'fecha_vencimiento' => now()->addDays($request->dias_vencimiento),
            ];

            $workflow = $this->workflowService->iniciarWorkflow($documento, $datos);

            return redirect()
                ->route('admin.workflow.show', $workflow)
                ->with('success', 'Workflow de aprobación iniciado exitosamente');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Mostrar detalles del workflow
     */
    public function show(WorkflowDocumento $workflow)
    {
        $workflow->load([
            'documento',
            'solicitante',
            'revisorActual',
            'aprobadorFinal',
            'aprobaciones.usuario'
        ]);

        // Obtener niveles de aprobación con información de usuarios
        $nivelesAprobacion = collect($workflow->niveles_aprobacion)
            ->map(function ($userId, $nivel) use ($workflow) {
                $user = User::find($userId);
                $aprobacion = $workflow->aprobaciones
                    ->where('nivel_aprobacion', $nivel)
                    ->first();

                return [
                    'nivel' => $nivel,
                    'usuario' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'cargo' => $user->cargo
                    ] : null,
                    'es_actual' => $nivel === $workflow->nivel_actual,
                    'completado' => $aprobacion !== null,
                    'aprobacion' => $aprobacion ? [
                        'accion' => $aprobacion->etiqueta_accion,
                        'comentarios' => $aprobacion->comentarios,
                        'fecha' => $aprobacion->fecha_accion,
                        'tiempo_respuesta' => $aprobacion->tiempo_respuesta_horas
                    ] : null
                ];
            });

        return Inertia::render('admin/workflow/show', [
            'workflow' => [
                'id' => $workflow->id,
                'estado' => $workflow->estado,
                'progreso' => $workflow->progreso,
                'prioridad' => $workflow->etiqueta_prioridad,
                'descripcion' => $workflow->descripcion_solicitud,
                'fecha_solicitud' => $workflow->fecha_solicitud,
                'fecha_vencimiento' => $workflow->fecha_vencimiento,
                'fecha_aprobacion' => $workflow->fecha_aprobacion,
                'fecha_rechazo' => $workflow->fecha_rechazo,
                'comentarios_finales' => $workflow->comentarios_finales,
                'esta_vencido' => $workflow->esta_vencido,
                'requiere_unanime' => $workflow->requiere_aprobacion_unanime,
                'documento' => [
                    'id' => $workflow->documento->id,
                    'nombre' => $workflow->documento->titulo,
                    'codigo' => $workflow->documento->codigo_documento
                ],
                'solicitante' => [
                    'name' => $workflow->solicitante->name,
                    'email' => $workflow->solicitante->email
                ],
                'revisor_actual' => $workflow->revisorActual ? [
                    'name' => $workflow->revisorActual->name,
                    'email' => $workflow->revisorActual->email
                ] : null,
                'aprobador_final' => $workflow->aprobadorFinal ? [
                    'name' => $workflow->aprobadorFinal->name,
                    'email' => $workflow->aprobadorFinal->email
                ] : null
            ],
            'nivelesAprobacion' => $nivelesAprobacion,
            'puedeAprobar' => $workflow->puedeAprobar(auth()->user()),
            'esSolicitante' => $workflow->solicitante_id === auth()->id(),
        ]);
    }

    /**
     * Mostrar formulario de aprobación
     */
    public function aprobar(WorkflowDocumento $workflow)
    {
        if (!$workflow->puedeAprobar(auth()->user())) {
            return redirect()
                ->route('admin.workflow.show', $workflow)
                ->withErrors(['error' => 'No tienes permisos para aprobar este documento']);
        }

        return Inertia::render('admin/workflow/aprobar', [
            'workflow' => [
                'id' => $workflow->id,
                'estado' => $workflow->estado,
                'descripcion' => $workflow->descripcion_solicitud,
                'prioridad' => $workflow->etiqueta_prioridad,
                'fecha_solicitud' => $workflow->fecha_solicitud,
                'fecha_vencimiento' => $workflow->fecha_vencimiento,
                'documento' => [
                    'id' => $workflow->documento->id,
                    'nombre' => $workflow->documento->titulo,
                    'codigo' => $workflow->documento->codigo_documento
                ],
                'solicitante' => $workflow->solicitante->name,
                'nivel_actual' => $workflow->nivel_actual + 1,
                'total_niveles' => count($workflow->niveles_aprobacion)
            ]
        ]);
    }

    /**
     * Procesar aprobación o rechazo
     */
    public function procesarAprobacion(Request $request, WorkflowDocumento $workflow)
    {
        $request->validate([
            'accion' => 'required|string|in:aprobado,rechazado',
            'comentarios' => 'nullable|string|max:1000',
            'archivos_adjuntos' => 'nullable|array'
        ]);

        try {
            $datos = [
                'comentarios' => $request->comentarios,
                'archivos_adjuntos' => $request->archivos_adjuntos
            ];

            $this->workflowService->procesarAprobacion($workflow, $request->accion, $datos);

            $mensaje = $request->accion === 'aprobado' 
                ? 'Documento aprobado exitosamente'
                : 'Documento rechazado';

            return redirect()
                ->route('admin.workflow.show', $workflow)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Delegar aprobación
     */
    public function delegar(Request $request, WorkflowDocumento $workflow)
    {
        $request->validate([
            'nuevo_aprobador_id' => 'required|exists:users,id',
            'comentarios' => 'nullable|string|max:500'
        ]);

        try {
            $this->workflowService->delegarAprobacion(
                $workflow,
                $request->nuevo_aprobador_id,
                $request->comentarios
            );

            return redirect()
                ->route('admin.workflow.show', $workflow)
                ->with('success', 'Aprobación delegada exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancelar workflow
     */
    public function cancelar(Request $request, WorkflowDocumento $workflow)
    {
        $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        try {
            if ($workflow->solicitante_id !== auth()->id()) {
                throw new \Exception('Solo el solicitante puede cancelar el workflow');
            }

            $this->workflowService->cancelarWorkflow($workflow, $request->motivo);

            return redirect()
                ->route('admin.workflow.index')
                ->with('success', 'Workflow cancelado exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
