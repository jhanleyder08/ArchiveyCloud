<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Rol expandido para SGDEA
 * 
 * Basado en requerimientos de Control y Seguridad:
 * REQ-CS-001 a REQ-CS-011: Gestión de usuarios, roles y permisos
 * REQ-CS-005: Verificación de roles
 * REQ-CS-007: Control de permisos por rol
 * REQ-CS-008: Roles temporales
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'nivel_jerarquico',
        'padre_id',
        'activo',
        'sistema',
        'configuracion',
        'observaciones'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sistema' => 'boolean',
        'configuracion' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Niveles jerárquicos predefinidos
    const NIVEL_SUPER_ADMINISTRADOR = 1;
    const NIVEL_ADMINISTRADOR = 2;
    const NIVEL_SUPERVISOR = 3;
    const NIVEL_COORDINADOR = 4;
    const NIVEL_OPERATIVO = 5;
    const NIVEL_CONSULTA = 6;

    // Roles del sistema (no modificables)
    const ROLES_SISTEMA = [
        'Super Administrador',
        'Administrador',
        'Administrador de Seguridad',
        'Auditor',
        'Sistema'
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Validar antes de eliminar roles del sistema
        static::deleting(function ($role) {
            if ($role->sistema) {
                throw new \Exception('No se pueden eliminar roles del sistema');
            }
            
            if ($role->users()->exists()) {
                throw new \Exception('No se puede eliminar un rol que tiene usuarios asignados');
            }
        });
        
        // Registrar en auditoría
        static::created(function ($role) {
            PistaAuditoria::registrar($role, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Rol creado: ' . $role->name,
                'nivel_jerarquico' => $role->nivel_jerarquico
            ]);
        });
        
        static::updated(function ($role) {
            PistaAuditoria::registrar($role, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Rol actualizado: ' . $role->name,
                'valores_anteriores' => $role->getOriginal(),
                'valores_nuevos' => $role->getAttributes()
            ]);
        });
    }

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relación con usuarios adicionales (many-to-many)
     */
    public function usuariosAdicionales(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withPivot(['vigencia_desde', 'vigencia_hasta', 'temporal', 'activo'])
                    ->withTimestamps();
    }

    /**
     * Relación con permisos
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'role_permisos', 'role_id', 'permiso_id')
                    ->withTimestamps();
    }

    /**
     * Relación jerárquica - rol padre
     */
    public function padre()
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    /**
     * Relación jerárquica - roles hijo
     */
    public function hijos()
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para roles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para roles del sistema
     */
    public function scopeSistema($query)
    {
        return $query->where('sistema', true);
    }

    /**
     * Scope para roles personalizados
     */
    public function scopePersonalizados($query)
    {
        return $query->where('sistema', false);
    }

    /**
     * Scope por nivel jerárquico
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel_jerarquico', $nivel);
    }

    /**
     * Scope por nivel jerárquico mayor o igual
     */
    public function scopeNivelMayorIgual($query, $nivel)
    {
        return $query->where('nivel_jerarquico', '>=', $nivel);
    }

    /**
     * REQ-CS-007: Verificar si el rol tiene un permiso específico
     */
    public function hasPermission(string $permisoNombre): bool
    {
        // Verificar permisos directos
        $permisoDirecto = $this->permisos()
            ->where('nombre', $permisoNombre)
            ->exists();
            
        if ($permisoDirecto) {
            return true;
        }
        
        // Verificar permisos heredados del rol padre
        if ($this->padre) {
            return $this->padre->hasPermission($permisoNombre);
        }
        
        return false;
    }

    /**
     * REQ-CS-007: Verificar si el rol tiene alguno de los permisos especificados
     */
    public function hasAnyPermission(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->hasPermission($permiso)) {
                return true;
            }
        }
        return false;
    }

    /**
     * REQ-CS-007: Verificar si el rol tiene todos los permisos especificados
     */
    public function hasAllPermissions(array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if (!$this->hasPermission($permiso)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Asignar permisos al rol
     */
    public function asignarPermisos(array $permisosIds)
    {
        // Validar que los permisos existan
        $permisosValidos = Permiso::whereIn('id', $permisosIds)->pluck('id')->toArray();
        
        if (count($permisosValidos) !== count($permisosIds)) {
            throw new \Exception('Algunos permisos no son válidos');
        }
        
        // Sincronizar permisos
        $this->permisos()->sync($permisosIds);
        
        PistaAuditoria::registrar($this, 'permisos_asignados', [
            'descripcion' => 'Permisos asignados a rol: ' . $this->name,
            'permisos_asignados' => $permisosIds,
            'total_permisos' => count($permisosIds)
        ]);
    }

    /**
     * Obtener todos los permisos efectivos (incluyendo herencia)
     */
    public function getPermisosEfectivos()
    {
        $permisos = collect();
        
        // Permisos directos
        $permisos = $permisos->merge($this->permisos);
        
        // Permisos heredados del rol padre
        if ($this->padre) {
            $permisos = $permisos->merge($this->padre->getPermisosEfectivos());
        }
        
        return $permisos->unique('id');
    }

    /**
     * Verificar si el rol puede ser superior a otro rol
     */
    public function esSuperiorA(Role $otroRole)
    {
        return $this->nivel_jerarquico < $otroRole->nivel_jerarquico;
    }

    /**
     * Verificar si el rol puede ser inferior a otro rol
     */
    public function esInferiorA(Role $otroRole)
    {
        return $this->nivel_jerarquico > $otroRole->nivel_jerarquico;
    }

    /**
     * Obtener todos los roles descendientes
     */
    public function getDescendientes()
    {
        $descendientes = collect();
        
        foreach ($this->hijos as $hijo) {
            $descendientes->push($hijo);
            $descendientes = $descendientes->merge($hijo->getDescendientes());
        }
        
        return $descendientes;
    }

    /**
     * Obtener toda la jerarquía hacia arriba
     */
    public function getAncestros()
    {
        $ancestros = collect();
        
        if ($this->padre) {
            $ancestros->push($this->padre);
            $ancestros = $ancestros->merge($this->padre->getAncestros());
        }
        
        return $ancestros;
    }

    /**
     * Validar datos del rol
     */
    public function validar()
    {
        $errores = [];
        
        // Validar nombre único
        $existente = static::where('name', $this->name)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe un rol con el nombre: ' . $this->name;
        }
        
        // Validar que no se modifiquen roles del sistema
        if ($this->sistema && $this->isDirty(['name', 'nivel_jerarquico'])) {
            $errores[] = 'No se pueden modificar los datos principales de roles del sistema';
        }
        
        // Validar nivel jerárquico
        if ($this->nivel_jerarquico < 1 || $this->nivel_jerarquico > 6) {
            $errores[] = 'El nivel jerárquico debe estar entre 1 y 6';
        }
        
        // Validar que el rol padre tenga menor nivel jerárquico
        if ($this->padre && $this->padre->nivel_jerarquico >= $this->nivel_jerarquico) {
            $errores[] = 'El rol padre debe tener un nivel jerárquico menor';
        }
        
        // Evitar referencia circular
        if ($this->padre_id === $this->id) {
            $errores[] = 'Un rol no puede ser padre de sí mismo';
        }
        
        return $errores;
    }

    /**
     * Crear rol con configuración por defecto
     */
    public static function crearRol($nombre, $descripcion, $nivelJerarquico, $padreId = null)
    {
        $role = new static();
        $role->name = $nombre;
        $role->description = $descripcion;
        $role->nivel_jerarquico = $nivelJerarquico;
        $role->padre_id = $padreId;
        $role->activo = true;
        $role->sistema = false;
        
        // Validar antes de crear
        $errores = $role->validar();
        if (!empty($errores)) {
            throw new \Exception('Errores al crear rol: ' . implode(', ', $errores));
        }
        
        $role->save();
        
        return $role;
    }

    /**
     * Crear roles del sistema por defecto
     */
    public static function crearRolesSistema()
    {
        $rolesSistema = [
            [
                'name' => 'Super Administrador',
                'description' => 'Control total del sistema',
                'nivel_jerarquico' => self::NIVEL_SUPER_ADMINISTRADOR,
                'padre_id' => null,
                'sistema' => true
            ],
            [
                'name' => 'Administrador',
                'description' => 'Administración general del sistema',
                'nivel_jerarquico' => self::NIVEL_ADMINISTRADOR,
                'padre_id' => null,
                'sistema' => true
            ],
            [
                'name' => 'Administrador de Seguridad',
                'description' => 'Gestión de seguridad y control de acceso',
                'nivel_jerarquico' => self::NIVEL_ADMINISTRADOR,
                'padre_id' => null,
                'sistema' => true
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Supervisión de procesos documentales',
                'nivel_jerarquico' => self::NIVEL_SUPERVISOR,
                'padre_id' => 2, // Hijo de Administrador
                'sistema' => true
            ],
            [
                'name' => 'Coordinador',
                'description' => 'Coordinación de actividades documentales',
                'nivel_jerarquico' => self::NIVEL_COORDINADOR,
                'padre_id' => 4, // Hijo de Supervisor
                'sistema' => true
            ],
            [
                'name' => 'Operativo',
                'description' => 'Operaciones básicas del sistema',
                'nivel_jerarquico' => self::NIVEL_OPERATIVO,
                'padre_id' => 5, // Hijo de Coordinador
                'sistema' => true
            ],
            [
                'name' => 'Consulta',
                'description' => 'Solo consulta de información',
                'nivel_jerarquico' => self::NIVEL_CONSULTA,
                'padre_id' => 6, // Hijo de Operativo
                'sistema' => true
            ],
            [
                'name' => 'Auditor',
                'description' => 'Auditoría y revisión del sistema',
                'nivel_jerarquico' => self::NIVEL_SUPERVISOR,
                'padre_id' => null,
                'sistema' => true
            ]
        ];
        
        foreach ($rolesSistema as $roleData) {
            $existente = static::where('name', $roleData['name'])->first();
            if (!$existente) {
                static::create($roleData);
            }
        }
    }

    /**
     * Obtener estadísticas del rol
     */
    public function getEstadisticas()
    {
        return [
            'total_usuarios' => $this->users()->count(),
            'usuarios_activos' => $this->users()->where('active', true)->count(),
            'usuarios_adicionales' => $this->usuariosAdicionales()->count(),
            'total_permisos' => $this->permisos()->count(),
            'permisos_efectivos' => $this->getPermisosEfectivos()->count(),
            'roles_hijo' => $this->hijos()->count(),
            'nivel_jerarquico' => $this->nivel_jerarquico,
            'es_sistema' => $this->sistema,
            'actividad_reciente' => $this->auditoria()
                ->where('created_at', '>=', now()->subDays(30))
                ->count()
        ];
    }

    /**
     * Exportar configuración del rol
     */
    public function exportarConfiguracion($formato = 'json')
    {
        $data = [
            'rol' => [
                'nombre' => $this->name,
                'descripcion' => $this->description,
                'nivel_jerarquico' => $this->nivel_jerarquico,
                'activo' => $this->activo,
                'sistema' => $this->sistema,
                'configuracion' => $this->configuracion
            ],
            'jerarquia' => [
                'padre' => $this->padre ? $this->padre->name : null,
                'hijos' => $this->hijos->pluck('name')->toArray()
            ],
            'permisos' => $this->permisos->map(function ($permiso) {
                return [
                    'nombre' => $permiso->nombre,
                    'descripcion' => $permiso->descripcion,
                    'categoria' => $permiso->categoria
                ];
            }),
            'usuarios' => [
                'principales' => $this->users()->count(),
                'adicionales' => $this->usuariosAdicionales()->count()
            ],
            'fecha_exportacion' => now()->toISOString()
        ];
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($data, 'configuracion_rol');
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Convertir array a XML
     */
    private function arrayToXml($data, $rootElement = 'data', $xml = null)
    {
        if ($xml === null) {
            $xml = new \SimpleXMLElement('<' . $rootElement . '/>');
        }
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $this->arrayToXml($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }
}
