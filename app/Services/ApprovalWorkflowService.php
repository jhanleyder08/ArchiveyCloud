<?php

namespace App\Services;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Models\User;
use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio especializado para flujos de aprobación multi-nivel
 * 
 * REQ-WF-002: Flujos de aprobación multi-nivel
 */
class ApprovalWorkflowService
{
    // Tipos de aprobación
    const APROBACION_SECUENCIAL = 'secuencial';
    const APROBACION_PARALELA = 'paralela';
    const APROBACION_MAYORIA = 'mayoria';
    const APROBACION_UNANIME = 'unanime';
    
    // Decisiones posibles
    const DECISION_APROBAR = 'aprobar';
    const DECISION_RECHAZAR = 'rechazar';
    const DECISION_SOLICITAR_CAMBIOS = 'solicitar_cambios';
    const DECISION_DELEGAR = 'delegar';
    
    // Niveles de prioridad
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';

    protected WorkflowEngineService $workflowEngine;

    public function __construct(WorkflowEngineService $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Crear flujo de aprobación para documento
     */
    public function crearAprobacionDocumento(
        Documento $documento,
        User $solicitante,
        array $configuracion = []
    ): WorkflowInstance {
        // Determinar aprobadores según la configuración del expediente/serie
        $aprobadores = $this->determinarAprobadoresDocumento($documento, $configuracion);
        
        // Crear workflow dinámico de aprobación
        $workflowConfig = $this->crearConfiguracionAprobacion([
            'tipo' => $configuracion['tipo_aprobacion'] ?? self::APROBACION_SECUENCIAL,
            'aprobadores' => $aprobadores,
            'requiere_unanimidad' => $configuracion['requiere_unanimidad'] ?? false,
            'permite_delegacion' => $configuracion['permite_delegacion'] ?? true,
            'dias_limite' => $configuracion['dias_limite'] ?? 5,
            'escalamiento_automatico' => $configuracion['escalamiento'] ?? true
        ]);
        
        // Crear instancia de workflow
        return $this->workflowEngine->iniciarWorkflow(
            $this->crearWorkflowAprobacion($workflowConfig),
            $documento,
            $solicitante,
            [
                'tipo_proceso' => 'aprobacion_documento',
                'configuracion_aprobacion' => $workflowConfig,
                'datos_documento' => $this->extraerDatosDocumento($documento)
            ]
        );
    }

    /**
     * Crear flujo de aprobación para expediente
     */
    public function crearAprobacionExpediente(
        Expediente $expediente,
        string $tipoAprobacion,
        User $solicitante,
        array $configuracion = []
    ): WorkflowInstance {
        $aprobadores = $this->determinarAprobadoresExpediente($expediente, $tipoAprobacion, $configuracion);
        
        $workflowConfig = $this->crearConfiguracionAprobacion([
            'tipo' => $configuracion['tipo_flujo'] ?? self::APROBACION_SECUENCIAL,
            'aprobadores' => $aprobadores,
            'tipo_aprobacion_expediente' => $tipoAprobacion, // cierre, transferencia, eliminacion
            'requiere_justificacion' => $configuracion['requiere_justificacion'] ?? true,
            'validaciones_previas' => $configuracion['validaciones'] ?? [],
            'dias_limite' => $this->calcularDiasLimiteExpediente($tipoAprobacion)
        ]);
        
        return $this->workflowEngine->iniciarWorkflow(
            $this->crearWorkflowAprobacion($workflowConfig),
            $expediente,
            $solicitante,
            [
                'tipo_proceso' => 'aprobacion_expediente',
                'tipo_aprobacion' => $tipoAprobacion,
                'configuracion_aprobacion' => $workflowConfig,
                'datos_expediente' => $this->extraerDatosExpediente($expediente)
            ]
        );
    }

    /**
     * Procesar decisión de aprobación
     */
    public function procesarDecisionAprobacion(
        WorkflowTask $tarea,
        User $aprobador,
        string $decision,
        array $datos = []
    ): array {
        DB::beginTransaction();
        
        try {
            // Validar decisión
            if (!in_array($decision, [self::DECISION_APROBAR, self::DECISION_RECHAZAR, self::DECISION_SOLICITAR_CAMBIOS, self::DECISION_DELEGAR])) {
                throw new \Exception("Decisión de aprobación no válida: {$decision}");
            }
            
            // Registrar decisión
            $resultadoDecision = [
                'decision' => $decision,
                'aprobador_id' => $aprobador->id,
                'aprobador_nombre' => $aprobador->name,
                'fecha_decision' => now()->toISOString(),
                'comentarios' => $datos['comentarios'] ?? null,
                'adjuntos' => $datos['adjuntos'] ?? [],
                'justificacion' => $datos['justificacion'] ?? null
            ];
            
            // Procesar según tipo de decisión
            switch ($decision) {
                case self::DECISION_APROBAR:
                    $resultado = $this->procesarAprobacion($tarea, $aprobador, $resultadoDecision);
                    break;
                    
                case self::DECISION_RECHAZAR:
                    $resultado = $this->procesarRechazo($tarea, $aprobador, $resultadoDecision);
                    break;
                    
                case self::DECISION_SOLICITAR_CAMBIOS:
                    $resultado = $this->procesarSolicitudCambios($tarea, $aprobador, $resultadoDecision);
                    break;
                    
                case self::DECISION_DELEGAR:
                    $resultado = $this->procesarDelegacion($tarea, $aprobador, $datos, $resultadoDecision);
                    break;
            }
            
            DB::commit();
            
            // Registrar en auditoría
            Log::info('Decisión de aprobación procesada', [
                'tarea_id' => $tarea->id,
                'aprobador_id' => $aprobador->id,
                'decision' => $decision,
                'instancia_id' => $tarea->instancia_id
            ]);
            
            return $resultado;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error procesando decisión de aprobación', [
                'tarea_id' => $tarea->id,
                'aprobador_id' => $aprobador->id,
                'decision' => $decision,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtener flujos de aprobación pendientes para usuario
     */
    public function obtenerAprobacionesPendientes(User $usuario, array $filtros = []): array
    {
        $query = WorkflowTask::where('estado', 'pendiente')
            ->whereIn('tipo', ['aprobacion', 'revision'])
            ->whereHas('asignaciones', function ($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)->where('activo', true);
            })
            ->with(['instancia.entidad', 'actividad']);
        
        // Aplicar filtros
        if (!empty($filtros['prioridad'])) {
            $query->where('prioridad', $filtros['prioridad']);
        }
        
        if (!empty($filtros['tipo_entidad'])) {
            $query->whereHas('instancia', function ($q) use ($filtros) {
                $q->where('entidad_tipo', $filtros['tipo_entidad']);
            });
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }
        
        $tareas = $query->orderBy('prioridad', 'desc')
            ->orderBy('fecha_limite', 'asc')
            ->get();
        
        return $tareas->map(function ($tarea) {
            return $this->formatearTareaAprobacion($tarea);
        })->toArray();
    }

    /**
     * Escalar aprobación por vencimiento
     */
    public function escalarAprobacionesVencidas(): array
    {
        $tareasVencidas = WorkflowTask::where('estado', 'pendiente')
            ->whereIn('tipo', ['aprobacion', 'revision'])
            ->where('fecha_limite', '<', now())
            ->with(['instancia', 'asignaciones.usuario'])
            ->get();
        
        $resultados = [];
        
        foreach ($tareasVencidas as $tarea) {
            try {
                $configuracion = $tarea->configuracion ?? [];
                
                if ($configuracion['escalamiento_automatico'] ?? false) {
                    $supervisor = $this->obtenerSupervisor($tarea);
                    
                    if ($supervisor) {
                        // Reasignar a supervisor
                        $this->reasignarTarea($tarea, $supervisor, 'Escalamiento por vencimiento');
                        
                        $resultados[] = [
                            'tarea_id' => $tarea->id,
                            'accion' => 'escalado',
                            'supervisor_id' => $supervisor->id,
                            'supervisor_nombre' => $supervisor->name
                        ];
                    } else {
                        // Marcar como vencida
                        $this->marcarTareaVencida($tarea);
                        
                        $resultados[] = [
                            'tarea_id' => $tarea->id,
                            'accion' => 'vencida',
                            'motivo' => 'No se encontró supervisor para escalamiento'
                        ];
                    }
                }
                
            } catch (\Exception $e) {
                Log::error('Error escalando tarea vencida', [
                    'tarea_id' => $tarea->id,
                    'error' => $e->getMessage()
                ]);
                
                $resultados[] = [
                    'tarea_id' => $tarea->id,
                    'accion' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $resultados;
    }

    /**
     * Generar reporte de flujos de aprobación
     */
    public function generarReporteAprobaciones(array $parametros = []): array
    {
        $fechaInicio = $parametros['fecha_inicio'] ?? now()->subMonth();
        $fechaFin = $parametros['fecha_fin'] ?? now();
        
        $instancias = WorkflowInstance::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->whereHas('workflow', function ($q) {
                $q->where('tipo', 'aprobacion');
            })
            ->with(['tareas', 'workflow'])
            ->get();
        
        $estadisticas = [
            'total_procesos' => $instancias->count(),
            'completados' => $instancias->where('estado', 'completado')->count(),
            'en_progreso' => $instancias->where('estado', 'en_progreso')->count(),
            'cancelados' => $instancias->where('estado', 'cancelado')->count(),
            'tiempo_promedio_aprobacion' => $this->calcularTiempoPromedio($instancias),
            'tasa_aprobacion' => $this->calcularTasaAprobacion($instancias),
            'aprobadores_mas_activos' => $this->obtenerAprobadoresMasActivos($instancias),
            'procesos_por_tipo' => $this->agruparPorTipoEntidad($instancias)
        ];
        
        return [
            'periodo' => [
                'inicio' => $fechaInicio->toDateString(),
                'fin' => $fechaFin->toDateString()
            ],
            'estadisticas' => $estadisticas,
            'detalle_procesos' => $instancias->map(function ($instancia) {
                return $this->formatearInstanciaReporte($instancia);
            })->toArray()
        ];
    }

    // Métodos auxiliares privados
    private function determinarAprobadoresDocumento(Documento $documento, array $config): array
    {
        // Lógica para determinar aprobadores según expediente, serie, tipología
        return []; // Implementación específica
    }
    
    private function determinarAprobadoresExpediente(Expediente $expediente, string $tipo, array $config): array
    {
        // Lógica para determinar aprobadores según tipo de operación
        return []; // Implementación específica
    }
    
    private function crearConfiguracionAprobacion(array $params): array
    {
        return $params; // Procesar y validar configuración
    }
    
    private function crearWorkflowAprobacion(array $config): object
    {
        // Crear objeto workflow dinámico
        return new \stdClass(); // Implementación específica
    }
    
    private function extraerDatosDocumento(Documento $documento): array
    {
        return [
            'nombre' => $documento->nombre,
            'expediente' => $documento->expediente?->nombre,
            'serie' => $documento->expediente?->serie?->nombre,
            'formato' => $documento->formato,
            'tamaño' => $documento->tamaño
        ];
    }
    
    private function extraerDatosExpediente(Expediente $expediente): array
    {
        return [
            'nombre' => $expediente->nombre,
            'codigo' => $expediente->codigo,
            'serie' => $expediente->serie?->nombre,
            'total_documentos' => $expediente->documentos()->count(),
            'estado' => $expediente->estado
        ];
    }
    
    private function calcularDiasLimiteExpediente(string $tipo): int
    {
        return match($tipo) {
            'cierre' => 3,
            'transferencia' => 7,
            'eliminacion' => 15,
            default => 5
        };
    }
    
    // Métodos de procesamiento de decisiones - implementación básica
    private function procesarAprobacion($tarea, $aprobador, $resultado): array { return ['resultado' => 'aprobado']; }
    private function procesarRechazo($tarea, $aprobador, $resultado): array { return ['resultado' => 'rechazado']; }
    private function procesarSolicitudCambios($tarea, $aprobador, $resultado): array { return ['resultado' => 'cambios_solicitados']; }
    private function procesarDelegacion($tarea, $aprobador, $datos, $resultado): array { return ['resultado' => 'delegado']; }
    private function formatearTareaAprobacion($tarea): array { return []; }
    private function obtenerSupervisor($tarea): ?User { return null; }
    private function reasignarTarea($tarea, $supervisor, $motivo): void { }
    private function marcarTareaVencida($tarea): void { }
    private function calcularTiempoPromedio($instancias): float { return 0.0; }
    private function calcularTasaAprobacion($instancias): float { return 0.0; }
    private function obtenerAprobadoresMasActivos($instancias): array { return []; }
    private function agruparPorTipoEntidad($instancias): array { return []; }
    private function formatearInstanciaReporte($instancia): array { return []; }
}
