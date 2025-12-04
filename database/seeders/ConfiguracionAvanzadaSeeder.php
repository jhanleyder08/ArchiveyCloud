<?php

namespace Database\Seeders;

use App\Models\ConfiguracionServicio;
use Illuminate\Database\Seeder;

class ConfiguracionAvanzadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            // Sistema
            [
                'clave' => 'app_name',
                'valor' => 'ArchiveyCloud',
                'categoria' => 'sistema',
                'descripcion' => 'Nombre de la aplicación',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'app_description',
                'valor' => 'Sistema de Gestión Documental Electrónica de Archivo',
                'categoria' => 'sistema',
                'descripcion' => 'Descripción de la aplicación',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'app_version',
                'valor' => '2.0.0',
                'categoria' => 'sistema',
                'descripcion' => 'Versión de la aplicación',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'session_timeout',
                'valor' => '600',
                'categoria' => 'sistema',
                'descripcion' => 'Timeout de sesión en segundos (10 minutos)',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'max_upload_size',
                'valor' => '50',
                'categoria' => 'sistema',
                'descripcion' => 'Tamaño máximo de subida en MB',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'timezone',
                'valor' => 'America/Bogota',
                'categoria' => 'sistema',
                'descripcion' => 'Zona horaria del sistema',
                'tipo' => 'seleccion',
            ],
            [
                'clave' => 'locale',
                'valor' => 'es',
                'categoria' => 'sistema',
                'descripcion' => 'Idioma predeterminado del sistema',
                'tipo' => 'seleccion',
            ],
            
            // Branding
            [
                'clave' => 'color_primario',
                'valor' => '#3b82f6',
                'categoria' => 'branding',
                'descripcion' => 'Color primario del tema',
                'tipo' => 'color',
            ],
            [
                'clave' => 'color_secundario',
                'valor' => '#64748b',
                'categoria' => 'branding',
                'descripcion' => 'Color secundario del tema',
                'tipo' => 'color',
            ],
            [
                'clave' => 'tema_predeterminado',
                'valor' => 'light',
                'categoria' => 'branding',
                'descripcion' => 'Tema predeterminado del sistema',
                'tipo' => 'seleccion',
            ],
            [
                'clave' => 'logo_principal',
                'valor' => '',
                'categoria' => 'branding',
                'descripcion' => 'Ruta del logo principal',
                'tipo' => 'archivo',
            ],
            [
                'clave' => 'logo_secundario',
                'valor' => '',
                'categoria' => 'branding',
                'descripcion' => 'Ruta del logo secundario (opcional)',
                'tipo' => 'archivo',
            ],
            [
                'clave' => 'favicon',
                'valor' => '',
                'categoria' => 'branding',
                'descripcion' => 'Ruta del favicon',
                'tipo' => 'archivo',
            ],
            
            // Email
            [
                'clave' => 'mail_mailer',
                'valor' => 'smtp',
                'categoria' => 'email',
                'descripcion' => 'Driver de correo electrónico',
                'tipo' => 'seleccion',
            ],
            [
                'clave' => 'mail_host',
                'valor' => 'localhost',
                'categoria' => 'email',
                'descripcion' => 'Host del servidor de correo',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'mail_port',
                'valor' => '587',
                'categoria' => 'email',
                'descripcion' => 'Puerto del servidor de correo',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'mail_username',
                'valor' => '',
                'categoria' => 'email',
                'descripcion' => 'Usuario del correo electrónico',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'mail_password',
                'valor' => '',
                'categoria' => 'email',
                'descripcion' => 'Contraseña del correo electrónico',
                'tipo' => 'password',
            ],
            [
                'clave' => 'mail_encryption',
                'valor' => 'tls',
                'categoria' => 'email',
                'descripcion' => 'Encriptación del correo electrónico',
                'tipo' => 'seleccion',
            ],
            [
                'clave' => 'mail_from_address',
                'valor' => 'noreply@archiveycloud.com',
                'categoria' => 'email',
                'descripcion' => 'Dirección de correo de envío',
                'tipo' => 'email',
            ],
            [
                'clave' => 'mail_from_name',
                'valor' => 'ArchiveyCloud',
                'categoria' => 'email',
                'descripcion' => 'Nombre del remitente',
                'tipo' => 'texto',
            ],
            
            // SMS
            [
                'clave' => 'sms_provider',
                'valor' => 'disabled',
                'categoria' => 'sms',
                'descripcion' => 'Proveedor de SMS',
                'tipo' => 'seleccion',
            ],
            [
                'clave' => 'sms_api_key',
                'valor' => '',
                'categoria' => 'sms',
                'descripcion' => 'API Key del proveedor SMS',
                'tipo' => 'password',
            ],
            [
                'clave' => 'sms_from',
                'valor' => 'ArchiveyCloud',
                'categoria' => 'sms',
                'descripcion' => 'Nombre del remitente SMS',
                'tipo' => 'texto',
            ],
            [
                'clave' => 'sms_limit_per_hour',
                'valor' => '10',
                'categoria' => 'sms',
                'descripcion' => 'Límite de SMS por hora por usuario',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'sms_limit_per_day',
                'valor' => '50',
                'categoria' => 'sms',
                'descripcion' => 'Límite de SMS por día por usuario',
                'tipo' => 'numero',
            ],
            
            // Seguridad
            [
                'clave' => '2fa_enabled',
                'valor' => 'false',
                'categoria' => 'seguridad',
                'descripcion' => 'Habilitar autenticación de dos factores',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'password_min_length',
                'valor' => '8',
                'categoria' => 'seguridad',
                'descripcion' => 'Longitud mínima de contraseña',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'password_require_uppercase',
                'valor' => 'true',
                'categoria' => 'seguridad',
                'descripcion' => 'Requiere al menos una mayúscula en contraseña',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'password_require_lowercase',
                'valor' => 'true',
                'categoria' => 'seguridad',
                'descripcion' => 'Requiere al menos una minúscula en contraseña',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'password_require_numbers',
                'valor' => 'true',
                'categoria' => 'seguridad',
                'descripcion' => 'Requiere al menos un número en contraseña',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'password_require_symbols',
                'valor' => 'false',
                'categoria' => 'seguridad',
                'descripcion' => 'Requiere al menos un símbolo en contraseña',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'login_attempts_max',
                'valor' => '5',
                'categoria' => 'seguridad',
                'descripcion' => 'Número máximo de intentos de login fallidos',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'login_lockout_time',
                'valor' => '900',
                'categoria' => 'seguridad',
                'descripcion' => 'Tiempo de bloqueo en segundos (15 minutos)',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'session_encrypt',
                'valor' => 'true',
                'categoria' => 'seguridad',
                'descripcion' => 'Encriptar datos de sesión',
                'tipo' => 'boolean',
            ],
            
            // Notificaciones
            [
                'clave' => 'notificaciones_email_enabled',
                'valor' => 'true',
                'categoria' => 'notificaciones',
                'descripcion' => 'Habilitar notificaciones por correo electrónico',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'notificaciones_sms_enabled',
                'valor' => 'false',
                'categoria' => 'notificaciones',
                'descripcion' => 'Habilitar notificaciones por SMS',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'notificaciones_browser_enabled',
                'valor' => 'true',
                'categoria' => 'notificaciones',
                'descripcion' => 'Habilitar notificaciones en navegador',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'notificaciones_daily_summary',
                'valor' => 'true',
                'categoria' => 'notificaciones',
                'descripcion' => 'Enviar resumen diario',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'notificaciones_weekly_report',
                'valor' => 'true',
                'categoria' => 'notificaciones',
                'descripcion' => 'Enviar reporte semanal',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'notificaciones_reminder_time',
                'valor' => '24',
                'categoria' => 'notificaciones',
                'descripcion' => 'Horas antes de vencimiento para recordatorios',
                'tipo' => 'numero',
            ],
            
            // Performance y Cache
            [
                'clave' => 'cache_enabled',
                'valor' => 'true',
                'categoria' => 'sistema',
                'descripcion' => 'Habilitar sistema de cache',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'cache_ttl_default',
                'valor' => '3600',
                'categoria' => 'sistema',
                'descripcion' => 'TTL por defecto del cache en segundos',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'logs_retention_days',
                'valor' => '30',
                'categoria' => 'sistema',
                'descripcion' => 'Días de retención de logs del sistema',
                'tipo' => 'numero',
            ],
            [
                'clave' => 'backup_enabled',
                'valor' => 'true',
                'categoria' => 'sistema',
                'descripcion' => 'Habilitar backups automáticos',
                'tipo' => 'boolean',
            ],
            [
                'clave' => 'backup_frequency',
                'valor' => 'daily',
                'categoria' => 'sistema',
                'descripcion' => 'Frecuencia de backups automáticos',
                'tipo' => 'seleccion',
            ],
        ];

        foreach ($configuraciones as $config) {
            ConfiguracionServicio::updateOrCreate(
                ['clave' => $config['clave']],
                [
                    'valor' => $config['valor'],
                    'categoria' => $config['categoria'],
                    'descripcion' => $config['descripcion'],
                    'tipo' => $config['tipo'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('Configuraciones avanzadas creadas exitosamente.');
    }
}
