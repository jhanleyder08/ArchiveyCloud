<?php

namespace App\Jobs;

use App\Models\Documento;
use App\Models\Workflow;
use App\Models\WorkflowInstancia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job para procesar workflows de documentos de forma asíncrona
 */
class ProcessDocumentWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Timeout en segundos
     */
    public $timeout = 120;

    /**
     * Constructor
     */
    public function __construct(
        public Documento $documento,
        public Workflow $workflow,
        public int $usuarioId,
        public array $datos = []
    ) {
        // Queue específica para workflows
        $this->onQueue('workflows');
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        Log::info('Iniciando procesamiento de workflow', [
            'documento_id' => $this->documento->id,
            'workflow_id' => $this->workflow->id,
            'usuario_id' => $this->usuarioId,
        ]);

        try {
            // Verificar que el workflow esté activo
            if (!$this->workflow->activo) {
                throw new Exception('El workflow no está activo');
            }

            // Crear la instancia del workflow
            $instancia = $this->workflow->iniciar(
                entidadId: $this->documento->id,
                usuarioId: $this->usuarioId,
                datos: $this->datos
            );

            Log::info('Workflow iniciado exitosamente', [
                'instancia_id' => $instancia->id,
                'documento_id' => $this->documento->id,
            ]);

            // Actualizar el documento con el workflow
            $this->documento->update([
                'workflow_instancia_id' => $instancia->id,
                'estado_workflow' => 'en_proceso',
            ]);

        } catch (Exception $e) {
            Log::error('Error al procesar workflow', [
                'documento_id' => $this->documento->id,
                'workflow_id' => $this->workflow->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para reintentos
            throw $e;
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Job de workflow falló después de todos los intentos', [
            'documento_id' => $this->documento->id,
            'workflow_id' => $this->workflow->id,
            'error' => $exception->getMessage(),
        ]);

        // Actualizar documento con estado de error
        $this->documento->update([
            'estado_workflow' => 'error',
            'workflow_error' => $exception->getMessage(),
        ]);
    }

    /**
     * Tags para identificar el job en Horizon
     */
    public function tags(): array
    {
        return [
            'workflow:' . $this->workflow->id,
            'documento:' . $this->documento->id,
            'usuario:' . $this->usuarioId,
        ];
    }
}
