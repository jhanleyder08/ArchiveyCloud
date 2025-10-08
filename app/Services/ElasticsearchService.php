<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class ElasticsearchService
{
    protected Client $client;
    protected array $config;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->config = config('elasticsearch');
    }

    /**
     * Crear un índice si no existe
     * 
     * @param string $indexType Tipo de índice (documentos, expedientes)
     * @return bool
     */
    public function createIndexIfNotExists(string $indexType): bool
    {
        try {
            $indexConfig = $this->config['indices'][$indexType] ?? null;
            
            if (!$indexConfig) {
                throw new Exception("Índice tipo '{$indexType}' no configurado");
            }

            $indexName = $indexConfig['name'];

            // Verificar si el índice existe
            if ($this->client->indices()->exists(['index' => $indexName])) {
                return true;
            }

            // Crear el índice
            $params = [
                'index' => $indexName,
                'body' => [
                    'settings' => $indexConfig['settings'],
                    'mappings' => $indexConfig['mappings'],
                ]
            ];

            $this->client->indices()->create($params);
            
            Log::info("Índice de Elasticsearch creado: {$indexName}");
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al crear índice de Elasticsearch: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un índice
     * 
     * @param string $indexType
     * @return bool
     */
    public function deleteIndex(string $indexType): bool
    {
        try {
            $indexName = $this->getIndexName($indexType);
            
            if ($this->client->indices()->exists(['index' => $indexName])) {
                $this->client->indices()->delete(['index' => $indexName]);
                Log::info("Índice de Elasticsearch eliminado: {$indexName}");
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al eliminar índice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Indexar un documento
     * 
     * @param string $indexType
     * @param string $id
     * @param array $body
     * @return bool
     */
    public function indexDocument(string $indexType, string $id, array $body): bool
    {
        try {
            $params = [
                'index' => $this->getIndexName($indexType),
                'id' => $id,
                'body' => $body,
            ];

            if ($this->config['indexing']['refresh_after_index']) {
                $params['refresh'] = true;
            }

            $this->client->index($params);
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al indexar documento {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Indexar documentos en lote (bulk)
     * 
     * @param string $indexType
     * @param array $documents Array de ['id' => ..., 'body' => ...]
     * @return array ['indexed' => int, 'errors' => int]
     */
    public function bulkIndex(string $indexType, array $documents): array
    {
        $indexed = 0;
        $errors = 0;
        $indexName = $this->getIndexName($indexType);
        
        try {
            $chunks = array_chunk($documents, $this->config['indexing']['bulk_size']);
            
            foreach ($chunks as $chunk) {
                $params = ['body' => []];
                
                foreach ($chunk as $doc) {
                    $params['body'][] = [
                        'index' => [
                            '_index' => $indexName,
                            '_id' => $doc['id'],
                        ]
                    ];
                    $params['body'][] = $doc['body'];
                }
                
                $response = $this->client->bulk($params);
                
                if ($response['errors']) {
                    foreach ($response['items'] as $item) {
                        if (isset($item['index']['error'])) {
                            $errors++;
                            Log::warning("Error en bulk index: " . json_encode($item['index']['error']));
                        } else {
                            $indexed++;
                        }
                    }
                } else {
                    $indexed += count($chunk);
                }
            }
        } catch (Exception $e) {
            Log::error("Error en bulk indexing: " . $e->getMessage());
        }
        
        return ['indexed' => $indexed, 'errors' => $errors];
    }

    /**
     * Actualizar un documento
     * 
     * @param string $indexType
     * @param string $id
     * @param array $body
     * @return bool
     */
    public function updateDocument(string $indexType, string $id, array $body): bool
    {
        try {
            $params = [
                'index' => $this->getIndexName($indexType),
                'id' => $id,
                'body' => [
                    'doc' => $body,
                    'doc_as_upsert' => true,
                ]
            ];

            $this->client->update($params);
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al actualizar documento {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un documento del índice
     * 
     * @param string $indexType
     * @param string $id
     * @return bool
     */
    public function deleteDocument(string $indexType, string $id): bool
    {
        try {
            $params = [
                'index' => $this->getIndexName($indexType),
                'id' => $id,
            ];

            $this->client->delete($params);
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al eliminar documento {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener nombre del índice
     * 
     * @param string $indexType
     * @return string
     */
    protected function getIndexName(string $indexType): string
    {
        return $this->config['indices'][$indexType]['name'] ?? 
               $this->config['index_prefix'] . '_' . $indexType;
    }

    /**
     * Verificar si Elasticsearch está disponible
     * 
     * @return bool
     */
    public function ping(): bool
    {
        try {
            return $this->client->ping();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener estadísticas del índice
     * 
     * @param string $indexType
     * @return array|null
     */
    public function getIndexStats(string $indexType): ?array
    {
        try {
            $indexName = $this->getIndexName($indexType);
            $response = $this->client->indices()->stats(['index' => $indexName]);
            
            return $response['indices'][$indexName] ?? null;
        } catch (Exception $e) {
            Log::error("Error al obtener estadísticas del índice: " . $e->getMessage());
            return null;
        }
    }
}
