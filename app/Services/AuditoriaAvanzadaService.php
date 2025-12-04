<?php

namespace App\Services;

use App\Models\PistaAuditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuditoriaAvanzadaService
{
    /**
     * Registrar evento de auditoría con análisis avanzado
     */
    public function registrarEvento(array $datos): PistaAuditoria
    {
        try {
            $datosCompletos = $this->enriquecerDatosAuditoria($datos);
            
            $auditoria = PistaAuditoria::create($datosCompletos);
            
            // Análisis de patrones sospechosos en tiempo real
            $this->analizarPatronesSospechosos($auditoria);
            
            // Generar alertas si es necesario
            $this->evaluarAlertas($auditoria);
            
            return $auditoria;
            
        } catch (\Exception $e) {
            Log::error('Error al registrar auditoría: ' . $e->getMessage(), [
                'datos' => $datos,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Enriquecer datos de auditoría con información contextual
     */
    private function enriquecerDatosAuditoria(array $datos): array
    {
        $usuario = Auth::user();
        $request = Request::instance();
        
        return array_merge($datos, [
            'usuario_id' => $usuario?->id,
            'fecha_hora' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'sesion_id' => session()->getId(),
            'pais' => $this->detectarPais($request->ip()),
            'ciudad' => $this->detectarCiudad($request->ip()),
            'dispositivo_tipo' => $this->detectarTipoDispositivo($request->userAgent()),
            'navegador' => $this->detectarNavegador($request->userAgent()),
            'hash_integridad' => $this->generarHashIntegridad($datos),
            'contexto_adicional' => $this->obtenerContextoAdicional(),
            'nivel_riesgo' => $this->evaluarNivelRiesgo($datos),
            'categoria_evento' => $this->categorizarEvento($datos),
        ]);
    }

    /**
     * Analizar patrones sospechosos en tiempo real
     */
    private function analizarPatronesSospechosos(PistaAuditoria $auditoria): void
    {
        try {
            $patrones = [
                'accesos_multiples_rapidos' => $this->detectarAccesosMultiplesRapidos($auditoria),
                'cambios_masivos' => $this->detectarCambiosMasivos($auditoria),
                'horarios_inusuales' => $this->detectarHorariosInusuales($auditoria),
                'ips_sospechosas' => $this->detectarIPsSospechosas($auditoria),
                'acciones_privilegiadas' => $this->detectarAccionesPrivilegiadas($auditoria),
                'patron_escalada_privilegios' => $this->detectarEscaladaPrivilegios($auditoria),
            ];
            
            foreach ($patrones as $patron => $detectado) {
                if ($detectado) {
                    $this->registrarPatronSospechoso($auditoria, $patron, $detectado);
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Error en análisis de patrones: ' . $e->getMessage());
        }
    }

    /**
     * Detectar accesos múltiples rápidos
     */
    private function detectarAccesosMultiplesRapidos(PistaAuditoria $auditoria): array|bool
    {
        if (!$auditoria->usuario_id) return false;
        
        $accesos = PistaAuditoria::where('usuario_id', $auditoria->usuario_id)
            ->where('accion', 'login')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
            
        if ($accesos > 10) {
            return [
                'tipo' => 'accesos_multiples_rapidos',
                'cantidad' => $accesos,
                'ventana_tiempo' => '5 minutos',
                'riesgo' => 'alto'
            ];
        }
        
        return false;
    }

    /**
     * Detectar cambios masivos
     */
    private function detectarCambiosMasivos(PistaAuditoria $auditoria): array|bool
    {
        if (!$auditoria->usuario_id) return false;
        
        $cambios = PistaAuditoria::where('usuario_id', $auditoria->usuario_id)
            ->whereIn('accion', ['crear', 'actualizar', 'eliminar'])
            ->where('created_at', '>=', now()->subHour())
            ->count();
            
        if ($cambios > 50) {
            return [
                'tipo' => 'cambios_masivos',
                'cantidad' => $cambios,
                'ventana_tiempo' => '1 hora',
                'riesgo' => 'medio'
            ];
        }
        
        return false;
    }

    /**
     * Detectar horarios inusuales
     */
    private function detectarHorariosInusuales(PistaAuditoria $auditoria): array|bool
    {
        $hora = $auditoria->fecha_hora->hour;
        
        // Horarios fuera del rango laboral (6 AM - 10 PM)
        if ($hora < 6 || $hora > 22) {
            return [
                'tipo' => 'horario_inusual',
                'hora' => $auditoria->fecha_hora->format('H:i'),
                'dia_semana' => $auditoria->fecha_hora->dayOfWeek,
                'riesgo' => $hora < 3 || $hora > 23 ? 'alto' : 'medio'
            ];
        }
        
        return false;
    }

    /**
     * Detectar IPs sospechosas
     */
    private function detectarIPsSospechosas(PistaAuditoria $auditoria): array|bool
    {
        $ip = $auditoria->ip_address;
        
        // Verificar lista negra de IPs (simulada)
        $ipsProhibidas = Cache::get('ips_prohibidas', []);
        if (in_array($ip, $ipsProhibidas)) {
            return [
                'tipo' => 'ip_prohibida',
                'ip' => $ip,
                'riesgo' => 'crítico'
            ];
        }
        
        // Verificar cambios frecuentes de IP para el mismo usuario
        if ($auditoria->usuario_id) {
            $ipsRecientes = PistaAuditoria::where('usuario_id', $auditoria->usuario_id)
                ->where('created_at', '>=', now()->subHour())
                ->distinct('ip_address')
                ->count('ip_address');
                
            if ($ipsRecientes > 5) {
                return [
                    'tipo' => 'cambios_ip_frecuentes',
                    'ips_diferentes' => $ipsRecientes,
                    'ventana_tiempo' => '1 hora',
                    'riesgo' => 'alto'
                ];
            }
        }
        
        return false;
    }

    /**
     * Detectar acciones privilegiadas
     */
    private function detectarAccionesPrivilegiadas(PistaAuditoria $auditoria): array|bool
    {
        $accionesPrivilegiadas = [
            'eliminar_usuario',
            'cambiar_permisos',
            'eliminar_expediente',
            'revocar_certificado',
            'eliminar_backup',
            'cambiar_configuracion_sistema'
        ];
        
        if (in_array($auditoria->accion, $accionesPrivilegiadas)) {
            return [
                'tipo' => 'accion_privilegiada',
                'accion' => $auditoria->accion,
                'requiere_revision' => true,
                'riesgo' => 'alto'
            ];
        }
        
        return false;
    }

    /**
     * Detectar escalada de privilegios
     */
    private function detectarEscaladaPrivilegios(PistaAuditoria $auditoria): array|bool
    {
        if (!$auditoria->usuario_id || $auditoria->accion !== 'cambiar_permisos') {
            return false;
        }
        
        // Verificar si el usuario cambió sus propios permisos
        if (isset($auditoria->metadatos_cambios['usuario_modificado']) && 
            $auditoria->metadatos_cambios['usuario_modificado'] == $auditoria->usuario_id) {
            
            return [
                'tipo' => 'auto_escalada_privilegios',
                'usuario_id' => $auditoria->usuario_id,
                'cambios' => $auditoria->metadatos_cambios,
                'riesgo' => 'crítico'
            ];
        }
        
        return false;
    }

    /**
     * Registrar patrón sospechoso detectado
     */
    private function registrarPatronSospechoso(PistaAuditoria $auditoria, string $patron, array $detalles): void
    {
        try {
            $this->registrarEvento([
                'accion' => 'patron_sospechoso_detectado',
                'modelo' => 'Sistema',
                'descripcion' => "Patrón sospechoso detectado: {$patron}",
                'detalles' => $detalles,
                'nivel_riesgo' => $detalles['riesgo'] ?? 'medio',
                'auditoria_relacionada_id' => $auditoria->id,
                'requiere_investigacion' => true
            ]);
            
            // Enviar alerta inmediata para riesgos críticos
            if (($detalles['riesgo'] ?? '') === 'crítico') {
                $this->enviarAlertaCritica($patron, $detalles, $auditoria);
            }
            
        } catch (\Exception $e) {
            Log::error('Error registrando patrón sospechoso: ' . $e->getMessage());
        }
    }

    /**
     * Evaluar y generar alertas
     */
    private function evaluarAlertas(PistaAuditoria $auditoria): void
    {
        try {
            $alertas = [];
            
            // Evaluar diferentes tipos de alertas
            if ($this->evaluarAlertaFallosLogin($auditoria)) {
                $alertas[] = 'fallos_login_consecutivos';
            }
            
            if ($this->evaluarAlertaCambiosConfig($auditoria)) {
                $alertas[] = 'cambio_configuracion_critica';
            }
            
            if ($this->evaluarAlertaAccesoNoAutorizado($auditoria)) {
                $alertas[] = 'intento_acceso_no_autorizado';
            }
            
            // Procesar alertas
            foreach ($alertas as $tipoAlerta) {
                $this->procesarAlerta($tipoAlerta, $auditoria);
            }
            
        } catch (\Exception $e) {
            Log::warning('Error evaluando alertas: ' . $e->getMessage());
        }
    }

    /**
     * Generar reportes de auditoría avanzados
     */
    public function generarReporteAvanzado(array $filtros): array
    {
        try {
            $query = PistaAuditoria::query()
                ->with(['usuario:id,name,email']);
            
            // Aplicar filtros
            if (!empty($filtros['fecha_inicio'])) {
                $query->where('fecha_hora', '>=', $filtros['fecha_inicio']);
            }
            
            if (!empty($filtros['fecha_fin'])) {
                $query->where('fecha_hora', '<=', $filtros['fecha_fin']);
            }
            
            if (!empty($filtros['usuario_id'])) {
                $query->where('usuario_id', $filtros['usuario_id']);
            }
            
            if (!empty($filtros['nivel_riesgo'])) {
                $query->where('nivel_riesgo', $filtros['nivel_riesgo']);
            }
            
            if (!empty($filtros['accion'])) {
                $query->where('accion', $filtros['accion']);
            }
            
            $eventos = $query->orderBy('fecha_hora', 'desc')
                ->paginate(50);
            
            // Generar estadísticas del reporte
            $estadisticas = $this->generarEstadisticasReporte($query->clone());
            
            // Análisis de tendencias
            $tendencias = $this->analizarTendenciasAuditoria($query->clone());
            
            // Análisis de riesgos
            $analisisRiesgos = $this->analizarRiesgosAuditoria($query->clone());
            
            return [
                'eventos' => $eventos,
                'estadisticas' => $estadisticas,
                'tendencias' => $tendencias,
                'analisis_riesgos' => $analisisRiesgos,
                'resumen_ejecutivo' => $this->generarResumenEjecutivo($estadisticas, $tendencias, $analisisRiesgos)
            ];
            
        } catch (\Exception $e) {
            Log::error('Error generando reporte avanzado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar estadísticas del reporte
     */
    private function generarEstadisticasReporte($query): array
    {
        return [
            'total_eventos' => $query->count(),
            'eventos_por_usuario' => $query->selectRaw('usuario_id, COUNT(*) as total')
                ->groupBy('usuario_id')
                ->with('usuario:id,name')
                ->get(),
            'eventos_por_accion' => $query->selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->orderBy('total', 'desc')
                ->get(),
            'eventos_por_riesgo' => $query->selectRaw('nivel_riesgo, COUNT(*) as total')
                ->groupBy('nivel_riesgo')
                ->get(),
            'horarios_actividad' => $query->selectRaw('HOUR(fecha_hora) as hora, COUNT(*) as total')
                ->groupBy('hora')
                ->orderBy('hora')
                ->get(),
            'actividad_diaria' => $query->selectRaw('DATE(fecha_hora) as fecha, COUNT(*) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
        ];
    }

    /**
     * Analizar tendencias de auditoría
     */
    private function analizarTendenciasAuditoria($query): array
    {
        return [
            'crecimiento_actividad' => $this->calcularCrecimientoActividad($query),
            'patrones_horarios' => $this->identificarPatronesHorarios($query),
            'usuarios_mas_activos' => $this->identificarUsuariosMasActivos($query),
            'acciones_frecuentes' => $this->identificarAccionesFrecuentes($query),
            'anomalias_detectadas' => $this->identificarAnomalias($query)
        ];
    }

    /**
     * Analizar riesgos de auditoría
     */
    private function analizarRiesgosAuditoria($query): array
    {
        return [
            'eventos_criticos' => $query->where('nivel_riesgo', 'crítico')->count(),
            'eventos_alto_riesgo' => $query->where('nivel_riesgo', 'alto')->count(),
            'intentos_acceso_fallidos' => $query->where('accion', 'login_fallido')->count(),
            'cambios_privilegios' => $query->where('accion', 'cambiar_permisos')->count(),
            'ips_sospechosas' => $query->whereNotNull('ip_address')
                ->distinct('ip_address')
                ->where('nivel_riesgo', 'alto')
                ->count(),
            'horarios_inusuales' => $query->whereRaw('HOUR(fecha_hora) < 6 OR HOUR(fecha_hora) > 22')->count()
        ];
    }

    /**
     * Detectar país por IP (simulado)
     */
    private function detectarPais(string $ip): string
    {
        // En producción usaría un servicio de geolocalización real
        if (str_starts_with($ip, '192.168.') || str_starts_with($ip, '127.0.')) {
            return 'Local';
        }
        return 'Colombia'; // Valor por defecto
    }

    /**
     * Detectar ciudad por IP (simulado)
     */
    private function detectarCiudad(string $ip): string
    {
        // En producción usaría un servicio de geolocalización real
        return 'Bogotá';
    }

    /**
     * Detectar tipo de dispositivo
     */
    private function detectarTipoDispositivo(string $userAgent): string
    {
        if (str_contains(strtolower($userAgent), 'mobile')) {
            return 'Móvil';
        } elseif (str_contains(strtolower($userAgent), 'tablet')) {
            return 'Tablet';
        }
        return 'Escritorio';
    }

    /**
     * Detectar navegador
     */
    private function detectarNavegador(string $userAgent): string
    {
        $navegadores = [
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edge/i',
            'Internet Explorer' => '/MSIE|Trident/i'
        ];
        
        foreach ($navegadores as $nombre => $patron) {
            if (preg_match($patron, $userAgent)) {
                return $nombre;
            }
        }
        
        return 'Desconocido';
    }

    /**
     * Generar hash de integridad
     */
    private function generarHashIntegridad(array $datos): string
    {
        $contenido = json_encode($datos, JSON_SORT_KEYS);
        return hash('sha256', $contenido . config('app.key'));
    }

    /**
     * Obtener contexto adicional
     */
    private function obtenerContextoAdicional(): array
    {
        return [
            'memoria_usada' => memory_get_usage(true),
            'tiempo_ejecucion' => microtime(true) - LARAVEL_START,
            'version_app' => config('app.version', '1.0.0'),
            'entorno' => config('app.env')
        ];
    }

    /**
     * Evaluar nivel de riesgo
     */
    private function evaluarNivelRiesgo(array $datos): string
    {
        $accionesAltoRiesgo = [
            'eliminar_usuario', 'eliminar_expediente', 'revocar_certificado',
            'cambiar_permisos', 'login_fallido', 'acceso_denegado'
        ];
        
        $accionesMedioRiesgo = [
            'actualizar_usuario', 'crear_usuario', 'eliminar_documento',
            'cambiar_configuracion'
        ];
        
        $accion = $datos['accion'] ?? '';
        
        if (in_array($accion, $accionesAltoRiesgo)) {
            return 'alto';
        } elseif (in_array($accion, $accionesMedioRiesgo)) {
            return 'medio';
        }
        
        return 'bajo';
    }

    /**
     * Categorizar evento
     */
    private function categorizarEvento(array $datos): string
    {
        $accion = $datos['accion'] ?? '';
        
        $categorias = [
            'autenticacion' => ['login', 'logout', 'login_fallido'],
            'gestion_usuarios' => ['crear_usuario', 'actualizar_usuario', 'eliminar_usuario'],
            'gestion_documentos' => ['crear_documento', 'actualizar_documento', 'eliminar_documento'],
            'gestion_expedientes' => ['crear_expediente', 'actualizar_expediente', 'eliminar_expediente'],
            'seguridad' => ['cambiar_permisos', 'revocar_certificado', 'acceso_denegado'],
            'sistema' => ['configuracion', 'backup', 'optimizacion']
        ];
        
        foreach ($categorias as $categoria => $acciones) {
            if (in_array($accion, $acciones)) {
                return $categoria;
            }
        }
        
        return 'general';
    }

    /**
     * Enviar alerta crítica
     */
    private function enviarAlertaCritica(string $patron, array $detalles, PistaAuditoria $auditoria): void
    {
        // En producción enviaría notificaciones reales (email, SMS, Slack, etc.)
        Log::critical("ALERTA CRÍTICA DE SEGURIDAD: {$patron}", [
            'patron' => $patron,
            'detalles' => $detalles,
            'auditoria_id' => $auditoria->id,
            'usuario_id' => $auditoria->usuario_id,
            'ip_address' => $auditoria->ip_address,
            'timestamp' => $auditoria->fecha_hora
        ]);
    }

    /**
     * Métodos auxiliares para evaluación de alertas
     */
    private function evaluarAlertaFallosLogin(PistaAuditoria $auditoria): bool
    {
        if ($auditoria->accion !== 'login_fallido') return false;
        
        $fallos = PistaAuditoria::where('ip_address', $auditoria->ip_address)
            ->where('accion', 'login_fallido')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
            
        return $fallos >= 5;
    }
    
    private function evaluarAlertaCambiosConfig(PistaAuditoria $auditoria): bool
    {
        return str_contains($auditoria->accion, 'configuracion') || 
               str_contains($auditoria->accion, 'sistema');
    }
    
    private function evaluarAlertaAccesoNoAutorizado(PistaAuditoria $auditoria): bool
    {
        return $auditoria->accion === 'acceso_denegado' || 
               $auditoria->accion === 'permiso_denegado';
    }
    
    private function procesarAlerta(string $tipo, PistaAuditoria $auditoria): void
    {
        Log::warning("Alerta de auditoría: {$tipo}", [
            'auditoria_id' => $auditoria->id,
            'usuario_id' => $auditoria->usuario_id,
            'ip_address' => $auditoria->ip_address
        ]);
    }

    /**
     * Métodos auxiliares para análisis de tendencias
     */
    private function calcularCrecimientoActividad($query): array
    {
        // Implementación simplificada
        return ['porcentaje' => 0, 'tendencia' => 'estable'];
    }
    
    private function identificarPatronesHorarios($query): array
    {
        return ['patron_principal' => 'horario_laboral'];
    }
    
    private function identificarUsuariosMasActivos($query): array
    {
        return $query->selectRaw('usuario_id, COUNT(*) as actividad')
            ->groupBy('usuario_id')
            ->orderBy('actividad', 'desc')
            ->take(10)
            ->get()
            ->toArray();
    }
    
    private function identificarAccionesFrecuentes($query): array
    {
        return $query->selectRaw('accion, COUNT(*) as frecuencia')
            ->groupBy('accion')
            ->orderBy('frecuencia', 'desc')
            ->take(10)
            ->get()
            ->toArray();
    }
    
    private function identificarAnomalias($query): array
    {
        return ['anomalias_detectadas' => 0, 'detalles' => []];
    }

    /**
     * Generar resumen ejecutivo
     */
    private function generarResumenEjecutivo(array $estadisticas, array $tendencias, array $analisisRiesgos): array
    {
        return [
            'total_eventos' => $estadisticas['total_eventos'] ?? 0,
            'nivel_riesgo_general' => $this->calcularNivelRiesgoGeneral($analisisRiesgos),
            'recomendaciones' => $this->generarRecomendaciones($analisisRiesgos),
            'puntos_atencion' => $this->identificarPuntosAtencion($estadisticas, $analisisRiesgos)
        ];
    }
    
    private function calcularNivelRiesgoGeneral(array $analisisRiesgos): string
    {
        if ($analisisRiesgos['eventos_criticos'] > 0) return 'crítico';
        if ($analisisRiesgos['eventos_alto_riesgo'] > 10) return 'alto';
        if ($analisisRiesgos['eventos_alto_riesgo'] > 5) return 'medio';
        return 'bajo';
    }
    
    private function generarRecomendaciones(array $analisisRiesgos): array
    {
        $recomendaciones = [];
        
        if ($analisisRiesgos['intentos_acceso_fallidos'] > 20) {
            $recomendaciones[] = 'Implementar bloqueo automático de IPs con múltiples fallos de login';
        }
        
        if ($analisisRiesgos['horarios_inusuales'] > 50) {
            $recomendaciones[] = 'Revisar accesos fuera del horario laboral';
        }
        
        return $recomendaciones;
    }
    
    private function identificarPuntosAtencion(array $estadisticas, array $analisisRiesgos): array
    {
        $puntos = [];
        
        if ($analisisRiesgos['eventos_criticos'] > 0) {
            $puntos[] = "Se detectaron {$analisisRiesgos['eventos_criticos']} eventos críticos que requieren investigación inmediata";
        }
        
        return $puntos;
    }
}
