<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\TipologiaDocumental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

/**
 * Servicio de Procesamiento Avanzado de Documentos
 * 
 * Implementa requerimientos:
 * REQ-CP-002: Contenidos multimedia avanzados
 * REQ-CP-007: Validación avanzada de formatos
 * REQ-CP-013/014: OCR básico
 * REQ-CP-028: Conversión automática de formatos
 */
class DocumentProcessingService
{
    /**
     * REQ-CP-007: Formatos permitidos expandidos con validaciones avanzadas
     */
    const FORMATOS_PERMITIDOS = [
        'texto' => [
            'formatos' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'pages'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'application/rtf',
                'application/vnd.oasis.opendocument.text'
            ],
            'tamaño_max' => 50, // MB
            'validacion_contenido' => true
        ],
        'imagen' => [
            'formatos' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'webp', 'heic', 'raw'],
            'mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/tiff',
                'image/svg+xml',
                'image/webp',
                'image/heic'
            ],
            'tamaño_max' => 25, // MB
            'validacion_contenido' => true,
            'ocr_disponible' => true,
            'genera_miniatura' => true,
            'resolucion_min' => ['width' => 100, 'height' => 100],
            'resolucion_max' => ['width' => 8000, 'height' => 8000]
        ],
        'hoja_calculo' => [
            'formatos' => ['xls', 'xlsx', 'csv', 'ods', 'numbers'],
            'mime_types' => [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
                'application/vnd.oasis.opendocument.spreadsheet'
            ],
            'tamaño_max' => 100, // MB
            'validacion_contenido' => true
        ],
        'presentacion' => [
            'formatos' => ['ppt', 'pptx', 'odp', 'key'],
            'mime_types' => [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.presentation'
            ],
            'tamaño_max' => 200, // MB
            'validacion_contenido' => true
        ],
        'audio' => [
            'formatos' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'],
            'mime_types' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/flac',
                'audio/mp4',
                'audio/aac',
                'audio/x-ms-wma'
            ],
            'tamaño_max' => 500, // MB
            'validacion_contenido' => true,
            'duracion_max' => 14400, // 4 horas en segundos
            'genera_espectrograma' => true,
            'calidad_min' => 64 // kbps
        ],
        'video' => [
            'formatos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v', '3gp'],
            'mime_types' => [
                'video/mp4',
                'video/x-msvideo',
                'video/quicktime',
                'video/x-ms-wmv',
                'video/x-flv',
                'video/webm',
                'video/x-matroska'
            ],
            'tamaño_max' => 2048, // MB
            'validacion_contenido' => true,
            'duracion_max' => 7200, // 2 horas en segundos
            'genera_miniatura' => true,
            'resolucion_max' => ['width' => 4096, 'height' => 2160], // 4K
            'fps_max' => 60
        ],
        'comprimido' => [
            'formatos' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'],
            'mime_types' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip'
            ],
            'tamaño_max' => 1024, // MB
            'validacion_contenido' => true,
            'escanear_virus' => true
        ]
    ];

    /**
     * REQ-CP-007: Validación avanzada de archivo
     */
    public function validarArchivo(UploadedFile $archivo, ?int $tipologiaId = null): array
    {
        $resultado = [
            'valido' => false,
            'errores' => [],
            'advertencias' => [],
            'metadatos' => []
        ];

        try {
            // 1. Validación básica de extensión
            $extension = strtolower($archivo->getClientOriginalExtension());
            $categoria = $this->getCategoriaFormato($extension);
            
            if (!$categoria) {
                $resultado['errores'][] = "Formato de archivo no soportado: {$extension}";
                return $resultado;
            }

            $config = self::FORMATOS_PERMITIDOS[$categoria];

            // 2. Validación de MIME type
            $mimeType = $archivo->getMimeType();
            if (!in_array($mimeType, $config['mime_types'])) {
                $resultado['errores'][] = "Tipo MIME no válido para {$extension}: {$mimeType}";
            }

            // 3. Validación de tamaño
            $tamañoMB = $archivo->getSize() / (1024 * 1024);
            if ($tamañoMB > $config['tamaño_max']) {
                $resultado['errores'][] = "Archivo excede el tamaño máximo para {$categoria}: {$config['tamaño_max']}MB";
            }

            // 4. Validación contra tipología si existe
            if ($tipologiaId) {
                $tipologia = TipologiaDocumental::find($tipologiaId);
                if ($tipologia && !empty($tipologia->formato_archivo)) {
                    if (!in_array($extension, $tipologia->formato_archivo)) {
                        $resultado['errores'][] = "El formato {$extension} no está permitido para la tipología {$tipologia->nombre}";
                    }
                }
            }

            // 5. Validaciones específicas por tipo
            $validacionEspecifica = $this->validarContenidoEspecifico($archivo, $categoria, $config);
            $resultado['errores'] = array_merge($resultado['errores'], $validacionEspecifica['errores']);
            $resultado['advertencias'] = array_merge($resultado['advertencias'], $validacionEspecifica['advertencias']);
            $resultado['metadatos'] = $validacionEspecifica['metadatos'];

            // 6. Escaneo de seguridad
            $seguridadResult = $this->validarSeguridad($archivo);
            if (!$seguridadResult['seguro']) {
                $resultado['errores'][] = 'Archivo rechazado por razones de seguridad';
            }

            $resultado['valido'] = empty($resultado['errores']);

        } catch (Exception $e) {
            Log::error('Error en validación de archivo', [
                'archivo' => $archivo->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            $resultado['errores'][] = 'Error interno de validación';
        }

        return $resultado;
    }

    /**
     * REQ-CP-028: Procesamiento y conversión automática de archivos
     */
    public function procesarArchivo(UploadedFile $archivo, Documento $documento, array $opciones = []): array
    {
        $resultado = [
            'success' => false,
            'archivo_procesado' => null,
            'metadatos' => [],
            'conversiones' => [],
            'ocr_texto' => null,
            'miniatura' => null
        ];

        try {
            $extension = strtolower($archivo->getClientOriginalExtension());
            $categoria = $this->getCategoriaFormato($extension);
            
            // 1. Guardar archivo original
            $rutaOriginal = $this->guardarArchivo($archivo, $documento);
            $resultado['archivo_procesado'] = $rutaOriginal;

            // 2. Extraer metadatos
            $resultado['metadatos'] = $this->extraerMetadatos($rutaOriginal, $categoria);

            // 3. Generar miniatura si es necesario
            if (self::FORMATOS_PERMITIDOS[$categoria]['genera_miniatura'] ?? false) {
                $resultado['miniatura'] = $this->generarMiniatura($rutaOriginal, $categoria);
            }

            // 4. OCR para imágenes si está habilitado
            if (($opciones['ocr'] ?? true) && (self::FORMATOS_PERMITIDOS[$categoria]['ocr_disponible'] ?? false)) {
                $resultado['ocr_texto'] = $this->extraerTextoOCR($rutaOriginal);
            }

            // 5. Conversiones automáticas
            if ($opciones['convertir'] ?? true) {
                $resultado['conversiones'] = $this->aplicarConversiones($rutaOriginal, $categoria, $opciones);
            }

            // 6. Procesamiento específico por tipo
            $this->procesarEspecificoPorTipo($rutaOriginal, $categoria, $resultado);

            $resultado['success'] = true;

        } catch (Exception $e) {
            Log::error('Error procesando archivo', [
                'archivo' => $archivo->getClientOriginalName(),
                'documento_id' => $documento->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $resultado;
    }

    /**
     * Validación de contenido específico por tipo
     */
    private function validarContenidoEspecifico(UploadedFile $archivo, string $categoria, array $config): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        switch ($categoria) {
            case 'imagen':
                $resultado = $this->validarImagen($archivo, $config);
                break;
            case 'video':
                $resultado = $this->validarVideo($archivo, $config);
                break;
            case 'audio':
                $resultado = $this->validarAudio($archivo, $config);
                break;
            case 'texto':
                $resultado = $this->validarDocumentoTexto($archivo, $config);
                break;
        }

        return $resultado;
    }

    /**
     * Validación específica para imágenes
     */
    private function validarImagen(UploadedFile $archivo, array $config): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        try {
            // Usar getimagesize para validación más robusta
            $imageInfo = @getimagesize($archivo->getPathname());
            
            if (!$imageInfo) {
                $resultado['errores'][] = 'Archivo de imagen corrupto o no válido';
                return $resultado;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Validar resolución mínima
            if (isset($config['resolucion_min'])) {
                if ($width < $config['resolucion_min']['width'] || 
                    $height < $config['resolucion_min']['height']) {
                    $resultado['errores'][] = "Resolución muy baja. Mínimo: {$config['resolucion_min']['width']}x{$config['resolucion_min']['height']}";
                }
            }

            // Validar resolución máxima
            if (isset($config['resolucion_max'])) {
                if ($width > $config['resolucion_max']['width'] || 
                    $height > $config['resolucion_max']['height']) {
                    $resultado['advertencias'][] = "Resolución muy alta, se puede redimensionar automáticamente";
                }
            }

            $resultado['metadatos'] = [
                'width' => $width,
                'height' => $height,
                'tipo_imagen' => $imageInfo[2],
                'ratio_aspecto' => round($width / $height, 2)
            ];

        } catch (Exception $e) {
            $resultado['errores'][] = 'Error validando imagen';
        }

        return $resultado;
    }

    /**
     * Validación específica para videos
     */
    private function validarVideo(UploadedFile $archivo, array $config): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        // Para validación completa de video se necesitaría FFmpeg
        // Por ahora, validación básica
        $resultado['metadatos'] = [
            'formato' => $archivo->getClientOriginalExtension(),
            'tamaño' => $archivo->getSize()
        ];

        return $resultado;
    }

    /**
     * Validación específica para audio
     */
    private function validarAudio(UploadedFile $archivo, array $config): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        // Para validación completa de audio se necesitaría FFmpeg o similar
        $resultado['metadatos'] = [
            'formato' => $archivo->getClientOriginalExtension(),
            'tamaño' => $archivo->getSize()
        ];

        return $resultado;
    }

    /**
     * Validación específica para documentos de texto
     */
    private function validarDocumentoTexto(UploadedFile $archivo, array $config): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        $extension = strtolower($archivo->getClientOriginalExtension());

        // Validación específica para PDFs
        if ($extension === 'pdf') {
            $resultado = $this->validarPDF($archivo);
        }

        return $resultado;
    }

    /**
     * Validación específica para PDFs
     */
    private function validarPDF(UploadedFile $archivo): array
    {
        $resultado = ['errores' => [], 'advertencias' => [], 'metadatos' => []];

        try {
            $contenido = file_get_contents($archivo->getPathname());
            
            // Verificar que sea un PDF válido
            if (strpos($contenido, '%PDF-') !== 0) {
                $resultado['errores'][] = 'Archivo PDF corrupto o no válido';
                return $resultado;
            }

            // Verificar si está protegido con contraseña
            if (strpos($contenido, '/Encrypt') !== false) {
                $resultado['advertencias'][] = 'PDF protegido con contraseña detectado';
            }

            $resultado['metadatos'] = [
                'pdf_version' => $this->extraerVersionPDF($contenido),
                'protegido' => strpos($contenido, '/Encrypt') !== false
            ];

        } catch (Exception $e) {
            $resultado['errores'][] = 'Error validando PDF';
        }

        return $resultado;
    }

    /**
     * Validación de seguridad básica
     */
    private function validarSeguridad(UploadedFile $archivo): array
    {
        $resultado = ['seguro' => true, 'amenazas' => []];

        // 1. Verificar extensiones peligrosas
        $extensionesPeligrosas = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'vbs', 'js', 'jar'];
        $extension = strtolower($archivo->getClientOriginalExtension());
        
        if (in_array($extension, $extensionesPeligrosas)) {
            $resultado['seguro'] = false;
            $resultado['amenazas'][] = 'Extensión de archivo potencialmente peligrosa';
        }

        // 2. Verificar tamaño sospechoso (archivos muy pequeños o muy grandes)
        $tamaño = $archivo->getSize();
        if ($tamaño < 10) { // Menos de 10 bytes
            $resultado['seguro'] = false;
            $resultado['amenazas'][] = 'Archivo sospechosamente pequeño';
        }

        // 3. Verificar nombre de archivo sospechoso
        $nombreArchivo = $archivo->getClientOriginalName();
        $patronesSospechosos = ['..', '<script>', 'javascript:', 'data:'];
        
        foreach ($patronesSospechosos as $patron) {
            if (stripos($nombreArchivo, $patron) !== false) {
                $resultado['seguro'] = false;
                $resultado['amenazas'][] = 'Nombre de archivo sospechoso';
                break;
            }
        }

        return $resultado;
    }

    /**
     * REQ-CP-014: Extracción de texto OCR básica
     */
    private function extraerTextoOCR(string $rutaArchivo): ?string
    {
        try {
            // Implementación básica de OCR
            // En producción se usaría Tesseract o similar
            
            // Por ahora retornamos null, se implementaría con:
            // - Tesseract OCR
            // - Google Vision API
            // - AWS Textract
            // - Azure Computer Vision

            Log::info('OCR solicitado para archivo', ['ruta' => $rutaArchivo]);
            return null;

        } catch (Exception $e) {
            Log::error('Error en OCR', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extraer metadatos del archivo
     */
    private function extraerMetadatos(string $rutaArchivo, string $categoria): array
    {
        $metadatos = [
            'tamaño' => filesize(storage_path('app/public/' . $rutaArchivo)),
            'fecha_creacion' => filemtime(storage_path('app/public/' . $rutaArchivo)),
            'hash_sha256' => hash_file('sha256', storage_path('app/public/' . $rutaArchivo))
        ];

        // Metadatos específicos por tipo se agregarían aquí
        // usando librerías como ExifRead para imágenes, FFmpeg para video/audio, etc.

        return $metadatos;
    }

    /**
     * Generar miniatura para archivos compatibles
     */
    private function generarMiniatura(string $rutaArchivo, string $categoria): ?string
    {
        try {
            // Implementación de generación de miniaturas
            // Se usarían librerías como Intervention Image, FFmpeg, etc.
            
            Log::info('Miniatura solicitada', ['archivo' => $rutaArchivo, 'categoria' => $categoria]);
            return null;

        } catch (Exception $e) {
            Log::error('Error generando miniatura', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Aplicar conversiones automáticas
     */
    private function aplicarConversiones(string $rutaArchivo, string $categoria, array $opciones): array
    {
        $conversiones = [];

        // Implementar conversiones automáticas según configuración
        // Por ejemplo: PDF a PDF/A, imágenes a WebP, etc.

        return $conversiones;
    }

    /**
     * Procesamiento específico por tipo de archivo
     */
    private function procesarEspecificoPorTipo(string $rutaArchivo, string $categoria, array &$resultado): void
    {
        switch ($categoria) {
            case 'imagen':
                // Procesar imágenes específicamente
                break;
            case 'video':
                // Procesar videos específicamente
                break;
            case 'audio':
                // Procesar audios específicamente
                break;
        }
    }

    /**
     * Guardar archivo en storage organizado
     */
    private function guardarArchivo(UploadedFile $archivo, Documento $documento): string
    {
        $año = now()->format('Y');
        $mes = now()->format('m');
        $expediente = $documento->expediente_id ?? 'sin_expediente';
        
        $directorio = "documentos/{$año}/{$mes}/expediente_{$expediente}";
        
        return $archivo->store($directorio, 'public');
    }

    /**
     * Obtener categoría de formato
     */
    private function getCategoriaFormato(string $formato): ?string
    {
        foreach (self::FORMATOS_PERMITIDOS as $categoria => $config) {
            if (in_array($formato, $config['formatos'])) {
                return $categoria;
            }
        }
        return null;
    }

    /**
     * Extraer versión de PDF del contenido
     */
    private function extraerVersionPDF(string $contenido): ?string
    {
        if (preg_match('/%PDF-(\d+\.\d+)/', $contenido, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * REQ-CP-002: Obtener configuración de formatos multimedia
     */
    public function getConfiguracionMultimedia(): array
    {
        return [
            'audio' => self::FORMATOS_PERMITIDOS['audio'],
            'video' => self::FORMATOS_PERMITIDOS['video'],
            'imagen' => self::FORMATOS_PERMITIDOS['imagen']
        ];
    }

    /**
     * Obtener todos los formatos soportados
     */
    public static function getFormatosSoportados(): array
    {
        $formatos = [];
        foreach (self::FORMATOS_PERMITIDOS as $categoria => $config) {
            $formatos[$categoria] = $config['formatos'];
        }
        return $formatos;
    }
}
