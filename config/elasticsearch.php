<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Elasticsearch para búsqueda
    | de texto completo según REQ-BP-001
    |
    */

    // Host y conexión
    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200'),
    ],

    // Usuario y contraseña (si se requiere autenticación)
    'username' => env('ELASTICSEARCH_USERNAME', null),
    'password' => env('ELASTICSEARCH_PASSWORD', null),

    // Usar HTTPS
    'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),

    // Nombre del índice principal
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'sgdea'),

    // Configuración de índices
    'indices' => [
        'documentos' => [
            'name' => env('ELASTICSEARCH_INDEX_PREFIX', 'sgdea') . '_documentos',
            'settings' => [
                'number_of_shards' => 3,
                'number_of_replicas' => 1,
                'analysis' => [
                    'analyzer' => [
                        'spanish_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'spanish_stop',
                                'spanish_stemmer',
                                'asciifolding'
                            ]
                        ]
                    ],
                    'filter' => [
                        'spanish_stop' => [
                            'type' => 'stop',
                            'stopwords' => '_spanish_'
                        ],
                        'spanish_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'spanish'
                        ]
                    ]
                ]
            ],
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'codigo' => ['type' => 'keyword'],
                    'nombre' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword'],
                            'suggest' => ['type' => 'completion']
                        ]
                    ],
                    'descripcion' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer'
                    ],
                    'contenido' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer',
                        'term_vector' => 'with_positions_offsets'
                    ],
                    'tipo_documento' => ['type' => 'keyword'],
                    'formato_archivo' => ['type' => 'keyword'],
                    'tamanio' => ['type' => 'long'],
                    'fecha_creacion' => ['type' => 'date'],
                    'fecha_modificacion' => ['type' => 'date'],
                    'usuario_creador' => ['type' => 'keyword'],
                    'usuario_modificador' => ['type' => 'keyword'],
                    'serie_documental_id' => ['type' => 'keyword'],
                    'serie_documental_nombre' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'subserie_documental_id' => ['type' => 'keyword'],
                    'subserie_documental_nombre' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer'
                    ],
                    'expediente_id' => ['type' => 'keyword'],
                    'expediente_nombre' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer'
                    ],
                    'palabras_clave' => [
                        'type' => 'keyword',
                        'fields' => [
                            'text' => ['type' => 'text', 'analyzer' => 'spanish_analyzer']
                        ]
                    ],
                    'metadatos' => ['type' => 'object', 'enabled' => true],
                    'estado' => ['type' => 'keyword'],
                    'nivel_seguridad' => ['type' => 'keyword'],
                    'firmado' => ['type' => 'boolean'],
                    'version' => ['type' => 'integer'],
                ]
            ]
        ],
        'expedientes' => [
            'name' => env('ELASTICSEARCH_INDEX_PREFIX', 'sgdea') . '_expedientes',
            'settings' => [
                'number_of_shards' => 2,
                'number_of_replicas' => 1,
            ],
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'codigo' => ['type' => 'keyword'],
                    'nombre' => [
                        'type' => 'text',
                        'analyzer' => 'spanish_analyzer',
                        'fields' => ['keyword' => ['type' => 'keyword']]
                    ],
                    'descripcion' => ['type' => 'text', 'analyzer' => 'spanish_analyzer'],
                    'estado' => ['type' => 'keyword'],
                    'fecha_apertura' => ['type' => 'date'],
                    'fecha_cierre' => ['type' => 'date'],
                    'serie_documental_id' => ['type' => 'keyword'],
                    'subserie_documental_id' => ['type' => 'keyword'],
                ]
            ]
        ],
    ],

    // Configuración de búsqueda
    'search' => [
        // Tamaño de página por defecto
        'default_size' => 20,
        'max_size' => 1000,
        
        // Timeout de búsqueda (segundos)
        'timeout' => env('ELASTICSEARCH_SEARCH_TIMEOUT', 5),
        
        // Habilitar highlighting
        'highlight' => true,
        
        // Configuración de highlighting
        'highlight_settings' => [
            'pre_tags' => ['<mark class="highlight">'],
            'post_tags' => ['</mark>'],
            'fields' => [
                'nombre' => ['number_of_fragments' => 0],
                'descripcion' => ['fragment_size' => 150, 'number_of_fragments' => 3],
                'contenido' => ['fragment_size' => 150, 'number_of_fragments' => 5],
            ]
        ],
        
        // Fuzziness para búsquedas aproximadas (REQ-BP-002)
        'fuzziness' => 'AUTO',
        
        // Boost por campos (peso de relevancia)
        'field_boosts' => [
            'codigo' => 3.0,
            'nombre' => 2.0,
            'descripcion' => 1.5,
            'contenido' => 1.0,
            'palabras_clave' => 2.5,
        ],
    ],

    // Configuración de indexación
    'indexing' => [
        // Tamaño del lote para indexación masiva
        'bulk_size' => env('ELASTICSEARCH_BULK_SIZE', 500),
        
        // Refrescar índice después de indexar
        'refresh_after_index' => env('ELASTICSEARCH_REFRESH', false),
        
        // Número máximo de reintentos
        'max_retries' => 3,
        
        // Timeout de indexación
        'timeout' => 30,
    ],

    // Logging
    'logging' => [
        'enabled' => env('ELASTICSEARCH_LOGGING', false),
        'level' => env('ELASTICSEARCH_LOG_LEVEL', 'warning'),
    ],

    // Queue para indexación asíncrona
    'queue' => [
        'enabled' => env('ELASTICSEARCH_QUEUE_ENABLED', true),
        'connection' => env('ELASTICSEARCH_QUEUE_CONNECTION', 'database'),
        'queue' => env('ELASTICSEARCH_QUEUE_NAME', 'elasticsearch'),
    ],
];
