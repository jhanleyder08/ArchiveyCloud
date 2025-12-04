<?php

namespace App\Services;

use App\Models\WorkflowDocumento;
use App\Models\AprobacionWorkflow;
use App\Models\Documento;
use App\Models\User;
use App\Models\Notificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WorkflowService
{
    /**
     * Iniciar un workflow de aprobación para un documento
     */
    public function iniciarWorkflow(Documento $documento, array $datos): WorkflowDocumento
    {
        DB::beginTransaction();
        
        try {
            // Validar que no exista un workflow activo
            $workflowExistente = WorkflowDocumento::where('documento_id', $documento->id)
                ->whereIn('estado', [
                    WorkflowDocumento::ESTADO_PENDIENTE,
                    WorkflowDocumento::ESTADO_EN_REVISION
                ])
                ->first();

            if ($workflowExistente) {
                throw new \Exception('El documento ya tiene un workflow de aprobación activo');
            }

            // Crear el workflow
            $workflow = WorkflowDocumento::create([
                'documento_id' => $documento->id,
                'estado' => WorkflowDocumento::ESTADO_PENDIENTE,
                'solicitante_id' => auth()->id(),
                'niveles_aprobacion' => $datos['aprobadores'],
                'nivel_actual' => 0,
                'requiere_aprobacion_unanime' => $datos['requiere_unanime'] ?? false,
                'fecha_solicitud' => now(),
                'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? now()->addDays(7),
                'descripcion_solicitud' => $datos['descripcion'] ?? null,
                'prioridad' => $datos['prioridad'] ?? WorkflowDocumento::PRIORIDAD_MEDIA,
                'metadata' => $datos['metadata'] ?? null
            ]);

            // Asignar al primer aprobador
            if (!empty($datos['aprobadores'])) {
                $primerAprobadorId = $datos['aprobadores'][0];
                $workflow->update([
                    'revisor_actual_id' => $primerAprobadorId,
                    'estado' => WorkflowDocumento::ESTADO_EN_REVISION,
                    'fecha_asignacion' => now()
                ]);

                // Enviar notificación al primer aprobador
                $this->enviarNotificacionAprobacion($workflow, $primerAprobadorId);
            }

            // Registrar en auditoría
            $this->registrarAuditoria($workflow, 'workflow_iniciado', [
                'solicitante' => auth()->user()->name,
                'total_aprobadores' => count($datos['aprobadores']),
                'prioridad' => $workflow->etiqueta_prioridad
            ]);

            DB::commit();
            return $workflow;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al iniciar workflow: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar aprobación o rechazo
     */
    public function procesarAprobacion(WorkflowDocumento $workflow, string $accion, array $datos): bool
    {
        DB::beginTransaction();

        try {
            $usuario = auth()->user();

            // Verificar que el usuario puede aprobar
            if (!$workflow->puedeAprobar($usuario)) {
                throw new \Exception('No tienes permisos para aprobar este documento en su estado actual');
            }

            // Calcular tiempo de respuesta
            $tiempoRespuesta = $workflow->fecha_asignacion 
                ? now()->diffInHours($workflow->fecha_asignacion)
                : null;

            // Registrar la aprobación/rechazo
            $aprobacion = AprobacionWorkflow::create([
                'workflow_documento_id' => $workflow->id,
                'usuario_id' => $usuario->id,
                'nivel_aprobacion' => $workflow->nivel_actual,
                'accion' => $accion,
                'comentarios' => $datos['comentarios'] ?? null,
                'fecha_accion' => now(),
                'tiempo_respuesta_horas' => $tiempoRespuesta,
                'archivos_adjuntos' => $datos['archivos_adjuntos'] ?? null
            ]);

            if ($accion === AprobacionWorkflow::ACCION_APROBADO) {
                $this->procesarAprobado($workflow);
            } else {
                $this->procesarRechazado($workflow, $datos['comentarios'] ?? '');
            }

            // Registrar en auditoría
            $this->registrarAuditoria($workflow, 'workflow_accion', [
                'accion' => $accion,
                'usuario' => $usuario->name,
                'nivel' => $workflow->nivel_actual,
                'comentarios' => $datos['comentarios'] ?? null
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al procesar aprobación: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar aprobación exitosa
     */
    private function procesarAprobado(WorkflowDocumento $workflow): void
    {
        // Avanzar al siguiente nivel
        $workflow->nivel_actual++;

        // Verificar si es el último nivel
        if ($workflow->nivel_actual >= count($workflow->niveles_aprobacion)) {
            // Workflow completado
            $workflow->update([
                'estado' => WorkflowDocumento::ESTADO_APROBADO,
                'fecha_aprobacion' => now(),
                'aprobador_final_id' => auth()->id(),
                'revisor_actual_id' => null
            ]);

            // Notificar al solicitante
            $this->enviarNotificacionCompletado($workflow);

        } else {
            // Asignar al siguiente aprobador
            $siguienteAprobadorId = $workflow->niveles_aprobacion[$workflow->nivel_actual];
            $workflow->update([
                'revisor_actual_id' => $siguienteAprobadorId,
                'fecha_asignacion' => now()
            ]);

            // Notificar al siguiente aprobador
            $this->enviarNotificacionAprobacion($workflow, $siguienteAprobadorId);
        }
    }

    /**
     * Procesar rechazo
     */
    private function procesarRechazado(WorkflowDocumento $workflow, string $comentarios): void
    {
        $workflow->update([
            'estado' => WorkflowDocumento::ESTADO_RECHAZADO,
            'fecha_rechazo' => now(),
            'comentarios_finales' => $comentarios,
            'revisor_actual_id' => null
        ]);

        // Notificar al solicitante del rechazo
        $this->enviarNotificacionRechazado($workflow);
    }

    /**
     * Delegar aprobación a otro usuario
     */
    public function delegarAprobacion(WorkflowDocumento $workflow, int $nuevoAprobadorId, string $comentarios = null): bool
    {
        DB::beginTransaction();

        try {
            $usuario = auth()->user();

            if (!$workflow->puedeAprobar($usuario)) {
                throw new \Exception('No puedes delegar esta aprobación');
            }

            // Registrar la delegación
            AprobacionWorkflow::create([
                'workflow_documento_id' => $workflow->id,
                'usuario_id' => $usuario->id,
                'nivel_aprobacion' => $workflow->nivel_actual,
                'accion' => AprobacionWorkflow::ACCION_DELEGADO,
                'comentarios' => $comentarios,
                'fecha_accion' => now()
            ]);

            // Actualizar los niveles de aprobación
            $nivelesAprobacion = $workflow->niveles_aprobacion;
            $nivelesAprobacion[$workflow->nivel_actual] = $nuevoAprobadorId;

            $workflow->update([
                'niveles_aprobacion' => $nivelesAprobacion,
                'revisor_actual_id' => $nuevoAprobadorId,
                'fecha_asignacion' => now()
            ]);

            // Notificar al nuevo aprobador
            $this->enviarNotificacionDelegacion($workflow, $nuevoAprobadorId, $usuario);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Cancelar workflow
     */
    public function cancelarWorkflow(WorkflowDocumento $workflow, string $motivo = null): bool
    {
        if (!in_array($workflow->estado, [WorkflowDocumento::ESTADO_PENDIENTE, WorkflowDocumento::ESTADO_EN_REVISION])) {
            throw new \Exception('No se puede cancelar un workflow que ya fue completado o rechazado');
        }

        $workflow->update([
            'estado' => WorkflowDocumento::ESTADO_RECHAZADO,
            'fecha_rechazo' => now(),
            'comentarios_finales' => 'Cancelado: ' . $motivo,
            'revisor_actual_id' => null
        ]);

        return true;
    }

    /**
     * Obtener workflows pendientes para un usuario
     */
    public function getWorkflowsPendientes(int $usuarioId): array
    {
        return WorkflowDocumento::with(['documento', 'solicitante'])
            ->where('revisor_actual_id', $usuarioId)
            ->where('estado', WorkflowDocumento::ESTADO_EN_REVISION)
            ->orderBy('prioridad', 'asc')
            ->orderBy('fecha_asignacion', 'asc')
            ->get()
            ->map(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'documento' => [
                        'id' => $workflow->documento->id,
                        'nombre' => $workflow->documento->titulo,
                        'codigo' => $workflow->documento->codigo_documento
                    ],
                    'solicitante' => $workflow->solicitante->name,
                    'prioridad' => $workflow->etiqueta_prioridad,
                    'fecha_solicitud' => $workflow->fecha_solicitud,
                    'fecha_vencimiento' => $workflow->fecha_vencimiento,
                    'esta_vencido' => $workflow->esta_vencido,
                    'progreso' => $workflow->progreso,
                    'descripcion' => $workflow->descripcion_solicitud
                ];
            })
            ->toArray();
    }

    /**
     * Enviar notificación de nueva aprobación
     */
    private function enviarNotificacionAprobacion(WorkflowDocumento $workflow, int $aprobadorId): void
    {
        Notificacion::create([
            'user_id' => $aprobadorId,
            'tipo' => 'workflow_aprobacion',
            'titulo' => 'Documento pendiente de aprobación',
            'mensaje' => "El documento '{$workflow->documento->titulo}' requiere tu aprobación",
            'prioridad' => $this->mapearPrioridadNotificacion($workflow->prioridad),
            'accion_url' => "/admin/workflow/{$workflow->id}/aprobar",
            'relacionado_tipo' => 'workflow_documento',
            'relacionado_id' => $workflow->id
        ]);
    }

    /**
     * Enviar notificación de workflow completado
     */
    private function enviarNotificacionCompletado(WorkflowDocumento $workflow): void
    {
        Notificacion::create([
            'user_id' => $workflow->solicitante_id,
            'tipo' => 'workflow_completado',
            'titulo' => 'Documento aprobado exitosamente',
            'mensaje' => "El documento '{$workflow->documento->titulo}' ha sido aprobado por todos los revisores",
            'prioridad' => 'media',
            'accion_url' => "/admin/documentos/{$workflow->documento->id}",
            'relacionado_tipo' => 'workflow_documento',
            'relacionado_id' => $workflow->id
        ]);
    }

    /**
     * Enviar notificación de rechazo
     */
    private function enviarNotificacionRechazado(WorkflowDocumento $workflow): void
    {
        Notificacion::create([
            'user_id' => $workflow->solicitante_id,
            'tipo' => 'workflow_rechazado',
            'titulo' => 'Documento rechazado',
            'mensaje' => "El documento '{$workflow->documento->titulo}' ha sido rechazado en el proceso de aprobación",
            'prioridad' => 'alta',
            'accion_url' => "/admin/workflow/{$workflow->id}",
            'relacionado_tipo' => 'workflow_documento',
            'relacionado_id' => $workflow->id
        ]);
    }

    /**
     * Enviar notificación de delegación
     */
    private function enviarNotificacionDelegacion(WorkflowDocumento $workflow, int $nuevoAprobadorId, User $delegante): void
    {
        Notificacion::create([
            'user_id' => $nuevoAprobadorId,
            'tipo' => 'workflow_delegacion',
            'titulo' => 'Aprobación delegada',
            'mensaje' => "{$delegante->name} te ha delegado la aprobación del documento '{$workflow->documento->titulo}'",
            'prioridad' => $this->mapearPrioridadNotificacion($workflow->prioridad),
            'accion_url' => "/admin/workflow/{$workflow->id}/aprobar",
            'relacionado_tipo' => 'workflow_documento',
            'relacionado_id' => $workflow->id
        ]);
    }

    /**
     * Mapear prioridad de workflow a prioridad de notificación
     */
    private function mapearPrioridadNotificacion(int $prioridadWorkflow): string
    {
        $mapeo = [
            WorkflowDocumento::PRIORIDAD_CRITICA => 'critica',
            WorkflowDocumento::PRIORIDAD_ALTA => 'alta',
            WorkflowDocumento::PRIORIDAD_MEDIA => 'media',
            WorkflowDocumento::PRIORIDAD_BAJA => 'baja',
        ];

        return $mapeo[$prioridadWorkflow] ?? 'media';
    }

    /**
     * Registrar auditoría
     */
    private function registrarAuditoria(WorkflowDocumento $workflow, string $accion, array $datos = []): void
    {
        \App\Models\PistaAuditoria::create([
            'user_id' => auth()->id(),
            'tabla_afectada' => 'workflow_documentos',
            'registro_id' => $workflow->id,
            'accion' => $accion,
            'descripcion' => "Workflow {$accion} para documento {$workflow->documento->titulo}",
            'datos_nuevos' => $datos,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
