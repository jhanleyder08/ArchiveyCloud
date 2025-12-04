<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio de extracción de texto de documentos
 * Soporta múltiples formatos para indexación de texto completo
 */
class TextExtractionService
{
    /**
     * Extraer texto de un archivo
     * 
     * @param string $filePath Ruta completa al archivo
     * @param string $format Formato del archivo (pdf, docx, txt, etc.)
     * @return string Texto extraído
     */
    public function extractText(string $filePath, string $format): string
    {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo no encontrado: {$filePath}");
        }

        $format = strtolower($format);
        
        return match($format) {
            'txt', 'text' => $this->extractFromText($filePath),
            'pdf' => $this->extractFromPdf($filePath),
            'doc', 'docx' => $this->extractFromWord($filePath),
            'xls', 'xlsx' => $this->extractFromExcel($filePath),
            'html', 'htm' => $this->extractFromHtml($filePath),
            'xml' => $this->extractFromXml($filePath),
            'json' => $this->extractFromJson($filePath),
            default => $this->extractGeneric($filePath, $format),
        };
    }

    /**
     * Extraer texto de archivo de texto plano
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromText(string $filePath): string
    {
        try {
            $content = file_get_contents($filePath);
            return mb_convert_encoding($content, 'UTF-8', 'auto');
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto plano: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extraer texto de PDF
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromPdf(string $filePath): string
    {
        try {
            // Opción 1: Usar pdftotext (si está instalado)
            if ($this->commandExists('pdftotext')) {
                $outputFile = tempnam(sys_get_temp_dir(), 'pdf_');
                $command = sprintf('pdftotext %s %s', escapeshellarg($filePath), escapeshellarg($outputFile));
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($outputFile)) {
                    $text = file_get_contents($outputFile);
                    unlink($outputFile);
                    return $text;
                }
            }

            // Opción 2: Usar librería PHP si está disponible
            if (class_exists('\Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            }

            // Si no hay métodos disponibles, indicar que se requiere OCR
            Log::info("PDF sin extracción de texto disponible, se requiere OCR: {$filePath}");
            return '[CONTENIDO PDF - REQUIERE OCR]';
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de PDF: " . $e->getMessage());
            return '[ERROR EXTRAYENDO PDF]';
        }
    }

    /**
     * Extraer texto de Word (DOC/DOCX)
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromWord(string $filePath): string
    {
        try {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if ($extension === 'docx') {
                return $this->extractFromDocx($filePath);
            } else {
                // Para .doc usar antiword o catdoc si están disponibles
                if ($this->commandExists('antiword')) {
                    $command = sprintf('antiword %s', escapeshellarg($filePath));
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        return implode("\n", $output);
                    }
                }
            }

            return '[CONTENIDO WORD - FORMATO NO SOPORTADO]';
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de Word: " . $e->getMessage());
            return '[ERROR EXTRAYENDO WORD]';
        }
    }

    /**
     * Extraer texto de DOCX
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromDocx(string $filePath): string
    {
        try {
            // DOCX es básicamente un ZIP con XML
            $zip = new \ZipArchive();
            
            if ($zip->open($filePath) === true) {
                $content = $zip->getFromName('word/document.xml');
                $zip->close();
                
                if ($content) {
                    // Limpiar XML y extraer texto
                    $content = str_replace('</w:p>', "\n", $content);
                    $content = strip_tags($content);
                    return trim($content);
                }
            }
            
            return '[ERROR ABRIENDO DOCX]';
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de DOCX: " . $e->getMessage());
            return '[ERROR EXTRAYENDO DOCX]';
        }
    }

    /**
     * Extraer texto de Excel
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromExcel(string $filePath): string
    {
        try {
            // Para XLSX
            if (pathinfo($filePath, PATHINFO_EXTENSION) === 'xlsx') {
                $zip = new \ZipArchive();
                
                if ($zip->open($filePath) === true) {
                    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
                    $zip->close();
                    
                    if ($sharedStrings) {
                        // Extraer texto del XML
                        $xml = simplexml_load_string($sharedStrings);
                        $texts = [];
                        
                        foreach ($xml->si as $si) {
                            if (isset($si->t)) {
                                $texts[] = (string) $si->t;
                            }
                        }
                        
                        return implode(' ', $texts);
                    }
                }
            }
            
            return '[CONTENIDO EXCEL - FORMATO NO SOPORTADO]';
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de Excel: " . $e->getMessage());
            return '[ERROR EXTRAYENDO EXCEL]';
        }
    }

    /**
     * Extraer texto de HTML
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromHtml(string $filePath): string
    {
        try {
            $html = file_get_contents($filePath);
            
            // Eliminar scripts y styles
            $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
            $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
            
            // Eliminar tags HTML
            $text = strip_tags($html);
            
            // Limpiar espacios múltiples
            $text = preg_replace('/\s+/', ' ', $text);
            
            return trim($text);
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de HTML: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extraer texto de XML
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromXml(string $filePath): string
    {
        try {
            $xml = file_get_contents($filePath);
            
            // Eliminar tags XML pero mantener contenido
            $text = strip_tags($xml);
            
            // Limpiar espacios múltiples
            $text = preg_replace('/\s+/', ' ', $text);
            
            return trim($text);
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de XML: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extraer texto de JSON
     * 
     * @param string $filePath
     * @return string
     */
    protected function extractFromJson(string $filePath): string
    {
        try {
            $json = file_get_contents($filePath);
            $data = json_decode($json, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Convertir el array a texto plano
                return $this->jsonToText($data);
            }
            
            return '';
        } catch (Exception $e) {
            Log::warning("Error extrayendo texto de JSON: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Convertir JSON a texto plano
     * 
     * @param mixed $data
     * @param string $prefix
     * @return string
     */
    protected function jsonToText($data, string $prefix = ''): string
    {
        if (is_scalar($data)) {
            return (string) $data . ' ';
        }
        
        $text = '';
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $text .= $this->jsonToText($value, $prefix . $key . ' ');
            }
        }
        
        return $text;
    }

    /**
     * Extracción genérica
     * 
     * @param string $filePath
     * @param string $format
     * @return string
     */
    protected function extractGeneric(string $filePath, string $format): string
    {
        try {
            // Intentar leer como texto plano
            $content = file_get_contents($filePath);
            
            // Verificar si es texto legible
            if (mb_check_encoding($content, 'UTF-8') || mb_check_encoding($content, 'ISO-8859-1')) {
                return mb_convert_encoding($content, 'UTF-8', 'auto');
            }
            
            Log::info("Formato no soportado para extracción de texto: {$format}");
            return "[FORMATO {$format} - NO SOPORTADO PARA EXTRACCIÓN]";
        } catch (Exception $e) {
            Log::warning("Error en extracción genérica: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Verificar si un comando existe en el sistema
     * 
     * @param string $command
     * @return bool
     */
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $result = shell_exec(sprintf('%s %s 2>&1', $whereIsCommand, escapeshellarg($command)));
        return !empty($result);
    }

    /**
     * Limpiar texto extraído
     * 
     * @param string $text
     * @return string
     */
    public function cleanText(string $text): string
    {
        // Eliminar caracteres de control
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalizar espacios en blanco
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trimear
        $text = trim($text);
        
        return $text;
    }

    /**
     * Limitar longitud del texto para indexación
     * 
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    public function truncateText(string $text, int $maxLength = 50000): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        
        return mb_substr($text, 0, $maxLength) . '... [TRUNCADO]';
    }
}
