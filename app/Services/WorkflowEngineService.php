<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Models\WorkflowTransition;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

/**
 * Motor de Workflow para SGDEA
 * 
 * Implementa requerimientos:
 * REQ-WF-001: Motor de workflow configurable
 * REQ-WF-002: Flujos de aprobación multi-nivel  
 * REQ-WF-003: Automatización de procesos
 * REQ-WF-004: Seguimiento y auditoría de procesos
 */
class WorkflowEngineService
{
    // Estados de instancia de workflow
    const ESTADO_INICIADO = 'iniciado';
    const ESTADO_EN_PROGRESO = 'en_progreso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';
    const ESTADO_SUSPENDIDO = 'suspendido';
    
    // Tipos de tarea
    const TAREA_MANUAL = 'manual';
    const TAREA_AUTOMATICA = 'automatica';
    const TAREA_APROBACION = 'aprobacion';
    const TAREA_REVISION = 'revision';
    const TAREA_FIRMA = 'firma';
    const TAREA_NOTIFICACION = 'notificacion';
    
    // Tipos de transición
    const TRANSICION_AUTOMATICA = 'automatica';
    const TRANSICION_MANUAL = 'manual';
    const TRANSICION_CONDICIONAL = 'condicional';

    /**
     * REQ-WF-001: Iniciar instancia de workflow
     */
    public function iniciarWorkflow(
        Workflow $workflow,
        $entidad,
        User $iniciador,
        array $datosIniciales = []
    ): WorkflowInstance {
        DB::beginTransaction();
        
        try {
            // Validar que el workflow esté activo
            if (!$workflow->activo) {
                throw new Exception("El workflow '{$workflow->nombre}' no está activo");
            }
            
            // Crear instancia de workflow
            $instancia = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'entidad_tipo' => get_class($entidad),
                'entidad_id' => $entidad->id,
                'usuario_iniciador_id' => $iniciador->id,
                'estado' => self::ESTADO_INICIADO,
                'datos_contexto' => array_merge($datosIniciales, [
                    'entidad_nombre' => $this->obtenerNombreEntidad($entidad),
                    'fecha_inicio' => now()->toISOString(),
                    'iniciador' => $iniciador->name
                ]),
                'prioridad' => $datosIniciales['prioridad'] ?? $workflow->prioridad_default,
                'fecha_limite' => $this->calcularFechaLimite($workflow, $datosIniciales),
                'codigo_seguimiento' => $this->generarCodigoSeguimiento($workflow)
            ]);
            
            // Obtener primera actividad del workflow
            $primeraActividad = $workflow->actividades()
                ->where('es_inicial', true)
                ->first();
            
            if (!$primeraActividad) {
                throw new Exception("El workflow no tiene una actividad inicial definida");
            }
            
            // Crear primera tarea
            $primeraTarea = $this->crearTarea(
                $instancia,
                $primeraActividad,
                $iniciador,
                $datosIniciales
            );
            
            // Actualizar estado de la instancia
            $instancia->update([
                'estado' => self::ESTADO_EN_PROGRESO,
                'tarea_actual_id' => $primeraTarea->id,
                'fecha_inicio' => now()
            ]);
            
            // Procesar tarea si es automática
            if ($primeraActividad->tipo === self::TAREA_AUTOMATICA) {
                $this->procesarTareaAutomatica($primeraTarea);
            } else {
                // Asignar tarea a usuario(s)
                $this->asignarTarea($primeraTarea, $primeraActividad);
            }
            
            DB::commit();
            
            Log::info('Workflow iniciado', [
                'workflow_id' => $workflow->id,
                'instancia_id' => $instancia->id,
                'entidad_tipo' => get_class($entidad),
                'entidad_id' => $entidad->id,
                'iniciador_id' => $iniciador->id
            ]);
            
            // Disparar evento de inicio
            event(new \App\Events\WorkflowStartedEvent($instancia, $primeraTarea));
            
            return $instancia->fresh(['tareas', 'workflow']);
            
        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('Error iniciando workflow', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception("Error al iniciar workflow: {$e->getMessage()}");
        }
    }

    /**
     * REQ-WF-002: Completar tarea de workflow
     */
    public function completarTarea(
        WorkflowTask $tarea,
        User $usuario,
        array $datosComplecion = []
    ): ?WorkflowTask {
        DB::beginTransaction();
        
        try {
            // Validar que la tarea esté en estado pendiente
            if ($tarea->estado !== 'pendiente') {
                throw new Exception("La tarea no está en estado pendiente");
            }
            
            // Validar permisos del usuario
            if (!$this->usuarioPuedeCompletarTarea($tarea, $usuario)) {
                throw new Exception("El usuario no tiene permisos para completar esta tarea");
            }
            
            // Procesar según tipo de tarea
            $resultadoComplecion = $this->procesarComplecionTarea($tarea, $datosComplecion);
            
            // Actualizar tarea
            $tarea->update([
                'estado' => 'completada',
                'usuario_completado_id' => $usuario->id,
                'fecha_completado' => now(),
                'resultado' => $resultadoComplecion['resultado'],
                'datos_resultado' => $resultadoComplecion['datos'],
                'comentarios' => $datosComplecion['comentarios'] ?? null
            ]);
            
            // Determinar siguiente actividad
            $siguienteActividad = $this->determinarSiguienteActividad($tarea, $resultadoComplecion);
            
            $siguienteTarea = null;
            if ($siguienteActividad) {
                // Crear siguiente tarea
                $siguienteTarea = $this->crearTarea(
                    $tarea->instancia,
                    $siguienteActividad,
                    $usuario,
                    $datosComplecion
                );
                
                // Actualizar instancia
                $tarea->instancia->update([
                    'tarea_actual_id' => $siguienteTarea->id,
                    'datos_contexto' => array_merge(
                        $tarea->instancia->datos_contexto ?? [],
                        $resultadoComplecion['contexto_actualizado'] ?? []
                    )
                ]);
                
                // Procesar siguiente tarea si es automática
                if ($siguienteActividad->tipo === self::TAREA_AUTOMATICA) {
                    return $this->procesarTareaAutomatica($siguienteTarea);
                } else {
                    $this->asignarTarea($siguienteTarea, $siguienteActividad);
                }
                
            } else {
                // No hay más actividades - completar workflow
                $this->completarWorkflow($tarea->instancia, $usuario);
            }
            
            DB::commit();
            
            Log::info('Tarea completada', [
                'tarea_id' => $tarea->id,
                'usuario_id' => $usuario->id,
                'resultado' => $resultadoComplecion['resultado'],
                'siguiente_tarea_id' => $siguienteTarea?->id
            ]);
            
            // Disparar eventos
            event(new \App\Events\WorkflowTaskCompletedEvent($tarea, $usuario));
            
            if ($siguienteTarea) {
                event(new \App\Events\WorkflowTaskCreatedEvent($siguienteTarea));
            }
            
            return $siguienteTarea;
            
        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('Error completando tarea', [
                'tarea_id' => $tarea->id,
                'usuario_id' => $usuario->id,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception("Error al completar tarea: {$e->getMessage()}");
        }
    }

    /**
     * REQ-WF-003: Procesar tarea automática
     */
    private function procesarTareaAutomatica(WorkflowTask $tarea): ?WorkflowTask
    {
        try {
            $actividad = $tarea->actividad;
            $accionAutomatica = $actividad->configuracion['accion_automatica'] ?? null;
            
            if (!$accionAutomatica) {
                throw new Exception("Tarea automática sin acción definida");
            }
            
            $resultado = match($accionAutomatica) {
                'aprobar_automatico' => $this->procesarAprobacionAutomatica($tarea),
                'generar_documento' => $this->procesarGeneracionDocumento($tarea),
                'enviar_notificacion' => $this->procesarNotificacionAutomatica($tarea),
                'validar_condiciones' => $this->procesarValidacionCondiciones($tarea),
                'asignar_serie' => $this->procesarAsignacionSerie($tarea),
                'calcular_vencimientos' => $this->procesarCalculoVencimientos($tarea),
                default => throw new Exception("Acción automática no reconocida: {$accionAutomatica}")
            };
            
            // Completar tarea automática
            return $this->completarTarea(
                $tarea,
                User::where('email', 'sistema@sgdea.com')->first() ?? User::first(),
                $resultado
            );
            
        } catch (Exception $e) {
            // Marcar tarea como fallida
            $tarea->update([
                'estado' => 'fallida',
                'error_procesamiento' => $e->getMessage(),
                'fecha_fallo' => now()
            ]);
            
            Log::error('Error en tarea automática', [
                'tarea_id' => $tarea->id,
                'accion' => $accionAutomatica ?? 'desconocida',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * REQ-WF-004: Obtener estado actual del workflow
     */
    public function obtenerEstadoWorkflow(WorkflowInstance $instancia): array
    {
        $instancia->load(['workflow', 'tareas.actividad', 'tareas.asignaciones.usuario']);
        
        return [
            'instancia' => [
                'id' => $instancia->id,
                'codigo_seguimiento' => $instancia->codigo_seguimiento,
                'estado' => $instancia->estado,
                'progreso' => $this->calcularProgreso($instancia),
                'fecha_inicio' => $instancia->fecha_inicio,
                'fecha_limite' => $instancia->fecha_limite,
                'prioridad' => $instancia->prioridad
            ],
            'workflow' => [
                'id' => $instancia->workflow->id,
                'nombre' => $instancia->workflow->nombre,
                'descripcion' => $instancia->workflow->descripcion,
                'version' => $instancia->workflow->version
            ],
            'entidad' => [
                'tipo' => $instancia->entidad_tipo,
                'id' => $instancia->entidad_id,
                'nombre' => $instancia->datos_contexto['entidad_nombre'] ?? 'N/A'
            ],
            'tarea_actual' => $this->formatearTareaActual($instancia),
            'historial_tareas' => $this->formatearHistorialTareas($instancia),
            'siguiente_acciones' => $this->obtenerSiguientesAcciones($instancia),
            'metricas' => $this->calcularMetricas($instancia)
        ];
    }

    /**
     * Cancelar instancia de workflow
     */
    public function cancelarWorkflow(
        WorkflowInstance $instancia,
        User $usuario,
        string $motivo
    ): void {
        DB::beginTransaction();
        
        try {
            // Cancelar tareas pendientes
            $instancia->tareas()
                ->where('estado', 'pendiente')
                ->update([
                    'estado' => 'cancelada',
                    'fecha_cancelacion' => now(),
                    'motivo_cancelacion' => $motivo
                ]);
            
            // Actualizar instancia
            $instancia->update([
                'estado' => self::ESTADO_CANCELADO,
                'usuario_cancelado_id' => $usuario->id,
                'fecha_cancelacion' => now(),
                'motivo_cancelacion' => $motivo
            ]);
            
            DB::commit();
            
            Log::info('Workflow cancelado', [
                'instancia_id' => $instancia->id,
                'usuario_id' => $usuario->id,
                'motivo' => $motivo
            ]);
            
            event(new \App\Events\WorkflowCancelledEvent($instancia, $usuario, $motivo));
            
        } catch (Exception $e) {
            DB::rollback();
            throw new Exception("Error cancelando workflow: {$e->getMessage()}");
        }
    }

    // Métodos auxiliares privados
    private function obtenerNombreEntidad($entidad): string
    {
        return match(get_class($entidad)) {
            Documento::class => $entidad->nombre,
            Expediente::class => $entidad->nombre,
            default => "Entidad ID: {$entidad->id}"
        };
    }

    private function calcularFechaLimite(Workflow $workflow, array $datos): ?string
    {
        $diasLimite = $datos['dias_limite'] ?? $workflow->dias_limite_default;
        return $diasLimite ? now()->addDays($diasLimite)->toDateTimeString() : null;
    }

    private function generarCodigoSeguimiento(Workflow $workflow): string
    {
        $prefijo = strtoupper(substr($workflow->codigo, 0, 3));
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return "{$prefijo}-{$timestamp}-{$random}";
    }

    private function crearTarea(
        WorkflowInstance $instancia,
        $actividad,
        User $usuario,
        array $datos = []
    ): WorkflowTask {
        return WorkflowTask::create([
            'instancia_id' => $instancia->id,
            'actividad_id' => $actividad->id,
            'nombre' => $actividad->nombre,
            'descripcion' => $actividad->descripcion,
            'tipo' => $actividad->tipo,
            'estado' => 'pendiente',
            'prioridad' => $datos['prioridad'] ?? $actividad->prioridad,
            'fecha_limite' => $this->calcularFechaLimiteTarea($actividad, $datos),
            'datos_entrada' => $datos,
            'configuracion' => $actividad->configuracion ?? []
        ]);
    }

    private function usuarioPuedeCompletarTarea(WorkflowTask $tarea, User $usuario): bool
    {
        return $tarea->asignaciones()
            ->where('usuario_id', $usuario->id)
            ->where('activo', true)
            ->exists();
    }

    private function procesarComplecionTarea(WorkflowTask $tarea, array $datos): array
    {
        return match($tarea->tipo) {
            self::TAREA_APROBACION => $this->procesarAprobacion($datos),
            self::TAREA_REVISION => $this->procesarRevision($datos),
            self::TAREA_FIRMA => $this->procesarFirma($datos),
            default => [
                'resultado' => $datos['resultado'] ?? 'completada',
                'datos' => $datos,
                'contexto_actualizado' => []
            ]
        };
    }

    private function determinarSiguienteActividad(WorkflowTask $tarea, array $resultado): ?object
    {
        $transiciones = $tarea->actividad->transicionesSalida()
            ->where('activa', true)
            ->get();

        foreach ($transiciones as $transicion) {
            if ($this->evaluarCondicionTransicion($transicion, $resultado, $tarea)) {
                return $transicion->actividadDestino;
            }
        }

        return null;
    }

    private function completarWorkflow(WorkflowInstance $instancia, User $usuario): void
    {
        $instancia->update([
            'estado' => self::ESTADO_COMPLETADO,
            'fecha_completado' => now(),
            'usuario_completado_id' => $usuario->id
        ]);

        event(new \App\Events\WorkflowCompletedEvent($instancia));
    }

    private function calcularProgreso(WorkflowInstance $instancia): float
    {
        $totalTareas = $instancia->tareas()->count();
        $tareasCompletadas = $instancia->tareas()->whereIn('estado', ['completada', 'cancelada'])->count();
        
        return $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100, 2) : 0;
    }

    private function asignarTarea(WorkflowTask $tarea, $actividad): void
    {
        // Implementar lógica de asignación según configuración
        $configuracionAsignacion = $actividad->configuracion['asignacion'] ?? [];
        
        // Por ahora, asignar al usuario iniciador del workflow
        $usuarioAsignado = User::find($tarea->instancia->usuario_iniciador_id);
        
        $tarea->asignaciones()->create([
            'usuario_id' => $usuarioAsignado->id,
            'fecha_asignacion' => now(),
            'activo' => true
        ]);
    }

    // Métodos de procesamiento específico - implementación básica
    private function procesarAprobacionAutomatica(WorkflowTask $tarea): array { return ['resultado' => 'aprobado']; }
    private function procesarGeneracionDocumento(WorkflowTask $tarea): array { return ['resultado' => 'documento_generado']; }
    private function procesarNotificacionAutomatica(WorkflowTask $tarea): array { return ['resultado' => 'notificacion_enviada']; }
    private function procesarValidacionCondiciones(WorkflowTask $tarea): array { return ['resultado' => 'condiciones_validadas']; }
    private function procesarAsignacionSerie(WorkflowTask $tarea): array { return ['resultado' => 'serie_asignada']; }
    private function procesarCalculoVencimientos(WorkflowTask $tarea): array { return ['resultado' => 'vencimientos_calculados']; }
    private function procesarAprobacion(array $datos): array { return ['resultado' => $datos['decision'] ?? 'aprobado', 'datos' => $datos]; }
    private function procesarRevision(array $datos): array { return ['resultado' => 'revisado', 'datos' => $datos]; }
    private function procesarFirma(array $datos): array { return ['resultado' => 'firmado', 'datos' => $datos]; }
    private function calcularFechaLimiteTarea($actividad, array $datos): ?string { return null; }
    private function evaluarCondicionTransicion($transicion, array $resultado, WorkflowTask $tarea): bool { return true; }
    private function formatearTareaActual(WorkflowInstance $instancia): ?array { return null; }
    private function formatearHistorialTareas(WorkflowInstance $instancia): array { return []; }
    private function obtenerSiguientesAcciones(WorkflowInstance $instancia): array { return []; }
    private function calcularMetricas(WorkflowInstance $instancia): array { return []; }
}
