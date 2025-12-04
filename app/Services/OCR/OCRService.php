<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Servicio principal de OCR
 * Implementa REQ-CP-013: Digitalización con OCR
 * Implementa REQ-CP-014: Reconocimiento de códigos de barras
 */
class OCRService
{
    protected array $config;
    protected string $engine;
    protected OCREngineInterface $ocrEngine;

    public function __construct()
    {
        $this->config = config('ocr');
        $this->engine = $this->config['default_engine'];
        $this->initializeEngine();
    }

    /**
     * Inicializar motor OCR
     */
    protected function initializeEngine(): void
    {
        $this->ocrEngine = match($this->engine) {
            'tesseract' => new TesseractOCREngine($this->config['engines']['tesseract']),
            'cloud_vision' => new GoogleCloudVisionEngine($this->config['engines']['cloud_vision']),
            'azure_vision' => new AzureVisionEngine($this->config['engines']['azure_vision']),
            default => throw new Exception("Motor OCR no soportado: {$this->engine}"),
        };
    }

    /**
     * Procesar archivo con OCR
     * 
     * @param string $filePath Ruta al archivo
     * @param array $options Opciones adicionales
     * @return array Resultado del OCR
     */
    public function process(string $filePath, array $options = []): array
    {
        try {
            // Validar archivo
            $this->validateFile($filePath);

            // Preprocesar imagen si está habilitado
            $processedPath = $filePath;
            if ($this->config['preprocessing']['enabled']) {
                $processedPath = $this->preprocessImage($filePath);
            }

            // Ejecutar OCR
            $result = $this->ocrEngine->recognize($processedPath, $options);

            // Postprocesar texto
            if ($this->config['postprocessing']['enabled']) {
                $result['text'] = $this->postprocessText($result['text']);
            }

            // Extraer metadatos adicionales
            if ($this->config['metadata_extraction']['enabled']) {
                $result['metadata'] = $this->extractMetadata($processedPath, $result);
            }

            // Detectar códigos de barras si está habilitado
            if ($this->config['barcode_detection']['enabled']) {
                $result['barcodes'] = $this->detectBarcodes($processedPath);
            }

            // Guardar resultados
            $this->saveResults($filePath, $processedPath, $result);

            // Log
            if ($this->config['logging']['enabled']) {
                Log::channel($this->config['logging']['channel'])->info('OCR procesado exitosamente', [
                    'file' => basename($filePath),
                    'confidence' => $result['confidence'] ?? null,
                    'text_length' => strlen($result['text']),
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Error en procesamiento OCR: ' . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validar archivo para OCR
     */
    protected function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['supported_formats'])) {
            throw new Exception("Formato no soportado: {$extension}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize > $this->config['processing']['max_file_size']) {
            throw new Exception("Archivo demasiado grande: " . ($fileSize / 1024 / 1024) . "MB");
        }
    }

    /**
     * Preprocesar imagen para mejorar OCR
     */
    protected function preprocessImage(string $filePath): string
    {
        try {
            $preprocessor = new ImagePreprocessor($this->config['preprocessing']);
            return $preprocessor->process($filePath);
        } catch (Exception $e) {
            Log::warning('Error en preprocesamiento de imagen: ' . $e->getMessage());
            return $filePath; // Devolver original si falla
        }
    }

    /**
     * Postprocesar texto extraído
     */
    protected function postprocessText(string $text): string
    {
        $config = $this->config['postprocessing'];

        // Eliminar espacios extra
        if ($config['remove_extra_spaces']) {
            $text = preg_replace('/\s+/', ' ', $text);
        }

        // Normalizar espacios en blanco
        if ($config['normalize_whitespace']) {
            $text = trim($text);
            $text = str_replace(["\r\n", "\r"], "\n", $text);
        }

        // Corrección ortográfica (si está habilitado)
        if ($config['spell_check']) {
            // TODO: Implementar spell check
        }

        return $text;
    }

    /**
     * Extraer metadatos de la imagen y resultado OCR
     */
    protected function extractMetadata(string $filePath, array $ocrResult): array
    {
        $metadata = [];

        // Idioma detectado
        if ($this->config['metadata_extraction']['extract_language']) {
            $metadata['language'] = $this->detectLanguage($ocrResult['text']);
        }

        // DPI de la imagen
        if ($this->config['metadata_extraction']['extract_dpi']) {
            $imageInfo = getimagesize($filePath);
            $metadata['dpi'] = $imageInfo['dpi'] ?? null;
        }

        // Orientación
        if ($this->config['metadata_extraction']['extract_orientation']) {
            $metadata['orientation'] = $ocrResult['orientation'] ?? 0;
        }

        // Regiones de texto
        if ($this->config['metadata_extraction']['extract_text_regions']) {
            $metadata['text_regions'] = $ocrResult['regions'] ?? [];
        }

        return $metadata;
    }

    /**
     * Detectar idioma del texto
     */
    protected function detectLanguage(string $text): string
    {
        // Detección simple por patrones comunes
        $spanishWords = ['el', 'la', 'de', 'que', 'en', 'un', 'por', 'con'];
        $englishWords = ['the', 'of', 'and', 'to', 'in', 'is', 'it', 'for'];

        $textLower = strtolower($text);
        $spanishCount = 0;
        $englishCount = 0;

        foreach ($spanishWords as $word) {
            if (str_contains($textLower, " $word ")) {
                $spanishCount++;
            }
        }

        foreach ($englishWords as $word) {
            if (str_contains($textLower, " $word ")) {
                $englishCount++;
            }
        }

        return $spanishCount > $englishCount ? 'spa' : 'eng';
    }

    /**
     * Detectar códigos de barras y QR
     */
    protected function detectBarcodes(string $filePath): array
    {
        try {
            $detector = new BarcodeDetector($this->config['barcode_detection']);
            return $detector->detect($filePath);
        } catch (Exception $e) {
            Log::warning('Error en detección de códigos de barras: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar resultados del OCR
     */
    protected function saveResults(string $originalPath, string $processedPath, array $result): void
    {
        $config = $this->config['storage'];
        $disk = Storage::disk($config['disk']);
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

        // Guardar imagen procesada
        if ($config['save_processed_image'] && $processedPath !== $originalPath) {
            $processedName = $config['path'] . '/' . $baseName . '_processed.png';
            $disk->put($processedName, file_get_contents($processedPath));
        }

        // Guardar texto OCR
        if ($config['save_ocr_text']) {
            $textName = $config['path'] . '/' . $baseName . '_ocr.txt';
            $disk->put($textName, $result['text']);
        }

        // Guardar datos de confianza
        if ($this->config['quality']['save_confidence_data'] && isset($result['confidence_data'])) {
            $confidenceName = $config['path'] . '/' . $baseName . '_confidence.json';
            $disk->put($confidenceName, json_encode($result['confidence_data'], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Procesar múltiples archivos en lote
     */
    public function processBatch(array $filePaths, array $options = []): array
    {
        $results = [];

        foreach ($filePaths as $filePath) {
            $results[$filePath] = $this->process($filePath, $options);
        }

        return $results;
    }

    /**
     * Obtener confianza promedio del resultado
     */
    public function getAverageConfidence(array $result): float
    {
        if (!isset($result['confidence_data'])) {
            return $result['confidence'] ?? 0;
        }

        $total = 0;
        $count = 0;

        foreach ($result['confidence_data'] as $word) {
            if (isset($word['confidence'])) {
                $total += $word['confidence'];
                $count++;
            }
        }

        return $count > 0 ? $total / $count : 0;
    }

    /**
     * Cambiar motor OCR en tiempo de ejecución
     */
    public function setEngine(string $engine): void
    {
        $this->engine = $engine;
        $this->initializeEngine();
    }
}
