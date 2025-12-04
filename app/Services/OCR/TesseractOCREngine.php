<?php

namespace App\Services\OCR;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;
use Exception;

class TesseractOCREngine implements OCREngineInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Reconocer texto usando Tesseract
     */
    public function recognize(string $filePath, array $options = []): array
    {
        try {
            $tesseract = new TesseractOCR($filePath);

            // Configurar idiomas
            $languages = $options['languages'] ?? $this->config['languages'];
            if (!empty($languages)) {
                $tesseract->lang(implode('+', $languages));
            }

            // Page Segmentation Mode
            $psm = $options['psm'] ?? $this->config['psm'];
            $tesseract->psm($psm);

            // OCR Engine Mode
            $oem = $options['oem'] ?? $this->config['oem'];
            $tesseract->oem($oem);

            // Ejecutar OCR
            $text = $tesseract->run();

            // Obtener datos TSV para análisis de confianza
            $tsvData = $this->getTsvData($filePath, $languages);

            return [
                'success' => true,
                'text' => $text,
                'confidence' => $this->calculateConfidence($tsvData),
                'confidence_data' => $this->parseConfidenceData($tsvData),
                'engine' => 'tesseract',
            ];
        } catch (Exception $e) {
            Log::error('Error en Tesseract OCR: ' . $e->getMessage());
            
            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
                'engine' => 'tesseract',
            ];
        }
    }

    /**
     * Obtener datos TSV de Tesseract para análisis
     */
    protected function getTsvData(string $filePath, array $languages): string
    {
        try {
            $tesseract = new TesseractOCR($filePath);
            $tesseract->lang(implode('+', $languages));
            $tesseract->configFile('tsv');
            return $tesseract->run();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Calcular confianza promedio
     */
    protected function calculateConfidence(string $tsvData): float
    {
        if (empty($tsvData)) {
            return 0;
        }

        $lines = explode("\n", $tsvData);
        $confidences = [];

        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            if (count($parts) >= 11 && is_numeric($parts[10]) && $parts[10] > 0) {
                $confidences[] = (float) $parts[10];
            }
        }

        if (empty($confidences)) {
            return 0;
        }

        return array_sum($confidences) / count($confidences);
    }

    /**
     * Parsear datos de confianza por palabra
     */
    protected function parseConfidenceData(string $tsvData): array
    {
        if (empty($tsvData)) {
            return [];
        }

        $lines = explode("\n", $tsvData);
        $words = [];

        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            
            // Formato TSV: level, page_num, block_num, par_num, line_num, word_num, left, top, width, height, conf, text
            if (count($parts) >= 12 && !empty(trim($parts[11]))) {
                $words[] = [
                    'text' => trim($parts[11]),
                    'confidence' => (float) ($parts[10] ?? 0),
                    'bbox' => [
                        'left' => (int) ($parts[6] ?? 0),
                        'top' => (int) ($parts[7] ?? 0),
                        'width' => (int) ($parts[8] ?? 0),
                        'height' => (int) ($parts[9] ?? 0),
                    ],
                ];
            }
        }

        return $words;
    }

    /**
     * Verificar si Tesseract está disponible
     */
    public function isAvailable(): bool
    {
        try {
            $command = $this->config['binary_path'] . ' --version';
            exec($command, $output, $returnCode);
            return $returnCode === 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener idiomas soportados
     */
    public function getSupportedLanguages(): array
    {
        return $this->config['languages'];
    }
}
