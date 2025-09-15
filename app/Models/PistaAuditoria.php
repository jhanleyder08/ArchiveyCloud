<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para Pistas de Auditoría
 * 
 * Basado en REQ-CS-006: Pistas de auditoría inalterables
 * REQ-CS-007: Información mínima requerida en auditoría
 * REQ-CS-010: Prevención de desactivación
 * REQ-CS-035: Rastreo automático sin intervención manual
 */
class PistaAuditoria extends Model
{
    use HasFactory;

    protected $table = 'pistas_auditoria';

    // REQ-CS-006: Pistas inalterables - sin soft deletes, sin updates
    protected $fillable = [
        'entidad_type',
        'entidad_id',
        'usuario_id',
        'accion',
        'descripcion',
        'valores_anteriores',
        'valores_nuevos',
        'metadatos_cambios',
        'permisos_anteriores',
        'permisos_nuevos',
        'ip_address',
        'user_agent',
        'pais',
        'navegador',
        'dispositivo',
        'sistema_operativo',
        'sesion_id',
        'hash_integridad',
        'contexto_adicional'
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'metadatos_cambios' => 'array',
        'permisos_anteriores' => 'array',
        'permisos_nuevos' => 'array',
        'contexto_adicional' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Tipos de acciones auditables
    const ACCION_CREAR = 'crear';
    const ACCION_LEER = 'leer';
    const ACCION_ACTUALIZAR = 'actualizar';
    const ACCION_ELIMINAR = 'eliminar';
    const ACCION_LOGIN = 'login';
    const ACCION_LOGOUT = 'logout';
    const ACCION_ACCESO_DENEGADO = 'acceso_denegado';
    const ACCION_EXPORTAR = 'exportar';
    const ACCION_IMPORTAR = 'importar';
    const ACCION_CAMBIO_PERMISOS = 'cambio_permisos';
    const ACCION_REUBICACION = 'reubicacion';
    const ACCION_TRANSFERENCIA = 'transferencia';
    const ACCION_ELIMINACION_PROGRAMADA = 'eliminacion_programada';
    const ACCION_VIOLACION_SEGURIDAD = 'violacion_seguridad';

    protected static function boot()
    {
        parent::boot();
        
        // REQ-CS-035: Rastreo automático - capturar información del contexto
        static::creating(function ($auditoria) {
            $auditoria->capturarContexto();
            $auditoria->generarHashIntegridad();
        });

        // REQ-CS-006: Prevenir modificaciones (pistas inalterables)
        static::updating(function ($auditoria) {
            throw new \Exception('Las pistas de auditoría no pueden ser modificadas una vez creadas');
        });

        static::deleting(function ($auditoria) {
            throw new \Exception('Las pistas de auditoría no pueden ser eliminadas');
        });
    }

    /**
     * Relación polimórfica con la entidad auditada
     */
    public function entidad()
    {
        return $this->morphTo();
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * REQ-CS-007: Capturar información contextual automáticamente
     */
    private function capturarContexto()
    {
        if (!$this->ip_address) {
            $this->ip_address = request()->ip();
        }

        if (!$this->user_agent) {
            $this->user_agent = request()->userAgent();
        }

        if (!$this->sesion_id) {
            $this->sesion_id = session()->getId();
        }

        // Detectar información del navegador y dispositivo
        $this->analizarUserAgent();

        // Detectar país por IP (simplificado)
        $this->detectarPais();
    }

    /**
     * Analizar User Agent para extraer información del navegador y dispositivo
     */
    private function analizarUserAgent()
    {
        $userAgent = $this->user_agent ?? '';

        // Detectar navegador
        if (strpos($userAgent, 'Chrome') !== false) {
            $this->navegador = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $this->navegador = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $this->navegador = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $this->navegador = 'Edge';
        } else {
            $this->navegador = 'Desconocido';
        }

        // Detectar sistema operativo
        if (strpos($userAgent, 'Windows') !== false) {
            $this->sistema_operativo = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $this->sistema_operativo = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $this->sistema_operativo = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $this->sistema_operativo = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $this->sistema_operativo = 'iOS';
        } else {
            $this->sistema_operativo = 'Desconocido';
        }

        // Detectar tipo de dispositivo
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
            $this->dispositivo = 'Móvil';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            $this->dispositivo = 'Tablet';
        } else {
            $this->dispositivo = 'Escritorio';
        }
    }

    /**
     * Detectar país basado en IP (implementación básica)
     */
    private function detectarPais()
    {
        // En una implementación real, se usaría un servicio de geolocalización
        // Por ahora, detección básica
        $ip = $this->ip_address;
        
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
            $this->pais = 'Local';
        } else {
            // Aquí se integraría con un servicio como MaxMind GeoIP
            $this->pais = 'Colombia'; // Valor por defecto
        }
    }

    /**
     * REQ-CS-027: Generar hash de integridad para validación
     */
    private function generarHashIntegridad()
    {
        $datos = [
            'entidad_type' => $this->entidad_type,
            'entidad_id' => $this->entidad_id,
            'usuario_id' => $this->usuario_id,
            'accion' => $this->accion,
            'valores_anteriores' => $this->valores_anteriores,
            'valores_nuevos' => $this->valores_nuevos,
            'ip_address' => $this->ip_address,
            'timestamp' => now()->toISOString()
        ];

        $this->hash_integridad = hash('sha256', json_encode($datos, JSON_SORT_KEYS));
    }

    /**
     * Validar integridad del registro de auditoría
     */
    public function validarIntegridad()
    {
        $datos = [
            'entidad_type' => $this->entidad_type,
            'entidad_id' => $this->entidad_id,
            'usuario_id' => $this->usuario_id,
            'accion' => $this->accion,
            'valores_anteriores' => $this->valores_anteriores,
            'valores_nuevos' => $this->valores_nuevos,
            'ip_address' => $this->ip_address,
            'timestamp' => $this->created_at->toISOString()
        ];

        $hashCalculado = hash('sha256', json_encode($datos, JSON_SORT_KEYS));
        return $hashCalculado === $this->hash_integridad;
    }

    /**
     * Método estático para registrar acción de auditoría
     */
    public static function registrar($entidad, $accion, $detalles = [])
    {
        return static::create([
            'entidad_type' => is_object($entidad) ? get_class($entidad) : $entidad,
            'entidad_id' => is_object($entidad) ? $entidad->id : null,
            'usuario_id' => auth()->id(),
            'accion' => $accion,
            'descripcion' => $detalles['descripcion'] ?? null,
            'valores_anteriores' => $detalles['valores_anteriores'] ?? null,
            'valores_nuevos' => $detalles['valores_nuevos'] ?? null,
            'metadatos_cambios' => $detalles['metadatos_cambios'] ?? null,
            'permisos_anteriores' => $detalles['permisos_anteriores'] ?? null,
            'permisos_nuevos' => $detalles['permisos_nuevos'] ?? null,
            'contexto_adicional' => $detalles['contexto_adicional'] ?? null
        ]);
    }

    /**
     * REQ-CS-009: Registrar intentos de violación de seguridad
     */
    public static function registrarViolacionSeguridad($descripcion, $contexto = [])
    {
        return static::registrar(null, self::ACCION_VIOLACION_SEGURIDAD, [
            'descripcion' => $descripcion,
            'contexto_adicional' => array_merge($contexto, [
                'nivel_amenaza' => 'alto',
                'requiere_atencion' => true,
                'timestamp_violacion' => now()->toISOString()
            ])
        ]);
    }

    /**
     * Scope para filtrar por entidad
     */
    public function scopePorEntidad($query, $entidadType, $entidadId = null)
    {
        $query->where('entidad_type', $entidadType);
        
        if ($entidadId) {
            $query->where('entidad_id', $entidadId);
        }
        
        return $query;
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopePorFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para violaciones de seguridad
     */
    public function scopeViolacionesSeguridad($query)
    {
        return $query->where('accion', self::ACCION_VIOLACION_SEGURIDAD);
    }

    /**
     * REQ-CS-015: Generar informes de auditoría con filtros
     */
    public static function generarInforme($filtros = [])
    {
        $query = static::query()
                      ->with(['usuario', 'entidad'])
                      ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if (isset($filtros['usuario_id'])) {
            $query->porUsuario($filtros['usuario_id']);
        }

        if (isset($filtros['accion'])) {
            $query->porAccion($filtros['accion']);
        }

        if (isset($filtros['entidad_type'])) {
            $query->porEntidad($filtros['entidad_type'], $filtros['entidad_id'] ?? null);
        }

        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $query->porFechas($filtros['fecha_inicio'], $filtros['fecha_fin']);
        }

        if (isset($filtros['ip_address'])) {
            $query->where('ip_address', $filtros['ip_address']);
        }

        return $query->paginate($filtros['per_page'] ?? 50);
    }

    /**
     * REQ-CS-011: Identificar errores en ejecución de procesos
     */
    public static function identificarErroresProcesos($fechaInicio = null, $fechaFin = null)
    {
        $query = static::query()
                      ->whereIn('accion', ['error', 'fallo', 'excepcion'])
                      ->orWhere('descripcion', 'LIKE', '%error%')
                      ->orWhere('descripcion', 'LIKE', '%fallo%')
                      ->orderBy('created_at', 'desc');

        if ($fechaInicio && $fechaFin) {
            $query->porFechas($fechaInicio, $fechaFin);
        }

        return $query->get()->groupBy(function ($auditoria) {
            return $auditoria->entidad_type . ':' . $auditoria->accion;
        });
    }

    /**
     * Exportar pistas de auditoría (REQ-CL-038)
     */
    public static function exportar($filtros = [], $formato = 'json')
    {
        $datos = static::generarInforme($filtros)->items();
        
        switch ($formato) {
            case 'csv':
                return static::exportarCSV($datos);
            case 'xml':
                return static::exportarXML($datos);
            default:
                return json_encode($datos, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Exportar a CSV
     */
    private static function exportarCSV($datos)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers
        $headers = [
            'ID', 'Entidad', 'Usuario', 'Acción', 'Descripción', 
            'IP', 'Navegador', 'Sistema', 'País', 'Fecha'
        ];
        fputcsv($output, $headers);
        
        // Datos
        foreach ($datos as $item) {
            fputcsv($output, [
                $item->id,
                $item->entidad_type,
                $item->usuario->name ?? 'N/A',
                $item->accion,
                $item->descripcion,
                $item->ip_address,
                $item->navegador,
                $item->sistema_operativo,
                $item->pais,
                $item->created_at->format('Y-m-d H:i:s')
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Exportar a XML
     */
    private static function exportarXML($datos)
    {
        $xml = new \SimpleXMLElement('<pistas_auditoria/>');
        
        foreach ($datos as $item) {
            $pista = $xml->addChild('pista');
            $pista->addChild('id', $item->id);
            $pista->addChild('entidad_type', htmlspecialchars($item->entidad_type));
            $pista->addChild('entidad_id', $item->entidad_id);
            $pista->addChild('usuario', htmlspecialchars($item->usuario->name ?? 'N/A'));
            $pista->addChild('accion', htmlspecialchars($item->accion));
            $pista->addChild('descripcion', htmlspecialchars($item->descripcion));
            $pista->addChild('ip_address', $item->ip_address);
            $pista->addChild('navegador', htmlspecialchars($item->navegador));
            $pista->addChild('sistema_operativo', htmlspecialchars($item->sistema_operativo));
            $pista->addChild('pais', htmlspecialchars($item->pais));
            $pista->addChild('fecha', $item->created_at->toISOString());
            $pista->addChild('hash_integridad', $item->hash_integridad);
        }
        
        return $xml->asXML();
    }
}
