<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Log;
use Exception;

class ImagePreprocessor
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Procesar imagen para mejorar resultados de OCR
     */
    public function process(string $filePath): string
    {
        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            // Cargar imagen según formato
            $image = match($extension) {
                'jpg', 'jpeg' => imagecreatefromjpeg($filePath),
                'png' => imagecreatefrompng($filePath),
                'gif' => imagecreatefromgif($filePath),
                'bmp' => imagecreatefrombmp($filePath),
                default => throw new Exception("Formato no soportado para preprocesamiento: {$extension}"),
            };

            if (!$image) {
                throw new Exception("No se pudo cargar la imagen: {$filePath}");
            }

            // Escalar imagen si está configurado
            if ($this->config['scale'] != 1.0) {
                $image = $this->scaleImage($image, $this->config['scale']);
            }

            // Mejorar contraste
            if ($this->config['enhance_contrast']) {
                $image = $this->enhanceContrast($image);
            }

            // Reducir ruido
            if ($this->config['denoise']) {
                $image = $this->denoise($image);
            }

            // Binarizar (convertir a blanco y negro)
            if ($this->config['binarize']) {
                $image = $this->binarize($image);
            }

            // Corregir inclinación
            if ($this->config['deskew']) {
                $image = $this->deskew($image);
            }

            // Guardar imagen procesada
            $outputPath = sys_get_temp_dir() . '/' . uniqid('ocr_preprocessed_') . '.png';
            imagepng($image, $outputPath);
            imagedestroy($image);

            return $outputPath;
        } catch (Exception $e) {
            Log::error('Error en preprocesamiento de imagen: ' . $e->getMessage());
            return $filePath; // Devolver original si falla
        }
    }

    /**
     * Escalar imagen
     */
    protected function scaleImage($image, float $scale)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $newWidth = (int)($width * $scale);
        $newHeight = (int)($height * $scale);
        
        $scaled = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        
        return $scaled;
    }

    /**
     * Mejorar contraste
     */
    protected function enhanceContrast($image)
    {
        imagefilter($image, IMG_FILTER_CONTRAST, -30);
        return $image;
    }

    /**
     * Reducir ruido (blur inverso)
     */
    protected function denoise($image)
    {
        imagefilter($image, IMG_FILTER_SMOOTH, -10);
        return $image;
    }

    /**
     * Binarizar imagen (blanco y negro)
     */
    protected function binarize($image)
    {
        // Convertir a escala de grises
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        
        // Aplicar umbral (threshold)
        $width = imagesx($image);
        $height = imagesy($image);
        
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $gray = ($rgb >> 16) & 0xFF;
                
                // Umbral en 128
                $color = $gray > 128 ? 255 : 0;
                $newColor = imagecolorallocate($image, $color, $color, $color);
                imagesetpixel($image, $x, $y, $newColor);
            }
        }
        
        return $image;
    }

    /**
     * Corregir inclinación (deskew)
     */
    protected function deskew($image)
    {
        // Implementación básica
        // Para una implementación completa, usar bibliotecas como ImageMagick
        $angle = $this->detectSkewAngle($image);
        
        if (abs($angle) > 0.5) {
            $image = imagerotate($image, $angle, 0);
        }
        
        return $image;
    }

    /**
     * Detectar ángulo de inclinación
     */
    protected function detectSkewAngle($image): float
    {
        // Implementación simplificada
        // En producción, usar algoritmos más sofisticados
        return 0.0;
    }
}
