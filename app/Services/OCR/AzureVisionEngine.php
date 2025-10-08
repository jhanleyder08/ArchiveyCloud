<?php

namespace App\Services\OCR;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AzureVisionEngine implements OCREngineInterface
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
                throw new Exception('Azure Computer Vision no está configurado');
            }

            $endpoint = rtrim($this->config['endpoint'], '/');
            $url = $endpoint . '/vision/v3.2/ocr';

            $imageContent = file_get_contents($filePath);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->config['key'],
                'Content-Type' => 'application/octet-stream',
            ])->withBody($imageContent, 'application/octet-stream')
              ->post($url, [
                  'language' => 'unk', // Auto-detect
                  'detectOrientation' => true,
              ]);

            if (!$response->successful()) {
                throw new Exception('Error en Azure API: ' . $response->body());
            }

            $result = $response->json();
            $text = $this->extractText($result);

            return [
                'success' => true,
                'text' => $text,
                'confidence' => 85, // Azure no proporciona confianza directa en OCR
                'orientation' => $result['orientation'] ?? 0,
                'engine' => 'azure_vision',
            ];
        } catch (Exception $e) {
            Log::error('Error en Azure Vision: ' . $e->getMessage());
            
            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage(),
                'engine' => 'azure_vision',
            ];
        }
    }

    protected function extractText(array $result): string
    {
        $lines = [];
        
        foreach ($result['regions'] ?? [] as $region) {
            foreach ($region['lines'] ?? [] as $line) {
                $words = [];
                foreach ($line['words'] ?? [] as $word) {
                    $words[] = $word['text'];
                }
                $lines[] = implode(' ', $words);
            }
        }

        return implode("\n", $lines);
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['endpoint']) && 
               !empty($this->config['key']) &&
               $this->config['enabled'];
    }

    public function getSupportedLanguages(): array
    {
        return ['all'];
    }
}
