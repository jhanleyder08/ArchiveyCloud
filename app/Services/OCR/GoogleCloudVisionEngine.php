<?php

namespace App\Services\OCR;

use Exception;
use Illuminate\Support\Facades\Log;

class GoogleCloudVisionEngine implements OCREngineInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function recognize(string $filePath, array $options = []): array
    {
        try {
            if (!$this->isAvailable()) {
                throw new Exception('Google Cloud Vision no está configurado');
            }

            // Requiere: composer require google/cloud-vision
            if (!class_exists('Google\Cloud\Vision\V1\ImageAnnotatorClient')) {
                throw new Exception('SDK de Google Cloud Vision no está instalado');
            }

            $imageAnnotator = new \Google\Cloud\Vision\V1\ImageAnnotatorClient([
                'credentials' => $this->config['credentials'],
            ]);

            $imageContent = file_get_contents($filePath);
            $image = (new \Google\Cloud\Vision\V1\Image())->setContent($imageContent);

            $response = $imageAnnotator->textDetection($image);
            $texts = $response->getTextAnnotations();

            if ($texts->count() === 0) {
                return [
                    'success' => true,
                    'text' => '',
                    'confidence' => 0,
                    'engine' => 'google_cloud_vision',
                ];
            }

            $fullText = $texts[0]->getDescription();
            
            // Calcular confianza promedio
            $confidenceSum = 0;
            $count = 0;
            foreach ($texts as $text) {
                if ($text->getConfidence() > 0) {
                    $confidenceSum += $text->getConfidence();
                    $count++;
                }
            }
            $avgConfidence = $count > 0 ? ($confidenceSum / $count) * 100 : 0;

            $imageAnnotator->close();

            return [
                'success' => true,
                'text' => $fullText,
                'confidence' => $avgConfidence,
                'engine' => 'google_cloud_vision',
            ];
        } catch (Exception $e) {
            Log::error('Error en Google Cloud Vision: ' . $e->getMessage());
            
            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
                'engine' => 'google_cloud_vision',
            ];
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['credentials']) && 
               !empty($this->config['project_id']) &&
               $this->config['enabled'];
    }

    public function getSupportedLanguages(): array
    {
        return ['all']; // Google Cloud Vision soporta múltiples idiomas automáticamente
    }
}
