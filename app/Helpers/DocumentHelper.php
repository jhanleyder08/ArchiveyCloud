<?php

namespace App\Helpers;

use Carbon\Carbon;

class DocumentHelper
{
    /**
     * Generar código único para documentos
     */
    public static function generarCodigo(string $prefijo = 'DOC', ?int $serieId = null): string
    {
        $año = date('Y');
        $mes = date('m');
        $consecutivo = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $codigo = $prefijo . '-' . $año . $mes . '-' . $consecutivo;
        
        if ($serieId) {
            $codigo .= '-S' . str_pad($serieId, 3, '0', STR_PAD_LEFT);
        }
        
        return $codigo;
    }

    /**
     * Formatear tamaño de archivo
     */
    public static function formatearTamaño(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Sanitizar nombre de archivo
     */
    public static function sanitizarNombre(string $nombre): string
    {
        // Reemplazar caracteres especiales
        $nombre = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'],
            $nombre
        );
        
        // Remover caracteres no permitidos
        $nombre = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombre);
        
        // Limitar longitud
        return substr($nombre, 0, 200);
    }

    /**
     * Obtener extensión de archivo
     */
    public static function obtenerExtension(string $nombreArchivo): string
    {
        return strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    }

    /**
     * Verificar si es imagen
     */
    public static function esImagen(string $extension): bool
    {
        $extensionesImagen = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        return in_array(strtolower($extension), $extensionesImagen);
    }

    /**
     * Verificar si es PDF
     */
    public static function esPDF(string $extension): bool
    {
        return strtolower($extension) === 'pdf';
    }

    /**
     * Verificar si es documento de oficina
     */
    public static function esDocumentoOficina(string $extension): bool
    {
        $extensionesOficina = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods'];
        return in_array(strtolower($extension), $extensionesOficina);
    }

    /**
     * Calcular hash de archivo
     */
    public static function calcularHash(string $rutaArchivo, string $algoritmo = 'sha256'): string
    {
        return hash_file($algoritmo, $rutaArchivo);
    }

    /**
     * Formatear fecha para mostrar
     */
    public static function formatearFecha(?Carbon $fecha, string $formato = 'd/m/Y H:i'): string
    {
        return $fecha ? $fecha->format($formato) : 'N/A';
    }

    /**
     * Obtener tiempo relativo (hace X tiempo)
     */
    public static function tiempoRelativo(?Carbon $fecha): string
    {
        return $fecha ? $fecha->diffForHumans() : 'N/A';
    }

    /**
     * Generar nombre único para archivo
     */
    public static function generarNombreUnico(string $nombreOriginal): string
    {
        $extension = self::obtenerExtension($nombreOriginal);
        $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $nombreBase = self::sanitizarNombre($nombreBase);
        
        return $nombreBase . '_' . time() . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Convertir array a string para metadatos
     */
    public static function arrayToMetadata(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Convertir string de metadatos a array
     */
    public static function metadataToArray(?string $metadata): array
    {
        if (!$metadata) {
            return [];
        }
        
        $data = json_decode($metadata, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Generar breadcrumb de ruta
     */
    public static function generarBreadcrumb(string $ruta): array
    {
        $partes = explode('/', trim($ruta, '/'));
        $breadcrumb = [];
        $rutaAcumulada = '';
        
        foreach ($partes as $parte) {
            $rutaAcumulada .= '/' . $parte;
            $breadcrumb[] = [
                'nombre' => $parte,
                'ruta' => $rutaAcumulada,
            ];
        }
        
        return $breadcrumb;
    }

    /**
     * Validar si la fecha está vencida
     */
    public static function estaVencido(?Carbon $fechaVencimiento): bool
    {
        return $fechaVencimiento && $fechaVencimiento->isPast();
    }

    /**
     * Calcular días hasta vencimiento
     */
    public static function diasHastaVencimiento(?Carbon $fechaVencimiento): ?int
    {
        if (!$fechaVencimiento) {
            return null;
        }
        
        return (int) now()->diffInDays($fechaVencimiento, false);
    }

    /**
     * Obtener clase CSS según prioridad
     */
    public static function clasePrioridad(string $prioridad): string
    {
        return match($prioridad) {
            'urgente', 'vencida' => 'text-red-600 bg-red-50',
            'alta' => 'text-orange-600 bg-orange-50',
            'normal' => 'text-blue-600 bg-blue-50',
            'baja' => 'text-gray-600 bg-gray-50',
            default => 'text-gray-600 bg-gray-50',
        };
    }

    /**
     * Obtener icono según tipo de archivo
     */
    public static function obtenerIcono(string $extension): string
    {
        return match(strtolower($extension)) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-excel',
            'ppt', 'pptx' => 'file-powerpoint',
            'jpg', 'jpeg', 'png', 'gif' => 'file-image',
            'mp4', 'avi', 'mov' => 'file-video',
            'mp3', 'wav' => 'file-audio',
            'zip', 'rar' => 'file-archive',
            default => 'file',
        };
    }

    /**
     * Truncar texto
     */
    public static function truncar(string $texto, int $longitud = 100, string $sufijo = '...'): string
    {
        if (mb_strlen($texto) <= $longitud) {
            return $texto;
        }
        
        return mb_substr($texto, 0, $longitud) . $sufijo;
    }

    /**
     * Resaltar términos de búsqueda
     */
    public static function resaltarBusqueda(string $texto, string $termino): string
    {
        if (empty($termino)) {
            return $texto;
        }
        
        return preg_replace(
            '/(' . preg_quote($termino, '/') . ')/i',
            '<mark>$1</mark>',
            $texto
        );
    }
}
