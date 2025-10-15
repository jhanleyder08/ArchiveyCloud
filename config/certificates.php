<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Certificados Digitales
    |--------------------------------------------------------------------------
    |
    | Configuraciones para la gestión de certificados digitales
    |
    */

    // Formatos de certificado soportados
    'supported_formats' => [
        'PEM' => [
            'extensions' => ['pem', 'crt', 'cer'],
            'mime_types' => ['application/x-pem-file', 'text/plain'],
            'description' => 'Privacy-Enhanced Mail (Base64 encoded)'
        ],
        'DER' => [
            'extensions' => ['der', 'crt', 'cer'],
            'mime_types' => ['application/x-x509-ca-cert', 'application/octet-stream'],
            'description' => 'Distinguished Encoding Rules (Binary)'
        ],
        'P12' => [
            'extensions' => ['p12', 'pfx'],
            'mime_types' => ['application/x-pkcs12'],
            'description' => 'PKCS#12 (Personal Information Exchange)'
        ]
    ],

    // Algoritmos de hash soportados
    'hash_algorithms' => [
        'SHA-256' => [
            'oid' => '2.16.840.1.101.3.4.2.1',
            'recommended' => true,
            'min_key_size' => 2048
        ],
        'SHA-384' => [
            'oid' => '2.16.840.1.101.3.4.2.2', 
            'recommended' => true,
            'min_key_size' => 2048
        ],
        'SHA-512' => [
            'oid' => '2.16.840.1.101.3.4.2.3',
            'recommended' => true,
            'min_key_size' => 2048
        ],
        'SHA-1' => [
            'oid' => '1.3.14.3.2.26',
            'recommended' => false, // Deprecated
            'min_key_size' => 1024
        ]
    ],

    // Key Usage estándar
    'key_usage' => [
        'digitalSignature' => 'Firma Digital',
        'nonRepudiation' => 'No Repudio', 
        'keyEncipherment' => 'Cifrado de Claves',
        'dataEncipherment' => 'Cifrado de Datos',
        'keyAgreement' => 'Acuerdo de Claves',
        'keyCertSign' => 'Firma de Certificados',
        'cRLSign' => 'Firma de CRL',
        'encipherOnly' => 'Solo Cifrado',
        'decipherOnly' => 'Solo Descifrado'
    ],

    // Extended Key Usage
    'extended_key_usage' => [
        'serverAuth' => 'Autenticación de Servidor',
        'clientAuth' => 'Autenticación de Cliente',
        'codeSigning' => 'Firma de Código',
        'emailProtection' => 'Protección de Email',
        'timeStamping' => 'Sellado de Tiempo',
        'OCSPSigning' => 'Firma OCSP'
    ],

    // Configuración de validación
    'validation' => [
        'cache_duration' => env('CERT_VALIDATION_CACHE', 3600), // 1 hora
        'max_chain_depth' => env('MAX_CERT_CHAIN_DEPTH', 10),
        'require_basic_constraints' => env('REQUIRE_BASIC_CONSTRAINTS', true),
        'check_critical_extensions' => env('CHECK_CRITICAL_EXTENSIONS', true),
        'allow_self_signed' => env('ALLOW_SELF_SIGNED', false),
        'verify_hostname' => env('VERIFY_HOSTNAME', true)
    ],

    // URLs de servicios de revocación
    'revocation_services' => [
        'crl_timeout' => env('CRL_TIMEOUT', 30),
        'ocsp_timeout' => env('OCSP_TIMEOUT', 15),
        'max_crl_size' => env('MAX_CRL_SIZE', 5 * 1024 * 1024), // 5MB
        'crl_cache_duration' => env('CRL_CACHE_DURATION', 7200), // 2 horas
        'ocsp_cache_duration' => env('OCSP_CACHE_DURATION', 1800) // 30 minutos
    ],

    // Configuración de almacenamiento seguro
    'storage' => [
        'disk' => env('CERTIFICATES_DISK', 'local'),
        'path' => env('CERTIFICATES_PATH', 'certificates'),
        'encryption' => env('CERTIFICATES_ENCRYPTION', true),
        'backup_enabled' => env('CERTIFICATES_BACKUP', true),
        'backup_schedule' => env('CERTIFICATES_BACKUP_SCHEDULE', 'daily'),
        'retention_days' => env('CERTIFICATES_RETENTION_DAYS', 2555) // ~7 años
    ],

    // Límites de seguridad
    'security_limits' => [
        'max_file_size' => env('MAX_CERT_FILE_SIZE', 100 * 1024), // 100KB
        'max_imports_per_hour' => env('MAX_CERT_IMPORTS_PER_HOUR', 10),
        'min_key_size_rsa' => env('MIN_RSA_KEY_SIZE', 2048),
        'min_key_size_ecc' => env('MIN_ECC_KEY_SIZE', 256),
        'max_validity_years' => env('MAX_CERT_VALIDITY_YEARS', 10)
    ],

    // Notificaciones de vencimiento
    'expiry_notifications' => [
        'enabled' => env('CERT_EXPIRY_NOTIFICATIONS', true),
        'warning_days' => [90, 60, 30, 15, 7, 3, 1],
        'channels' => ['mail', 'database'],
        'include_admins' => env('NOTIFY_ADMINS_CERT_EXPIRY', true),
        'batch_size' => env('CERT_NOTIFICATION_BATCH_SIZE', 50)
    ],

    // Auditoría y logging
    'audit' => [
        'log_imports' => env('LOG_CERT_IMPORTS', true),
        'log_validations' => env('LOG_CERT_VALIDATIONS', true),
        'log_revocation_checks' => env('LOG_REVOCATION_CHECKS', true),
        'detailed_logging' => env('DETAILED_CERT_LOGGING', false),
        'retention_days' => env('CERT_AUDIT_RETENTION', 2555)
    ],

    // Autoridades de certificación reconocidas en Colombia
    'colombian_cas' => [
        'certicamara' => [
            'name' => 'Certicámara S.A.',
            'oid' => '1.2.170.1.1',
            'website' => 'https://www.certicamara.com',
            'crl_urls' => [
                'http://www.certicamara.com/crl/certicamara.crl'
            ],
            'ocsp_urls' => [
                'http://ocsp.certicamara.com'
            ]
        ],
        'andes_scd' => [
            'name' => 'Andes SCD',
            'oid' => '1.2.170.2.1',
            'website' => 'https://www.andesscd.com.co',
            'crl_urls' => [
                'http://www.andesscd.com.co/crl/andesscd.crl'
            ],
            'ocsp_urls' => [
                'http://ocsp.andesscd.com.co'
            ]
        ],
        'gse' => [
            'name' => 'GSE Colombia',
            'oid' => '1.2.170.3.1',
            'website' => 'https://www.gse.com.co',
            'crl_urls' => [
                'http://www.gse.com.co/crl/gse.crl'
            ],
            'ocsp_urls' => [
                'http://ocsp.gse.com.co'
            ]
        ]
    ],

    // Configuración de tareas programadas
    'scheduled_tasks' => [
        'certificate_verification' => [
            'enabled' => env('CERT_SCHEDULED_VERIFICATION', true),
            'frequency' => env('CERT_VERIFICATION_FREQUENCY', 'daily'),
            'time' => env('CERT_VERIFICATION_TIME', '02:00'),
            'batch_size' => env('CERT_VERIFICATION_BATCH', 100)
        ],
        'expiry_check' => [
            'enabled' => env('CERT_EXPIRY_CHECK', true),
            'frequency' => env('CERT_EXPIRY_FREQUENCY', 'daily'),
            'time' => env('CERT_EXPIRY_TIME', '06:00')
        ],
        'cleanup' => [
            'enabled' => env('CERT_CLEANUP', true),
            'frequency' => env('CERT_CLEANUP_FREQUENCY', 'weekly'),
            'remove_expired_days' => env('CERT_CLEANUP_EXPIRED_DAYS', 365)
        ]
    ]
];
