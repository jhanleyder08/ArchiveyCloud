<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'api_tokens';

    protected $fillable = [
        'nombre',
        'token',
        'usuario_id',
        'permisos',
        'activo',
        'fecha_expiracion',
        'limite_usos',
        'usos_realizados',
        'ips_permitidas',
        'descripcion',
        'ultimo_uso',
        'ultima_ip',
    ];

    protected $casts = [
        'permisos' => 'array',
        'ips_permitidas' => 'array',
        'activo' => 'boolean',
        'fecha_expiracion' => 'datetime',
        'ultimo_uso' => 'datetime',
        'usos_realizados' => 'integer',
        'limite_usos' => 'integer',
    ];

    protected $hidden = [
        'token', // Nunca exponer el token hasheado
    ];

    protected $dates = [
        'fecha_expiracion',
        'ultimo_uso',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relación con el usuario propietario del token
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con los logs de uso del token
     */
    public function logs()
    {
        return $this->hasMany(ApiTokenLog::class);
    }

    /**
     * Scope para tokens activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para tokens no expirados
     */
    public function scopeNoExpirados($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_expiracion')
              ->orWhere('fecha_expiracion', '>', now());
        });
    }

    /**
     * Scope para tokens válidos (activos y no expirados)
     */
    public function scopeValidos($query)
    {
        return $query->activos()->noExpirados();
    }

    /**
     * Generar un nuevo token
     */
    public static function generarToken(): array
    {
        $plainToken = 'at_' . Str::random(64);
        $hashedToken = hash('sha256', $plainToken);
        
        return [
            'plain' => $plainToken,
            'hashed' => $hashedToken,
        ];
    }

    /**
     * Crear un nuevo token API
     */
    public static function crearToken(array $datos): array
    {
        $tokens = self::generarToken();
        
        $apiToken = self::create([
            'nombre' => $datos['nombre'],
            'token' => $tokens['hashed'],
            'usuario_id' => $datos['usuario_id'],
            'permisos' => $datos['permisos'] ?? [],
            'descripcion' => $datos['descripcion'] ?? null,
            'fecha_expiracion' => $datos['fecha_expiracion'] ?? null,
            'limite_usos' => $datos['limite_usos'] ?? null,
            'ips_permitidas' => $datos['ips_permitidas'] ?? null,
            'activo' => $datos['activo'] ?? true,
        ]);

        return [
            'token' => $apiToken,
            'plain_token' => $tokens['plain'],
        ];
    }

    /**
     * Verificar si el token está expirado
     */
    public function estaExpirado(): bool
    {
        if (!$this->fecha_expiracion) {
            return false;
        }
        
        return Carbon::parse($this->fecha_expiracion)->isPast();
    }

    /**
     * Verificar si el token ha alcanzado su límite de usos
     */
    public function alcanzeLimiteUsos(): bool
    {
        if (!$this->limite_usos) {
            return false;
        }
        
        return $this->usos_realizados >= $this->limite_usos;
    }

    /**
     * Verificar si una IP está permitida
     */
    public function ipPermitida(string $ip): bool
    {
        if (!$this->ips_permitidas || empty($this->ips_permitidas)) {
            return true; // Si no hay restricciones de IP, permitir todas
        }
        
        return in_array($ip, $this->ips_permitidas);
    }

    /**
     * Verificar si el token tiene un permiso específico
     */
    public function tienePermiso(string $permiso): bool
    {
        if (!$this->permisos || empty($this->permisos)) {
            return false;
        }

        // Si tiene permisos de admin o wildcard, permitir todo
        if (in_array('admin', $this->permisos) || in_array('*', $this->permisos)) {
            return true;
        }

        // Verificar permiso específico
        if (in_array($permiso, $this->permisos)) {
            return true;
        }

        // Verificar wildcards (ej: documentos:* incluye documentos:read)
        $permisoParts = explode(':', $permiso);
        if (count($permisoParts) === 2) {
            $wildcardPermiso = $permisoParts[0] . ':*';
            if (in_array($wildcardPermiso, $this->permisos)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Revocar el token
     */
    public function revocar(): void
    {
        $this->update(['activo' => false]);
    }

    /**
     * Renovar el token (generar uno nuevo)
     */
    public function renovar(): array
    {
        $tokens = self::generarToken();
        
        $this->update([
            'token' => $tokens['hashed'],
            'usos_realizados' => 0,
            'ultimo_uso' => null,
            'ultima_ip' => null,
        ]);

        return [
            'token' => $this->fresh(),
            'plain_token' => $tokens['plain'],
        ];
    }

    /**
     * Obtener estadísticas de uso
     */
    public function estadisticasUso(): array
    {
        $logs = $this->logs()
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(DISTINCT DATE(created_at)) as dias_activos,
                MAX(created_at) as ultimo_request,
                COUNT(DISTINCT ip) as ips_diferentes
            ')
            ->first();

        $requestsPorDia = $this->logs()
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as requests')
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->limit(30)
            ->pluck('requests', 'fecha');

        $requestsPorRuta = $this->logs()
            ->selectRaw('ruta, COUNT(*) as requests')
            ->groupBy('ruta')
            ->orderByDesc('requests')
            ->limit(10)
            ->pluck('requests', 'ruta');

        return [
            'resumen' => [
                'total_requests' => $logs->total_requests ?? 0,
                'dias_activos' => $logs->dias_activos ?? 0,
                'ultimo_request' => $logs->ultimo_request,
                'ips_diferentes' => $logs->ips_diferentes ?? 0,
                'promedio_requests_dia' => $logs->dias_activos > 0 ? 
                    round(($logs->total_requests ?? 0) / $logs->dias_activos, 2) : 0,
            ],
            'requests_por_dia' => $requestsPorDia,
            'rutas_mas_usadas' => $requestsPorRuta,
        ];
    }

    /**
     * Accessor para mostrar el estado del token
     */
    public function getEstadoAttribute(): string
    {
        if (!$this->activo) {
            return 'inactivo';
        }
        
        if ($this->estaExpirado()) {
            return 'expirado';
        }
        
        if ($this->alcanzeLimiteUsos()) {
            return 'limite_alcanzado';
        }
        
        return 'activo';
    }

    /**
     * Accessor para mostrar días hasta la expiración
     */
    public function getDiasHastaExpiracionAttribute(): ?int
    {
        if (!$this->fecha_expiracion) {
            return null;
        }
        
        $diasRestantes = now()->diffInDays(Carbon::parse($this->fecha_expiracion), false);
        
        return $diasRestantes > 0 ? $diasRestantes : 0;
    }

    /**
     * Accessor para mostrar porcentaje de uso
     */
    public function getPorcentajeUsoAttribute(): ?float
    {
        if (!$this->limite_usos) {
            return null;
        }
        
        return ($this->usos_realizados / $this->limite_usos) * 100;
    }

    /**
     * Listar permisos disponibles
     */
    public static function permisosDisponibles(): array
    {
        return [
            'admin' => 'Administrador (todos los permisos)',
            'documentos:read' => 'Leer documentos',
            'documentos:write' => 'Crear/editar documentos',
            'documentos:delete' => 'Eliminar documentos',
            'expedientes:read' => 'Leer expedientes',
            'expedientes:write' => 'Crear/editar expedientes',
            'expedientes:delete' => 'Eliminar expedientes',
            'usuarios:read' => 'Leer usuarios',
            'usuarios:write' => 'Crear/editar usuarios',
            'firmas:read' => 'Leer firmas digitales',
            'firmas:write' => 'Crear firmas digitales',
            'auditoria:read' => 'Leer auditoría',
        ];
    }
}
