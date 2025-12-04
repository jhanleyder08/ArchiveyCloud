<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio de Optimización de Elasticsearch para SGDEA
 * 
 * Implementa optimizaciones:
 * - Mejores mapeos de índices
 * - Configuraciones de análisis de texto
 * - Índices optimizados para búsqueda
 * - Templates para documentos
 */
class ElasticsearchOptimizationService
{
    protected Client $client;
    protected array $config;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->config = config('elasticsearch');
    }

    /**
     * Crear índice optimizado para documentos SGDEA
     */
    public function createOptimizedDocumentIndex(): bool
    {
        try {
            $indexName = $this->config['indices']['documentos']['name'] ?? 'documentos_sgdea';
            
            // Eliminar índice existente si existe
            if ($this->client->indices()->exists(['index' => $indexName])->asBool()) {
                $this->client->indices()->delete(['index' => $indexName]);
                Log::info("Índice existente eliminado: {$indexName}");
            }

            // Configuración optimizada del índice
            $indexConfig = [
                'index' => $indexName,
                'body' => [
                    'settings' => $this->getOptimizedSettings(),
                    'mappings' => $this->getOptimizedMappings()
                ]
            ];

            $response = $this->client->indices()->create($indexConfig);
            
            Log::info("Índice optimizado creado exitosamente: {$indexName}");
            return true;

        } catch (Exception $e) {
            Log::error('Error creando índice optimizado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Configuraciones optimizadas del índice
     */
    private function getOptimizedSettings(): array
    {
        return [
            'number_of_shards' => 2,
            'number_of_replicas' => 1,
            'refresh_interval' => '5s',
            'max_result_window' => 50000,
            
            // Configuración de análisis avanzado
            'analysis' => [
                'analyzer' => [
                    // Analizador para texto español optimizado
                    'spanish_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'spanish_stemmer',
                            'spanish_stop',
                            'asciifolding',
                            'elision'
                        ]
                    ],
                    
                    // Analizador para códigos y números
                    'code_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                        'filter' => ['lowercase', 'asciifolding']
                    ],
                    
                    // Analizador para autocompletado
                    'autocomplete_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'autocomplete_tokenizer',
                        'filter' => ['lowercase', 'asciifolding']
                    ],
                    
                    // Analizador de búsqueda para autocompletado
                    'autocomplete_search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding']
                    ]
                ],
                
                'tokenizer' => [
                    'autocomplete_tokenizer' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 20,
                        'token_chars' => ['letter', 'digit']
                    ]
                ],
                
                'filter' => [
                    'spanish_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'spanish'
                    ],
                    'spanish_stop' => [
                        'type' => 'stop',
                        'stopwords' => '_spanish_'
                    ],
                    'elision' => [
                        'type' => 'elision',
                        'articles' => ['el', 'la', 'los', 'las', 'del', 'de', 'al', 'a']
                    ]
                ]
            ]
        ];
    }

    /**
     * Mapeos optimizados para documentos SGDEA
     */
    private function getOptimizedMappings(): array
    {
        return [
            'dynamic' => 'strict',
            'properties' => [
                // Identificadores únicos
                'id' => ['type' => 'long'],
                'codigo' => [
                    'type' => 'text',
                    'analyzer' => 'code_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword'],
                        'suggest' => [
                            'type' => 'completion',
                            'analyzer' => 'autocomplete_analyzer'
                        ]
                    ]
                ],
                
                // Información principal del documento
                'nombre' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'search_analyzer' => 'spanish_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword'],
                        'suggest' => [
                            'type' => 'completion',
                            'analyzer' => 'autocomplete_analyzer',
                            'search_analyzer' => 'autocomplete_search_analyzer'
                        ],
                        'raw' => [
                            'type' => 'text',
                            'analyzer' => 'keyword'
                        ]
                    ],
                    'boost' => 3.0
                ],
                
                'descripcion' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'boost' => 2.0
                ],
                
                // Contenido OCR indexado
                'contenido_ocr' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'boost' => 1.5,
                    'index_options' => 'positions'
                ],
                
                // Metadatos documentales
                'tipo_documental' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword'],
                        'suggest' => ['type' => 'completion']
                    ]
                ],
                
                'palabras_clave' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ],
                    'boost' => 2.5
                ],
                
                // Estados y clasificaciones
                'estado' => ['type' => 'keyword'],
                'confidencialidad' => ['type' => 'keyword'],
                'tipo_soporte' => ['type' => 'keyword'],
                'formato' => ['type' => 'keyword'],
                
                // Información de archivo
                'tamaño' => ['type' => 'long'],
                'numero_folios' => ['type' => 'integer'],
                'hash_sha256' => ['type' => 'keyword', 'index' => false],
                'ruta_archivo' => ['type' => 'keyword', 'index' => false],
                'ruta_miniatura' => ['type' => 'keyword', 'index' => false],
                
                // Fechas importantes
                'fecha_creacion' => ['type' => 'date'],
                'fecha_modificacion' => ['type' => 'date'],
                'fecha_digitalizacion' => ['type' => 'date'],
                'fecha_procesamiento' => ['type' => 'date'],
                
                // Relaciones
                'expediente_id' => ['type' => 'long'],
                'tipologia_id' => ['type' => 'long'],
                'usuario_creador_id' => ['type' => 'long'],
                'usuario_modificador_id' => ['type' => 'long'],
                
                // Información del expediente (nested para mejor búsqueda)
                'expediente' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'codigo' => [
                            'type' => 'text',
                            'analyzer' => 'code_analyzer',
                            'fields' => ['keyword' => ['type' => 'keyword']]
                        ],
                        'nombre' => [
                            'type' => 'text',
                            'analyzer' => 'spanish_analyzer',
                            'fields' => ['keyword' => ['type' => 'keyword']]
                        ],
                        'serie_id' => ['type' => 'long'],
                        'subserie_id' => ['type' => 'long'],
                        'serie' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'long'],
                                'codigo' => ['type' => 'keyword'],
                                'nombre' => [
                                    'type' => 'text',
                                    'analyzer' => 'spanish_analyzer',
                                    'fields' => ['keyword' => ['type' => 'keyword']]
                                ]
                            ]
                        ]
                    ]
                ],
                
                // Información de tipología
                'tipologia' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'nombre' => [
                            'type' => 'text',
                            'analyzer' => 'spanish_analyzer',
                            'fields' => ['keyword' => ['type' => 'keyword']]
                        ],
                        'categoria' => ['type' => 'keyword']
                    ]
                ],
                
                // Información del usuario creador
                'usuario_creador' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'long'],
                        'name' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword']]
                        ],
                        'email' => ['type' => 'keyword']
                    ]
                ],
                
                // Observaciones adicionales
                'observaciones' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer'
                ],
                
                // Ubicación física
                'ubicacion_fisica' => [
                    'type' => 'text',
                    'analyzer' => 'spanish_analyzer',
                    'fields' => ['keyword' => ['type' => 'keyword']]
                ],
                
                // Metadatos adicionales (JSON)
                'metadatos_archivo' => ['type' => 'object', 'enabled' => false],
                'metadatos_documento' => ['type' => 'object', 'enabled' => false],
                
                // Estado de procesamiento
                'estado_procesamiento' => ['type' => 'keyword'],
                
                // Timestamp de indexación
                'indexed_at' => ['type' => 'date'],
                
                // Versioning
                'version' => ['type' => 'keyword'],
                'es_version_principal' => ['type' => 'boolean'],
                'documento_padre_id' => ['type' => 'long']
            ]
        ];
    }

    /**
     * Crear template de índice para futuros documentos
     */
    public function createDocumentTemplate(): bool
    {
        try {
            $templateName = 'sgdea_documentos_template';
            
            $template = [
                'name' => $templateName,
                'body' => [
                    'index_patterns' => ['documentos_*', 'sgdea_*'],
                    'settings' => $this->getOptimizedSettings(),
                    'mappings' => $this->getOptimizedMappings(),
                    'priority' => 1
                ]
            ];

            $this->client->indices()->putIndexTemplate($template);
            
            Log::info("Template de índice creado: {$templateName}");
            return true;

        } catch (Exception $e) {
            Log::error('Error creando template de índice', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Optimizar índice existente
     */
    public function optimizeExistingIndex(string $indexName): bool
    {
        try {
            // Force merge para optimizar segmentos
            $this->client->indices()->forceMerge([
                'index' => $indexName,
                'max_num_segments' => 1
            ]);
            
            // Refresh del índice
            $this->client->indices()->refresh(['index' => $indexName]);
            
            Log::info("Índice optimizado: {$indexName}");
            return true;

        } catch (Exception $e) {
            Log::error('Error optimizando índice', [
                'index' => $indexName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Configurar aliases para facilitar operaciones
     */
    public function setupIndexAliases(): bool
    {
        try {
            $indexName = $this->config['indices']['documentos']['name'] ?? 'documentos_sgdea';
            
            $aliases = [
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $indexName,
                                'alias' => 'documentos_activos',
                                'filter' => [
                                    'terms' => [
                                        'estado' => ['activo', 'aprobado']
                                    ]
                                ]
                            ]
                        ],
                        [
                            'add' => [
                                'index' => $indexName,
                                'alias' => 'documentos_publicos',
                                'filter' => [
                                    'terms' => [
                                        'confidencialidad' => ['publica', 'interna']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $this->client->indices()->updateAliases($aliases);
            
            Log::info('Aliases de índice configurados correctamente');
            return true;

        } catch (Exception $e) {
            Log::error('Error configurando aliases', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener estadísticas detalladas del índice
     */
    public function getIndexStats(string $indexName): array
    {
        try {
            $stats = $this->client->indices()->stats(['index' => $indexName]);
            $health = $this->client->cluster()->health(['index' => $indexName]);
            
            return [
                'health' => $health['status'] ?? 'unknown',
                'documents' => $stats['indices'][$indexName]['total']['docs']['count'] ?? 0,
                'size' => $stats['indices'][$indexName]['total']['store']['size_in_bytes'] ?? 0,
                'shards' => $health['active_shards'] ?? 0,
                'segments' => $stats['indices'][$indexName]['total']['segments']['count'] ?? 0,
                'search_queries' => $stats['indices'][$indexName]['total']['search']['query_total'] ?? 0,
                'indexing_operations' => $stats['indices'][$indexName]['total']['indexing']['index_total'] ?? 0
            ];

        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas del índice', [
                'index' => $indexName,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Ejecutar optimización completa del sistema de búsqueda
     */
    public function runFullOptimization(): array
    {
        $results = [
            'index_created' => false,
            'template_created' => false,
            'aliases_configured' => false,
            'optimization_completed' => false,
            'errors' => []
        ];

        try {
            // 1. Crear índice optimizado
            $results['index_created'] = $this->createOptimizedDocumentIndex();
            
            // 2. Crear template
            $results['template_created'] = $this->createDocumentTemplate();
            
            // 3. Configurar aliases
            $results['aliases_configured'] = $this->setupIndexAliases();
            
            // 4. Optimizar índice
            $indexName = $this->config['indices']['documentos']['name'] ?? 'documentos_sgdea';
            $results['optimization_completed'] = $this->optimizeExistingIndex($indexName);
            
            Log::info('Optimización completa de Elasticsearch completada', $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Error en optimización completa', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }
}
