<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use App\Models\WorkflowTarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Motor de Workflows Paralelos y Complejos
 * Soporta: Parallel Gateways, Sub-workflows, Condiciones complejas
 */
class ParallelWorkflowEngine
{
    /**
     * Ejecutar tareas en paralelo (AND Gateway)
     */
    public function executeParallelTasks(WorkflowInstancia $instancia, array $tasks): array
    {
        $results = [];

        DB::transaction(function () use ($instancia, $tasks, &$results) {
            foreach ($tasks as $taskConfig) {
                $tarea = WorkflowTarea::create([
                    'instancia_id' => $instancia->id,
                    'nombre' => $taskConfig['nombre'],
                    'descripcion' => $taskConfig['descripcion'] ?? null,
                    'paso_numero' => $taskConfig['paso'] ?? 1,
                    'asignado_type' => $taskConfig['asignado_type'],
                    'asignado_id' => $taskConfig['asignado_id'],
                    'estado' => 'pendiente',
                    'fecha_asignacion' => now(),
                    'es_paralela' => true, // Marca como tarea paralela
                    'grupo_paralelo' => $taskConfig['grupo'] ?? null, // ID del grupo paralelo
                ]);

                $results[] = $tarea;

                Log::info('Tarea paralela creada', [
                    'tarea_id' => $tarea->id,
                    'instancia_id' => $instancia->id,
                    'grupo' => $taskConfig['grupo'] ?? null,
                ]);
            }
        });

        return $results;
    }

    /**
     * Verificar si todas las tareas paralelas están completadas (AND Join)
     */
    public function areAllParallelTasksCompleted(string $grupoParalelo): bool
    {
        $totalTasks = WorkflowTarea::where('grupo_paralelo', $grupoParalelo)->count();
        $completedTasks = WorkflowTarea::where('grupo_paralelo', $grupoParalelo)
            ->where('estado', 'completada')
            ->count();

        return $totalTasks > 0 && $totalTasks === $completedTasks;
    }

    /**
     * Ejecutar primera tarea que complete (OR Gateway)
     */
    public function raceParallelTasks(WorkflowInstancia $instancia, array $tasks): ?WorkflowTarea
    {
        // Marcar tareas como race condition
        $grupoRace = uniqid('race_');

        foreach ($tasks as $taskConfig) {
            WorkflowTarea::create([
                'instancia_id' => $instancia->id,
                'nombre' => $taskConfig['nombre'],
                'descripcion' => $taskConfig['descripcion'] ?? null,
                'paso_numero' => $taskConfig['paso'] ?? 1,
                'asignado_type' => $taskConfig['asignado_type'],
                'asignado_id' => $taskConfig['asignado_id'],
                'estado' => 'pendiente',
                'fecha_asignacion' => now(),
                'es_paralela' => true,
                'grupo_paralelo' => $grupoRace,
                'es_race' => true, // Marca como race condition
            ]);
        }

        // Retorna la primera que se complete
        return WorkflowTarea::where('grupo_paralelo', $grupoRace)
            ->where('estado', 'completada')
            ->orderBy('fecha_completado')
            ->first();
    }

    /**
     * Cancelar tareas paralelas pendientes del mismo grupo
     */
    public function cancelPendingParallelTasks(string $grupoParalelo, int $exceptTaskId = null): void
    {
        $query = WorkflowTarea::where('grupo_paralelo', $grupoParalelo)
            ->where('estado', 'pendiente');

        if ($exceptTaskId) {
            $query->where('id', '!=', $exceptTaskId);
        }

        $query->update([
            'estado' => 'cancelada',
            'fecha_completado' => now(),
            'observaciones' => 'Cancelada automáticamente (tarea paralela completada antes)',
        ]);
    }

    /**
     * Ejecutar sub-workflow
     */
    public function executeSubWorkflow(
        WorkflowInstancia $parentInstancia,
        int $subWorkflowId,
        array $context = []
    ): WorkflowInstancia {
        $subWorkflow = Workflow::findOrFail($subWorkflowId);

        // Crear instancia del sub-workflow
        $subInstancia = $subWorkflow->iniciar(
            entidadId: $context['entidad_id'] ?? $parentInstancia->entidad_id,
            usuarioId: $context['usuario_id'] ?? $parentInstancia->usuario_inicio_id,
            datos: array_merge($parentInstancia->datos ?? [], $context)
        );

        // Vincular con el workflow padre
        $subInstancia->update([
            'workflow_padre_id' => $parentInstancia->id,
            'datos' => array_merge($subInstancia->datos ?? [], [
                'parent_workflow' => $parentInstancia->id,
                'context' => $context,
            ]),
        ]);

        Log::info('Sub-workflow ejecutado', [
            'parent_instancia_id' => $parentInstancia->id,
            'sub_instancia_id' => $subInstancia->id,
            'sub_workflow_id' => $subWorkflowId,
        ]);

        return $subInstancia;
    }

    /**
     * Evaluar condición compleja
     */
    public function evaluateComplexCondition(string $expression, array $context): bool
    {
        try {
            // Sanitizar expresión
            $expression = $this->sanitizeExpression($expression);

            // Reemplazar variables del contexto
            $expression = $this->replaceContextVariables($expression, $context);

            // Evaluar expresión
            $result = eval("return ($expression);");

            return (bool) $result;

        } catch (Exception $e) {
            Log::error('Error al evaluar condición compleja', [
                'expression' => $expression,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sanitizar expresión para prevenir código malicioso
     */
    private function sanitizeExpression(string $expression): string
    {
        // Permitir solo operadores seguros
        $allowedOperators = ['==', '!=', '>', '<', '>=', '<=', '&&', '||', 'and', 'or', '(', ')'];
        
        // Remover caracteres peligrosos
        $expression = preg_replace('/[^\w\s\.\=\!\>\<\&\|\(\)]+/', '', $expression);

        return $expression;
    }

    /**
     * Reemplazar variables del contexto en la expresión
     */
    private function replaceContextVariables(string $expression, array $context): string
    {
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            
            // Escapar strings
            if (is_string($value)) {
                $value = "'" . addslashes($value) . "'";
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = 'null';
            }

            $expression = str_replace($placeholder, $value, $expression);
        }

        return $expression;
    }

    /**
     * Ejecutar acción condicional (IF-THEN-ELSE)
     */
    public function executeConditionalAction(
        WorkflowInstancia $instancia,
        string $condition,
        array $thenActions,
        array $elseActions = []
    ): array {
        $context = $instancia->datos ?? [];
        $conditionMet = $this->evaluateComplexCondition($condition, $context);

        $actions = $conditionMet ? $thenActions : $elseActions;
        $results = [];

        foreach ($actions as $action) {
            $result = $this->executeAction($instancia, $action);
            $results[] = $result;
        }

        Log::info('Acción condicional ejecutada', [
            'instancia_id' => $instancia->id,
            'condition' => $condition,
            'condition_met' => $conditionMet,
            'actions_executed' => count($results),
        ]);

        return $results;
    }

    /**
     * Ejecutar una acción
     */
    private function executeAction(WorkflowInstancia $instancia, array $action): mixed
    {
        return match($action['type']) {
            'create_task' => $this->createTask($instancia, $action['config']),
            'update_data' => $this->updateInstanceData($instancia, $action['data']),
            'send_notification' => $this->sendNotification($instancia, $action['config']),
            'execute_sub_workflow' => $this->executeSubWorkflow($instancia, $action['workflow_id'], $action['context'] ?? []),
            default => throw new Exception('Tipo de acción desconocido: ' . $action['type']),
        };
    }

    /**
     * Crear tarea
     */
    private function createTask(WorkflowInstancia $instancia, array $config): WorkflowTarea
    {
        return WorkflowTarea::create([
            'instancia_id' => $instancia->id,
            'nombre' => $config['nombre'],
            'descripcion' => $config['descripcion'] ?? null,
            'paso_numero' => $config['paso'] ?? 1,
            'asignado_type' => $config['asignado_type'],
            'asignado_id' => $config['asignado_id'],
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);
    }

    /**
     * Actualizar datos de instancia
     */
    private function updateInstanceData(WorkflowInstancia $instancia, array $data): bool
    {
        $currentData = $instancia->datos ?? [];
        $newData = array_merge($currentData, $data);

        return $instancia->update(['datos' => $newData]);
    }

    /**
     * Enviar notificación
     */
    private function sendNotification(WorkflowInstancia $instancia, array $config): bool
    {
        // Implementar envío de notificación
        Log::info('Notificación enviada', [
            'instancia_id' => $instancia->id,
            'config' => $config,
        ]);

        return true;
    }

    /**
     * Verificar estado de sub-workflows
     */
    public function areAllSubWorkflowsCompleted(WorkflowInstancia $parentInstancia): bool
    {
        $pendingSubWorkflows = WorkflowInstancia::where('workflow_padre_id', $parentInstancia->id)
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->count();

        return $pendingSubWorkflows === 0;
    }

    /**
     * Obtener resultados de sub-workflows
     */
    public function getSubWorkflowsResults(WorkflowInstancia $parentInstancia): array
    {
        return WorkflowInstancia::where('workflow_padre_id', $parentInstancia->id)
            ->get()
            ->map(fn($sub) => [
                'id' => $sub->id,
                'workflow' => $sub->workflow->nombre ?? 'N/A',
                'estado' => $sub->estado,
                'datos' => $sub->datos,
                'fecha_inicio' => $sub->fecha_inicio,
                'fecha_finalizacion' => $sub->fecha_finalizacion,
            ])
            ->toArray();
    }
}
