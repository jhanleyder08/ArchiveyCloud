<?php

namespace App\Observers;

use App\Models\Workflow;
use Illuminate\Support\Facades\Log;

/**
 * Observer para auditoría automática de Workflows
 */
class WorkflowObserver
{
    /**
     * Handle the Workflow "created" event.
     */
    public function created(Workflow $workflow): void
    {
        Log::info('Workflow creado', [
            'workflow_id' => $workflow->id,
            'nombre' => $workflow->nombre,
            'usuario_creador_id' => $workflow->usuario_creador_id,
            'tipo_entidad' => $workflow->tipo_entidad,
            'total_pasos' => count($workflow->pasos ?? []),
        ]);
    }

    /**
     * Handle the Workflow "updated" event.
     */
    public function updated(Workflow $workflow): void
    {
        // Obtener cambios
        $cambios = $workflow->getChanges();
        $original = $workflow->getOriginal();

        // Log solo si hay cambios significativos
        $camposImportantes = ['nombre', 'pasos', 'activo', 'configuracion'];
        $hubo = array_intersect_key($cambios, array_flip($camposImportantes));

        if (!empty($hubo)) {
            Log::info('Workflow actualizado', [
                'workflow_id' => $workflow->id,
                'nombre' => $workflow->nombre,
                'cambios' => array_keys($hubo),
                'usuario' => auth()->id(),
            ]);

            // Log específico si se desactivó
            if (isset($cambios['activo']) && !$cambios['activo']) {
                Log::warning('Workflow desactivado', [
                    'workflow_id' => $workflow->id,
                    'nombre' => $workflow->nombre,
                ]);
            }
        }
    }

    /**
     * Handle the Workflow "deleted" event.
     */
    public function deleted(Workflow $workflow): void
    {
        Log::warning('Workflow eliminado', [
            'workflow_id' => $workflow->id,
            'nombre' => $workflow->nombre,
            'usuario' => auth()->id(),
            'soft_delete' => $workflow->trashed(),
        ]);
    }

    /**
     * Handle the Workflow "restored" event.
     */
    public function restored(Workflow $workflow): void
    {
        Log::info('Workflow restaurado', [
            'workflow_id' => $workflow->id,
            'nombre' => $workflow->nombre,
            'usuario' => auth()->id(),
        ]);
    }

    /**
     * Handle the Workflow "force deleted" event.
     */
    public function forceDeleted(Workflow $workflow): void
    {
        Log::critical('Workflow eliminado permanentemente', [
            'workflow_id' => $workflow->id,
            'nombre' => $workflow->nombre,
            'usuario' => auth()->id(),
        ]);
    }

    /**
     * Handle the Workflow "saving" event (antes de guardar).
     */
    public function saving(Workflow $workflow): void
    {
        // Validaciones adicionales antes de guardar
        if ($workflow->isDirty('pasos')) {
            $pasos = $workflow->pasos ?? [];
            
            // Validar que tenga al menos un paso
            if (empty($pasos)) {
                Log::error('Intento de guardar workflow sin pasos', [
                    'workflow_id' => $workflow->id,
                ]);
            }
        }
    }
}
