<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio de búsqueda avanzada con Elasticsearch
 * Implementa REQ-BP-001, REQ-BP-002, REQ-BP-011, REQ-BP-013
 */
class SearchService
{
    protected Client $client;
    protected array $config;
    protected ElasticsearchService $elasticsearchService;

    public function __construct(Client $client, ElasticsearchService $elasticsearchService)
    {
        $this->client = $client;
        $this->elasticsearchService = $elasticsearchService;
        $this->config = config('elasticsearch');
    }

    /**
     * Búsqueda simple (REQ-BP-001)
     * 
     * @param string $query
     * @param string $indexType
     * @param array $options
     * @return array
     */
    public function searchSimple(string $query, string $indexType = 'documentos', array $options = []): array
    {
        try {
            $indexName = $this->config['indices'][$indexType]['name'];
            $size = $options['size'] ?? $this->config['search']['default_size'];
            $from = $options['from'] ?? 0;
            
            $params = [
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => $this->getSearchFieldsWithBoosts(),
                            'fuzziness' => $this->config['search']['fuzziness'],
                            'type' => 'best_fields',
                            'operator' => 'or',
                        ]
                    ],
                    'size' => min($size, $this->config['search']['max_size']),
                    'from' => $from,
                    'timeout' => $this->config['search']['timeout'] . 's',
                ],
            ];

            // Agregar highlighting si está habilitado
            if ($this->config['search']['highlight']) {
                $params['body']['highlight'] = $this->config['search']['highlight_settings'];
            }

            // Agregar sorting si se especifica
            if (!empty($options['sort'])) {
                $params['body']['sort'] = $this->buildSortClause($options['sort']);
            }

            $response = $this->client->search($params);
            
            return $this->formatSearchResponse($response);
        } catch (Exception $e) {
            Log::error("Error en búsqueda simple: " . $e->getMessage());
            return $this->emptyResponse();
        }
    }

    /**
     * Búsqueda avanzada con operadores booleanos (REQ-BP-002)
     * 
     * Soporta:
     * - AND (y): Intersección de resultados
     * - OR (o): Unión de resultados  
     * - NOT (no): Exclusión de términos
     * - Wildcards: *, ?, etc.
     * - Rangos de fechas
     * - Búsqueda por campos específicos
     * 
     * @param array $searchParams
     * @param string $indexType
     * @param array $options
     * @return array
     */
    public function searchAdvanced(array $searchParams, string $indexType = 'documentos', array $options = []): array
    {
        try {
            $indexName = $this->config['indices'][$indexType]['name'];
            $size = $options['size'] ?? $this->config['search']['default_size'];
            $from = $options['from'] ?? 0;

            // Construir la consulta avanzada
            $query = $this->buildAdvancedQuery($searchParams);

            $params = [
                'index' => $indexName,
                'body' => [
                    'query' => $query,
                    'size' => min($size, $this->config['search']['max_size']),
                    'from' => $from,
                    'timeout' => $this->config['search']['timeout'] . 's',
                ],
            ];

            // Agregar facets/aggregations si se solicitan
            if (!empty($options['aggregations'])) {
                $params['body']['aggs'] = $this->buildAggregations($options['aggregations']);
            }

            // Highlighting
            if ($this->config['search']['highlight']) {
                $params['body']['highlight'] = $this->config['search']['highlight_settings'];
            }

            // Sorting
            if (!empty($options['sort'])) {
                $params['body']['sort'] = $this->buildSortClause($options['sort']);
            }

            $response = $this->client->search($params);
            
            return $this->formatSearchResponse($response, true);
        } catch (Exception $e) {
            Log::error("Error en búsqueda avanzada: " . $e->getMessage());
            return $this->emptyResponse();
        }
    }

    /**
     * Construir consulta avanzada con operadores booleanos
     * 
     * @param array $searchParams
     * @return array
     */
    protected function buildAdvancedQuery(array $searchParams): array
    {
        $boolQuery = [
            'bool' => [
                'must' => [],
                'should' => [],
                'must_not' => [],
                'filter' => [],
            ]
        ];

        // Procesar términos de búsqueda con operador AND
        if (!empty($searchParams['must'])) {
            foreach ($searchParams['must'] as $term) {
                $boolQuery['bool']['must'][] = $this->buildTermQuery($term);
            }
        }

        // Procesar términos con operador OR
        if (!empty($searchParams['should'])) {
            foreach ($searchParams['should'] as $term) {
                $boolQuery['bool']['should'][] = $this->buildTermQuery($term);
            }
            $boolQuery['bool']['minimum_should_match'] = 1;
        }

        // Procesar términos con operador NOT
        if (!empty($searchParams['must_not'])) {
            foreach ($searchParams['must_not'] as $term) {
                $boolQuery['bool']['must_not'][] = $this->buildTermQuery($term);
            }
        }

        // Búsqueda por campos específicos
        if (!empty($searchParams['fields'])) {
            foreach ($searchParams['fields'] as $field => $value) {
                if ($this->isWildcardSearch($value)) {
                    // Búsqueda con comodines
                    $boolQuery['bool']['must'][] = [
                        'wildcard' => [
                            $field => ['value' => $value]
                        ]
                    ];
                } elseif ($this->isExactMatch($value)) {
                    // Búsqueda exacta (operador =)
                    $cleanValue = trim($value, '=');
                    $boolQuery['bool']['filter'][] = [
                        'term' => [$field . '.keyword' => $cleanValue]
                    ];
                } else {
                    // Búsqueda normal en el campo
                    $boolQuery['bool']['must'][] = [
                        'match' => [$field => $value]
                    ];
                }
            }
        }

        // Filtros por rangos de fechas (REQ-BP-002)
        if (!empty($searchParams['date_range'])) {
            foreach ($searchParams['date_range'] as $field => $range) {
                $rangeQuery = ['range' => [$field => []]];
                
                if (!empty($range['from'])) {
                    $rangeQuery['range'][$field]['gte'] = $range['from'];
                }
                if (!empty($range['to'])) {
                    $rangeQuery['range'][$field]['lte'] = $range['to'];
                }
                
                $boolQuery['bool']['filter'][] = $rangeQuery;
            }
        }

        // Filtros por categoría (serie, subserie, expediente)
        if (!empty($searchParams['filters'])) {
            foreach ($searchParams['filters'] as $field => $value) {
                if (is_array($value)) {
                    // Múltiples valores (OR)
                    $boolQuery['bool']['filter'][] = [
                        'terms' => [$field => $value]
                    ];
                } else {
                    // Valor único
                    $boolQuery['bool']['filter'][] = [
                        'term' => [$field => $value]
                    ];
                }
            }
        }

        // Búsqueda por palabras clave
        if (!empty($searchParams['keywords'])) {
            $keywordTerms = is_array($searchParams['keywords']) 
                ? $searchParams['keywords'] 
                : [$searchParams['keywords']];
            
            $boolQuery['bool']['should'][] = [
                'terms' => [
                    'palabras_clave' => $keywordTerms,
                    'boost' => 2.0
                ]
            ];
        }

        return $boolQuery;
    }

    /**
     * Construir consulta para un término individual
     * 
     * @param string $term
     * @return array
     */
    protected function buildTermQuery(string $term): array
    {
        // Soportar wildcards (*, ?)
        if ($this->isWildcardSearch($term)) {
            return [
                'query_string' => [
                    'query' => $term,
                    'fields' => array_keys($this->config['search']['field_boosts']),
                    'default_operator' => 'AND',
                ]
            ];
        }

        // Búsqueda con fuzziness para coincidencias aproximadas
        return [
            'multi_match' => [
                'query' => $term,
                'fields' => $this->getSearchFieldsWithBoosts(),
                'fuzziness' => $this->config['search']['fuzziness'],
                'prefix_length' => 2,
            ]
        ];
    }

    /**
     * Verificar si es búsqueda con wildcard
     * 
     * @param string $value
     * @return bool
     */
    protected function isWildcardSearch(string $value): bool
    {
        return strpbrk($value, '*?') !== false;
    }

    /**
     * Verificar si es búsqueda exacta (=)
     * 
     * @param string $value
     * @return bool
     */
    protected function isExactMatch(string $value): bool
    {
        return strpos($value, '=') === 0;
    }

    /**
     * Obtener campos de búsqueda con sus boosts
     * 
     * @return array
     */
    protected function getSearchFieldsWithBoosts(): array
    {
        $fields = [];
        foreach ($this->config['search']['field_boosts'] as $field => $boost) {
            $fields[] = $field . '^' . $boost;
        }
        return $fields;
    }

    /**
     * Construir cláusula de ordenamiento (REQ-BP-013)
     * 
     * @param array $sortOptions
     * @return array
     */
    protected function buildSortClause(array $sortOptions): array
    {
        $sort = [];
        
        foreach ($sortOptions as $field => $direction) {
            $direction = strtolower($direction);
            
            // Manejar ordenamiento por relevancia (score)
            if ($field === '_score' || $field === 'relevancia') {
                $sort[] = ['_score' => ['order' => $direction]];
            } 
            // Ordenamiento por campos keyword
            elseif (in_array($field, ['codigo', 'estado', 'tipo_documento'])) {
                $sort[] = [$field => ['order' => $direction]];
            }
            // Ordenamiento por campos de texto (usar .keyword)
            elseif (in_array($field, ['nombre', 'usuario_creador'])) {
                $sort[] = [$field . '.keyword' => ['order' => $direction]];
            }
            // Ordenamiento por fechas y números
            else {
                $sort[] = [$field => ['order' => $direction]];
            }
        }
        
        // Agregar score como tiebreaker si no está incluido
        if (!isset($sortOptions['_score'])) {
            $sort[] = ['_score' => ['order' => 'desc']];
        }
        
        return $sort;
    }

    /**
     * Construir aggregations para búsqueda facetada
     * 
     * @param array $aggregations
     * @return array
     */
    protected function buildAggregations(array $aggregations): array
    {
        $aggs = [];
        
        foreach ($aggregations as $name => $field) {
            $aggs[$name] = [
                'terms' => [
                    'field' => $field,
                    'size' => 50,
                ]
            ];
        }
        
        return $aggs;
    }

    /**
     * Formatear respuesta de búsqueda
     * 
     * @param array $response
     * @param bool $includeAggregations
     * @return array
     */
    protected function formatSearchResponse(array $response, bool $includeAggregations = false): array
    {
        $hits = $response['hits'] ?? [];
        $results = [];
        
        foreach ($hits['hits'] ?? [] as $hit) {
            $result = [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'source' => $hit['_source'],
            ];
            
            // Agregar highlights si existen
            if (isset($hit['highlight'])) {
                $result['highlights'] = $hit['highlight'];
            }
            
            $results[] = $result;
        }
        
        $formatted = [
            'total' => $hits['total']['value'] ?? 0,
            'max_score' => $hits['max_score'] ?? 0,
            'results' => $results,
            'took' => $response['took'] ?? 0,
        ];
        
        // Incluir aggregations si se solicitaron
        if ($includeAggregations && isset($response['aggregations'])) {
            $formatted['aggregations'] = $response['aggregations'];
        }
        
        return $formatted;
    }

    /**
     * Respuesta vacía
     * 
     * @return array
     */
    protected function emptyResponse(): array
    {
        return [
            'total' => 0,
            'max_score' => 0,
            'results' => [],
            'took' => 0,
        ];
    }

    /**
     * Autocompletado (REQ-BP-002)
     * 
     * @param string $query
     * @param string $field
     * @param string $indexType
     * @return array
     */
    public function autocomplete(string $query, string $field = 'nombre', string $indexType = 'documentos'): array
    {
        try {
            $indexName = $this->config['indices'][$indexType]['name'];
            
            $params = [
                'index' => $indexName,
                'body' => [
                    'suggest' => [
                        'suggestions' => [
                            'prefix' => $query,
                            'completion' => [
                                'field' => $field . '.suggest',
                                'size' => 10,
                                'skip_duplicates' => true,
                            ]
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->search($params);
            
            $suggestions = [];
            foreach ($response['suggest']['suggestions'][0]['options'] ?? [] as $option) {
                $suggestions[] = $option['text'];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            Log::error("Error en autocompletado: " . $e->getMessage());
            return [];
        }
    }
}
