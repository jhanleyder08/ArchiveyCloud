<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Capture Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para captura automática de correos electrónicos
    | Implementa REQ-CP-015, REQ-CP-016, REQ-CP-017, REQ-CP-023-025
    |
    */

    // Modo de captura
    'mode' => env('EMAIL_CAPTURE_MODE', 'automatic'), // automatic, manual, hybrid

    // Protocolo de conexión
    'protocol' => env('EMAIL_PROTOCOL', 'imap'), // imap, pop3

    // Configuración de servidores
    'servers' => [
        'default' => [
            'host' => env('EMAIL_CAPTURE_HOST', 'imap.gmail.com'),
            'port' => env('EMAIL_CAPTURE_PORT', 993),
            'encryption' => env('EMAIL_CAPTURE_ENCRYPTION', 'ssl'), // ssl, tls, none
            'validate_cert' => env('EMAIL_VALIDATE_CERT', true),
        ],
    ],

    // Cuentas de correo para captura
    'accounts' => [
        // Las cuentas se gestionan desde la base de datos
        // Este es solo un ejemplo de estructura
        // [
        //     'email' => 'archivo@empresa.com',
        //     'password' => 'encrypted_password',
        //     'server' => 'default',
        //     'folders' => ['INBOX', 'Archivo'],
        //     'auto_capture' => true,
        // ]
    ],

    // Frecuencia de captura automática
    'schedule' => [
        'enabled' => env('EMAIL_CAPTURE_SCHEDULE', true),
        'frequency' => env('EMAIL_CAPTURE_FREQUENCY', '*/15'), // Cada 15 minutos (cron)
        'max_emails_per_run' => 100,
    ],

    // Procesamiento de emails
    'processing' => [
        'mark_as_read' => true,
        'move_to_folder' => 'Procesados', // Mover emails capturados
        'delete_after_capture' => false,
        'save_attachments' => true,
        'max_attachment_size' => 25 * 1024 * 1024, // 25MB
        'allowed_attachment_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 
            'jpg', 'jpeg', 'png', 'gif', 'tiff',
            'txt', 'csv', 'zip', 'rar',
        ],
    ],

    // Creación automática de documentos
    'document_creation' => [
        'auto_create' => true,
        'default_serie_id' => null,
        'extract_metadata_from_subject' => true,
        'use_sender_as_creator' => true,
        'save_email_body' => true,
        'save_html_version' => true,
    ],

    // Filtros de captura
    'filters' => [
        'enabled' => true,
        'rules' => [
            // 'from' => ['@empresa.com', '@proveedor.com'],
            // 'subject_contains' => ['factura', 'contrato'],
            // 'has_attachments' => true,
        ],
    ],

    // Notificaciones
    'notifications' => [
        'on_new_capture' => true,
        'on_error' => true,
        'notify_roles' => ['admin', 'archivo'],
    ],

    // Seguridad
    'security' => [
        'encrypt_credentials' => true,
        'allow_html_content' => false, // Prevenir XSS
        'scan_attachments' => false, // Requiere antivirus
        'whitelist_senders' => [],
        'blacklist_senders' => [],
    ],

    // Almacenamiento
    'storage' => [
        'disk' => 'public',
        'path' => 'email_captures',
        'attachments_path' => 'email_attachments',
    ],

    // Queue
    'queue' => [
        'enabled' => true,
        'connection' => 'database',
        'queue_name' => 'email-capture',
    ],

    // Retry y timeout
    'retry' => [
        'attempts' => 3,
        'delay' => 60, // segundos
    ],

    'timeout' => 60, // segundos

    // Logging
    'logging' => [
        'enabled' => true,
        'channel' => 'daily',
        'level' => 'info',
    ],
];
