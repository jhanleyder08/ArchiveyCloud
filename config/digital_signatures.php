<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Firmas Digitales
    |--------------------------------------------------------------------------
    |
    | Configuraciones para el sistema de firmas digitales del SGDEA
    |
    */

    // URL del servicio TSA (Time Stamping Authority)
    'tsa_url' => env('TSA_URL', null),

    // Configuración de algoritmos
    'algorithms' => [
        'hash' => [
            'default' => 'SHA-256',
            'allowed' => ['SHA-256', 'SHA-384', 'SHA-512']
        ],
        'signature' => [
            'default' => 'RSA',
            'allowed' => ['RSA', 'DSA', 'ECDSA']
        ]
    ],

    // Tipos de firma soportados por formato
    'signature_types_by_format' => [
        'pdf' => ['PADES'],
        'xml' => ['XADES'],
        'default' => ['CADES']
    ],

    // Niveles de firma AdES
    'signature_levels' => [
        'BES' => [
            'name' => 'Basic Electronic Signature',
            'description' => 'Firma electrónica básica'
        ],
        'EPES' => [
            'name' => 'Explicit Policy-based Electronic Signature',
            'description' => 'Firma con política explícita'
        ],
        'T' => [
            'name' => 'Timestamp',
            'description' => 'Firma con sellado de tiempo'
        ],
        'LT' => [
            'name' => 'Long Term',
            'description' => 'Firma de largo plazo'
        ],
        'LTA' => [
            'name' => 'Long Term Archive',
            'description' => 'Firma de archivo de largo plazo'
        ]
    ],

    // Configuración de validación
    'validation' => [
        'check_revocation' => env('CHECK_CERTIFICATE_REVOCATION', true),
        'trust_chain_validation' => env('VALIDATE_TRUST_CHAIN', true),
        'ocsp_timeout' => env('OCSP_TIMEOUT', 30),
        'crl_cache_duration' => env('CRL_CACHE_DURATION', 3600), // 1 hora
        'max_chain_length' => env('MAX_CERT_CHAIN_LENGTH', 10)
    ],

    // Configuración de almacenamiento
    'storage' => [
        'certificates_path' => 'certificates',
        'signed_documents_path' => 'signed_documents',
        'encryption_key' => env('CERTIFICATE_ENCRYPTION_KEY', null),
        'backup_enabled' => env('CERTIFICATE_BACKUP_ENABLED', true)
    ],

    // Autoridades de certificación confiables
    'trusted_cas' => [
        // CAs colombianas
        'certicamara' => [
            'name' => 'Certicámara S.A.',
            'root_cert' => 'certicamara_root.pem',
            'country' => 'CO'
        ],
        'andes_scd' => [
            'name' => 'Andes SCD',
            'root_cert' => 'andes_scd_root.pem',
            'country' => 'CO'
        ],
        'gse' => [
            'name' => 'GSE Colombia',
            'root_cert' => 'gse_root.pem',
            'country' => 'CO'
        ],
        
        // CAs internacionales comunes
        'globalsign' => [
            'name' => 'GlobalSign',
            'root_cert' => 'globalsign_root.pem',
            'country' => 'BE'
        ],
        'digicert' => [
            'name' => 'DigiCert',
            'root_cert' => 'digicert_root.pem',
            'country' => 'US'
        ]
    ],

    // Políticas de firma
    'signature_policies' => [
        'colombia_ley_527' => [
            'oid' => '1.2.170.1.1.1',
            'name' => 'Política de Firma Colombia - Ley 527/1999',
            'description' => 'Política conforme a la Ley 527 de Colombia',
            'hash_algorithm' => 'SHA-256',
            'url' => 'https://www.mintic.gov.co/politicas-firma/'
        ],
        'etsi_baseline' => [
            'oid' => '0.4.0.194112.1.0',
            'name' => 'ETSI Baseline Policy',
            'description' => 'Política baseline de ETSI',
            'hash_algorithm' => 'SHA-256'
        ]
    ],

    // Configuración de sellado de tiempo
    'timestamping' => [
        'enabled' => env('TSA_ENABLED', false),
        'default_tsa' => env('DEFAULT_TSA', null),
        'backup_tsa' => env('BACKUP_TSA', null),
        'timeout' => env('TSA_TIMEOUT', 30),
        'retry_attempts' => env('TSA_RETRY_ATTEMPTS', 3),
        'hash_algorithm' => env('TSA_HASH_ALGORITHM', 'SHA-256')
    ],

    // Configuración de validación de certificados
    'certificate_validation' => [
        'check_expiration' => true,
        'check_not_before' => true,
        'check_key_usage' => true,
        'check_extended_key_usage' => true,
        'require_non_repudiation' => env('REQUIRE_NON_REPUDIATION', false),
        'warning_days_before_expiry' => env('CERT_EXPIRY_WARNING_DAYS', 30)
    ],

    // Configuración de formatos
    'formats' => [
        'cades' => [
            'mime_type' => 'application/pkcs7-signature',
            'extension' => 'p7s',
            'detached' => true
        ],
        'pades' => [
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'detached' => false
        ],
        'xades' => [
            'mime_type' => 'application/xml',
            'extension' => 'xml',
            'detached' => false
        ]
    ],

    // Configuración de auditoría
    'audit' => [
        'log_signatures' => env('LOG_SIGNATURES', true),
        'log_validations' => env('LOG_VALIDATIONS', true),
        'log_certificate_operations' => env('LOG_CERTIFICATE_OPS', true),
        'detailed_logging' => env('DETAILED_SIGNATURE_LOGGING', false)
    ],

    // Límites y restricciones
    'limits' => [
        'max_file_size' => env('MAX_SIGNATURE_FILE_SIZE', 100 * 1024 * 1024), // 100MB
        'max_signatures_per_document' => env('MAX_SIGNATURES_PER_DOC', 10),
        'max_certificate_size' => env('MAX_CERTIFICATE_SIZE', 10 * 1024), // 10KB
        'signature_timeout' => env('SIGNATURE_TIMEOUT', 300) // 5 minutos
    ],

    // Configuración de notificaciones
    'notifications' => [
        'certificate_expiry_warning' => env('NOTIFY_CERT_EXPIRY', true),
        'signature_validation_failure' => env('NOTIFY_SIGNATURE_FAILURE', true),
        'certificate_revocation' => env('NOTIFY_CERT_REVOCATION', true),
        'notification_channels' => ['mail', 'database']
    ],

    // URLs de servicios externos
    'external_services' => [
        'crl_distribution_points' => [
            // URLs comunes de CRL
        ],
        'ocsp_responders' => [
            // URLs de respondedores OCSP
        ],
        'ca_issuers' => [
            // URLs de emisores de CA
        ]
    ]
];
