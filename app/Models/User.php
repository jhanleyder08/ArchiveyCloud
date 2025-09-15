<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\CustomResetPassword;

/**
 * Modelo de Usuario expandido para SGDEA
 * 
 * Basado en requerimientos de Control y Seguridad:
 * REQ-CS-001 a REQ-CS-011: Gestión de usuarios, roles y permisos
 * REQ-CS-012 a REQ-CS-016: Autenticación y autorización
 * REQ-CS-017 a REQ-CS-021: Políticas de contraseñas
 * REQ-CS-022 a REQ-CS-024: Control de sesiones
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'active',
        'email_verified_at',
        'documento_identidad',
        'tipo_documento',
        'telefono',
        'cargo',
        'dependencia',
        'fecha_ingreso',
        'fecha_vencimiento_cuenta',
        'ultimo_acceso',
        'intentos_fallidos',
        'bloqueado_hasta',
        'cambio_password_requerido',
        'fecha_ultimo_cambio_password',
        'historial_passwords',
        'configuracion_notificaciones',
        'preferencias_usuario',
        'estado_cuenta',
        'observaciones'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'historial_passwords'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'fecha_ingreso' => 'date',
            'fecha_vencimiento_cuenta' => 'date',
            'ultimo_acceso' => 'datetime',
            'bloqueado_hasta' => 'datetime',
            'cambio_password_requerido' => 'boolean',
            'fecha_ultimo_cambio_password' => 'datetime',
            'historial_passwords' => 'array',
            'configuracion_notificaciones' => 'array',
            'preferencias_usuario' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime'
        ];
    }

    // Estados de cuenta
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';
    const ESTADO_BLOQUEADO = 'bloqueado';
    const ESTADO_SUSPENDIDO = 'suspendido';
    const ESTADO_VENCIDO = 'vencido';

    // Tipos de documento
    const TIPO_DOC_CC = 'cedula_ciudadania';
    const TIPO_DOC_CE = 'cedula_extranjeria';
    const TIPO_DOC_PASAPORTE = 'pasaporte';
    const TIPO_DOC_TI = 'tarjeta_identidad';

    // Configuraciones de seguridad
    const MAX_INTENTOS_FALLIDOS = 3;
    const TIEMPO_BLOQUEO_MINUTOS = 30;
    const DIAS_VIGENCIA_PASSWORD = 90;
    const HISTORIAL_PASSWORDS = 5;

    protected static function boot()
    {
        parent::boot();
        
        // Registrar eventos de auditoría
        static::created(function ($user) {
            PistaAuditoria::registrar($user, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Usuario creado: ' . $user->name,
                'email' => $user->email,
                'role' => $user->role->name ?? null
            ]);
        });
        
        static::updated(function ($user) {
            $cambios = $user->getDirty();
            
            // Registrar cambio de contraseña
            if (isset($cambios['password'])) {
                PistaAuditoria::registrar($user, 'cambio_password', [
                    'descripcion' => 'Contraseña cambiada para usuario: ' . $user->name
                ]);
            }
            
            // Registrar otros cambios
            PistaAuditoria::registrar($user, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Usuario actualizado: ' . $user->name,
                'valores_anteriores' => $user->getOriginal(),
                'valores_nuevos' => $user->getAttributes()
            ]);
        });
        
        static::deleted(function ($user) {
            PistaAuditoria::registrar($user, PistaAuditoria::ACCION_ELIMINAR, [
                'descripcion' => 'Usuario eliminado: ' . $user->name
            ]);
        });
    }

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relación con roles adicionales (many-to-many)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withTimestamps();
    }

    /**
     * Relación con permisos específicos
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'user_permisos', 'user_id', 'permiso_id')
                    ->withPivot(['vigencia_desde', 'vigencia_hasta', 'activo'])
                    ->withTimestamps();
    }

    /**
     * Relación con sesiones activas
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionUsuario::class, 'user_id');
    }

    /**
     * Relación con notificaciones de seguridad
     */
    public function notificacionesSeguridad(): HasMany
    {
        return $this->hasMany(NotificacionSeguridad::class, 'user_id');
    }

    /**
     * Relación con TRDs creadas
     */
    public function trdsCreadas(): HasMany
    {
        return $this->hasMany(TablaRetencionDocumental::class, 'usuario_creador_id');
    }

    /**
     * Relación con TRDs modificadas
     */
    public function trdsModificadas(): HasMany
    {
        return $this->hasMany(TablaRetencionDocumental::class, 'usuario_modificador_id');
    }

    /**
     * Relación con auditoría
     */
    public function auditoria(): HasMany
    {
        return $this->hasMany(PistaAuditoria::class, 'usuario_id');
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('active', true)
                    ->where('estado_cuenta', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para usuarios por rol
     */
    public function scopePorRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope para usuarios por dependencia
     */
    public function scopePorDependencia($query, $dependencia)
    {
        return $query->where('dependencia', $dependencia);
    }

    /**
     * REQ-CS-005: Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        // Verificar rol principal
        if ($this->role && $this->role->name === $roleName) {
            return true;
        }
        
        // Verificar roles adicionales
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * REQ-CS-006: Check if user has any of the specified roles.
     */
    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * REQ-CS-007: Check if user has a specific permission.
     */
    public function hasPermission(string $permisoNombre): bool
    {
        // Verificar permisos directos del usuario
        $permisoDirecto = $this->permisos()
            ->where('nombre', $permisoNombre)
            ->wherePivot('activo', true)
            ->where(function($query) {
                $query->whereNull('vigencia_hasta')
                      ->orWhere('vigencia_hasta', '>=', now());
            })
            ->exists();
            
        if ($permisoDirecto) {
            return true;
        }
        
        // Verificar permisos a través de roles
        if ($this->role) {
            if ($this->role->hasPermission($permisoNombre)) {
                return true;
            }
        }
        
        // Verificar permisos a través de roles adicionales
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permisoNombre)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Administrador') || $this->hasRole('Super Administrador');
    }

    /**
     * REQ-CS-008: Asignar rol temporal
     */
    public function asignarRolTemporal($roleId, $vigenciaHasta = null)
    {
        $vigenciaHasta = $vigenciaHasta ?? now()->addDays(30);
        
        $this->roles()->attach($roleId, [
            'vigencia_desde' => now(),
            'vigencia_hasta' => $vigenciaHasta,
            'temporal' => true
        ]);
        
        PistaAuditoria::registrar($this, 'rol_temporal_asignado', [
            'descripcion' => 'Rol temporal asignado a usuario: ' . $this->name,
            'role_id' => $roleId,
            'vigencia_hasta' => $vigenciaHasta
        ]);
    }

    /**
     * REQ-CS-009: Gestionar estado de cuenta
     */
    public function cambiarEstadoCuenta($nuevoEstado, $observaciones = null)
    {
        $estadoAnterior = $this->estado_cuenta;
        
        $this->estado_cuenta = $nuevoEstado;
        $this->active = in_array($nuevoEstado, [self::ESTADO_ACTIVO]);
        
        if ($observaciones) {
            $this->observaciones = $observaciones;
        }
        
        // Manejar estados específicos
        switch ($nuevoEstado) {
            case self::ESTADO_BLOQUEADO:
                $this->bloqueado_hasta = now()->addMinutes(self::TIEMPO_BLOQUEO_MINUTOS);
                break;
                
            case self::ESTADO_VENCIDO:
                $this->fecha_vencimiento_cuenta = now();
                break;
                
            case self::ESTADO_ACTIVO:
                $this->bloqueado_hasta = null;
                $this->intentos_fallidos = 0;
                break;
        }
        
        $this->save();
        
        PistaAuditoria::registrar($this, 'estado_cuenta_cambiado', [
            'descripcion' => 'Estado de cuenta cambiado para usuario: ' . $this->name,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
            'observaciones' => $observaciones
        ]);
    }

    /**
     * REQ-CS-017: Validar política de contraseñas
     */
    public function validarPoliticaPassword($password)
    {
        $errores = [];
        
        // Longitud mínima
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        // Complejidad
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un carácter especial';
        }
        
        // Verificar historial
        if ($this->passwordEnHistorial($password)) {
            $errores[] = 'No puede usar una de las últimas ' . self::HISTORIAL_PASSWORDS . ' contraseñas';
        }
        
        return $errores;
    }

    /**
     * REQ-CS-018: Cambiar contraseña con validaciones
     */
    public function cambiarPassword($passwordActual, $passwordNueva)
    {
        // Verificar contraseña actual
        if (!Hash::check($passwordActual, $this->password)) {
            throw new \Exception('La contraseña actual no es correcta');
        }
        
        // Validar nueva contraseña
        $errores = $this->validarPoliticaPassword($passwordNueva);
        if (!empty($errores)) {
            throw new \Exception('Errores en la nueva contraseña: ' . implode(', ', $errores));
        }
        
        // Actualizar historial
        $historial = $this->historial_passwords ?? [];
        array_unshift($historial, $this->password);
        $historial = array_slice($historial, 0, self::HISTORIAL_PASSWORDS);
        
        // Actualizar contraseña
        $this->password = $passwordNueva;
        $this->historial_passwords = $historial;
        $this->fecha_ultimo_cambio_password = now();
        $this->cambio_password_requerido = false;
        $this->save();
        
        PistaAuditoria::registrar($this, 'password_cambiada', [
            'descripcion' => 'Contraseña cambiada por el usuario: ' . $this->name
        ]);
    }

    /**
     * REQ-CS-019: Verificar si contraseña está en historial
     */
    private function passwordEnHistorial($password)
    {
        $historial = $this->historial_passwords ?? [];
        
        foreach ($historial as $hashAnterior) {
            if (Hash::check($password, $hashAnterior)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * REQ-CS-020: Verificar si contraseña ha vencido
     */
    public function passwordVencida()
    {
        if (!$this->fecha_ultimo_cambio_password) {
            return true; // Si no hay fecha, asumir que está vencida
        }
        
        return $this->fecha_ultimo_cambio_password->addDays(self::DIAS_VIGENCIA_PASSWORD)->isPast();
    }

    /**
     * REQ-CS-022: Registrar intento de acceso fallido
     */
    public function registrarIntentoFallido()
    {
        $this->intentos_fallidos = ($this->intentos_fallidos ?? 0) + 1;
        
        if ($this->intentos_fallidos >= self::MAX_INTENTOS_FALLIDOS) {
            $this->cambiarEstadoCuenta(self::ESTADO_BLOQUEADO, 'Cuenta bloqueada por intentos fallidos');
        }
        
        $this->save();
        
        PistaAuditoria::registrar($this, 'intento_acceso_fallido', [
            'descripcion' => 'Intento de acceso fallido para usuario: ' . $this->name,
            'intentos_totales' => $this->intentos_fallidos,
            'bloqueado' => $this->intentos_fallidos >= self::MAX_INTENTOS_FALLIDOS
        ]);
    }

    /**
     * REQ-CS-023: Registrar acceso exitoso
     */
    public function registrarAccesoExitoso()
    {
        $this->ultimo_acceso = now();
        $this->intentos_fallidos = 0;
        
        // Desbloquear si estaba bloqueado por intentos fallidos
        if ($this->estado_cuenta === self::ESTADO_BLOQUEADO && 
            $this->bloqueado_hasta && 
            $this->bloqueado_hasta->isPast()) {
            $this->cambiarEstadoCuenta(self::ESTADO_ACTIVO);
        }
        
        $this->save();
        
        PistaAuditoria::registrar($this, 'acceso_exitoso', [
            'descripcion' => 'Acceso exitoso de usuario: ' . $this->name
        ]);
    }

    /**
     * REQ-CS-011: Verificar si cuenta está vencida
     */
    public function cuentaVencida()
    {
        return $this->fecha_vencimiento_cuenta && $this->fecha_vencimiento_cuenta->isPast();
    }

    /**
     * Verificar si usuario puede acceder al sistema
     */
    public function puedeAcceder()
    {
        // Verificar estado activo
        if (!$this->active || $this->estado_cuenta !== self::ESTADO_ACTIVO) {
            return false;
        }
        
        // Verificar si está bloqueado
        if ($this->bloqueado_hasta && $this->bloqueado_hasta->isFuture()) {
            return false;
        }
        
        // Verificar si cuenta está vencida
        if ($this->cuentaVencida()) {
            return false;
        }
        
        // Verificar verificación de email
        if (!$this->hasVerifiedEmail()) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtener permisos efectivos del usuario
     */
    public function getPermisosEfectivos()
    {
        $permisos = collect();
        
        // Permisos del rol principal
        if ($this->role) {
            $permisos = $permisos->merge($this->role->permisos);
        }
        
        // Permisos de roles adicionales
        foreach ($this->roles as $role) {
            $permisos = $permisos->merge($role->permisos);
        }
        
        // Permisos directos del usuario
        $permisos = $permisos->merge($this->permisos);
        
        return $permisos->unique('id');
    }

    /**
     * REQ-CS-038: Generar reporte de actividad del usuario
     */
    public function generarReporteActividad($fechaInicio = null, $fechaFin = null)
    {
        $fechaInicio = $fechaInicio ?? now()->subDays(30);
        $fechaFin = $fechaFin ?? now();
        
        $auditoria = $this->auditoria()
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return [
            'usuario' => [
                'id' => $this->id,
                'nombre' => $this->name,
                'email' => $this->email,
                'role' => $this->role->name ?? null,
                'dependencia' => $this->dependencia
            ],
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d')
            ],
            'estadisticas' => [
                'total_acciones' => $auditoria->count(),
                'accesos_exitosos' => $auditoria->where('accion', 'acceso_exitoso')->count(),
                'intentos_fallidos' => $auditoria->where('accion', 'intento_acceso_fallido')->count(),
                'documentos_creados' => $auditoria->where('accion', 'crear')->where('entidad_type', 'like', '%Documento%')->count(),
                'documentos_modificados' => $auditoria->where('accion', 'actualizar')->where('entidad_type', 'like', '%Documento%')->count()
            ],
            'actividades' => $auditoria->map(function ($item) {
                return [
                    'fecha' => $item->created_at->format('Y-m-d H:i:s'),
                    'accion' => $item->accion,
                    'descripcion' => $item->descripcion,
                    'entidad' => $item->entidad_type,
                    'ip' => $item->ip_address
                ];
            })
        ];
    }

    /**
     * Send the email verification notification using our custom Gmail SMTP template.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    /**
     * Send the password reset notification using our custom Gmail SMTP template.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }
}
