<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Servicio de indexación de documentos para Elasticsearch
 * Implementa REQ-BP-001 (Indexación de texto completo)
 */
class DocumentIndexingService
{
    protected ElasticsearchService $elasticsearchService;
    protected TextExtractionService $textExtractionService;

    public function __construct(
        ElasticsearchService $elasticsearchService,
        TextExtractionService $textExtractionService
    ) {
        $this->elasticsearchService = $elasticsearchService;
        $this->textExtractionService = $textExtractionService;
    }

    /**
     * Indexar un documento individual
     * 
     * @param Documento $documento
     * @return bool
     */
    public function indexDocument(Documento $documento): bool
    {
        try {
            $body = $this->prepareDocumentForIndexing($documento);
            
            return $this->elasticsearchService->indexDocument(
                'documentos',
                (string) $documento->id,
                $body
            );
        } catch (Exception $e) {
            Log::error("Error al indexar documento {$documento->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Indexar múltiples documentos en lote
     * 
     * @param \Illuminate\Database\Eloquent\Collection|array $documentos
     * @return array
     */
    public function bulkIndexDocuments($documentos): array
    {
        $preparedDocs = [];
        
        foreach ($documentos as $documento) {
            try {
                $body = $this->prepareDocumentForIndexing($documento);
                $preparedDocs[] = [
                    'id' => (string) $documento->id,
                    'body' => $body,
                ];
            } catch (Exception $e) {
                Log::warning("Error al preparar documento {$documento->id}: " . $e->getMessage());
            }
        }
        
        return $this->elasticsearchService->bulkIndex('documentos', $preparedDocs);
    }

    /**
     * Preparar documento para indexación
     * 
     * @param Documento $documento
     * @return array
     */
    protected function prepareDocumentForIndexing(Documento $documento): array
    {
        // Extraer texto del contenido del archivo
        $contenidoTexto = $this->extractTextFromDocument($documento);

        // Obtener relaciones
        $documento->load([
            'serieDocumental',
            'subserieDocumental',
            'expediente',
            'usuarioCreador',
            'usuarioModificador'
        ]);

        $body = [
            'id' => $documento->id,
            'codigo' => $documento->codigo,
            'nombre' => $documento->nombre,
            'descripcion' => $documento->descripcion ?? '',
            'contenido' => $contenidoTexto,
            'tipo_documento' => $documento->tipo_documento ?? 'general',
            'formato_archivo' => $documento->formato_archivo ?? '',
            'tamanio' => $documento->tamanio ?? 0,
            'fecha_creacion' => $documento->created_at?->toIso8601String(),
            'fecha_modificacion' => $documento->updated_at?->toIso8601String(),
            'usuario_creador' => $documento->usuarioCreador?->name ?? 'Sistema',
            'usuario_modificador' => $documento->usuarioModificador?->name ?? '',
            'estado' => $documento->estado ?? 'activo',
            'version' => $documento->version ?? 1,
            'firmado' => $documento->firmado ?? false,
        ];

        // Agregar información de serie documental
        if ($documento->serieDocumental) {
            $body['serie_documental_id'] = $documento->serie_documental_id;
            $body['serie_documental_nombre'] = $documento->serieDocumental->nombre;
        }

        // Agregar información de subserie
        if ($documento->subserieDocumental) {
            $body['subserie_documental_id'] = $documento->subserie_documental_id;
            $body['subserie_documental_nombre'] = $documento->subserieDocumental->nombre;
        }

        // Agregar información de expediente
        if ($documento->expediente) {
            $body['expediente_id'] = $documento->expediente_id;
            $body['expediente_nombre'] = $documento->expediente->nombre;
        }

        // Agregar palabras clave si existen
        if (!empty($documento->palabras_clave)) {
            $body['palabras_clave'] = is_array($documento->palabras_clave) 
                ? $documento->palabras_clave 
                : explode(',', $documento->palabras_clave);
        }

        // Agregar metadatos adicionales
        if (!empty($documento->metadatos)) {
            $body['metadatos'] = is_array($documento->metadatos) 
                ? $documento->metadatos 
                : json_decode($documento->metadatos, true);
        }

        // Nivel de seguridad
        $body['nivel_seguridad'] = $documento->nivel_seguridad ?? 'publico';

        return $body;
    }

    /**
     * Extraer texto del contenido del documento
     * 
     * @param Documento $documento
     * @return string
     */
    protected function extractTextFromDocument(Documento $documento): string
    {
        // Si el documento no tiene archivo, retornar descripción
        if (empty($documento->ruta_archivo)) {
            return $documento->descripcion ?? '';
        }

        try {
            // Verificar si el archivo existe
            if (!Storage::disk('public')->exists($documento->ruta_archivo)) {
                return $documento->descripcion ?? '';
            }

            $filePath = Storage::disk('public')->path($documento->ruta_archivo);
            
            // Extraer texto según el tipo de archivo
            return $this->textExtractionService->extractText($filePath, $documento->formato_archivo);
        } catch (Exception $e) {
            Log::warning("No se pudo extraer texto del documento {$documento->id}: " . $e->getMessage());
            return $documento->descripcion ?? '';
        }
    }

    /**
     * Actualizar documento en el índice
     * 
     * @param Documento $documento
     * @return bool
     */
    public function updateDocument(Documento $documento): bool
    {
        return $this->indexDocument($documento);
    }

    /**
     * Eliminar documento del índice
     * 
     * @param int $documentoId
     * @return bool
     */
    public function deleteDocument(int $documentoId): bool
    {
        return $this->elasticsearchService->deleteDocument('documentos', (string) $documentoId);
    }

    /**
     * Indexar un expediente
     * 
     * @param Expediente $expediente
     * @return bool
     */
    public function indexExpediente(Expediente $expediente): bool
    {
        try {
            $expediente->load(['serie', 'subserie', 'usuarioCreador']);

            $body = [
                'id' => $expediente->id,
                'codigo' => $expediente->codigo,
                'nombre' => $expediente->nombre,
                'descripcion' => $expediente->descripcion ?? '',
                'estado' => $expediente->estado,
                'fecha_apertura' => $expediente->fecha_apertura?->toIso8601String(),
                'fecha_cierre' => $expediente->fecha_cierre?->toIso8601String(),
                'serie_documental_id' => $expediente->serie_id,
                'subserie_documental_id' => $expediente->subserie_id,
            ];

            if ($expediente->serie) {
                $body['serie_documental_nombre'] = $expediente->serie->nombre;
            }

            if ($expediente->subserie) {
                $body['subserie_documental_nombre'] = $expediente->subserie->nombre;
            }

            return $this->elasticsearchService->indexDocument(
                'expedientes',
                (string) $expediente->id,
                $body
            );
        } catch (Exception $e) {
            Log::error("Error al indexar expediente {$expediente->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reindexar todos los documentos
     * 
     * @param int $chunkSize
     * @return array ['total' => int, 'indexed' => int, 'errors' => int]
     */
    public function reindexAllDocuments(int $chunkSize = 100): array
    {
        $total = 0;
        $indexed = 0;
        $errors = 0;

        Documento::with([
            'serieDocumental',
            'subserieDocumental',
            'expediente',
            'usuarioCreador'
        ])->chunk($chunkSize, function ($documentos) use (&$total, &$indexed, &$errors) {
            $total += $documentos->count();
            $result = $this->bulkIndexDocuments($documentos);
            $indexed += $result['indexed'];
            $errors += $result['errors'];
        });

        return [
            'total' => $total,
            'indexed' => $indexed,
            'errors' => $errors,
        ];
    }

    /**
     * Reindexar todos los expedientes
     * 
     * @param int $chunkSize
     * @return array
     */
    public function reindexAllExpedientes(int $chunkSize = 100): array
    {
        $total = 0;
        $indexed = 0;
        $errors = 0;

        Expediente::with([
            'serieDocumental',
            'subserieDocumental'
        ])->chunk($chunkSize, function ($expedientes) use (&$total, &$indexed, &$errors) {
            $total += $expedientes->count();
            
            $preparedDocs = [];
            foreach ($expedientes as $expediente) {
                try {
                    $expediente->load(['serie', 'subserie']);
                    $body = [
                        'id' => $expediente->id,
                        'codigo' => $expediente->codigo,
                        'nombre' => $expediente->nombre,
                        'descripcion' => $expediente->descripcion ?? '',
                        'estado' => $expediente->estado,
                        'fecha_apertura' => $expediente->fecha_apertura?->toIso8601String(),
                        'fecha_cierre' => $expediente->fecha_cierre?->toIso8601String(),
                        'serie_documental_id' => $expediente->serie_documental_id,
                        'subserie_documental_id' => $expediente->subserie_documental_id,
                    ];
                    
                    $preparedDocs[] = [
                        'id' => (string) $expediente->id,
                        'body' => $body,
                    ];
                } catch (Exception $e) {
                    Log::warning("Error preparando expediente {$expediente->id}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $result = $this->elasticsearchService->bulkIndex('expedientes', $preparedDocs);
            $indexed += $result['indexed'];
            $errors += $result['errors'];
        });

        return [
            'total' => $total,
            'indexed' => $indexed,
            'errors' => $errors,
        ];
    }
}
