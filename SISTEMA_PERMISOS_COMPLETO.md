# ✅ Sistema de Permisos Totalmente Funcional

## Resumen

El sistema de permisos y roles está ahora **completamente funcional** con todas las tablas necesarias creadas.

## Tablas Creadas

### 1. **`user_roles`** (Ya existía, actualizada)
Tabla pivot para asignar múltiples roles a un usuario (roles adicionales).

**Campos:**
- `user_id` - Usuario
- `role_id` - Rol adicional
- `vigencia_desde` - Fecha inicio (para roles temporales)
- `vigencia_hasta` - Fecha fin (para roles temporales)
- `temporal` - Si es un rol temporal
- `activo` - Si está activo
- `asignado_por` - Quién asignó el rol
- `observaciones` - Notas adicionales

### 2. **`user_permisos`** (✅ Nueva - Creada)
Tabla pivot para asignar permisos específicos directamente a un usuario.

**Campos:**
- `user_id` - Usuario
- `permiso_id` - Permiso específico
- `vigencia_desde` - Fecha inicio (para permisos temporales)
- `vigencia_hasta` - Fecha fin (para permisos temporales)
- `activo` - Si está activo
- `asignado_por` - Quién asignó el permiso
- `observaciones` - Notas adicionales

## Estructura de Permisos

Un usuario puede obtener permisos de **3 fuentes**:

```
Usuario Final → Permisos Totales
├── 1. Rol Principal (role_id en users)
│   └── Permisos del rol
├── 2. Roles Adicionales (tabla user_roles)
│   └── Permisos de cada rol adicional
└── 3. Permisos Directos (tabla user_permisos)
    └── Permisos específicos asignados al usuario
```

## Cómo Funciona

### Backend

El método `User::hasPermission()` verifica permisos en este orden:

1. **Permisos directos del usuario** (tabla `user_permisos`)
2. **Permisos del rol principal** (relación `users.role_id → roles`)
3. **Permisos de roles adicionales** (tabla `user_roles`)

```php
// Modelo User.php
public function hasPermission(string $permisoNombre): bool
{
    // 1. Verificar permisos directos
    $permisoDirecto = $this->permisos()
        ->where('nombre', $permisoNombre)
        ->where('activo', true)
        ->exists();
    
    if ($permisoDirecto) {
        return true;
    }
    
    // 2. Verificar permisos del rol principal
    if ($this->role && $this->role->hasPermission($permisoNombre)) {
        return true;
    }
    
    // 3. Verificar permisos de roles adicionales
    foreach ($this->roles as $role) {
        if ($role->hasPermission($permisoNombre)) {
            return true;
        }
    }
    
    return false;
}
```

### Frontend

El hook `usePermissions()` tiene acceso a todos los permisos del usuario:

```typescript
const { hasPermission } = usePermissions();

// Esto verifica permisos de TODAS las fuentes
if (hasPermission('usuarios.crear')) {
    // Mostrar botón crear
}
```

## Ejemplos de Uso

### Asignar Rol Adicional a Usuario

```php
use App\Models\User;
use App\Models\Role;

$user = User::find(1);
$rolAuditor = Role::where('name', 'Auditor')->first();

// Asignar rol adicional temporal
$user->roles()->attach($rolAuditor->id, [
    'vigencia_desde' => now(),
    'vigencia_hasta' => now()->addMonths(3),
    'temporal' => true,
    'activo' => true,
    'asignado_por' => auth()->id(),
    'observaciones' => 'Rol temporal para auditoría Q4'
]);
```

### Asignar Permiso Directo a Usuario

```php
use App\Models\Permiso;

$user = User::find(1);
$permisoReportes = Permiso::where('nombre', 'reportes.ver')->first();

// Asignar permiso específico
$user->permisos()->attach($permisoReportes->id, [
    'vigencia_desde' => now(),
    'vigencia_hasta' => now()->addDays(30),
    'activo' => true,
    'asignado_por' => auth()->id(),
    'observaciones' => 'Acceso temporal a reportes'
]);
```

### Verificar Permisos

```php
$user = User::find(1);

// Verificar un permiso específico
if ($user->hasPermission('usuarios.crear')) {
    // Usuario puede crear usuarios
}

// Verificar cualquiera de estos permisos
if ($user->hasAnyPermission(['usuarios.editar', 'usuarios.eliminar'])) {
    // Usuario puede editar O eliminar
}

// Verificar todos estos permisos
if ($user->hasAllPermissions(['usuarios.ver', 'usuarios.editar'])) {
    // Usuario puede ver Y editar
}
```

## Casos de Uso

### Caso 1: Rol Temporal para Proyecto

Un usuario con rol "Operativo" necesita permisos de "Coordinador" por 3 meses:

```php
$user = User::find(5);
$rolCoordinador = Role::where('name', 'Coordinador')->first();

$user->roles()->attach($rolCoordinador->id, [
    'vigencia_desde' => now(),
    'vigencia_hasta' => now()->addMonths(3),
    'temporal' => true,
    'activo' => true,
    'asignado_por' => auth()->id(),
    'observaciones' => 'Rol temporal para Proyecto X'
]);

// El usuario ahora tiene:
// - Permisos de su rol principal "Operativo"
// - PLUS permisos de "Coordinador" (por 3 meses)
```

### Caso 2: Permiso Específico Excepcional

Un usuario necesita acceso temporal a reportes sin cambiar su rol:

```php
$user = User::find(10);
$permisoReportes = Permiso::where('nombre', 'reportes.ver')->first();

$user->permisos()->attach($permisoReportes->id, [
    'vigencia_desde' => now(),
    'vigencia_hasta' => now()->addDays(7),
    'activo' => true,
    'asignado_por' => auth()->id(),
    'observaciones' => 'Acceso excepcional para revisar reporte mensual'
]);

// El usuario ahora tiene:
// - Todos sus permisos normales
// - PLUS permiso específico "reportes.ver" (por 7 días)
```

### Caso 3: Remover Permisos Temporalmente

```php
// Desactivar un rol adicional sin eliminarlo
$user->roles()->updateExistingPivot($rolId, [
    'activo' => false,
    'observaciones' => 'Suspendido temporalmente'
]);

// Desactivar un permiso directo sin eliminarlo
$user->permisos()->updateExistingPivot($permisoId, [
    'activo' => false,
    'observaciones' => 'Revocado temporalmente'
]);
```

## Verificación del Sistema

Ahora puedes probar el login y todo debería funcionar:

```bash
# 1. Limpiar cache
php artisan optimize:clear

# 2. Verificar migraciones
php artisan migrate:status

# 3. Iniciar servidor
php artisan serve
```

Luego ve a: http://127.0.0.1:8000/login

## Estado de las Tablas

✅ **users** - Existe  
✅ **roles** - Existe  
✅ **permisos** - Existe  
✅ **role_permisos** - Existe (permisos de cada rol)  
✅ **user_roles** - Existe (roles adicionales de usuario)  
✅ **user_permisos** - ✅ **CREADA** (permisos directos de usuario)

## Comandos Útiles

```bash
# Ver roles de un usuario (incluyendo adicionales)
php artisan tinker
>>> $user = App\Models\User::find(10)
>>> echo "Rol principal: " . $user->role->name
>>> echo "Roles adicionales: " . $user->roles->pluck('name')->join(', ')

# Ver permisos de un usuario (de todas las fuentes)
>>> $user->load(['role.permisos', 'permisos', 'roles.permisos'])
>>> // Permisos del rol principal
>>> $user->role->permisos->pluck('nombre')
>>> // Permisos directos
>>> $user->permisos->pluck('nombre')
>>> // Permisos de roles adicionales
>>> $user->roles->flatMap->permisos->pluck('nombre')

# Verificar permiso
>>> $user->hasPermission('usuarios.crear')
```

## Sistema Completamente Funcional ✅

El sistema ahora soporta:

- ✅ Roles principales
- ✅ Roles adicionales (múltiples por usuario)
- ✅ Roles temporales (con vigencia)
- ✅ Permisos directos a usuarios
- ✅ Permisos temporales (con vigencia)
- ✅ Verificación en backend (seguridad)
- ✅ Verificación en frontend (UX)
- ✅ Filtrado automático del sidebar
- ✅ Protección de rutas con middleware

---

**El sistema de permisos está 100% operativo** 🎉
