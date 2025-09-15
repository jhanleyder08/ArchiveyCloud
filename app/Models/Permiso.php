<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Permiso para SGDEA
 * 
 * Basado en requerimientos de Control y Seguridad:
 * REQ-CS-003: Control granular de permisos
 * REQ-CS-004: Permisos por funcionalidad específica
 * REQ-CS-007: Gestión de permisos por rol
 * REQ-CS-038: Reportes de auditoría de permisos
 */
class Permiso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'subcategoria',
        'recurso',
        'accion',
        'nivel_requerido',
        'activo',
        'sistema',
        'configuracion_adicional',
        'observaciones'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sistema' => 'boolean',
        'configuracion_adicional' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Categorías principales de permisos
    const CATEGORIA_ADMINISTRACION = 'administracion';
    const CATEGORIA_DOCUMENTOS = 'documentos';
    const CATEGORIA_CLASIFICACION = 'clasificacion';
    const CATEGORIA_RETENCION = 'retencion';
    const CATEGORIA_BUSQUEDA = 'busqueda';
    const CATEGORIA_REPORTES = 'reportes';
    const CATEGORIA_SEGURIDAD = 'seguridad';
    const CATEGORIA_AUDITORIA = 'auditoria';
    const CATEGORIA_CONFIGURACION = 'configuracion';
    const CATEGORIA_USUARIOS = 'usuarios';

    // Acciones básicas
    const ACCION_CREAR = 'crear';
    const ACCION_LEER = 'leer';
    const ACCION_ACTUALIZAR = 'actualizar';
    const ACCION_ELIMINAR = 'eliminar';
    const ACCION_EXPORTAR = 'exportar';
    const ACCION_IMPORTAR = 'importar';
    const ACCION_APROBAR = 'aprobar';
    const ACCION_RECHAZAR = 'rechazar';
    const ACCION_ARCHIVAR = 'archivar';

    // Niveles de permiso
    const NIVEL_BASICO = 1;
    const NIVEL_INTERMEDIO = 2;
    const NIVEL_AVANZADO = 3;
    const NIVEL_ADMINISTRADOR = 4;
    const NIVEL_SUPER_ADMINISTRADOR = 5;

    protected static function boot()
    {
        parent::boot();
        
        // Validar antes de eliminar permisos del sistema
        static::deleting(function ($permiso) {
            if ($permiso->sistema) {
                throw new \Exception('No se pueden eliminar permisos del sistema');
            }
            
            // Verificar si está siendo usado por roles o usuarios
            if ($permiso->roles()->exists() || $permiso->usuarios()->exists()) {
                throw new \Exception('No se puede eliminar un permiso que está asignado a roles o usuarios');
            }
        });
        
        // Registrar en auditoría
        static::created(function ($permiso) {
            PistaAuditoria::registrar($permiso, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Permiso creado: ' . $permiso->nombre,
                'categoria' => $permiso->categoria,
                'accion' => $permiso->accion
            ]);
        });
        
        static::updated(function ($permiso) {
            PistaAuditoria::registrar($permiso, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Permiso actualizado: ' . $permiso->nombre,
                'valores_anteriores' => $permiso->getOriginal(),
                'valores_nuevos' => $permiso->getAttributes()
            ]);
        });
    }

    /**
     * Relación con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permisos', 'permiso_id', 'role_id')
                    ->withTimestamps();
    }

    /**
     * Relación con usuarios (permisos directos)
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permisos', 'permiso_id', 'user_id')
                    ->withPivot(['vigencia_desde', 'vigencia_hasta', 'activo'])
                    ->withTimestamps();
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para permisos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para permisos del sistema
     */
    public function scopeSistema($query)
    {
        return $query->where('sistema', true);
    }

    /**
     * Scope por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope por subcategoría
     */
    public function scopePorSubcategoria($query, $subcategoria)
    {
        return $query->where('subcategoria', $subcategoria);
    }

    /**
     * Scope por acción
     */
    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope por recurso
     */
    public function scopePorRecurso($query, $recurso)
    {
        return $query->where('recurso', $recurso);
    }

    /**
     * Scope por nivel requerido
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel_requerido', '<=', $nivel);
    }

    /**
     * Validar datos del permiso
     */
    public function validar()
    {
        $errores = [];
        
        // Validar nombre único
        $existente = static::where('nombre', $this->nombre)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe un permiso con el nombre: ' . $this->nombre;
        }
        
        // Validar que no se modifiquen permisos del sistema
        if ($this->sistema && $this->isDirty(['nombre', 'categoria', 'accion', 'recurso'])) {
            $errores[] = 'No se pueden modificar los datos principales de permisos del sistema';
        }
        
        // Validar categoría
        $categoriasValidas = [
            self::CATEGORIA_ADMINISTRACION,
            self::CATEGORIA_DOCUMENTOS,
            self::CATEGORIA_CLASIFICACION,
            self::CATEGORIA_RETENCION,
            self::CATEGORIA_BUSQUEDA,
            self::CATEGORIA_REPORTES,
            self::CATEGORIA_SEGURIDAD,
            self::CATEGORIA_AUDITORIA,
            self::CATEGORIA_CONFIGURACION,
            self::CATEGORIA_USUARIOS
        ];
        
        if (!in_array($this->categoria, $categoriasValidas)) {
            $errores[] = 'La categoría no es válida';
        }
        
        // Validar acción
        $accionesValidas = [
            self::ACCION_CREAR,
            self::ACCION_LEER,
            self::ACCION_ACTUALIZAR,
            self::ACCION_ELIMINAR,
            self::ACCION_EXPORTAR,
            self::ACCION_IMPORTAR,
            self::ACCION_APROBAR,
            self::ACCION_RECHAZAR,
            self::ACCION_ARCHIVAR
        ];
        
        if (!in_array($this->accion, $accionesValidas)) {
            $errores[] = 'La acción no es válida';
        }
        
        // Validar nivel requerido
        if ($this->nivel_requerido < 1 || $this->nivel_requerido > 5) {
            $errores[] = 'El nivel requerido debe estar entre 1 y 5';
        }
        
        return $errores;
    }

    /**
     * Crear permisos del sistema por defecto
     */
    public static function crearPermisosSistema()
    {
        $permisosSistema = [
            // Administración General
            [
                'nombre' => 'administracion.dashboard.ver',
                'descripcion' => 'Ver dashboard administrativo',
                'categoria' => self::CATEGORIA_ADMINISTRACION,
                'subcategoria' => 'dashboard',
                'recurso' => 'dashboard',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],
            [
                'nombre' => 'administracion.configuracion.gestionar',
                'descripcion' => 'Gestionar configuración del sistema',
                'categoria' => self::CATEGORIA_ADMINISTRACION,
                'subcategoria' => 'configuracion',
                'recurso' => 'configuracion',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_SUPER_ADMINISTRADOR,
                'sistema' => true
            ],

            // Gestión de Usuarios
            [
                'nombre' => 'usuarios.crear',
                'descripcion' => 'Crear nuevos usuarios',
                'categoria' => self::CATEGORIA_USUARIOS,
                'recurso' => 'users',
                'accion' => self::ACCION_CREAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],
            [
                'nombre' => 'usuarios.ver',
                'descripcion' => 'Ver información de usuarios',
                'categoria' => self::CATEGORIA_USUARIOS,
                'recurso' => 'users',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],
            [
                'nombre' => 'usuarios.editar',
                'descripcion' => 'Editar información de usuarios',
                'categoria' => self::CATEGORIA_USUARIOS,
                'recurso' => 'users',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],
            [
                'nombre' => 'usuarios.eliminar',
                'descripcion' => 'Eliminar usuarios',
                'categoria' => self::CATEGORIA_USUARIOS,
                'recurso' => 'users',
                'accion' => self::ACCION_ELIMINAR,
                'nivel_requerido' => self::NIVEL_SUPER_ADMINISTRADOR,
                'sistema' => true
            ],

            // Gestión de Roles
            [
                'nombre' => 'roles.gestionar',
                'descripcion' => 'Gestionar roles del sistema',
                'categoria' => self::CATEGORIA_SEGURIDAD,
                'subcategoria' => 'roles',
                'recurso' => 'roles',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],

            // TRD - Tabla de Retención Documental
            [
                'nombre' => 'trd.crear',
                'descripcion' => 'Crear Tablas de Retención Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'trd',
                'recurso' => 'trd',
                'accion' => self::ACCION_CREAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],
            [
                'nombre' => 'trd.ver',
                'descripcion' => 'Ver Tablas de Retención Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'trd',
                'recurso' => 'trd',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'trd.editar',
                'descripcion' => 'Editar Tablas de Retención Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'trd',
                'recurso' => 'trd',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],
            [
                'nombre' => 'trd.aprobar',
                'descripcion' => 'Aprobar Tablas de Retención Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'trd',
                'recurso' => 'trd',
                'accion' => self::ACCION_APROBAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],
            [
                'nombre' => 'trd.exportar',
                'descripcion' => 'Exportar Tablas de Retención Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'trd',
                'recurso' => 'trd',
                'accion' => self::ACCION_EXPORTAR,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],

            // CCD - Cuadro de Clasificación Documental
            [
                'nombre' => 'ccd.crear',
                'descripcion' => 'Crear elementos del Cuadro de Clasificación',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'ccd',
                'recurso' => 'ccd',
                'accion' => self::ACCION_CREAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],
            [
                'nombre' => 'ccd.ver',
                'descripcion' => 'Ver Cuadro de Clasificación Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'ccd',
                'recurso' => 'ccd',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'ccd.editar',
                'descripcion' => 'Editar Cuadro de Clasificación Documental',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'ccd',
                'recurso' => 'ccd',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],

            // Series Documentales
            [
                'nombre' => 'series.crear',
                'descripcion' => 'Crear Series Documentales',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'series',
                'recurso' => 'series',
                'accion' => self::ACCION_CREAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],
            [
                'nombre' => 'series.ver',
                'descripcion' => 'Ver Series Documentales',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'series',
                'recurso' => 'series',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'series.editar',
                'descripcion' => 'Editar Series Documentales',
                'categoria' => self::CATEGORIA_CLASIFICACION,
                'subcategoria' => 'series',
                'recurso' => 'series',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],

            // Documentos
            [
                'nombre' => 'documentos.crear',
                'descripcion' => 'Crear y cargar documentos',
                'categoria' => self::CATEGORIA_DOCUMENTOS,
                'recurso' => 'documentos',
                'accion' => self::ACCION_CREAR,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'documentos.ver',
                'descripcion' => 'Ver documentos',
                'categoria' => self::CATEGORIA_DOCUMENTOS,
                'recurso' => 'documentos',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'documentos.editar',
                'descripcion' => 'Editar metadatos de documentos',
                'categoria' => self::CATEGORIA_DOCUMENTOS,
                'recurso' => 'documentos',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],
            [
                'nombre' => 'documentos.eliminar',
                'descripcion' => 'Eliminar documentos',
                'categoria' => self::CATEGORIA_DOCUMENTOS,
                'recurso' => 'documentos',
                'accion' => self::ACCION_ELIMINAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],

            // Búsqueda
            [
                'nombre' => 'busqueda.basica',
                'descripcion' => 'Realizar búsquedas básicas',
                'categoria' => self::CATEGORIA_BUSQUEDA,
                'subcategoria' => 'basica',
                'recurso' => 'busqueda',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_BASICO,
                'sistema' => true
            ],
            [
                'nombre' => 'busqueda.avanzada',
                'descripcion' => 'Realizar búsquedas avanzadas',
                'categoria' => self::CATEGORIA_BUSQUEDA,
                'subcategoria' => 'avanzada',
                'recurso' => 'busqueda',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],

            // Reportes
            [
                'nombre' => 'reportes.generar',
                'descripcion' => 'Generar reportes del sistema',
                'categoria' => self::CATEGORIA_REPORTES,
                'recurso' => 'reportes',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],
            [
                'nombre' => 'reportes.exportar',
                'descripcion' => 'Exportar reportes',
                'categoria' => self::CATEGORIA_REPORTES,
                'recurso' => 'reportes',
                'accion' => self::ACCION_EXPORTAR,
                'nivel_requerido' => self::NIVEL_INTERMEDIO,
                'sistema' => true
            ],

            // Auditoría
            [
                'nombre' => 'auditoria.ver',
                'descripcion' => 'Ver pistas de auditoría',
                'categoria' => self::CATEGORIA_AUDITORIA,
                'recurso' => 'auditoria',
                'accion' => self::ACCION_LEER,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],
            [
                'nombre' => 'auditoria.exportar',
                'descripcion' => 'Exportar pistas de auditoría',
                'categoria' => self::CATEGORIA_AUDITORIA,
                'recurso' => 'auditoria',
                'accion' => self::ACCION_EXPORTAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],

            // Retención y Disposición
            [
                'nombre' => 'retencion.gestionar',
                'descripcion' => 'Gestionar políticas de retención',
                'categoria' => self::CATEGORIA_RETENCION,
                'recurso' => 'retencion',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_AVANZADO,
                'sistema' => true
            ],
            [
                'nombre' => 'disposicion.ejecutar',
                'descripcion' => 'Ejecutar disposiciones finales',
                'categoria' => self::CATEGORIA_RETENCION,
                'subcategoria' => 'disposicion',
                'recurso' => 'disposicion',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_ADMINISTRADOR,
                'sistema' => true
            ],

            // Seguridad
            [
                'nombre' => 'seguridad.configurar',
                'descripcion' => 'Configurar políticas de seguridad',
                'categoria' => self::CATEGORIA_SEGURIDAD,
                'recurso' => 'seguridad',
                'accion' => self::ACCION_ACTUALIZAR,
                'nivel_requerido' => self::NIVEL_SUPER_ADMINISTRADOR,
                'sistema' => true
            ]
        ];
        
        foreach ($permisosSistema as $permisoData) {
            $existente = static::where('nombre', $permisoData['nombre'])->first();
            if (!$existente) {
                static::create($permisoData);
            }
        }
    }

    /**
     * Verificar si el usuario puede ejecutar este permiso
     */
    public function puedeEjecutar(User $usuario)
    {
        // Verificar si el usuario tiene el permiso directamente
        $permisoDirecto = $usuario->permisos()
            ->where('permiso_id', $this->id)
            ->wherePivot('activo', true)
            ->where(function($query) {
                $query->whereNull('vigencia_hasta')
                      ->orWhere('vigencia_hasta', '>=', now());
            })
            ->exists();
            
        if ($permisoDirecto) {
            return true;
        }
        
        // Verificar si el usuario tiene el permiso a través de sus roles
        return $usuario->roles()->whereHas('permisos', function ($query) {
            $query->where('permiso_id', $this->id);
        })->exists();
    }

    /**
     * Obtener todos los usuarios que tienen este permiso
     */
    public function getUsuariosConPermiso()
    {
        $usuariosDirectos = $this->usuarios;
        
        $usuariosPorRoles = User::whereHas('roles', function ($query) {
            $query->whereHas('permisos', function ($subQuery) {
                $subQuery->where('permiso_id', $this->id);
            });
        })->get();
        
        return $usuariosDirectos->merge($usuariosPorRoles)->unique('id');
    }

    /**
     * Obtener estadísticas del permiso
     */
    public function getEstadisticas()
    {
        return [
            'usuarios_directos' => $this->usuarios()->count(),
            'roles_asignados' => $this->roles()->count(),
            'usuarios_por_roles' => User::whereHas('roles', function ($query) {
                $query->whereHas('permisos', function ($subQuery) {
                    $subQuery->where('permiso_id', $this->id);
                });
            })->count(),
            'total_usuarios_con_permiso' => $this->getUsuariosConPermiso()->count(),
            'categoria' => $this->categoria,
            'nivel_requerido' => $this->nivel_requerido,
            'es_sistema' => $this->sistema,
            'uso_reciente' => $this->auditoria()
                ->where('accion', 'like', '%' . $this->accion . '%')
                ->where('created_at', '>=', now()->subDays(30))
                ->count()
        ];
    }

    /**
     * Generar nombre de permiso basado en patrón
     */
    public static function generarNombre($categoria, $recurso, $accion, $subcategoria = null)
    {
        $partes = [$categoria];
        
        if ($subcategoria) {
            $partes[] = $subcategoria;
        }
        
        $partes[] = $recurso;
        $partes[] = $accion;
        
        return implode('.', $partes);
    }

    /**
     * Crear permiso personalizado
     */
    public static function crearPermiso($nombre, $descripcion, $categoria, $recurso, $accion, $nivelRequerido = self::NIVEL_BASICO)
    {
        $permiso = new static();
        $permiso->nombre = $nombre;
        $permiso->descripcion = $descripcion;
        $permiso->categoria = $categoria;
        $permiso->recurso = $recurso;
        $permiso->accion = $accion;
        $permiso->nivel_requerido = $nivelRequerido;
        $permiso->activo = true;
        $permiso->sistema = false;
        
        // Validar antes de crear
        $errores = $permiso->validar();
        if (!empty($errores)) {
            throw new \Exception('Errores al crear permiso: ' . implode(', ', $errores));
        }
        
        $permiso->save();
        
        return $permiso;
    }

    /**
     * Exportar configuración del permiso
     */
    public function exportarConfiguracion($formato = 'json')
    {
        $data = [
            'permiso' => [
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'categoria' => $this->categoria,
                'subcategoria' => $this->subcategoria,
                'recurso' => $this->recurso,
                'accion' => $this->accion,
                'nivel_requerido' => $this->nivel_requerido,
                'activo' => $this->activo,
                'sistema' => $this->sistema,
                'configuracion_adicional' => $this->configuracion_adicional
            ],
            'asignaciones' => [
                'roles' => $this->roles->map(function ($role) {
                    return [
                        'nombre' => $role->name,
                        'descripcion' => $role->description,
                        'nivel_jerarquico' => $role->nivel_jerarquico
                    ];
                }),
                'usuarios_directos' => $this->usuarios->map(function ($user) {
                    return [
                        'nombre' => $user->name,
                        'email' => $user->email,
                        'vigencia_desde' => $user->pivot->vigencia_desde,
                        'vigencia_hasta' => $user->pivot->vigencia_hasta
                    ];
                })
            ],
            'estadisticas' => $this->getEstadisticas(),
            'fecha_exportacion' => now()->toISOString()
        ];
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($data, 'configuracion_permiso');
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
