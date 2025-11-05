# Solución Implementada: Sistema de Permisos y Roles

## Problema Identificado

Los usuarios con rol "Sin Acceso" podían ver todos los campos y opciones del sistema, aunque no deberían tener acceso a funcionalidades administrativas.

## Causa Raíz

1. **No se compartían permisos al frontend**: El middleware `HandleInertiaRequests` solo pasaba el usuario, sin información de permisos.
2. **No había protección en rutas**: Las rutas no verificaban permisos antes de permitir acceso.
3. **Sidebar sin filtrado**: El menú lateral mostraba todas las opciones sin verificar permisos del usuario.

## Solución Implementada

### 1. Compartir Permisos al Frontend ✅

**Archivo**: `app/Http/Middleware/HandleInertiaRequests.php`

Se actualizó el método `share()` para:
- Obtener todos los permisos del usuario (rol principal + roles adicionales + permisos directos)
- Compartir array de nombres de permisos al frontend mediante props de Inertia

```php
'auth' => [
    'user' => $user,
    'permissions' => $permissions, // Array de nombres de permisos
]
```

### 2. Middleware de Verificación de Permisos ✅

**Archivo**: `app/Http/Middleware/PermissionMiddleware.php`

Middleware que verifica si el usuario tiene los permisos requeridos antes de permitir acceso a una ruta.

**Uso**:
```php
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->middleware('permission:usuarios.ver');
```

### 3. Hook de React para Permisos ✅

**Archivo**: `resources/js/hooks/usePermissions.ts`

Hook personalizado que proporciona funciones para verificar permisos en componentes React:

```typescript
const { hasPermission, hasAnyPermission, hasAllPermissions, hasRole } = usePermissions();

// Verificar un permiso
if (hasPermission('usuarios.crear')) {
    // Mostrar botón crear
}
```

### 4. Filtrado Automático del Sidebar ✅

**Archivo**: `resources/js/components/app-sidebar.tsx`

El sidebar ahora:
- Define permisos requeridos para cada item del menú
- Filtra automáticamente los items según permisos del usuario
- Solo muestra opciones a las que el usuario tiene acceso

### 5. Protección de Rutas Críticas ✅

**Archivo**: `routes/web.php`

Se protegieron rutas de:
- **Gestión de Usuarios**: `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.eliminar`, `usuarios.activar`
- **TRD**: `trd.ver`, `trd.crear`, `trd.editar`, `trd.aprobar`

### 6. Documentación Completa ✅

**Archivo**: `GUIA_PROTECCION_RUTAS_PERMISOS.md`

Guía completa con:
- Lista de 33 permisos del sistema
- Ejemplos de protección de rutas
- Ejemplos de uso en componentes
- Instrucciones de mantenimiento

## Cómo Probar la Solución

### Paso 1: Verificar que el usuario actual tiene el rol correcto

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'jhanleyder71@gmail.com')->first();
echo "Rol: " . $user->role->name;
echo "\nPermisos: " . $user->role->permisos()->count();
```

### Paso 2: Crear un usuario de prueba con rol "Sin Acceso"

```php
$rolSinAcceso = App\Models\Role::where('name', 'Sin Acceso')->first();

$userTest = new App\Models\User();
$userTest->name = 'Usuario Prueba';
$userTest->email = 'prueba@test.com';
$userTest->password = bcrypt('Test1234');
$userTest->role_id = $rolSinAcceso->id;
$userTest->active = true;
$userTest->estado_cuenta = 'activo';
$userTest->email_verified_at = now();
$userTest->save();

echo "Usuario de prueba creado con rol: " . $userTest->role->name;
```

### Paso 3: Iniciar sesión con cada usuario y verificar

#### Usuario con Super Administrador
- ✅ Ve todas las opciones en el sidebar
- ✅ Puede acceder a todas las rutas administrativas
- ✅ Botones de crear, editar, eliminar visibles

#### Usuario con rol "Sin Acceso"
- ✅ Solo ve el Dashboard en el sidebar
- ✅ No ve opciones administrativas
- ✅ Al intentar acceder directamente a `/admin/users`, es redirigido
- ✅ Mensaje de error: "No tiene permisos para acceder a esta sección"

### Paso 4: Verificar en el navegador

1. **Abrir DevTools** (F12)
2. **Console** → Verificar que los permisos están disponibles:
   ```javascript
   // Inspeccionar props de Inertia
   window.Inertia?.page?.props?.auth?.permissions
   ```

3. **Network** → Verificar que las peticiones a rutas protegidas retornan:
   - `403 Forbidden` si no tiene permisos
   - `302 Redirect` a dashboard con mensaje de error

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  usePermissions Hook                                         │
│  ↓                                                            │
│  hasPermission('usuarios.crear')                            │
│  ↓                                                            │
│  Componentes filtran UI según permisos                      │
│  (botones, menús, páginas)                                  │
│                                                               │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    HandleInertiaRequests                     │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Comparte permisos del usuario al frontend:                 │
│  - Permisos del rol principal                               │
│  - Permisos de roles adicionales                            │
│  - Permisos directos del usuario                            │
│                                                               │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                         Backend                              │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  PermissionMiddleware                                        │
│  ↓                                                            │
│  Verifica permisos antes de ejecutar controlador            │
│  ↓                                                            │
│  User::hasPermission()                                       │
│  ↓                                                            │
│  Role::hasPermission()                                       │
│  ↓                                                            │
│  Permisos desde BD (tabla permisos)                         │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## Flujo de Verificación de Permisos

### En el Backend (Seguridad)

1. Usuario hace request a `/admin/users`
2. Middleware `PermissionMiddleware` intercepta
3. Verifica si usuario tiene permiso `usuarios.ver`
4. Si NO tiene → Redirige a dashboard con error
5. Si SÍ tiene → Permite acceso al controlador

### En el Frontend (UX)

1. Componente carga con `usePermissions()`
2. Hook obtiene permisos desde `usePage().props.auth.permissions`
3. Componente verifica: `hasPermission('usuarios.crear')`
4. Si NO tiene → No muestra botón/opción
5. Si SÍ tiene → Muestra botón/opción

## Próximos Pasos (Opcional)

Para proteger todas las rutas del sistema:

1. **Revisar `routes/web.php`** y agregar middleware a cada grupo de rutas
2. **Actualizar controladores** para verificar permisos adicionales si es necesario
3. **Revisar componentes React** y agregar verificaciones con `usePermissions`
4. **Crear tests** para verificar que los permisos funcionan correctamente

## Archivos Modificados

### Backend
- ✅ `app/Http/Middleware/HandleInertiaRequests.php` - Compartir permisos
- ✅ `app/Http/Middleware/PermissionMiddleware.php` - Nuevo middleware
- ✅ `bootstrap/app.php` - Registrar middleware
- ✅ `routes/web.php` - Proteger rutas de usuarios y TRD

### Frontend
- ✅ `resources/js/hooks/usePermissions.ts` - Nuevo hook
- ✅ `resources/js/components/app-sidebar.tsx` - Filtrado por permisos
- ✅ `resources/js/types/index.d.ts` - Agregar campo permission

### Documentación
- ✅ `GUIA_PROTECCION_RUTAS_PERMISOS.md` - Guía completa
- ✅ `SOLUCION_PERMISOS_ROLES.md` - Este archivo

## Resultado Final

✅ **Problema resuelto**: Los usuarios con rol "Sin Acceso" ahora solo ven lo que tienen permitido.

✅ **Seguridad mejorada**: Las rutas están protegidas en el backend.

✅ **Mejor UX**: Los usuarios solo ven opciones a las que tienen acceso.

✅ **Sistema escalable**: Fácil agregar nuevos permisos y proteger nuevas rutas.

---

## Comandos Útiles

```bash
# Ver roles y permisos
php artisan tinker
>>> App\Models\Role::with('permisos')->get()

# Ver permisos de un usuario
>>> $user = App\Models\User::find(10)
>>> $user->role->permisos->pluck('nombre')

# Cambiar rol de un usuario
>>> $user->role_id = 1  // Super Administrador
>>> $user->save()

# Limpiar cache
php artisan optimize:clear
```
