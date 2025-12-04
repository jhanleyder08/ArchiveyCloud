<?php

namespace App\Services;

use App\Models\ConfiguracionServicio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfiguracionService
{
    /**
     * Obtener valor de configuración con caché
     */
    public function obtener($clave, $default = null, $useCache = true)
    {
        try {
            if ($useCache) {
                return Cache::remember("config_{$clave}", 3600, function () use ($clave, $default) {
                    $config = ConfiguracionServicio::where('clave', $clave)
                        ->where('activo', true)
                        ->first();
                    
                    return $config ? $config->valor : $default;
                });
            }

            $config = ConfiguracionServicio::where('clave', $clave)
                ->where('activo', true)
                ->first();
            
            return $config ? $config->valor : $default;
        } catch (\Exception $e) {
            Log::error("Error obteniendo configuración {$clave}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Establecer valor de configuración
     */
    public function establecer($clave, $valor, $categoria = 'sistema', $descripcion = null, $tipo = 'texto')
    {
        try {
            $config = ConfiguracionServicio::updateOrCreate(
                ['clave' => $clave],
                [
                    'valor' => $valor,
                    'categoria' => $categoria,
                    'descripcion' => $descripcion,
                    'tipo' => $tipo,
                    'activo' => true,
                    'actualizado_por' => auth()->id(),
                ]
            );

            // Limpiar caché
            Cache::forget("config_{$clave}");
            
            return $config;
        } catch (\Exception $e) {
            Log::error("Error estableciendo configuración {$clave}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener configuraciones por categoría
     */
    public function obtenerPorCategoria($categoria, $useCache = true)
    {
        try {
            if ($useCache) {
                return Cache::remember("config_categoria_{$categoria}", 1800, function () use ($categoria) {
                    return ConfiguracionServicio::where('categoria', $categoria)
                        ->where('activo', true)
                        ->get()
                        ->keyBy('clave');
                });
            }

            return ConfiguracionServicio::where('categoria', $categoria)
                ->where('activo', true)
                ->get()
                ->keyBy('clave');
        } catch (\Exception $e) {
            Log::error("Error obteniendo configuraciones categoría {$categoria}: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtener configuraciones del sistema
     */
    public function obtenerSistema()
    {
        return $this->obtenerPorCategoria('sistema');
    }

    /**
     * Obtener configuraciones de email
     */
    public function obtenerEmail()
    {
        return $this->obtenerPorCategoria('email');
    }

    /**
     * Obtener configuraciones de SMS
     */
    public function obtenerSms()
    {
        return $this->obtenerPorCategoria('sms');
    }

    /**
     * Obtener configuraciones de seguridad
     */
    public function obtenerSeguridad()
    {
        return $this->obtenerPorCategoria('seguridad');
    }

    /**
     * Obtener configuraciones de branding
     */
    public function obtenerBranding()
    {
        return $this->obtenerPorCategoria('branding');
    }

    /**
     * Obtener configuraciones de notificaciones
     */
    public function obtenerNotificaciones()
    {
        return $this->obtenerPorCategoria('notificaciones');
    }

    /**
     * Obtener configuraciones por rol
     */
    public function obtenerPorRol($roleId)
    {
        return $this->obtenerPorCategoria("rol_{$roleId}");
    }

    /**
     * Verificar si una configuración está activa
     */
    public function estaActiva($clave)
    {
        try {
            return Cache::remember("config_active_{$clave}", 1800, function () use ($clave) {
                $config = ConfiguracionServicio::where('clave', $clave)->first();
                return $config ? $config->activo : false;
            });
        } catch (\Exception $e) {
            Log::error("Error verificando configuración activa {$clave}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener configuración con tipo específico
     */
    public function obtenerTipado($clave, $tipo = 'string', $default = null)
    {
        $valor = $this->obtener($clave, $default);

        if ($valor === null) {
            return $default;
        }

        switch ($tipo) {
            case 'boolean':
            case 'bool':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            
            case 'integer':
            case 'int':
                return (int) $valor;
            
            case 'float':
            case 'double':
                return (float) $valor;
            
            case 'array':
                return is_array($valor) ? $valor : json_decode($valor, true);
            
            case 'json':
                return json_decode($valor, true);
            
            default:
                return (string) $valor;
        }
    }

    /**
     * Configuraciones por defecto del sistema
     */
    public function crearConfiguracionesPorDefecto()
    {
        $configuraciones_default = [
            // Sistema
            ['clave' => 'app_name', 'valor' => 'ArchiveyCloud', 'categoria' => 'sistema', 'descripcion' => 'Nombre de la aplicación', 'tipo' => 'texto'],
            ['clave' => 'app_description', 'valor' => 'Sistema de Gestión Documental', 'categoria' => 'sistema', 'descripcion' => 'Descripción de la aplicación', 'tipo' => 'texto'],
            ['clave' => 'app_version', 'valor' => '1.0.0', 'categoria' => 'sistema', 'descripcion' => 'Versión de la aplicación', 'tipo' => 'texto'],
            ['clave' => 'session_timeout', 'valor' => '600', 'categoria' => 'sistema', 'descripcion' => 'Timeout de sesión en segundos', 'tipo' => 'numero'],
            ['clave' => 'max_upload_size', 'valor' => '50', 'categoria' => 'sistema', 'descripcion' => 'Tamaño máximo de subida en MB', 'tipo' => 'numero'],
            
            // Branding
            ['clave' => 'color_primario', 'valor' => '#3b82f6', 'categoria' => 'branding', 'descripcion' => 'Color primario del tema', 'tipo' => 'color'],
            ['clave' => 'color_secundario', 'valor' => '#64748b', 'categoria' => 'branding', 'descripcion' => 'Color secundario del tema', 'tipo' => 'color'],
            ['clave' => 'tema_predeterminado', 'valor' => 'light', 'categoria' => 'branding', 'descripcion' => 'Tema predeterminado', 'tipo' => 'seleccion'],
            ['clave' => 'logo_principal', 'valor' => '', 'categoria' => 'branding', 'descripcion' => 'Ruta del logo principal', 'tipo' => 'archivo'],
            ['clave' => 'logo_secundario', 'valor' => '', 'categoria' => 'branding', 'descripcion' => 'Ruta del logo secundario', 'tipo' => 'archivo'],
            ['clave' => 'favicon', 'valor' => '', 'categoria' => 'branding', 'descripcion' => 'Ruta del favicon', 'tipo' => 'archivo'],
            
            // Email
            ['clave' => 'mail_mailer', 'valor' => 'smtp', 'categoria' => 'email', 'descripcion' => 'Driver de email', 'tipo' => 'seleccion'],
            ['clave' => 'mail_host', 'valor' => 'localhost', 'categoria' => 'email', 'descripcion' => 'Host del servidor de email', 'tipo' => 'texto'],
            ['clave' => 'mail_port', 'valor' => '587', 'categoria' => 'email', 'descripcion' => 'Puerto del servidor de email', 'tipo' => 'numero'],
            ['clave' => 'mail_username', 'valor' => '', 'categoria' => 'email', 'descripcion' => 'Usuario del email', 'tipo' => 'texto'],
            ['clave' => 'mail_password', 'valor' => '', 'categoria' => 'email', 'descripcion' => 'Contraseña del email', 'tipo' => 'password'],
            ['clave' => 'mail_encryption', 'valor' => 'tls', 'categoria' => 'email', 'descripcion' => 'Encriptación del email', 'tipo' => 'seleccion'],
            ['clave' => 'mail_from_address', 'valor' => 'noreply@archiveycloud.com', 'categoria' => 'email', 'descripcion' => 'Email de envío', 'tipo' => 'email'],
            ['clave' => 'mail_from_name', 'valor' => 'ArchiveyCloud', 'categoria' => 'email', 'descripcion' => 'Nombre del remitente', 'tipo' => 'texto'],
            
            // SMS
            ['clave' => 'sms_provider', 'valor' => 'disabled', 'categoria' => 'sms', 'descripcion' => 'Proveedor de SMS', 'tipo' => 'seleccion'],
            ['clave' => 'sms_api_key', 'valor' => '', 'categoria' => 'sms', 'descripcion' => 'API Key del proveedor SMS', 'tipo' => 'password'],
            ['clave' => 'sms_from', 'valor' => 'ArchiveyCloud', 'categoria' => 'sms', 'descripcion' => 'Nombre del remitente SMS', 'tipo' => 'texto'],
            
            // Seguridad
            ['clave' => '2fa_enabled', 'valor' => 'false', 'categoria' => 'seguridad', 'descripcion' => 'Habilitar autenticación de dos factores', 'tipo' => 'boolean'],
            ['clave' => 'password_min_length', 'valor' => '8', 'categoria' => 'seguridad', 'descripcion' => 'Longitud mínima de contraseña', 'tipo' => 'numero'],
            ['clave' => 'login_attempts_max', 'valor' => '5', 'categoria' => 'seguridad', 'descripcion' => 'Intentos máximos de login', 'tipo' => 'numero'],
            ['clave' => 'login_lockout_time', 'valor' => '900', 'categoria' => 'seguridad', 'descripcion' => 'Tiempo de bloqueo en segundos', 'tipo' => 'numero'],
            
            // Notificaciones
            ['clave' => 'notificaciones_email_enabled', 'valor' => 'true', 'categoria' => 'notificaciones', 'descripcion' => 'Habilitar notificaciones por email', 'tipo' => 'boolean'],
            ['clave' => 'notificaciones_sms_enabled', 'valor' => 'false', 'categoria' => 'notificaciones', 'descripcion' => 'Habilitar notificaciones por SMS', 'tipo' => 'boolean'],
            ['clave' => 'notificaciones_browser_enabled', 'valor' => 'true', 'categoria' => 'notificaciones', 'descripcion' => 'Habilitar notificaciones en navegador', 'tipo' => 'boolean'],
        ];

        foreach ($configuraciones_default as $config) {
            ConfiguracionServicio::firstOrCreate(
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
    }

    /**
     * Limpiar caché de configuraciones
     */
    public function limpiarCache($clave = null)
    {
        if ($clave) {
            Cache::forget("config_{$clave}");
            Cache::forget("config_active_{$clave}");
        } else {
            // Limpiar todo el caché de configuraciones
            $claves = ConfiguracionServicio::pluck('clave');
            foreach ($claves as $clave) {
                Cache::forget("config_{$clave}");
                Cache::forget("config_active_{$clave}");
            }
            
            // Limpiar caché de categorías
            $categorias = ConfiguracionServicio::distinct()->pluck('categoria');
            foreach ($categorias as $categoria) {
                Cache::forget("config_categoria_{$categoria}");
            }
        }
    }

    /**
     * Validar configuraciones del sistema
     */
    public function validarConfiguraciones()
    {
        $errores = [];

        // Validar configuraciones críticas
        $criticas = [
            'app_name' => 'Nombre de la aplicación requerido',
            'app_url' => 'URL de la aplicación requerida',
            'mail_from_address' => 'Email de envío requerido',
        ];

        foreach ($criticas as $clave => $mensaje) {
            if (!$this->obtener($clave)) {
                $errores[] = $mensaje;
            }
        }

        // Validar formato de email
        $mail_from = $this->obtener('mail_from_address');
        if ($mail_from && !filter_var($mail_from, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Formato de email inválido en mail_from_address';
        }

        // Validar valores numéricos
        $numericos = ['session_timeout', 'max_upload_size', 'mail_port'];
        foreach ($numericos as $clave) {
            $valor = $this->obtener($clave);
            if ($valor && !is_numeric($valor)) {
                $errores[] = "Valor numérico inválido en {$clave}";
            }
        }

        return $errores;
    }
}
