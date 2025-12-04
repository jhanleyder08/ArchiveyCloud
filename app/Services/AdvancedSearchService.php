<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Serie;
use App\Models\User;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Servicio de Búsqueda Avanzada para SGDEA
 * 
 * Implementa requerimientos:
 * REQ-BP-002: Operadores booleanos (AND, OR, NOT)
 * REQ-BP-003: Filtros por fechas, series, usuarios
 * REQ-BP-004: Búsqueda con comodines y aproximada
 * REQ-BP-005: Autocompletado inteligente
 */
class AdvancedSearchService
{
    protected Client $client;
    protected ElasticsearchService $elasticsearchService;
    protected array $config;

    // Operadores booleanos soportados
    const OPERATORS = [
        'AND' => 'must',
        'OR' => 'should', 
        'NOT' => 'must_not',
        '+' => 'must',
        '-' => 'must_not'
    ];

    // Campos de búsqueda con pesos
    const SEARCH_FIELDS = [
        'nombre^3',           // Nombre del documento (peso alto)
        'codigo^2',           // Código (peso medio-alto)
        'descripcion^2',      // Descripción (peso medio-alto)
        'contenido_ocr^1.5', // Texto OCR (peso medio)
        'palabras_clave^2',   // Palabras clave (peso medio-alto)
        'tipo_documental^1.2', // Tipo documental (peso medio-bajo)
        'observaciones^1',    // Observaciones (peso base)
        'expediente.nombre^1.5', // Nombre del expediente
        'expediente.codigo^1.2', // Código del expediente
        'serie.nombre^1.2',   // Nombre de la serie
        'tipologia.nombre^1.2' // Nombre de la tipología
    ];

    public function __construct(Client $client, ElasticsearchService $elasticsearchService)
    {
        $this->client = $client;
        $this->elasticsearchService = $elasticsearchService;
        $this->config = config('elasticsearch', [
            'indices' => ['documentos' => ['name' => 'documentos']],
            'search' => ['default_size' => 20, 'fuzziness' => 'AUTO']
        ]);
    }

    /**
     * REQ-BP-002: Búsqueda avanzada con operadores booleanos
     */
    public function searchAdvanced(array $params, array $options = []): array
    {
        try {
            $query = $this->buildBooleanQuery($params);
            $filters = $this->buildFilters($params);
            $aggregations = $this->buildAggregations($params);
            
            $searchParams = [
                'index' => $this->config['indices']['documentos']['name'],
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => $query['must'] ?? [],
                            'should' => $query['should'] ?? [],
                            'must_not' => $query['must_not'] ?? [],
                            'filter' => $filters
                        ]
                    ],
                    'highlight' => $this->buildHighlighting(),
                    'sort' => $this->buildSort($options['sort'] ?? []),
                    'aggs' => $aggregations,
                    'size' => $options['size'] ?? 20,
                    'from' => $options['from'] ?? 0
                ]
            ];

            // Agregar source filtering si se especifica
            if (!empty($options['fields'])) {
                $searchParams['body']['_source'] = $options['fields'];
            }

            Log::info('Búsqueda avanzada ejecutada', [
                'params' => $searchParams,
                'user_id' => auth()->id()
            ]);

            $response = $this->client->search($searchParams);
            
            return $this->processSearchResults($response->asArray());

        } catch (Exception $e) {
            Log::error('Error en búsqueda avanzada', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'hits' => [],
                'total' => 0,
                'aggregations' => [],
                'took' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * REQ-BP-002: Construir consulta con operadores booleanos
     */
    private function buildBooleanQuery(array $params): array
    {
        $query = ['must' => [], 'should' => [], 'must_not' => []];

        // Procesar query string con operadores
        if (!empty($params['q'])) {
            $parsedQuery = $this->parseQueryString($params['q']);
            $query = array_merge_recursive($query, $parsedQuery);
        }

        // Procesar campos específicos con operadores
        foreach (['must', 'should', 'must_not'] as $operator) {
            if (!empty($params[$operator])) {
                foreach ($params[$operator] as $condition) {
                    $query[$operator][] = $this->buildFieldQuery($condition);
                }
            }
        }

        // Búsqueda por palabras clave específicas
        if (!empty($params['keywords'])) {
            foreach ($params['keywords'] as $keyword) {
                $query['must'][] = [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => ['palabras_clave^3', 'nombre^2'],
                        'type' => 'phrase'
                    ]
                ];
            }
        }

        // Búsqueda en texto OCR
        if (!empty($params['ocr_text'])) {
            $query['must'][] = [
                'match' => [
                    'contenido_ocr' => [
                        'query' => $params['ocr_text'],
                        'fuzziness' => 'AUTO'
                    ]
                ]
            ];
        }

        return $query;
    }

    /**
     * REQ-BP-002: Parser de query string con operadores booleanos
     */
    private function parseQueryString(string $queryString): array
    {
        $query = ['must' => [], 'should' => [], 'must_not' => []];
        
        // Separar por operadores
        $parts = preg_split('/\s+(AND|OR|NOT|\+|\-)\s+/i', $queryString, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $currentOperator = 'must';
        $terms = [];
        
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            $upperPart = strtoupper($part);
            if (isset(self::OPERATORS[$upperPart])) {
                // Es un operador
                $currentOperator = self::OPERATORS[$upperPart];
            } else {
                // Es un término de búsqueda
                $termQuery = $this->buildTermQuery($part);
                
                if ($currentOperator === 'should' && !empty($query['should'])) {
                    // Para OR, agregar al array should
                    $query['should'][] = $termQuery;
                } else {
                    $query[$currentOperator][] = $termQuery;
                }
            }
        }

        // Si no hay términos should pero se usó OR, mover must a should
        if (empty($query['should']) && $currentOperator === 'should') {
            $query['should'] = $query['must'];
            $query['must'] = [];
        }

        return $query;
    }

    /**
     * REQ-BP-004: Construir query de término con wildcards y fuzzy
     */
    private function buildTermQuery(string $term): array
    {
        // Detectar si contiene wildcards (* o ?)
        if (strpos($term, '*') !== false || strpos($term, '?') !== false) {
            return [
                'multi_match' => [
                    'query' => $term,
                    'fields' => self::SEARCH_FIELDS,
                    'type' => 'phrase_prefix'
                ]
            ];
        }

        // Detectar si está entre comillas (búsqueda exacta)
        if (preg_match('/^"(.+)"$/', $term, $matches)) {
            return [
                'multi_match' => [
                    'query' => $matches[1],
                    'fields' => self::SEARCH_FIELDS,
                    'type' => 'phrase'
                ]
            ];
        }

        // Búsqueda fuzzy normal
        return [
            'multi_match' => [
                'query' => $term,
                'fields' => self::SEARCH_FIELDS,
                'fuzziness' => $this->config['search']['fuzziness'],
                'type' => 'best_fields'
            ]
        ];
    }

    /**
     * REQ-BP-003: Construir filtros por fechas, series, usuarios, etc.
     */
    private function buildFilters(array $params): array
    {
        $filters = [];

        // Filtro por rango de fechas
        if (!empty($params['date_range'])) {
            $dateFilter = $this->buildDateRangeFilter($params['date_range']);
            if ($dateFilter) {
                $filters[] = $dateFilter;
            }
        }

        // Filtro por expediente
        if (!empty($params['expediente_id'])) {
            $filters[] = ['term' => ['expediente_id' => $params['expediente_id']]];
        }

        // Filtro por serie
        if (!empty($params['serie_id'])) {
            $filters[] = ['term' => ['expediente.serie_id' => $params['serie_id']]];
        }

        // Filtro por usuario creador
        if (!empty($params['usuario_creador_id'])) {
            $filters[] = ['term' => ['usuario_creador_id' => $params['usuario_creador_id']]];
        }

        // Filtro por estado
        if (!empty($params['estado'])) {
            if (is_array($params['estado'])) {
                $filters[] = ['terms' => ['estado' => $params['estado']]];
            } else {
                $filters[] = ['term' => ['estado' => $params['estado']]];
            }
        }

        // Filtro por formato de archivo
        if (!empty($params['formato'])) {
            if (is_array($params['formato'])) {
                $filters[] = ['terms' => ['formato' => $params['formato']]];
            } else {
                $filters[] = ['term' => ['formato' => $params['formato']]];
            }
        }

        // Filtro por confidencialidad
        if (!empty($params['confidencialidad'])) {
            $filters[] = ['term' => ['confidencialidad' => $params['confidencialidad']]];
        }

        // Filtro por tipología documental
        if (!empty($params['tipologia_id'])) {
            $filters[] = ['term' => ['tipologia_id' => $params['tipologia_id']]];
        }

        // Filtro por tamaño de archivo
        if (!empty($params['file_size_range'])) {
            $filters[] = [
                'range' => [
                    'tamaño' => [
                        'gte' => $params['file_size_range']['min'] ?? 0,
                        'lte' => $params['file_size_range']['max'] ?? PHP_INT_MAX
                    ]
                ]
            ];
        }

        // Filtro por disponibilidad de OCR
        if (isset($params['has_ocr']) && $params['has_ocr']) {
            $filters[] = ['exists' => ['field' => 'contenido_ocr']];
        }

        return $filters;
    }

    /**
     * REQ-BP-003: Construir filtro de rango de fechas
     */
    private function buildDateRangeFilter(array $dateRange): ?array
    {
        $field = $dateRange['field'] ?? 'fecha_creacion';
        $range = [];

        if (!empty($dateRange['from'])) {
            $range['gte'] = Carbon::parse($dateRange['from'])->startOfDay()->toISOString();
        }

        if (!empty($dateRange['to'])) {
            $range['lte'] = Carbon::parse($dateRange['to'])->endOfDay()->toISOString();
        }

        return !empty($range) ? ['range' => [$field => $range]] : null;
    }

    /**
     * Construir query para campos específicos
     */
    private function buildFieldQuery(array $condition): array
    {
        $field = $condition['field'];
        $value = $condition['value'];
        $type = $condition['type'] ?? 'match';

        return match($type) {
            'exact' => ['term' => [$field => $value]],
            'prefix' => ['prefix' => [$field => $value]],
            'wildcard' => ['wildcard' => [$field => $value]],
            'range' => ['range' => [$field => $value]],
            'exists' => ['exists' => ['field' => $field]],
            default => ['match' => [$field => $value]]
        };
    }

    /**
     * REQ-BP-005: Autocompletado inteligente
     */
    public function autocomplete(string $query, array $options = []): array
    {
        try {
            $cacheKey = "autocomplete:" . md5($query . serialize($options));
            
            return Cache::remember($cacheKey, 300, function () use ($query, $options) {
                $field = $options['field'] ?? 'nombre';
                $size = $options['size'] ?? 10;
                
                $searchParams = [
                    'index' => $this->config['indices']['documentos']['name'],
                    'body' => [
                        'suggest' => [
                            'autocomplete' => [
                                'prefix' => $query,
                                'completion' => [
                                    'field' => $field . '.suggest',
                                    'size' => $size,
                                    'skip_duplicates' => true
                                ]
                            ],
                            'phrase_suggest' => [
                                'text' => $query,
                                'phrase' => [
                                    'field' => $field,
                                    'size' => $size,
                                    'max_errors' => 1
                                ]
                            ]
                        ],
                        '_source' => false,
                        'size' => 0
                    ]
                ];

                $response = $this->client->search($searchParams);
                $result = $response->asArray();

                return [
                    'suggestions' => $this->processSuggestions($result['suggest'] ?? []),
                    'query' => $query
                ];
            });

        } catch (Exception $e) {
            Log::error('Error en autocompletado', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            
            return ['suggestions' => [], 'query' => $query];
        }
    }

    /**
     * Procesar sugerencias de autocompletado
     */
    private function processSuggestions(array $suggestions): array
    {
        $processed = [];
        
        if (!empty($suggestions['autocomplete'][0]['options'])) {
            foreach ($suggestions['autocomplete'][0]['options'] as $option) {
                $processed[] = [
                    'text' => $option['text'],
                    'score' => $option['_score'] ?? 0,
                    'type' => 'completion'
                ];
            }
        }
        
        if (!empty($suggestions['phrase_suggest'][0]['options'])) {
            foreach ($suggestions['phrase_suggest'][0]['options'] as $option) {
                $processed[] = [
                    'text' => $option['text'],
                    'score' => $option['score'] ?? 0,
                    'type' => 'phrase'
                ];
            }
        }
        
        // Ordenar por score y eliminar duplicados
        usort($processed, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_values(array_unique(array_column($processed, 'text')));
    }

    /**
     * Construir agregaciones para facetas
     */
    private function buildAggregations(array $params): array
    {
        return [
            'formatos' => [
                'terms' => ['field' => 'formato', 'size' => 20]
            ],
            'estados' => [
                'terms' => ['field' => 'estado', 'size' => 10]
            ],
            'expedientes' => [
                'terms' => ['field' => 'expediente.nombre.keyword', 'size' => 50]
            ],
            'series' => [
                'terms' => ['field' => 'expediente.serie.nombre.keyword', 'size' => 30]
            ],
            'usuarios' => [
                'terms' => ['field' => 'usuario_creador.name.keyword', 'size' => 20]
            ],
            'fecha_creacion' => [
                'date_histogram' => [
                    'field' => 'fecha_creacion',
                    'calendar_interval' => 'month',
                    'format' => 'yyyy-MM'
                ]
            ],
            'tamaños' => [
                'range' => [
                    'field' => 'tamaño',
                    'ranges' => [
                        ['key' => 'small', 'to' => 1024000], // < 1MB
                        ['key' => 'medium', 'from' => 1024000, 'to' => 10485760], // 1-10MB
                        ['key' => 'large', 'from' => 10485760] // > 10MB
                    ]
                ]
            ]
        ];
    }

    /**
     * Construir highlighting
     */
    private function buildHighlighting(): array
    {
        return [
            'fields' => [
                'nombre' => ['fragment_size' => 150, 'number_of_fragments' => 3],
                'descripcion' => ['fragment_size' => 200, 'number_of_fragments' => 2],
                'contenido_ocr' => ['fragment_size' => 250, 'number_of_fragments' => 2],
                'observaciones' => ['fragment_size' => 150, 'number_of_fragments' => 1]
            ],
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>']
        ];
    }

    /**
     * Construir ordenamiento
     */
    private function buildSort(array $sortOptions): array
    {
        if (empty($sortOptions)) {
            return [
                ['_score' => ['order' => 'desc']],
                ['fecha_creacion' => ['order' => 'desc']]
            ];
        }

        $sort = [];
        foreach ($sortOptions as $option) {
            $field = $option['field'] ?? '_score';
            $order = $option['order'] ?? 'desc';
            
            $sort[] = [$field => ['order' => $order]];
        }

        return $sort;
    }

    /**
     * Procesar resultados de búsqueda
     */
    private function processSearchResults(array $response): array
    {
        return [
            'hits' => $response['hits']['hits'] ?? [],
            'total' => $response['hits']['total']['value'] ?? 0,
            'max_score' => $response['hits']['max_score'] ?? 0,
            'aggregations' => $response['aggregations'] ?? [],
            'took' => $response['took'] ?? 0,
            'timed_out' => $response['timed_out'] ?? false
        ];
    }

    /**
     * Búsqueda por similitud (More Like This)
     */
    public function searchSimilar(int $documentId, array $options = []): array
    {
        try {
            $searchParams = [
                'index' => $this->config['indices']['documentos']['name'],
                'body' => [
                    'query' => [
                        'more_like_this' => [
                            'fields' => ['nombre', 'descripcion', 'contenido_ocr'],
                            'like' => [
                                [
                                    '_index' => $this->config['indices']['documentos']['name'],
                                    '_id' => $documentId
                                ]
                            ],
                            'min_term_freq' => 1,
                            'max_query_terms' => 25,
                            'min_doc_freq' => 1
                        ]
                    ],
                    'size' => $options['size'] ?? 10
                ]
            ];

            $response = $this->client->search($searchParams);
            return $this->processSearchResults($response->asArray());

        } catch (Exception $e) {
            Log::error('Error en búsqueda por similitud', [
                'error' => $e->getMessage(),
                'document_id' => $documentId
            ]);
            
            return ['hits' => [], 'total' => 0];
        }
    }

    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats(): array
    {
        return Cache::remember('search_stats', 3600, function () {
            try {
                $stats = $this->client->indices()->stats([
                    'index' => $this->config['indices']['documentos']['name']
                ]);

                return [
                    'total_documents' => $stats['indices'][$this->config['indices']['documentos']['name']]['total']['docs']['count'] ?? 0,
                    'index_size' => $stats['indices'][$this->config['indices']['documentos']['name']]['total']['store']['size_in_bytes'] ?? 0,
                    'search_queries_total' => $stats['indices'][$this->config['indices']['documentos']['name']]['total']['search']['query_total'] ?? 0
                ];
            } catch (Exception $e) {
                return ['total_documents' => 0, 'index_size' => 0, 'search_queries_total' => 0];
            }
        });
    }
}
