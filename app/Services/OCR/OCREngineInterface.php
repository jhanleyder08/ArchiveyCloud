<?php

namespace App\Services\OCR;

interface OCREngineInterface
{
    /**
     * Reconocer texto de una imagen
     * 
     * @param string $filePath Ruta al archivo
     * @param array $options Opciones adicionales
     * @return array Resultado con texto y metadatos
     */
    public function recognize(string $filePath, array $options = []): array;

    /**
     * Verificar si el motor está disponible
     * 
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Obtener idiomas soportados
     * 
     * @return array
     */
    public function getSupportedLanguages(): array;
}
