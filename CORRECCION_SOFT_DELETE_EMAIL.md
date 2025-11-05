# ✅ Corrección: Validación de Email con Soft Delete

## Problema

Cuando se eliminaba un usuario usando Soft Delete (el registro no se borra físicamente, solo se marca `deleted_at`), no se podía crear un nuevo usuario con el mismo email porque la validación `unique:users,email` verificaba todos los registros, incluyendo los "eliminados".

**Error**: "Este email ya está registrado"

## Causa

La validación de Laravel por defecto NO ignora los registros con Soft Delete:

```php
// ❌ INCORRECTO - Verifica TODOS los registros (incluso eliminados)
'email' => 'unique:users'
```

## Solución Aplicada

Se modificó la validación para ignorar usuarios eliminados (donde `deleted_at` no es NULL):

### 1. En el método `store()` (crear usuario)

**Archivo**: `app/Http/Controllers/Admin/AdminUserController.php`

```php
// ✅ CORRECTO - Ignora registros con deleted_at != NULL
'email' => 'unique:users,email,NULL,id,deleted_at,NULL'
```

Esto verifica:
- ✅ Email único en la tabla `users`
- ✅ Columna `email`
- ✅ Ignora el registro actual: `NULL` (no aplica en create)
- ✅ Columna de ID: `id`
- ✅ **Solo verifica registros donde `deleted_at` = `NULL` (usuarios activos)**

### 2. En el método `update()` (editar usuario)

```php
// ✅ CORRECTO - Ignora el usuario actual Y los eliminados
'email' => 'unique:users,email,' . $user->id . ',id,deleted_at,NULL'
```

Esto verifica:
- ✅ Email único en la tabla `users`
- ✅ Columna `email`
- ✅ Ignora el usuario actual que se está editando: `$user->id`
- ✅ Columna de ID: `id`
- ✅ **Solo verifica registros donde `deleted_at` = `NULL` (usuarios activos)**

## Sintaxis de la Regla Unique

```php
unique:tabla,columna,excepción,id_columna,where_columna,where_valor
```

**Ejemplo completo**:
```php
'email' => 'unique:users,email,' . $user->id . ',id,deleted_at,NULL'
```

Traducción:
- `unique:users` - Verifica unicidad en tabla users
- `,email` - Columna a verificar
- `,{$user->id}` - Excepto este ID (para updates)
- `,id` - Nombre de la columna ID
- `,deleted_at,NULL` - **SOLO donde deleted_at = NULL (no eliminados)**

## Resultado

Ahora puedes:

✅ **Crear un nuevo usuario con un email de un usuario previamente eliminado**
```
Usuario 1: juan@mail.com → Eliminado (deleted_at = '2025-11-04 20:00:00')
Usuario 2: juan@mail.com → ✅ Se puede crear sin problemas
```

✅ **Editar un usuario sin conflictos con emails de usuarios eliminados**

✅ **Mantener integridad de datos** - No permite emails duplicados entre usuarios activos

## Funcionamiento del Soft Delete

### Cuando eliminas un usuario desde la aplicación:

```php
$user->delete(); // Soft delete
```

**Base de datos**:
```
| id | name | email           | deleted_at           |
|----|------|-----------------|----------------------|
| 1  | Juan | juan@mail.com   | 2025-11-04 20:00:00 | ← Eliminado
| 2  | María| maria@mail.com  | NULL                | ← Activo
```

### Las consultas normales ignoran automáticamente los eliminados:

```php
User::all(); // Solo devuelve usuarios con deleted_at = NULL
User::find(1); // NULL (no encuentra usuarios eliminados)
```

### Para incluir eliminados:

```php
User::withTrashed()->get(); // Incluye todos
User::onlyTrashed()->get(); // Solo eliminados
```

### Para restaurar:

```php
$user = User::withTrashed()->find(1);
$user->restore(); // deleted_at vuelve a NULL
```

### Para eliminar permanentemente:

```php
$user = User::withTrashed()->find(1);
$user->forceDelete(); // ⚠️ ELIMINA FÍSICAMENTE (irreversible)
```

## Ventajas del Soft Delete

1. **Auditoría completa** - Mantiene historial de usuarios
2. **Recuperación** - Puedes restaurar usuarios eliminados por error
3. **Integridad referencial** - Preserva relaciones con documentos, auditorías, etc.
4. **Cumplimiento normativo** - Requerido para sistemas SGDEA
5. **Reutilización de emails** - Ahora funciona correctamente con esta corrección

## Verificar Usuarios Eliminados

### Desde phpMyAdmin:

```sql
-- Ver todos los usuarios eliminados
SELECT id, name, email, deleted_at 
FROM users 
WHERE deleted_at IS NOT NULL;

-- Ver todos los usuarios activos
SELECT id, name, email, deleted_at 
FROM users 
WHERE deleted_at IS NULL;
```

### Desde Tinker:

```bash
php artisan tinker
```

```php
// Ver usuarios eliminados
User::onlyTrashed()->get();

// Restaurar un usuario
$user = User::withTrashed()->find(10);
$user->restore();
```

## Testing

Ahora puedes probar:

1. Crear usuario: `test@example.com`
2. Eliminarlo desde la aplicación
3. Verificar en phpMyAdmin que `deleted_at` tiene fecha
4. Crear un nuevo usuario con `test@example.com`
5. ✅ **Debería funcionar sin errores**

---

## Resumen de Cambios

**Archivo**: `app/Http/Controllers/Admin/AdminUserController.php`

- ✅ Línea 77: Validación `store()` - Ignora soft deleted
- ✅ Línea 161: Validación `update()` - Ignora soft deleted + usuario actual

**Resultado**: Sistema de usuarios completamente funcional con Soft Delete.
