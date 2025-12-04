<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de Validación de Archivos
 * 
 * REQ-CP-007: Validación avanzada de formatos
 * - Verifica tipos MIME
 * - Valida extensiones
 * - Detecta archivos maliciosos
 * - Escanea contenido real
 */
class FileValidationService
{
    /**
     * Formatos permitidos por categoría
     */
    private const ALLOWED_FORMATS = [
        'documentos' => [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'odt' => ['application/vnd.oasis.opendocument.text'],
            'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
            'txt' => ['text/plain'],
            'rtf' => ['application/rtf', 'text/rtf'],
        ],
        'imagenes' => [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'bmp' => ['image/bmp'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'tiff' => ['image/tiff'],
        ],
        'video' => [
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'],
            'mkv' => ['video/x-matroska'],
            'webm' => ['video/webm'],
        ],
        'audio' => [
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'ogg' => ['audio/ogg'],
            'flac' => ['audio/flac'],
            'm4a' => ['audio/mp4'],
            'aac' => ['audio/aac'],
        ],
    ];

    /**
     * Tamaños máximos por tipo (en MB)
     */
    private const MAX_SIZES = [
        'documentos' => 50,
        'imagenes' => 20,
        'video' => 500,
        'audio' => 100,
    ];

    /**
     * Extensiones peligrosas prohibidas
     */
    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 
        'jar', 'msi', 'dll', 'sh', 'app', 'deb', 'rpm'
    ];

    /**
     * Validar archivo completo
     */
    public function validate(UploadedFile $file, ?string $categoria = null): array
    {
        $results = [
            'valido' => true,
            'errores' => [],
            'advertencias' => [],
            'info' => [],
        ];

        // 1. Validar que el archivo existe y es válido
        if (!$file->isValid()) {
            $results['valido'] = false;
            $results['errores'][] = 'El archivo no es válido o no se cargó correctamente';
            return $results;
        }

        // 2. Validar extensión peligrosa
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $results['valido'] = false;
            $results['errores'][] = 'Tipo de archivo no permitido por razones de seguridad';
            return $results;
        }

        // 3. Detectar categoría si no se especificó
        if (!$categoria) {
            $categoria = $this->detectarCategoria($extension);
        }

        // 4. Validar formato permitido
        $formatoValido = $this->validarFormato($file, $extension, $categoria);
        if (!$formatoValido['valido']) {
            $results['valido'] = false;
            $results['errores'][] = $formatoValido['mensaje'];
        }

        // 5. Validar tamaño
        $tamañoValido = $this->validarTamaño($file, $categoria);
        if (!$tamañoValido['valido']) {
            $results['valido'] = false;
            $results['errores'][] = $tamañoValido['mensaje'];
        }

        // 6. Validar MIME type real
        $mimeValido = $this->validarMimeType($file, $extension, $categoria);
        if (!$mimeValido['valido']) {
            $results['valido'] = false;
            $results['errores'][] = $mimeValido['mensaje'];
        } elseif (isset($mimeValido['advertencia'])) {
            $results['advertencias'][] = $mimeValido['advertencia'];
        }

        // 7. Escanear contenido sospechoso
        $contenidoSeguro = $this->escanearContenido($file);
        if (!$contenidoSeguro['seguro']) {
            $results['valido'] = false;
            $results['errores'][] = $contenidoSeguro['mensaje'];
        }

        // 8. Información adicional
        $results['info'] = [
            'nombre_original' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'tamaño_bytes' => $file->getSize(),
            'tamaño_mb' => round($file->getSize() / 1024 / 1024, 2),
            'categoria' => $categoria,
        ];

        return $results;
    }

    /**
     * Detectar categoría del archivo
     */
    private function detectarCategoria(string $extension): string
    {
        foreach (self::ALLOWED_FORMATS as $categoria => $formatos) {
            if (array_key_exists($extension, $formatos)) {
                return $categoria;
            }
        }

        return 'desconocido';
    }

    /**
     * Validar formato permitido
     */
    private function validarFormato(UploadedFile $file, string $extension, string $categoria): array
    {
        if (!isset(self::ALLOWED_FORMATS[$categoria])) {
            return [
                'valido' => false,
                'mensaje' => "Categoría de archivo '{$categoria}' no soportada"
            ];
        }

        if (!isset(self::ALLOWED_FORMATS[$categoria][$extension])) {
            $formatosPermitidos = implode(', ', array_keys(self::ALLOWED_FORMATS[$categoria]));
            return [
                'valido' => false,
                'mensaje' => "Formato '.{$extension}' no permitido. Formatos válidos: {$formatosPermitidos}"
            ];
        }

        return ['valido' => true];
    }

    /**
     * Validar tamaño del archivo
     */
    private function validarTamaño(UploadedFile $file, string $categoria): array
    {
        $maxSize = self::MAX_SIZES[$categoria] ?? 10; // 10MB por defecto
        $maxBytes = $maxSize * 1024 * 1024;
        $fileSize = $file->getSize();

        if ($fileSize > $maxBytes) {
            return [
                'valido' => false,
                'mensaje' => sprintf(
                    'El archivo excede el tamaño máximo permitido de %d MB (tamaño actual: %.2f MB)',
                    $maxSize,
                    $fileSize / 1024 / 1024
                )
            ];
        }

        return ['valido' => true];
    }

    /**
     * Validar MIME type real del archivo
     */
    private function validarMimeType(UploadedFile $file, string $extension, string $categoria): array
    {
        $mimeTypeReal = $file->getMimeType();
        $mimeTypesPermitidos = self::ALLOWED_FORMATS[$categoria][$extension] ?? [];

        // Verificar si el MIME type coincide
        if (!in_array($mimeTypeReal, $mimeTypesPermitidos)) {
            // Algunos archivos pueden tener variaciones de MIME
            $variacionesComunes = $this->obtenerVariacionesMime($extension);
            
            if (!in_array($mimeTypeReal, $variacionesComunes)) {
                return [
                    'valido' => false,
                    'mensaje' => sprintf(
                        'El contenido real del archivo no coincide con la extensión. Se esperaba %s pero se detectó %s',
                        implode(' o ', $mimeTypesPermitidos),
                        $mimeTypeReal
                    )
                ];
            } else {
                return [
                    'valido' => true,
                    'advertencia' => 'El archivo tiene un MIME type no estándar pero aceptable'
                ];
            }
        }

        return ['valido' => true];
    }

    /**
     * Obtener variaciones comunes de MIME types
     */
    private function obtenerVariacionesMime(string $extension): array
    {
        $variaciones = [
            'jpg' => ['image/jpg', 'image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpg', 'image/jpeg', 'image/pjpeg'],
            'doc' => ['application/msword', 'application/vnd.ms-word'],
            'xls' => ['application/vnd.ms-excel', 'application/excel'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
        ];

        return $variaciones[$extension] ?? [];
    }

    /**
     * Escanear contenido en busca de patrones sospechosos
     */
    private function escanearContenido(UploadedFile $file): array
    {
        try {
            // Leer los primeros bytes del archivo
            $handle = fopen($file->getRealPath(), 'rb');
            $header = fread($handle, 1024);
            fclose($handle);

            // Patrones sospechosos en archivos
            $patronesPeligrosos = [
                '<?php',           // Código PHP
                '<script',         // JavaScript
                'eval(',           // Funciones peligrosas
                'exec(',
                'system(',
                'passthru(',
                'shell_exec(',
                'base64_decode(',  // Ofuscación
            ];

            foreach ($patronesPeligrosos as $patron) {
                if (stripos($header, $patron) !== false) {
                    Log::warning('Archivo sospechoso detectado', [
                        'nombre' => $file->getClientOriginalName(),
                        'patron' => $patron,
                    ]);

                    return [
                        'seguro' => false,
                        'mensaje' => 'El archivo contiene contenido potencialmente peligroso'
                    ];
                }
            }

            return ['seguro' => true];

        } catch (\Exception $e) {
            Log::error('Error al escanear contenido del archivo', [
                'error' => $e->getMessage()
            ]);

            return ['seguro' => true]; // Permitir si no se puede escanear
        }
    }

    /**
     * Obtener información detallada del archivo
     */
    public function getFileInfo(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $categoria = $this->detectarCategoria($extension);

        return [
            'nombre_original' => $file->getClientOriginalName(),
            'nombre_seguro' => $this->generarNombreSeguro($file),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'tamaño_bytes' => $file->getSize(),
            'tamaño_legible' => $this->formatearTamaño($file->getSize()),
            'categoria' => $categoria,
            'hash_md5' => md5_file($file->getRealPath()),
            'hash_sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    /**
     * Generar nombre de archivo seguro
     */
    public function generarNombreSeguro(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $nombre = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Limpiar el nombre
        $nombre = preg_replace('/[^a-z0-9_\-]/i', '_', $nombre);
        $nombre = substr($nombre, 0, 50); // Limitar longitud
        
        // Agregar timestamp único
        return $nombre . '_' . time() . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Formatear tamaño de archivo
     */
    private function formatearTamaño(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Validar múltiples archivos
     */
    public function validateMultiple(array $files, ?string $categoria = null): array
    {
        $results = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $results[$index] = $this->validate($file, $categoria);
            }
        }

        return $results;
    }
}
