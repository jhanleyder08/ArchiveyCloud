<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema ArchiveyCloud
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas del sistema de gestión documental
    |
    */

    'firma_digital' => [
        /*
        |--------------------------------------------------------------------------
        | Validez de las Firmas Digitales
        |--------------------------------------------------------------------------
        |
        | Número de días que una firma digital será considerada válida
        |
        */
        'validez_dias' => env('FIRMA_VALIDEZ_DIAS', 365),

        /*
        |--------------------------------------------------------------------------
        | Algoritmo de Hash
        |--------------------------------------------------------------------------
        |
        | Algoritmo utilizado para generar hashes de integridad
        |
        */
        'algoritmo_hash' => 'SHA-256',

        /*
        |--------------------------------------------------------------------------
        | Tipos de Firma Permitidos
        |--------------------------------------------------------------------------
        |
        | Tipos de firma digital que se pueden aplicar
        |
        */
        'tipos_permitidos' => [
            'electronica' => 'Firma Electrónica',
            'digital' => 'Firma Digital',
            'avanzada' => 'Firma Avanzada'
        ],

        /*
        |--------------------------------------------------------------------------
        | Múltiples Firmas por Usuario
        |--------------------------------------------------------------------------
        |
        | Permitir que un usuario firme el mismo documento múltiples veces
        |
        */
        'permitir_multiples_firmas' => env('PERMITIR_MULTIPLES_FIRMAS', false),

        /*
        |--------------------------------------------------------------------------
        | Directorio de Certificados
        |--------------------------------------------------------------------------
        |
        | Directorio donde se almacenan los certificados de firma
        |
        */
        'directorio_certificados' => 'certificados',
    ],

    'documentos' => [
        /*
        |--------------------------------------------------------------------------
        | Tamaños Máximos por Tipo
        |--------------------------------------------------------------------------
        |
        | Tamaños máximos en MB para diferentes tipos de documentos
        |
        */
        'tamaños_maximos' => [
            'pdf' => 50,
            'doc' => 25,
            'docx' => 25,
            'xls' => 25,
            'xlsx' => 25,
            'jpg' => 10,
            'jpeg' => 10,
            'png' => 10,
            'gif' => 5,
            'mp4' => 100,
            'avi' => 100,
            'mov' => 100,
            'mp3' => 25,
            'wav' => 50,
            'zip' => 100,
            'rar' => 100,
        ],

        /*
        |--------------------------------------------------------------------------
        | Tipos MIME Permitidos
        |--------------------------------------------------------------------------
        |
        | Tipos MIME que se permiten subir al sistema
        |
        */
        'tipos_mime_permitidos' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'video/avi',
            'video/quicktime',
            'audio/mpeg',
            'audio/wav',
            'application/zip',
            'application/x-rar-compressed',
        ],
    ],

    'notificaciones' => [
        /*
        |--------------------------------------------------------------------------
        | Días de Retención de Notificaciones
        |--------------------------------------------------------------------------
        |
        | Número de días que se mantienen las notificaciones en el sistema
        |
        */
        'dias_retencion' => env('NOTIFICACIONES_RETENCION_DIAS', 30),

        /*
        |--------------------------------------------------------------------------
        | Frecuencia de Verificación
        |--------------------------------------------------------------------------
        |
        | Intervalo en minutos para verificar nuevas notificaciones
        |
        */
        'frecuencia_verificacion' => env('NOTIFICACIONES_FRECUENCIA', 30),
    ],

    'expedientes' => [
        /*
        |--------------------------------------------------------------------------
        | Volumen Máximo por Defecto
        |--------------------------------------------------------------------------
        |
        | Volumen máximo de documentos por expediente (por defecto)
        |
        */
        'volumen_maximo_defecto' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Estados Permitidos
        |--------------------------------------------------------------------------
        |
        | Estados posibles del ciclo de vida de expedientes
        |
        */
        'estados_permitidos' => [
            'tramite' => 'En Trámite',
            'gestion' => 'Archivo de Gestión',
            'central' => 'Archivo Central',
            'historico' => 'Archivo Histórico',
            'eliminado' => 'Eliminado'
        ],
    ],

    'auditoria' => [
        /*
        |--------------------------------------------------------------------------
        | Días de Retención de Auditoría
        |--------------------------------------------------------------------------
        |
        | Número de días que se mantienen los registros de auditoría
        |
        */
        'dias_retencion' => env('AUDITORIA_RETENCION_DIAS', 2555), // 7 años por defecto

        /*
        |--------------------------------------------------------------------------
        | Acciones a Registrar
        |--------------------------------------------------------------------------
        |
        | Acciones que se deben registrar en la auditoría
        |
        */
        'acciones_registrar' => [
            'crear',
            'actualizar',
            'eliminar',
            'firmar',
            'aprobar',
            'rechazar',
            'cambiar_estado',
            'subir_archivo',
            'descargar_archivo',
        ],
    ],

    'backup' => [
        /*
        |--------------------------------------------------------------------------
        | Directorio de Respaldos
        |--------------------------------------------------------------------------
        |
        | Directorio donde se almacenan los respaldos del sistema
        |
        */
        'directorio' => env('BACKUP_DIRECTORIO', 'backups'),

        /*
        |--------------------------------------------------------------------------
        | Frecuencia de Respaldo
        |--------------------------------------------------------------------------
        |
        | Frecuencia de respaldo automático (diario, semanal, mensual)
        |
        */
        'frecuencia' => env('BACKUP_FRECUENCIA', 'diario'),

        /*
        |--------------------------------------------------------------------------
        | Retención de Respaldos
        |--------------------------------------------------------------------------
        |
        | Número de respaldos a mantener
        |
        */
        'retencion' => env('BACKUP_RETENCION', 30),
    ],
];
