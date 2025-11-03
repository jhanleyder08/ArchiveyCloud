<?php

namespace App\Listeners;

use App\Events\WorkflowTaskAssignedEvent;
use App\Notifications\TareaAsignadaNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Enviar notificación cuando se asigna una tarea
 */
class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(WorkflowTaskAssignedEvent $event): void
    {
        $tarea = $event->tarea;

        try {
            // Obtener usuario asignado
            $usuario = User::find($tarea->asignado_id);

            if (!$usuario) {
                Log::warning('Usuario no encontrado para tarea', [
                    'tarea_id' => $tarea->id,
                    'asignado_id' => $tarea->asignado_id,
                ]);
                return;
            }

            // Enviar notificación
            $usuario->notify(new TareaAsignadaNotification($tarea));

            Log::info('Notificación de tarea enviada', [
                'tarea_id' => $tarea->id,
                'usuario_id' => $usuario->id,
                'workflow' => $tarea->instancia->workflow->nombre ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de tarea', [
                'tarea_id' => $tarea->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Manejar fallo del listener
     */
    public function failed(WorkflowTaskAssignedEvent $event, \Throwable $exception): void
    {
        Log::critical('Listener de tarea asignada falló', [
            'tarea_id' => $event->tarea->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
