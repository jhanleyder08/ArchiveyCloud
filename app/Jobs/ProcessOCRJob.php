<?php

namespace App\Jobs;

use App\Models\Documento;
use App\Services\OCR\OCRService;
use App\Services\DocumentIndexingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOCRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    protected Documento $documento;
    protected array $options;

    public function __construct(Documento $documento, array $options = [])
    {
        $this->documento = $documento;
        $this->options = $options;
    }

    public function handle(OCRService $ocrService, DocumentIndexingService $indexingService): void
    {
        try {
            $filePath = Storage::disk('public')->path($this->documento->ruta_archivo);

            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: {$filePath}");
            }

            // Procesar OCR
            $result = $ocrService->process($filePath, $this->options);

            if ($result['success']) {
                // Actualizar documento con texto extraído
                $this->documento->update([
                    'contenido_ocr' => $result['text'],
                    'ocr_confidence' => $result['confidence'] ?? 0,
                    'ocr_processed_at' => now(),
                ]);

                // Reindexar en Elasticsearch
                if (config('ocr.elasticsearch.auto_index')) {
                    $indexingService->updateDocument($this->documento);
                }

                Log::info("OCR procesado exitosamente para documento {$this->documento->id}");
            } else {
                Log::error("Error en OCR para documento {$this->documento->id}: " . ($result['error'] ?? 'Unknown'));
            }
        } catch (\Exception $e) {
            Log::error("Error procesando OCR: " . $e->getMessage());
            throw $e;
        }
    }
}
