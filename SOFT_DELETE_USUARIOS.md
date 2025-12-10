# Soft Delete de Usuarios - Implementación Completada

## Problema Resuelto

**Error Original:**
```
#1451 - Cannot delete or update a parent row: a foreign key constraint fails 
(`archivey_cloud`.`disposicion_finals`, CONSTRAINT `disposicion_finals_responsable_id_foreign` 
FOREIGN KEY (`responsable_id`) REFERENCES `users` (`id`))
```

## Solución Implementada

### 1. Soft Delete Activado

El modelo `User` ya tenía configurado **Soft Delete**, lo que significa que:

✅ **NO se elimina físicamente** el registro de la base de datos  
✅ **Se marca como eliminado** usando la columna `deleted_at`  
✅ **Se mantiene el historial** de quién creó documentos, expedientes, etc.  
✅ **Se permite reutilizar el email** para crear nuevos usuarios  
✅ **No rompe foreign keys** porque el registro sigue existiendo  

### 2. Validación de Email Único

Las validaciones ya están configuradas correctamente:

**Crear Usuario (`store`):**
```php
'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL'
```

**Actualizar Usuario (`update`):**
```php
'email' => 'required|string|email|max:255|unique:users,email,' . $user->id . ',id,deleted_at,NULL'
```

Esto significa:
- ✅ Permite crear usuario con email de usuario eliminado
- ✅ Valida que no exista en usuarios activos
- ✅ Ignora usuarios con `deleted_at` no nulo

### 3. Estadísticas Actualizadas

El controlador ahora muestra:

```php
$stats = [
    'total' => User::count(),                    // Solo activos
    'active' => User::whereNotNull('email_verified_at')
                    ->where('active', true)->count(),
    'pending' => User::whereNull('email_verified_at')->count(),
    'without_role' => User::whereNull('role_id')->count(),
    'deleted' => User::onlyTrashed()->count(),   // Usuarios eliminados
];
```

### 4. Interfaz Actualizada

**Nueva tarjeta en `/admin/users`:**

```tsx
{stats.deleted > 0 && (
    <div className="bg-white rounded-lg border border-gray-200 p-6">
        <div className="flex items-center justify-between">
            <div>
                <p className="text-sm text-gray-600">Eliminados</p>
                <p className="text-2xl font-semibold text-gray-700">{stats.deleted}</p>
                <p className="text-xs text-gray-500 mt-1">Sus correos pueden reutilizarse</p>
            </div>
            <div className="p-3 bg-gray-100 rounded-full">
                <Trash2 className="h-6 w-6 text-gray-600" />
            </div>
        </div>
    </div>
)}
```

### 5. Mensaje de Confirmación Mejorado

Al eliminar un usuario, ahora muestra:

```
Usuario eliminado exitosamente. El correo electrónico puede ser reutilizado para crear un nuevo usuario.
```

## Flujo de Trabajo

### Escenario 1: Eliminar Usuario con Relaciones

1. Usuario `juan@empresa.com` creó documentos, expedientes, etc.
2. Admin elimina el usuario desde `/admin/users`
3. **Resultado:**
   - Usuario marcado como eliminado (`deleted_at = now()`)
   - Sus documentos siguen vinculados a su ID
   - No aparece en listados de usuarios activos
   - El email `juan@empresa.com` queda disponible

### Escenario 2: Reutilizar Email

1. Usuario `juan@empresa.com` fue eliminado hace 1 mes
2. Admin crea nuevo usuario con email `juan@empresa.com`
3. **Resultado:**
   - ✅ Validación pasa (ignora usuarios eliminados)
   - ✅ Nuevo usuario se crea exitosamente
   - Dos registros en BD: uno eliminado (ID 1) y uno activo (ID 2)
   - Documentos antiguos siguen asociados al ID 1 (eliminado)
   - Nuevos documentos se asocian al ID 2 (activo)

### Escenario 3: Consultar Usuarios Eliminados

**Desde Tinker:**
```php
// Solo activos (por defecto)
User::all()

// Solo eliminados
User::onlyTrashed()->get()

// Todos (activos + eliminados)
User::withTrashed()->get()

// Restaurar usuario eliminado
$user = User::onlyTrashed()->where('email', 'juan@empresa.com')->first();
$user->restore();
```

## Verificación

**Estado actual del sistema:**
```
✅ Soft Delete: Activado
✅ Validación email: Ignora eliminados
✅ Estadísticas: Muestran usuarios eliminados
✅ Frontend: Tarjeta de eliminados visible
✅ Usuarios activos: 2
✅ Usuarios eliminados: 1
```

## Notas Técnicas

### ¿Por qué Soft Delete?

1. **Cumplimiento normativo:** Mantener historial de quién creó qué
2. **Integridad referencial:** No romper foreign keys
3. **Auditoría:** Trazabilidad completa de acciones
4. **Flexibilidad:** Posibilidad de restaurar usuarios

### Columnas Importantes

```sql
deleted_at TIMESTAMP NULL  -- NULL = activo, con fecha = eliminado
```

### Consultas Automáticas

Laravel automáticamente agrega `WHERE deleted_at IS NULL` a todas las consultas de `User`.

Para incluir eliminados, usar:
- `withTrashed()` - todos
- `onlyTrashed()` - solo eliminados

## Archivos Modificados

1. ✅ `app/Http/Controllers/Admin/AdminUserController.php`
   - Método `destroy()`: Comentarios mejorados
   - Método `index()`: Estadística de eliminados

2. ✅ `resources/js/pages/admin/users.tsx`
   - Interface `Stats`: Campo `deleted`
   - Tarjeta de usuarios eliminados

3. ✅ `app/Models/User.php`
   - Ya tenía `SoftDeletes` trait

## Comandos Útiles

```bash
# Ver usuarios eliminados
php artisan tinker
>>> User::onlyTrashed()->get(['id', 'name', 'email', 'deleted_at'])

# Restaurar usuario específico
>>> User::onlyTrashed()->find(5)->restore()

# Eliminar permanentemente (hard delete)
>>> User::onlyTrashed()->find(5)->forceDelete()

# Limpiar usuarios eliminados hace más de 30 días
>>> User::onlyTrashed()->where('deleted_at', '<', now()->subDays(30))->forceDelete()
```

---

**Fecha:** 10 de diciembre de 2025  
**Estado:** ✅ Implementado y funcionando
