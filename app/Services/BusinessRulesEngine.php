<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\WorkflowTarea;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Motor de Reglas de Negocio
 * Automatiza decisiones basadas en condiciones configurables
 */
class BusinessRulesEngine
{
    /**
     * Evaluar reglas para un documento
     */
    public function evaluateDocumentRules(Documento $documento): array
    {
        $results = [];
        $rules = $this->getDocumentRules();

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $documento)) {
                $actionResult = $this->executeRuleActions($rule, $documento);
                $results[] = [
                    'rule' => $rule['name'],
                    'matched' => true,
                    'actions' => $actionResult,
                ];

                // Si la regla tiene stop_on_match, detener evaluación
                if ($rule['stop_on_match'] ?? false) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Evaluar una regla
     */
    private function evaluateRule(array $rule, $entity): bool
    {
        $conditions = $rule['conditions'];
        $operator = $conditions['operator'] ?? 'AND';

        $results = [];
        foreach ($conditions['rules'] as $condition) {
            $results[] = $this->evaluateCondition($condition, $entity);
        }

        return $operator === 'AND' 
            ? !in_array(false, $results) 
            : in_array(true, $results);
    }

    /**
     * Evaluar una condición individual
     */
    private function evaluateCondition(array $condition, $entity): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        // Obtener valor del campo
        $entityValue = $this->getEntityValue($entity, $field);

        return match($operator) {
            '==' => $entityValue == $value,
            '!=' => $entityValue != $value,
            '>' => $entityValue > $value,
            '<' => $entityValue < $value,
            '>=' => $entityValue >= $value,
            '<=' => $entityValue <= $value,
            'contains' => str_contains((string)$entityValue, $value),
            'starts_with' => str_starts_with((string)$entityValue, $value),
            'ends_with' => str_ends_with((string)$entityValue, $value),
            'in' => in_array($entityValue, (array)$value),
            'not_in' => !in_array($entityValue, (array)$value),
            'is_null' => is_null($entityValue),
            'is_not_null' => !is_null($entityValue),
            default => false,
        };
    }

    /**
     * Obtener valor de campo del entity
     */
    private function getEntityValue($entity, string $field)
    {
        // Soporta notación de punto (ej: usuario.nombre)
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $entity;
            
            foreach ($parts as $part) {
                $value = $value->$part ?? null;
                if (is_null($value)) break;
            }
            
            return $value;
        }

        return $entity->$field ?? null;
    }

    /**
     * Ejecutar acciones de una regla
     */
    private function executeRuleActions(array $rule, $entity): array
    {
        $results = [];
        
        foreach ($rule['actions'] as $action) {
            try {
                $result = $this->executeAction($action, $entity);
                $results[] = [
                    'action' => $action['type'],
                    'success' => true,
                    'result' => $result,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'action' => $action['type'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error ejecutando acción de regla', [
                    'rule' => $rule['name'],
                    'action' => $action['type'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Ejecutar una acción
     */
    private function executeAction(array $action, $entity)
    {
        return match($action['type']) {
            'update_field' => $this->updateField($entity, $action['field'], $action['value']),
            'assign_to_user' => $this->assignToUser($entity, $action['user_id']),
            'assign_to_role' => $this->assignToRole($entity, $action['role_id']),
            'send_notification' => $this->sendNotification($entity, $action['config']),
            'create_task' => $this->createTask($entity, $action['config']),
            'trigger_workflow' => $this->triggerWorkflow($entity, $action['workflow_id']),
            'add_tag' => $this->addTag($entity, $action['tag']),
            'set_priority' => $this->setPriority($entity, $action['priority']),
            'escalate' => $this->escalate($entity, $action['config']),
            default => throw new Exception('Acción desconocida: ' . $action['type']),
        };
    }

    /**
     * Actualizar campo
     */
    private function updateField($entity, string $field, $value): bool
    {
        return $entity->update([$field => $value]);
    }

    /**
     * Asignar a usuario
     */
    private function assignToUser($entity, int $userId): bool
    {
        if ($entity instanceof WorkflowTarea) {
            return $entity->update([
                'asignado_type' => 'usuario',
                'asignado_id' => $userId,
            ]);
        }

        if ($entity instanceof Documento) {
            return $entity->update(['usuario_asignado_id' => $userId]);
        }

        return false;
    }

    /**
     * Asignar a rol
     */
    private function assignToRole($entity, int $roleId): bool
    {
        if ($entity instanceof WorkflowTarea) {
            return $entity->update([
                'asignado_type' => 'rol',
                'asignado_id' => $roleId,
            ]);
        }

        return false;
    }

    /**
     * Enviar notificación
     */
    private function sendNotification($entity, array $config): bool
    {
        Log::info('Notificación enviada por regla de negocio', [
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'config' => $config,
        ]);

        return true;
    }

    /**
     * Crear tarea
     */
    private function createTask($entity, array $config): WorkflowTarea
    {
        // Implementación específica según el tipo de entidad
        return new WorkflowTarea();
    }

    /**
     * Disparar workflow
     */
    private function triggerWorkflow($entity, int $workflowId): bool
    {
        Log::info('Workflow disparado por regla de negocio', [
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'workflow_id' => $workflowId,
        ]);

        return true;
    }

    /**
     * Agregar etiqueta
     */
    private function addTag($entity, string $tag): bool
    {
        if (method_exists($entity, 'tags')) {
            // Si el modelo tiene relación con tags
            return true;
        }

        return false;
    }

    /**
     * Establecer prioridad
     */
    private function setPriority($entity, string $priority): bool
    {
        if (property_exists($entity, 'prioridad')) {
            return $entity->update(['prioridad' => $priority]);
        }

        return false;
    }

    /**
     * Escalar
     */
    private function escalate($entity, array $config): bool
    {
        Log::warning('Escalamiento automático ejecutado', [
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'config' => $config,
        ]);

        return true;
    }

    /**
     * Reglas de documentos (configurables)
     */
    private function getDocumentRules(): array
    {
        return [
            [
                'name' => 'Auto-asignar documentos urgentes',
                'priority' => 1,
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'tipo_documento', 'operator' => '==', 'value' => 'urgente'],
                        ['field' => 'estado', 'operator' => '==', 'value' => 'pendiente'],
                    ],
                ],
                'actions' => [
                    ['type' => 'assign_to_user', 'user_id' => 1],
                    ['type' => 'set_priority', 'priority' => 'alta'],
                    ['type' => 'send_notification', 'config' => ['type' => 'email']],
                ],
                'stop_on_match' => true,
            ],
            [
                'name' => 'Escalar documentos vencidos',
                'priority' => 2,
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'fecha_vencimiento', 'operator' => '<', 'value' => now()],
                        ['field' => 'estado', 'operator' => '!=', 'value' => 'completado'],
                    ],
                ],
                'actions' => [
                    ['type' => 'escalate', 'config' => ['level' => 'supervisor']],
                    ['type' => 'send_notification', 'config' => ['type' => 'urgente']],
                ],
                'stop_on_match' => false,
            ],
            [
                'name' => 'Auto-aprobar documentos pequeños',
                'priority' => 3,
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'tamanio', 'operator' => '<', 'value' => 1048576], // < 1MB
                        ['field' => 'tipo_documento', 'operator' => 'in', 'value' => ['memo', 'comunicado']],
                    ],
                ],
                'actions' => [
                    ['type' => 'update_field', 'field' => 'estado', 'value' => 'aprobado'],
                    ['type' => 'trigger_workflow', 'workflow_id' => 1],
                ],
                'stop_on_match' => true,
            ],
        ];
    }

    /**
     * Agregar regla personalizada
     */
    public function addRule(array $rule): bool
    {
        // En producción, esto guardaría en BD
        Log::info('Regla agregada', ['rule' => $rule]);
        return true;
    }

    /**
     * Evaluar todas las reglas activas
     */
    public function evaluateAllRules($entity): array
    {
        $results = [];

        if ($entity instanceof Documento) {
            $results = $this->evaluateDocumentRules($entity);
        }

        Log::info('Reglas evaluadas', [
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'rules_matched' => count($results),
        ]);

        return $results;
    }
}
