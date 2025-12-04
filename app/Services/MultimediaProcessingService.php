<?php

namespace App\Services;

use App\Models\Documento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use Intervention\Image\Facades\Image;

/**
 * Servicio de Procesamiento Multimedia Avanzado
 * 
 * REQ-CP-002: Contenidos multimedia completo
 * - Procesamiento de video
 * - Procesamiento de audio
 * - Extracción de metadatos
 * - Generación de miniaturas y transcodes
 */
class MultimediaProcessingService
{
    protected $ffmpeg;
    protected $supportedVideoFormats = ['mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv', 'webm'];
    protected $supportedAudioFormats = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'];
    protected $supportedImageFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'bmp'];

    public function __construct()
    {
        // Inicializar FFMpeg si está disponible
        if (class_exists(FFMpeg::class)) {
            try {
                $this->ffmpeg = FFMpeg::create([
                    'ffmpeg.binaries'  => env('FFMPEG_PATH', 'ffmpeg'),
                    'ffprobe.binaries' => env('FFPROBE_PATH', 'ffprobe'),
                    'timeout'          => 3600,
                    'ffmpeg.threads'   => 12,
                ]);
            } catch (\Exception $e) {
                Log::warning('FFMpeg no disponible: ' . $e->getMessage());
            }
        }
    }

    /**
     * Procesar archivo multimedia
     */
    public function processMultimedia(Documento $documento): array
    {
        $extension = strtolower(pathinfo($documento->ruta_archivo, PATHINFO_EXTENSION));
        $results = [];

        try {
            if (in_array($extension, $this->supportedVideoFormats)) {
                $results = $this->processVideo($documento);
            } elseif (in_array($extension, $this->supportedAudioFormats)) {
                $results = $this->processAudio($documento);
            } elseif (in_array($extension, $this->supportedImageFormats)) {
                $results = $this->processImage($documento);
            }

            $this->updateDocumentoWithResults($documento, $results);

            return [
                'success' => true,
                'results' => $results,
            ];
        } catch (\Exception $e) {
            Log::error('Error procesando multimedia: ' . $e->getMessage());
            
            $documento->update([
                'estado_procesamiento' => 'error',
                'error_procesamiento' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Procesar video
     */
    protected function processVideo(Documento $documento): array
    {
        if (!$this->ffmpeg) {
            throw new \Exception('FFMpeg no está disponible');
        }

        $rutaOriginal = Storage::path($documento->ruta_archivo);
        $video = $this->ffmpeg->open($rutaOriginal);

        $results = [];

        // Extraer metadatos
        $results['metadata'] = $this->extractVideoMetadata($video);

        // Generar miniatura
        $thumbnailPath = $this->generateVideoThumbnail($video, $documento);
        if ($thumbnailPath) {
            $results['thumbnail'] = $thumbnailPath;
        }

        // Generar versión web-optimizada (MP4 H.264)
        $webVersionPath = $this->generateWebVersion($video, $documento);
        if ($webVersionPath) {
            $results['web_version'] = $webVersionPath;
        }

        // Generar múltiples resoluciones
        $results['resolutions'] = $this->generateMultipleResolutions($video, $documento);

        // Extraer audio del video
        $audioPath = $this->extractAudioFromVideo($video, $documento);
        if ($audioPath) {
            $results['audio_extract'] = $audioPath;
        }

        return $results;
    }

    /**
     * Procesar audio
     */
    protected function processAudio(Documento $documento): array
    {
        if (!$this->ffmpeg) {
            throw new \Exception('FFMpeg no está disponible');
        }

        $rutaOriginal = Storage::path($documento->ruta_archivo);
        $audio = $this->ffmpeg->open($rutaOriginal);

        $results = [];

        // Extraer metadatos
        $results['metadata'] = $this->extractAudioMetadata($audio);

        // Generar waveform (forma de onda)
        $waveformPath = $this->generateWaveform($audio, $documento);
        if ($waveformPath) {
            $results['waveform'] = $waveformPath;
        }

        // Convertir a MP3 si no lo es
        if (pathinfo($documento->nombre, PATHINFO_EXTENSION) !== 'mp3') {
            $mp3Path = $this->convertToMP3($audio, $documento);
            if ($mp3Path) {
                $results['mp3_version'] = $mp3Path;
            }
        }

        // Normalizar audio
        $normalizedPath = $this->normalizeAudio($audio, $documento);
        if ($normalizedPath) {
            $results['normalized'] = $normalizedPath;
        }

        return $results;
    }

    /**
     * Procesar imagen
     */
    protected function processImage(Documento $documento): array
    {
        $rutaOriginal = Storage::path($documento->ruta_archivo);
        $image = Image::make($rutaOriginal);

        $results = [];

        // Extraer metadatos EXIF
        $results['metadata'] = $this->extractImageMetadata($rutaOriginal);

        // Generar miniatura
        $thumbnailPath = $this->generateImageThumbnail($image, $documento);
        if ($thumbnailPath) {
            $results['thumbnail'] = $thumbnailPath;
        }

        // Generar versiones en diferentes tamaños
        $results['sizes'] = $this->generateImageSizes($image, $documento);

        // Optimizar imagen
        $optimizedPath = $this->optimizeImage($image, $documento);
        if ($optimizedPath) {
            $results['optimized'] = $optimizedPath;
        }

        // Generar WebP
        $webpPath = $this->convertToWebP($image, $documento);
        if ($webpPath) {
            $results['webp'] = $webpPath;
        }

        return $results;
    }

    /**
     * Generar miniatura de video
     */
    protected function generateVideoThumbnail($video, Documento $documento): ?string
    {
        try {
            $frame = $video->frame(TimeCode::fromSeconds(5));
            $thumbnailName = 'thumbnails/' . $documento->id . '_thumb.jpg';
            $thumbnailPath = Storage::path($thumbnailName);

            $frame->save($thumbnailPath);

            return $thumbnailName;
        } catch (\Exception $e) {
            Log::warning('Error generando miniatura de video: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar versión web del video
     */
    protected function generateWebVersion($video, Documento $documento): ?string
    {
        try {
            $format = new X264('aac', 'libx264');
            $format->setKiloBitrate(1000)
                   ->setAudioKiloBitrate(128);

            $webName = 'web_versions/' . $documento->id . '_web.mp4';
            $webPath = Storage::path($webName);

            $video->save($format, $webPath);

            return $webName;
        } catch (\Exception $e) {
            Log::warning('Error generando versión web: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar múltiples resoluciones
     */
    protected function generateMultipleResolutions($video, Documento $documento): array
    {
        $resolutions = [
            '720p' => new Dimension(1280, 720),
            '480p' => new Dimension(854, 480),
            '360p' => new Dimension(640, 360),
        ];

        $generated = [];

        foreach ($resolutions as $name => $dimension) {
            try {
                $format = new X264('aac', 'libx264');
                $format->setKiloBitrate(800);

                $resName = "resolutions/{$documento->id}_{$name}.mp4";
                $resPath = Storage::path($resName);

                $video->filters()->resize($dimension)->synchronize();
                $video->save($format, $resPath);

                $generated[$name] = $resName;
            } catch (\Exception $e) {
                Log::warning("Error generando resolución {$name}: " . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * Extraer metadatos de video
     */
    protected function extractVideoMetadata($video): array
    {
        try {
            $streams = $video->getStreams();
            $videoStream = $streams->videos()->first();
            $audioStream = $streams->audios()->first();

            return [
                'duration' => $videoStream->get('duration'),
                'width' => $videoStream->get('width'),
                'height' => $videoStream->get('height'),
                'codec' => $videoStream->get('codec_name'),
                'bitrate' => $videoStream->get('bit_rate'),
                'framerate' => $videoStream->get('r_frame_rate'),
                'audio_codec' => $audioStream ? $audioStream->get('codec_name') : null,
                'audio_sample_rate' => $audioStream ? $audioStream->get('sample_rate') : null,
            ];
        } catch (\Exception $e) {
            Log::warning('Error extrayendo metadatos de video: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extraer metadatos de audio
     */
    protected function extractAudioMetadata($audio): array
    {
        try {
            $streams = $audio->getStreams();
            $audioStream = $streams->audios()->first();

            return [
                'duration' => $audioStream->get('duration'),
                'codec' => $audioStream->get('codec_name'),
                'bitrate' => $audioStream->get('bit_rate'),
                'sample_rate' => $audioStream->get('sample_rate'),
                'channels' => $audioStream->get('channels'),
            ];
        } catch (\Exception $e) {
            Log::warning('Error extrayendo metadatos de audio: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extraer metadatos de imagen
     */
    protected function extractImageMetadata(string $path): array
    {
        $metadata = [];

        try {
            $exif = @exif_read_data($path);
            if ($exif) {
                $metadata['exif'] = [
                    'camera' => $exif['Model'] ?? null,
                    'date_taken' => $exif['DateTime'] ?? null,
                    'width' => $exif['COMPUTED']['Width'] ?? null,
                    'height' => $exif['COMPUTED']['Height'] ?? null,
                    'exposure_time' => $exif['ExposureTime'] ?? null,
                    'f_number' => $exif['FNumber'] ?? null,
                    'iso' => $exif['ISOSpeedRatings'] ?? null,
                ];
            }

            $image = Image::make($path);
            $metadata['image'] = [
                'width' => $image->width(),
                'height' => $image->height(),
                'mime' => $image->mime(),
                'file_size' => filesize($path),
            ];
        } catch (\Exception $e) {
            Log::warning('Error extrayendo metadatos de imagen: ' . $e->getMessage());
        }

        return $metadata;
    }

    /**
     * Generar miniatura de imagen
     */
    protected function generateImageThumbnail($image, Documento $documento): ?string
    {
        try {
            $thumbnailName = 'thumbnails/' . $documento->id . '_thumb.jpg';
            $thumbnailPath = Storage::path($thumbnailName);

            $image->fit(300, 300)->save($thumbnailPath, 85);

            return $thumbnailName;
        } catch (\Exception $e) {
            Log::warning('Error generando miniatura de imagen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar diferentes tamaños de imagen
     */
    protected function generateImageSizes($image, Documento $documento): array
    {
        $sizes = [
            'small' => [320, 320],
            'medium' => [640, 640],
            'large' => [1024, 1024],
        ];

        $generated = [];

        foreach ($sizes as $sizeName => [$width, $height]) {
            try {
                $sizeFileName = "sizes/{$documento->id}_{$sizeName}.jpg";
                $sizePath = Storage::path($sizeFileName);

                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($sizePath, 85);

                $generated[$sizeName] = $sizeFileName;
            } catch (\Exception $e) {
                Log::warning("Error generando tamaño {$sizeName}: " . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * Convertir imagen a WebP
     */
    protected function convertToWebP($image, Documento $documento): ?string
    {
        try {
            $webpName = 'webp/' . $documento->id . '.webp';
            $webpPath = Storage::path($webpName);

            $image->save($webpPath, 85, 'webp');

            return $webpName;
        } catch (\Exception $e) {
            Log::warning('Error convirtiendo a WebP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Optimizar imagen
     */
    protected function optimizeImage($image, Documento $documento): ?string
    {
        try {
            $optimizedName = 'optimized/' . $documento->id . '.jpg';
            $optimizedPath = Storage::path($optimizedName);

            $image->save($optimizedPath, 75);

            return $optimizedName;
        } catch (\Exception $e) {
            Log::warning('Error optimizando imagen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar documento con resultados
     */
    protected function updateDocumentoWithResults(Documento $documento, array $results): void
    {
        $updateData = [
            'estado_procesamiento' => 'completado',
            'fecha_procesamiento' => now(),
            'metadatos_archivo' => array_merge(
                $documento->metadatos_archivo ?? [],
                $results['metadata'] ?? []
            ),
            'rutas_conversiones' => $results,
        ];

        if (isset($results['thumbnail'])) {
            $updateData['ruta_miniatura'] = $results['thumbnail'];
        }

        $documento->update($updateData);
    }

    /**
     * Métodos stub para funcionalidades adicionales
     */
    protected function extractAudioFromVideo($video, Documento $documento): ?string
    {
        // Implementación futura
        return null;
    }

    protected function generateWaveform($audio, Documento $documento): ?string
    {
        // Implementación futura
        return null;
    }

    protected function convertToMP3($audio, Documento $documento): ?string
    {
        // Implementación futura
        return null;
    }

    protected function normalizeAudio($audio, Documento $documento): ?string
    {
        // Implementación futura
        return null;
    }
}
