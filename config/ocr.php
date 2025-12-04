<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OCR Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para OCR (Optical Character Recognition)
    | Implementa REQ-CP-013, REQ-CP-014
    |
    */

    // Motor OCR por defecto
    'default_engine' => env('OCR_DEFAULT_ENGINE', 'tesseract'),

    // Motores disponibles
    'engines' => [
        'tesseract' => [
            'enabled' => env('OCR_TESSERACT_ENABLED', true),
            'binary_path' => env('OCR_TESSERACT_PATH', 'tesseract'),
            'data_path' => env('OCR_TESSERACT_DATA', null),
            'languages' => ['spa', 'eng'], // Español e Inglés
            'psm' => 3, // Page Segmentation Mode (3 = Automatic)
            'oem' => 3, // OCR Engine Mode (3 = Default, based on what is available)
        ],

        'cloud_vision' => [
            'enabled' => env('OCR_CLOUD_VISION_ENABLED', false),
            'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        ],

        'azure_vision' => [
            'enabled' => env('OCR_AZURE_ENABLED', false),
            'endpoint' => env('AZURE_VISION_ENDPOINT'),
            'key' => env('AZURE_VISION_KEY'),
        ],
    ],

    // Tipos de reconocimiento
    'recognition_types' => [
        'ocr' => true,  // Optical Character Recognition
        'icr' => false, // Intelligent Character Recognition (requires specific engine)
        'hcr' => false, // Handwritten Character Recognition
        'omr' => false, // Optical Mark Recognition
    ],

    // Formatos soportados para OCR
    'supported_formats' => [
        'pdf',
        'png',
        'jpg',
        'jpeg',
        'tiff',
        'bmp',
        'gif',
    ],

    // Preprocesamiento de imagen
    'preprocessing' => [
        'enabled' => true,
        'deskew' => true,           // Corregir inclinación
        'denoise' => true,          // Reducir ruido
        'enhance_contrast' => true, // Mejorar contraste
        'binarize' => true,         // Convertir a blanco y negro
        'scale' => 2.0,             // Escalar imagen (2x mejora calidad)
    ],

    // Postprocesamiento de texto
    'postprocessing' => [
        'enabled' => true,
        'spell_check' => false,      // Corrección ortográfica
        'remove_extra_spaces' => true,
        'normalize_whitespace' => true,
    ],

    // Configuración de procesamiento
    'processing' => [
        'queue_enabled' => env('OCR_QUEUE_ENABLED', true),
        'queue_name' => env('OCR_QUEUE_NAME', 'ocr'),
        'timeout' => env('OCR_TIMEOUT', 300), // 5 minutos
        'max_file_size' => env('OCR_MAX_FILE_SIZE', 50 * 1024 * 1024), // 50MB
        'concurrent_jobs' => env('OCR_CONCURRENT_JOBS', 2),
    ],

    // Almacenamiento
    'storage' => [
        'save_original' => true,
        'save_processed_image' => true,
        'save_ocr_text' => true,
        'disk' => 'public',
        'path' => 'ocr',
    ],

    // Confianza y calidad
    'quality' => [
        'min_confidence' => 60,      // Mínimo 60% de confianza
        'save_confidence_data' => true,
        'highlight_low_confidence' => true,
    ],

    // Extracción de metadatos
    'metadata_extraction' => [
        'enabled' => true,
        'extract_language' => true,
        'extract_orientation' => true,
        'extract_dpi' => true,
        'extract_text_regions' => true,
    ],

    // Integración con Elasticsearch
    'elasticsearch' => [
        'auto_index' => true,
        'update_document_content' => true,
    ],

    // Códigos de barras y QR
    'barcode_detection' => [
        'enabled' => env('OCR_BARCODE_ENABLED', false),
        'types' => ['QR', 'CODE_128', 'CODE_39', 'EAN_13'],
    ],

    // Logging
    'logging' => [
        'enabled' => env('OCR_LOGGING', true),
        'channel' => env('OCR_LOG_CHANNEL', 'daily'),
        'level' => env('OCR_LOG_LEVEL', 'info'),
    ],
];
