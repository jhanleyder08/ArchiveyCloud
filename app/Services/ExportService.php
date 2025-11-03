<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Servicio de Exportación Avanzada
 * Exporta documentos y expedientes en múltiples formatos
 */
class ExportService
{
    /**
     * Exportar documentos a Excel
     */
    public function exportDocumentsToExcel(Collection $documentos, array $columns = []): string
    {
        $columns = $columns ?: [
            'codigo',
            'nombre',
            'tipo_documento',
            'fecha_documento',
            'estado',
            'usuario_creador',
            'created_at',
        ];

        $data = $documentos->map(function ($doc) use ($columns) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $this->getDocumentValue($doc, $column);
            }
            return $row;
        });

        // Aquí se integraría con una librería como PHPSpreadsheet
        // Por ahora retornamos CSV
        return $this->convertToCSV($data->toArray(), $columns);
    }

    /**
     * Exportar expediente completo
     */
    public function exportExpediente(Expediente $expediente, array $options = []): string
    {
        $includeDocuments = $options['include_documents'] ?? true;
        $includeMetadata = $options['include_metadata'] ?? true;
        $format = $options['format'] ?? 'zip';

        $tempDir = storage_path('app/temp/export_' . $expediente->id . '_' . time());
        mkdir($tempDir, 0755, true);

        try {
            // Exportar metadatos del expediente
            if ($includeMetadata) {
                $this->exportExpedienteMetadata($expediente, $tempDir);
            }

            // Exportar documentos
            if ($includeDocuments) {
                $this->exportExpedienteDocuments($expediente, $tempDir);
            }

            // Crear ZIP
            if ($format === 'zip') {
                $zipPath = $this->createZipFromDirectory($tempDir, $expediente->codigo);
                
                // Limpiar directorio temporal
                $this->deleteDirectory($tempDir);
                
                return $zipPath;
            }

            return $tempDir;

        } catch (\Exception $e) {
            // Limpiar en caso de error
            $this->deleteDirectory($tempDir);
            throw $e;
        }
    }

    /**
     * Exportar metadatos del expediente
     */
    private function exportExpedienteMetadata(Expediente $expediente, string $dir): void
    {
        $metadata = [
            'codigo' => $expediente->codigo,
            'nombre' => $expediente->nombre,
            'descripcion' => $expediente->descripcion,
            'estado' => $expediente->estado,
            'fecha_apertura' => $expediente->fecha_apertura?->format('Y-m-d'),
            'fecha_cierre' => $expediente->fecha_cierre?->format('Y-m-d'),
            'serie_documental' => $expediente->serieDocumental?->nombre,
            'subserie_documental' => $expediente->subserieDocumental?->nombre,
            'total_documentos' => $expediente->documentos()->count(),
            'usuario_creador' => $expediente->usuario->name ?? 'N/A',
            'created_at' => $expediente->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $expediente->updated_at?->format('Y-m-d H:i:s'),
        ];

        // Guardar como JSON
        file_put_contents(
            $dir . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Guardar como XML
        $xml = $this->arrayToXML($metadata, 'expediente');
        file_put_contents($dir . '/metadata.xml', $xml);
    }

    /**
     * Exportar documentos del expediente
     */
    private function exportExpedienteDocuments(Expediente $expediente, string $dir): void
    {
        $docsDir = $dir . '/documentos';
        mkdir($docsDir, 0755, true);

        $documentos = $expediente->documentos()->with('usuario')->get();
        $indice = [];

        foreach ($documentos as $index => $documento) {
            // Copiar archivo físico si existe
            if ($documento->ruta_archivo && Storage::exists($documento->ruta_archivo)) {
                $extension = pathinfo($documento->ruta_archivo, PATHINFO_EXTENSION);
                $filename = sprintf(
                    '%03d_%s.%s',
                    $index + 1,
                    $this->sanitizeFilename($documento->codigo),
                    $extension
                );
                
                copy(
                    Storage::path($documento->ruta_archivo),
                    $docsDir . '/' . $filename
                );

                $indice[] = [
                    'numero' => $index + 1,
                    'codigo' => $documento->codigo,
                    'nombre' => $documento->nombre,
                    'tipo' => $documento->tipo_documento,
                    'fecha' => $documento->fecha_documento?->format('Y-m-d'),
                    'archivo' => $filename,
                    'tamanio' => $documento->tamanio,
                    'hash' => $documento->hash,
                ];
            }
        }

        // Guardar índice de documentos
        file_put_contents(
            $docsDir . '/INDICE.json',
            json_encode($indice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Crear ZIP desde directorio
     */
    private function createZipFromDirectory(string $sourceDir, string $name): string
    {
        $zipPath = storage_path('app/exports/' . $this->sanitizeFilename($name) . '_' . time() . '.zip');
        
        // Crear directorio de exports si no existe
        $exportsDir = dirname($zipPath);
        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        $this->addDirectoryToZip($zip, $sourceDir, '');
        
        $zip->close();

        return $zipPath;
    }

    /**
     * Agregar directorio recursivamente a ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $path, string $relativePath): void
    {
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . '/' . $file;
            $zipPath = $relativePath . $file;

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirectoryToZip($zip, $fullPath, $zipPath . '/');
            } else {
                $zip->addFile($fullPath, $zipPath);
            }
        }
    }

    /**
     * Exportar a PDF
     */
    public function exportToPDF(Collection $data, array $options = []): string
    {
        // Aquí se integraría con una librería como DomPDF o wkhtmltopdf
        // Por ahora es placeholder
        $html = $this->generateHTMLReport($data, $options);
        
        // Guardar HTML temporalmente
        $tempFile = storage_path('app/temp/report_' . time() . '.html');
        file_put_contents($tempFile, $html);
        
        return $tempFile;
    }

    /**
     * Generar reporte HTML
     */
    private function generateHTMLReport(Collection $data, array $options): string
    {
        $title = $options['title'] ?? 'Reporte';
        $headers = $options['headers'] ?? [];

        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<title>{$title}</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; }\n";
        $html .= "table { border-collapse: collapse; width: 100%; }\n";
        $html .= "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
        $html .= "th { background-color: #4CAF50; color: white; }\n";
        $html .= "</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>{$title}</h1>\n";
        $html .= "<table>\n<thead>\n<tr>\n";

        // Headers
        foreach ($headers as $header) {
            $html .= "<th>{$header}</th>\n";
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";

        // Rows
        foreach ($data as $row) {
            $html .= "<tr>\n";
            foreach ($row as $value) {
                $html .= "<td>" . htmlspecialchars($value) . "</td>\n";
            }
            $html .= "</tr>\n";
        }

        $html .= "</tbody>\n</table>\n";
        $html .= "<p>Generado: " . now()->format('Y-m-d H:i:s') . "</p>\n";
        $html .= "</body>\n</html>";

        return $html;
    }

    /**
     * Convertir array a CSV
     */
    private function convertToCSV(array $data, array $headers = []): string
    {
        $output = fopen('php://temp', 'r+');

        // Escribir headers
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }

        // Escribir datos
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Convertir array a XML
     */
    private function arrayToXML(array $data, string $rootElement = 'root'): string
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}></{$rootElement}>");
        
        $this->arrayToXMLRecursive($data, $xml);
        
        return $xml->asXML();
    }

    /**
     * Convertir array a XML recursivamente
     */
    private function arrayToXMLRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXMLRecursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value ?? ''));
            }
        }
    }

    /**
     * Obtener valor de documento
     */
    private function getDocumentValue(Documento $doc, string $column)
    {
        return match($column) {
            'codigo' => $doc->codigo,
            'nombre' => $doc->nombre,
            'tipo_documento' => $doc->tipo_documento,
            'fecha_documento' => $doc->fecha_documento?->format('Y-m-d'),
            'estado' => $doc->estado,
            'usuario_creador' => $doc->usuario->name ?? 'N/A',
            'created_at' => $doc->created_at?->format('Y-m-d H:i:s'),
            'serie' => $doc->serieDocumental->nombre ?? 'N/A',
            'tamanio' => $this->formatBytes($doc->tamanio),
            default => $doc->$column ?? '',
        };
    }

    /**
     * Formatear bytes
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        return substr($filename, 0, 200);
    }

    /**
     * Eliminar directorio recursivamente
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}
