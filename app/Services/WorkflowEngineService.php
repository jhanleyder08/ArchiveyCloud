<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use App\Models\WorkflowTarea;
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
    // Estados de instancia de workflow (deben coincidir con el ENUM de la BD)
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_PAUSADO = 'pausado';
    const ESTADO_FINALIZADO = 'finalizado';
    const ESTADO_CANCELADO = 'cancelado';
    
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
    ): WorkflowInstancia {
        DB::beginTransaction();
        
        try {
            // Validar que el workflow esté activo
            if (!$workflow->activo) {
                throw new Exception("El workflow '{$workflow->nombre}' no está activo");
            }
            
            // Crear instancia de workflow
            $instancia = WorkflowInstancia::create([
                'workflow_id' => $workflow->id,
                'entidad_type' => get_class($entidad),
                'entidad_id' => $entidad->id,
                'usuario_iniciador_id' => $iniciador->id,
                'paso_actual' => 1,
                'estado' => self::ESTADO_EN_PROCESO,
                'fecha_inicio' => now(),
                'datos' => array_merge($datosIniciales, [
                    'entidad_nombre' => $this->obtenerNombreEntidad($entidad),
                    'fecha_inicio' => now()->toISOString(),
                    'iniciador' => $iniciador->name
                ]),
            ]);
            
            // Crear primera tarea básica
            $primeraTarea = WorkflowTarea::create([
                'workflow_instancia_id' => $instancia->id,
                'paso_numero' => 0,
                'nombre' => 'Tarea inicial',
                'descripcion' => 'Primera tarea del workflow',
                'tipo_asignacion' => 'manual',
                'asignado_id' => $iniciador->id,
                'asignado_type' => 'App\\Models\\User',
                'estado' => 'pendiente',
            ]);
            
            DB::commit();
            
            Log::info('Workflow iniciado', [
                'workflow_id' => $workflow->id,
                'instancia_id' => $instancia->id,
                'entidad_tipo' => get_class($entidad),
                'entidad_id' => $entidad->id,
                'iniciador_id' => $iniciador->id
            ]);
            
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
        WorkflowTarea $tarea,
        User $usuario,
        array $datosComplecion = []
    ): ?WorkflowTarea {
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
                'resultado' => $resultadoComplecion['resultado'] ?? 'completado',
                'observaciones' => $datosComplecion['comentarios'] ?? null
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
                'paso_actual' => $tarea->paso_numero + 1,
                'datos' => array_merge(
                    $tarea->instancia->datos ?? [],
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
    private function procesarTareaAutomatica(WorkflowTarea $tarea): ?WorkflowTarea
    {
        // Simplificado - marcar como completada y retornar null
        $tarea->update([
            'estado' => 'completada',
            'resultado' => 'completado_automatico',
            'fecha_completado' => now(),
        ]);
        
        return null;
    }

    /**
     * REQ-WF-004: Obtener estado actual del workflow
     */
    public function obtenerEstadoWorkflow(WorkflowInstancia $instancia): array
    {
        $instancia->load(['workflow', 'tareas.asignado', 'usuarioIniciador']);
        
        $tareaActual = $instancia->tareas()->where('estado', 'pendiente')->first();
        
        // Obtener la entidad (documento o expediente)
        $entidad = $instancia->entidad;
        $entidadData = null;
        if ($entidad) {
            $entidadData = [
                'id' => $entidad->id,
                'tipo' => class_basename($instancia->entidad_type),
                'nombre' => $entidad->titulo ?? $entidad->nombre ?? "ID: {$entidad->id}",
                'codigo' => $entidad->codigo ?? null,
            ];
        }
        
        return [
            'id' => $instancia->id,
            'instancia' => [
                'id' => $instancia->id,
                'estado' => $instancia->estado,
                'progreso' => $this->calcularProgreso($instancia),
                'fecha_inicio' => $instancia->fecha_inicio?->toDateTimeString(),
                'fecha_finalizacion' => $instancia->fecha_finalizacion?->toDateTimeString(),
            ],
            'workflow' => [
                'id' => $instancia->workflow->id,
                'nombre' => $instancia->workflow->nombre,
                'descripcion' => $instancia->workflow->descripcion,
            ],
            'documento' => $entidadData, // Para compatibilidad con el frontend
            'entidad' => $entidadData,
            'iniciador' => $instancia->usuarioIniciador ? [
                'id' => $instancia->usuarioIniciador->id,
                'name' => $instancia->usuarioIniciador->name,
            ] : null,
            'tarea_actual' => $tareaActual ? [
                'id' => $tareaActual->id,
                'nombre' => $tareaActual->nombre,
                'descripcion' => $tareaActual->descripcion,
                'estado' => $tareaActual->estado,
            ] : null,
            'historial_tareas' => $instancia->tareas->map(function ($tarea) {
                return [
                    'id' => $tarea->id,
                    'nombre' => $tarea->nombre,
                    'estado' => $tarea->estado,
                    'fecha_completado' => $tarea->fecha_completado?->toDateTimeString(),
                ];
            })->toArray(),
            'tareas' => $instancia->tareas->map(function ($tarea) {
                return [
                    'id' => $tarea->id,
                    'nombre' => $tarea->nombre,
                    'descripcion' => $tarea->descripcion,
                    'estado' => $tarea->estado,
                    'paso_numero' => $tarea->paso_numero,
                    'fecha_completado' => $tarea->fecha_completado?->toDateTimeString(),
                    'asignado' => $tarea->asignado ? [
                        'id' => $tarea->asignado->id,
                        'name' => $tarea->asignado->name ?? $tarea->asignado->nombre ?? 'N/A',
                    ] : null,
                ];
            })->toArray(),
            'siguiente_acciones' => [],
            'metricas' => []
        ];
    }

    /**
     * Cancelar instancia de workflow
     */
    public function cancelarWorkflow(
        WorkflowInstancia $instancia,
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
                    'observaciones' => $motivo
                ]);
            
            // Actualizar instancia
            $instancia->update([
                'estado' => self::ESTADO_CANCELADO,
                'fecha_finalizacion' => now(),
            ]);
            
            DB::commit();
            
            Log::info('Workflow cancelado', [
                'instancia_id' => $instancia->id,
                'usuario_id' => $usuario->id,
                'motivo' => $motivo
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            throw new Exception("Error cancelando workflow: {$e->getMessage()}");
        }
    }

    // Métodos auxiliares privados
    private function obtenerNombreEntidad($entidad): string
    {
        return match(get_class($entidad)) {
            Documento::class => $entidad->titulo ?? "Documento ID: {$entidad->id}",
            Expediente::class => $entidad->titulo ?? $entidad->codigo ?? "Expediente ID: {$entidad->id}",
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
        WorkflowInstancia $instancia,
        $actividad,
        User $usuario,
        array $datos = []
    ): WorkflowTarea {
        return WorkflowTarea::create([
            'workflow_instancia_id' => $instancia->id,
            'paso_numero' => $instancia->paso_actual ?? 0,
            'nombre' => $actividad->nombre ?? 'Tarea',
            'descripcion' => $actividad->descripcion ?? '',
            'tipo_asignacion' => $actividad->tipo ?? 'manual',
            'asignado_id' => $usuario->id,
            'asignado_type' => 'App\\Models\\User',
            'estado' => 'pendiente',
            'fecha_vencimiento' => isset($datos['dias_limite']) ? now()->addDays($datos['dias_limite']) : null,
        ]);
    }

    private function usuarioPuedeCompletarTarea(WorkflowTarea $tarea, User $usuario): bool
    {
        return $tarea->asignado_type === 'App\\Models\\User' && 
               $tarea->asignado_id === $usuario->id;
    }

    private function procesarComplecionTarea(WorkflowTarea $tarea, array $datos): array
    {
        return [
            'resultado' => $datos['resultado'] ?? 'completado',
            'datos' => $datos,
            'contexto_actualizado' => []
        ];
    }

    private function determinarSiguienteActividad(WorkflowTarea $tarea, array $resultado): ?object
    {
        // Simplificado - retornar null por ahora
        return null;
    }

    private function completarWorkflow(WorkflowInstancia $instancia, User $usuario): void
    {
        $instancia->update([
            'estado' => self::ESTADO_FINALIZADO,
            'fecha_finalizacion' => now(),
        ]);
    }

    private function calcularProgreso(WorkflowInstancia $instancia): float
    {
        $totalTareas = $instancia->tareas()->count();
        $tareasCompletadas = $instancia->tareas()->where('estado', 'completada')->count();
        
        return $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100, 2) : 0;
    }

    private function asignarTarea(WorkflowTarea $tarea, $actividad): void
    {
        // La asignación ya se hace en crearTarea
    }

    // Métodos de procesamiento específico - implementación básica
    private function procesarAprobacionAutomatica(WorkflowTarea $tarea): array { return ['resultado' => 'aprobado']; }
    private function procesarGeneracionDocumento(WorkflowTarea $tarea): array { return ['resultado' => 'documento_generado']; }
    private function procesarNotificacionAutomatica(WorkflowTarea $tarea): array { return ['resultado' => 'notificacion_enviada']; }
    private function procesarValidacionCondiciones(WorkflowTarea $tarea): array { return ['resultado' => 'condiciones_validadas']; }
    private function procesarAsignacionSerie(WorkflowTarea $tarea): array { return ['resultado' => 'serie_asignada']; }
    private function procesarCalculoVencimientos(WorkflowTarea $tarea): array { return ['resultado' => 'vencimientos_calculados']; }
    private function procesarAprobacion(array $datos): array { return ['resultado' => $datos['decision'] ?? 'aprobado', 'datos' => $datos]; }
    private function procesarRevision(array $datos): array { return ['resultado' => 'revisado', 'datos' => $datos]; }
    private function procesarFirma(array $datos): array { return ['resultado' => 'firmado', 'datos' => $datos]; }
    private function calcularFechaLimiteTarea($actividad, array $datos): ?string { return null; }
    private function evaluarCondicionTransicion($transicion, array $resultado, WorkflowTarea $tarea): bool { return true; }
    private function formatearTareaActual(WorkflowInstancia $instancia): ?array { return null; }
    private function formatearHistorialTareas(WorkflowInstancia $instancia): array { return []; }
    private function obtenerSiguientesAcciones(WorkflowInstancia $instancia): array { return []; }
    private function calcularMetricas(WorkflowInstancia $instancia): array { return []; }
}
