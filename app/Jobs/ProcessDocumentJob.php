<?php

namespace App\Jobs;

use App\Models\Documento;
use App\Services\DocumentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Exception;

/**
 * Job para procesamiento de documentos en background
 * 
 * Implementa requerimientos:
 * REQ-CP-012: Colas de captura masiva
 * REQ-CP-028: Conversión automática de formatos
 */
class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;
    public $backoff = 60; // Reintento cada 60 segundos

    protected int $documentoId;
    protected array $processingOptions;
    protected ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $documentoId, array $processingOptions = [], ?int $userId = null)
    {
        $this->documentoId = $documentoId;
        $this->processingOptions = $processingOptions;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentProcessingService $processor): void
    {
        try {
            $documento = Documento::find($this->documentoId);
            
            if (!$documento) {
                throw new Exception("Documento no encontrado: {$this->documentoId}");
            }

            if (!$documento->ruta_archivo || !Storage::disk('public')->exists($documento->ruta_archivo)) {
                throw new Exception("Archivo no encontrado: {$documento->ruta_archivo}");
            }

            Log::info('Iniciando procesamiento de documento', [
                'documento_id' => $this->documentoId,
                'archivo' => $documento->ruta_archivo,
                'opciones' => $this->processingOptions
            ]);

            // Actualizar estado a procesando
            $documento->update([
                'estado_procesamiento' => 'procesando'
            ]);

            // Crear archivo temporal para procesamiento
            $rutaCompleta = storage_path('app/public/' . $documento->ruta_archivo);
            $archivoTemporal = $this->crearArchivoTemporal($rutaCompleta);

            try {
                // Procesar con el servicio avanzado
                $resultado = $processor->procesarArchivo(
                    $archivoTemporal,
                    $documento,
                    $this->processingOptions
                );

                if ($resultado['success']) {
                    $this->actualizarDocumentoConResultados($documento, $resultado);
                    
                    Log::info('Documento procesado exitosamente', [
                        'documento_id' => $this->documentoId,
                        'ocr_disponible' => !empty($resultado['ocr_texto']),
                        'miniatura_generada' => !empty($resultado['miniatura']),
                        'conversiones' => count($resultado['conversiones'] ?? [])
                    ]);
                } else {
                    throw new Exception('Error en el procesamiento del documento');
                }

            } finally {
                // Limpiar archivo temporal
                if (file_exists($archivoTemporal->getPathname())) {
                    unlink($archivoTemporal->getPathname());
                }
            }

        } catch (Exception $e) {
            Log::error('Error procesando documento en background', [
                'documento_id' => $this->documentoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Actualizar documento con error
            if (isset($documento)) {
                $documento->update([
                    'estado_procesamiento' => 'error',
                    'error_procesamiento' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job de procesamiento de documento falló definitivamente', [
            'documento_id' => $this->documentoId,
            'error' => $exception->getMessage(),
            'intentos' => $this->attempts()
        ]);

        // Marcar documento como fallido
        $documento = Documento::find($this->documentoId);
        if ($documento) {
            $documento->update([
                'estado_procesamiento' => 'fallido',
                'error_procesamiento' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Crear archivo temporal para procesamiento
     */
    private function crearArchivoTemporal(string $rutaOriginal): UploadedFile
    {
        // Copiar archivo original a temporal
        $tempPath = tempnam(sys_get_temp_dir(), 'doc_process_');
        copy($rutaOriginal, $tempPath);

        // Obtener información del archivo original
        $originalName = basename($rutaOriginal);
        $mimeType = mime_content_type($tempPath);
        $size = filesize($tempPath);

        // Crear instancia de UploadedFile
        return new UploadedFile(
            $tempPath,
            $originalName,
            $mimeType,
            null,
            true // test mode para permitir archivos fuera de uploads
        );
    }

    /**
     * Actualizar documento con resultados del procesamiento
     */
    private function actualizarDocumentoConResultados(Documento $documento, array $resultado): void
    {
        $updateData = [
            'estado_procesamiento' => 'completado',
            'fecha_procesamiento' => now()
        ];

        // OCR texto extraído
        if (!empty($resultado['ocr_texto'])) {
            $updateData['contenido_ocr'] = $resultado['ocr_texto'];
        }

        // Ruta de miniatura generada
        if (!empty($resultado['miniatura'])) {
            $updateData['ruta_miniatura'] = $resultado['miniatura'];
        }

        // Hash de integridad si no existe
        if (empty($documento->hash_sha256) && !empty($resultado['metadatos']['hash_sha256'])) {
            $updateData['hash_sha256'] = $resultado['metadatos']['hash_sha256'];
        }

        // Metadatos adicionales del procesamiento
        $metadatosActuales = json_decode($documento->metadatos_archivo ?? '{}', true);
        $metadatosActuales['procesamiento_background'] = [
            'fecha' => now()->toISOString(),
            'metadatos' => $resultado['metadatos'],
            'conversiones' => $resultado['conversiones'] ?? [],
            'usuario_id' => $this->userId
        ];
        $updateData['metadatos_archivo'] = json_encode($metadatosActuales);

        $documento->update($updateData);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['document-processing', "document:{$this->documentoId}"];
    }
}
