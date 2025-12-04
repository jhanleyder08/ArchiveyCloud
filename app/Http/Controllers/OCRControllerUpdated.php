<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Services\OCR\OCRService;
use App\Jobs\ProcessOCRJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OCRController extends Controller
{
    protected OCRService $ocrService;

    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Mostrar interfaz de OCR
     */
    public function index()
    {
        $documentos = Documento::whereIn('formato', ['pdf', 'png', 'jpg', 'jpeg', 'tiff', 'bmp', 'gif'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/OCR/Index', [
            'documentos' => $documentos,
        ]);
    }

    /**
     * Procesar OCR para un documento
     */
    public function process(Request $request, Documento $documento)
    {
        $request->validate([
            'engine' => 'nullable|in:tesseract,cloud_vision,azure_vision',
            'async' => 'boolean',
        ]);

        $async = $request->boolean('async', true);

        if ($async) {
            ProcessOCRJob::dispatch($documento, [
                'engine' => $request->input('engine'),
            ])->onQueue(config('ocr.processing.queue_name'));

            return response()->json([
                'success' => true,
                'message' => 'OCR en proceso. Recibirás una notificación cuando termine.',
            ]);
        }

        // Procesamiento síncrono
        $filePath = Storage::disk('public')->path($documento->ruta_archivo);
        $result = $this->ocrService->process($filePath);

        if ($result['success']) {
            $documento->update([
                'contenido_ocr' => $result['text'],
                'ocr_confidence' => $result['confidence'] ?? 0,
                'ocr_processed_at' => now(),
            ]);
        }

        return response()->json($result);
    }

    /**
     * Procesar múltiples documentos
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'documento_ids' => 'required|array',
            'documento_ids.*' => 'exists:documentos,id',
        ]);

        $documentos = Documento::whereIn('id', $request->documento_ids)->get();

        foreach ($documentos as $documento) {
            ProcessOCRJob::dispatch($documento)->onQueue(config('ocr.processing.queue_name'));
        }

        return response()->json([
            'success' => true,
            'message' => "Procesando OCR para {$documentos->count()} documentos",
        ]);
    }

    /**
     * Verificar estado de OCR
     */
    public function status(Documento $documento)
    {
        return response()->json([
            'processed' => !is_null($documento->ocr_processed_at),
            'confidence' => $documento->ocr_confidence,
            'processed_at' => $documento->ocr_processed_at,
            'text_length' => strlen($documento->contenido_ocr ?? ''),
        ]);
    }
}
