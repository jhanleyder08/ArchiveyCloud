<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Log;
use Exception;

class BarcodeDetector
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Detectar códigos de barras en la imagen
     */
    public function detect(string $filePath): array
    {
        try {
            // Requiere: composer require zxing/zxing
            // O usar biblioteca alternativa
            
            // Por ahora, implementación básica que detecta usando ZXing si está disponible
            if (class_exists('ZXing\QrReader')) {
                return $this->detectWithZXing($filePath);
            }

            // Fallback: devolver vacío si no hay biblioteca disponible
            Log::info('No hay biblioteca de detección de códigos de barras instalada');
            return [];
        } catch (Exception $e) {
            Log::error('Error detectando códigos de barras: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Detectar usando ZXing
     */
    protected function detectWithZXing(string $filePath): array
    {
        $barcodes = [];

        try {
            // QR Code
            if (in_array('QR', $this->config['types'])) {
                $qrcode = new \ZXing\QrReader($filePath);
                $text = $qrcode->text();
                if ($text) {
                    $barcodes[] = [
                        'type' => 'QR',
                        'value' => $text,
                    ];
                }
            }

            // Otros tipos de códigos requieren bibliotecas adicionales
        } catch (Exception $e) {
            Log::warning('Error en detección ZXing: ' . $e->getMessage());
        }

        return $barcodes;
    }

    /**
     * Verificar si el detector está disponible
     */
    public function isAvailable(): bool
    {
        return $this->config['enabled'] && 
               (class_exists('ZXing\QrReader') || class_exists('Zxing\BarcodeReader'));
    }
}
