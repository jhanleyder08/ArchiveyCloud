<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Optimización para ArchiveyCloud
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para optimizar el rendimiento del sistema
    | de gestión documental en entornos de producción.
    |
    */

    'cache' => [
        // Tiempo de vida de caché por defecto (en segundos)
        'default_ttl' => env('CACHE_TTL', 3600),
        
        // Caché específico para diferentes tipos de datos
        'ttl' => [
            'user_permissions' => 1800,        // 30 minutos
            'document_metadata' => 3600,       // 1 hora
            'trd_data' => 7200,               // 2 horas
            'workflow_states' => 900,          // 15 minutos
            'statistics' => 1800,              // 30 minutos
            'notifications' => 300,            // 5 minutos
            'external_services' => 600,        // 10 minutos
        ],
        
        // Prefijos para diferentes tipos de caché
        'prefixes' => [
            'user' => 'user:',
            'document' => 'doc:',
            'expediente' => 'exp:',
            'trd' => 'trd:',
            'workflow' => 'wf:',
            'notification' => 'notif:',
            'report' => 'report:',
            'stats' => 'stats:',
        ],
    ],

    'database' => [
        // Configuración de pool de conexiones
        'pool_size' => env('DB_POOL_SIZE', 10),
        'timeout' => env('DB_TIMEOUT', 60),
        
        // Optimizaciones de consultas
        'chunk_size' => 1000,              // Tamaño de chunks para consultas masivas
        'batch_size' => 500,               // Tamaño de lotes para inserciones
        
        // Índices recomendados (para documentación)
        'recommended_indexes' => [
            'expedientes' => ['estado', 'fecha_creacion', 'usuario_responsable_id'],
            'documentos' => ['expediente_id', 'tipo_documento', 'fecha_creacion'],
            'notificaciones' => ['usuario_id', 'leida', 'archivada', 'created_at'],
            'workflow_documentos' => ['estado', 'prioridad', 'fecha_vencimiento'],
            'prestamos' => ['estado', 'fecha_vencimiento', 'usuario_solicitante_id'],
        ],
    ],

    'file_storage' => [
        // Configuración de almacenamiento de archivos
        'chunk_upload_size' => 1024 * 1024 * 5,  // 5MB chunks para uploads grandes
        'temp_cleanup_hours' => 24,               // Limpiar archivos temporales después de 24h
        
        // Compresión de documentos
        'compression' => [
            'enabled' => env('COMPRESSION_ENABLED', true),
            'quality' => env('COMPRESSION_QUALITY', 85),
            'formats' => ['jpg', 'jpeg', 'png', 'pdf'],
        ],
        
        // Configuración de thumbnails
        'thumbnails' => [
            'enabled' => env('THUMBNAILS_ENABLED', true),
            'sizes' => [
                'small' => [150, 150],
                'medium' => [300, 300],
                'large' => [600, 600],
            ],
        ],
    ],

    'performance' => [
        // Configuración de limites de memoria y tiempo
        'memory_limit' => env('MEMORY_LIMIT', '512M'),
        'max_execution_time' => env('MAX_EXECUTION_TIME', 300),
        
        // Configuración de paginación
        'pagination' => [
            'default_per_page' => 25,
            'max_per_page' => 100,
        ],
        
        // Configuración de búsquedas
        'search' => [
            'min_query_length' => 3,
            'max_results' => 1000,
            'timeout' => 30,
        ],
        
        // Configuración de reportes
        'reports' => [
            'max_records' => 50000,
            'export_chunk_size' => 1000,
            'timeout' => 600,               // 10 minutos para reportes grandes
        ],
    ],

    'security' => [
        // Configuración de rate limiting
        'rate_limits' => [
            'api' => env('THROTTLE_API_REQUESTS', 1000),
            'login' => env('THROTTLE_LOGIN_ATTEMPTS', 5),
            'upload' => 50,                 // 50 uploads por hora
            'search' => 200,                // 200 búsquedas por hora
        ],
        
        // Configuración de sesiones
        'session' => [
            'lifetime' => env('SESSION_LIFETIME', 480),    // 8 horas
            'timeout_warning' => 30,                       // Advertir 30 min antes
        ],
        
        // Configuración de archivos
        'file_security' => [
            'allowed_extensions' => [
                'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
                'images' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'],
                'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
                'audio' => ['mp3', 'wav', 'wma', 'aac'],
                'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
            ],
            'max_file_size' => env('UPLOAD_MAX_FILESIZE', '50M'),
            'scan_for_virus' => env('VIRUS_SCAN_ENABLED', false),
        ],
    ],

    'monitoring' => [
        // Configuración de logging
        'log_slow_queries' => env('LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => 2000,    // 2 segundos
        
        // Métricas de performance
        'track_performance' => env('TRACK_PERFORMANCE', true),
        'performance_thresholds' => [
            'page_load' => 3000,           // 3 segundos
            'api_response' => 1000,        // 1 segundo
            'database_query' => 500,       // 500ms
        ],
        
        // Configuración de health checks
        'health_checks' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
            'external_services' => true,
        ],
    ],

    'external_services' => [
        // Configuración de timeouts para servicios externos
        'timeouts' => [
            'email' => 30,                 // 30 segundos
            'sms' => 15,                   // 15 segundos
            'ocr' => 120,                  // 2 minutos
            'virus_scan' => 60,            // 1 minuto
        ],
        
        // Configuración de reintentos
        'retries' => [
            'email' => 3,
            'sms' => 2,
            'ocr' => 1,
        ],
        
        // Configuración de circuit breaker
        'circuit_breaker' => [
            'failure_threshold' => 5,
            'recovery_timeout' => 60,
            'test_timeout' => 10,
        ],
    ],

    'queue' => [
        // Configuración de colas por prioridad
        'priorities' => [
            'critical' => 'high',
            'notifications' => 'default',
            'reports' => 'low',
            'cleanup' => 'low',
        ],
        
        // Configuración de workers
        'workers' => [
            'high' => 3,
            'default' => 5,
            'low' => 2,
        ],
        
        // Configuración de reintentos por tipo de trabajo
        'job_retries' => [
            'email' => 3,
            'sms' => 2,
            'notification' => 1,
            'report_generation' => 1,
            'file_processing' => 2,
        ],
    ],

    'backup' => [
        // Configuración de backups automáticos
        'enabled' => env('BACKUP_ENABLED', true),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        
        // Qué respaldar
        'include' => [
            'database' => true,
            'uploaded_files' => true,
            'config_files' => true,
            'logs' => false,
        ],
        
        // Configuración de compresión
        'compression' => [
            'enabled' => true,
            'level' => 6,                  // Nivel de compresión (1-9)
        ],
    ],
];
