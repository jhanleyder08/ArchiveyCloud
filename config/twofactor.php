<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de autenticación de dos factores (2FA)
    |
    */

    // Tiempo de expiración del código 2FA (en minutos)
    'code_expiration' => env('2FA_CODE_EXPIRATION', 5),

    // Tiempo de validez de la sesión 2FA (en minutos)
    'session_lifetime' => env('2FA_SESSION_LIFETIME', 30),

    // Tiempo de espera para reenviar código (en segundos)
    'resend_cooldown' => env('2FA_RESEND_COOLDOWN', 60),

    // Número de códigos de recuperación a generar
    'recovery_codes_count' => env('2FA_RECOVERY_CODES_COUNT', 10),

    // Longitud de los códigos de recuperación
    'recovery_code_length' => env('2FA_RECOVERY_CODE_LENGTH', 10),

    // Ventana de tiempo para TOTP (número de períodos de 30 seg a ambos lados)
    'totp_window' => env('2FA_TOTP_WINDOW', 2),

    // Nombre de la aplicación para TOTP
    'app_name' => env('APP_NAME', 'ArchiveyCloud'),

    // Métodos de 2FA habilitados
    'enabled_methods' => [
        'totp' => true,
        'sms' => env('2FA_SMS_ENABLED', false),
        'email' => env('2FA_EMAIL_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration (Twilio)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'driver' => env('2FA_SMS_DRIVER', 'twilio'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'template' => 'Tu código de verificación de {app_name} es: {code}\n\nEste código expira en {minutes} minutos.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@archiveycloud.com'),
            'name' => env('MAIL_FROM_NAME', 'ArchiveyCloud Security'),
        ],
        'subject' => 'Código de Verificación 2FA',
        'template' => 'emails.two-factor-code', // Opcional: plantilla personalizada
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Máximo de intentos fallidos antes de bloqueo temporal
        'max_attempts' => env('2FA_MAX_ATTEMPTS', 5),
        
        // Tiempo de bloqueo después de exceder intentos (en minutos)
        'lockout_time' => env('2FA_LOCKOUT_TIME', 15),
        
        // Registrar intentos fallidos en auditoría
        'log_failed_attempts' => true,
        
        // Notificar al usuario por email de cambios en 2FA
        'notify_changes' => true,
    ],

];
