# Guía de Protección de Rutas con Permisos

## Sistema Implementado

Se ha implementado un sistema completo de permisos que incluye:

1. **Middleware de Permisos** (`PermissionMiddleware`)
2. **Compartición de Permisos al Frontend** (HandleInertiaRequests)
3. **Hook de React para verificar permisos** (`usePermissions`)
4. **Filtrado automático del Sidebar** según permisos

## Cómo Proteger Rutas

### Backend - Protección de Rutas

Para proteger una ruta o grupo de rutas en `routes/web.php`, usa el middleware `permission`:

```php
// Proteger una sola ruta
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:usuarios.ver'])
    ->name('admin.users.index');

// Proteger un grupo de rutas con el mismo permiso
Route::middleware(['auth', 'verified', 'permission:usuarios.gestionar'])->group(function () {
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
});

// Permitir múltiples permisos (cualquiera de ellos es válido)
Route::get('/admin/reportes', [ReportController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:reportes.ver,auditoria.ver'])
    ->name('admin.reportes.index');
```

### Frontend - Verificación de Permisos en Componentes

En componentes React/TypeScript, usa el hook `usePermissions`:

```typescript
import { usePermissions } from '@/hooks/usePermissions';

function MiComponente() {
    const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions();

    // Verificar un solo permiso
    if (!hasPermission('usuarios.crear')) {
        return null; // No mostrar el componente
    }

    return (
        <div>
            {/* Botón visible solo si tiene permiso */}
            {hasPermission('usuarios.crear') && (
                <Button>Crear Usuario</Button>
            )}

            {/* Contenido visible si tiene cualquiera de estos permisos */}
            {hasAnyPermission(['usuarios.editar', 'usuarios.eliminar']) && (
                <div>Opciones de edición</div>
            )}

            {/* Contenido visible solo si tiene TODOS estos permisos */}
            {hasAllPermissions(['usuarios.ver', 'usuarios.editar']) && (
                <div>Panel completo de gestión</div>
            )}
        </div>
    );
}
```

## Lista de Permisos Disponibles

Según el documento `EJECUTAR_IMPLEMENTACION_ROLES.md`, estos son los 33 permisos del sistema:

### Administración (2 permisos)
- `administracion.dashboard.ver`
- `administracion.configuracion.gestionar`

### Usuarios (6 permisos)
- `usuarios.crear`
- `usuarios.ver`
- `usuarios.editar`
- `usuarios.eliminar`
- `usuarios.activar`
- `usuarios.gestionar`

### Roles y Permisos (4 permisos)
- `roles.crear`
- `roles.editar`
- `roles.eliminar`
- `roles.asignar`

### TRD (4 permisos)
- `trd.crear`
- `trd.editar`
- `trd.aprobar`
- `trd.ver`

### Series (2 permisos)
- `series.crear`
- `series.ver`

### Subseries (2 permisos)
- `subseries.crear`
- `subseries.ver`

### CCD (2 permisos)
- `ccd.crear`
- `ccd.ver`

### Expedientes (3 permisos)
- `expedientes.crear`
- `expedientes.editar`
- `expedientes.ver`

### Documentos (3 permisos)
- `documentos.crear`
- `documentos.editar`
- `documentos.ver`

### Plantillas (2 permisos)
- `plantillas.crear`
- `plantillas.ver`

### Préstamos (1 permiso)
- `prestamos.ver`

### Disposiciones (1 permiso)
- `disposiciones.ver`

### Reportes (1 permiso)
- `reportes.ver`

### Auditoría (1 permiso)
- `auditoria.ver`

### Notificaciones (1 permiso)
- `notificaciones.gestionar`

### Índices (1 permiso)
- `indices.ver`

### Firmas Digitales (1 permiso)
- `firmas.gestionar`

### Workflow (1 permiso)
- `workflow.gestionar`

### Certificados (1 permiso)
- `certificados.gestionar`

### Importación (1 permiso)
- `importacion.gestionar`

### API (1 permiso)
- `api.gestionar`

## Ejemplo de Protección Completa

### 1. Proteger Rutas en web.php

```php
// Grupo de gestión de usuarios
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    
    // Ver usuarios - requiere permiso de visualización
    Route::get('users', [AdminUserController::class, 'index'])
        ->middleware('permission:usuarios.ver')
        ->name('users.index');
    
    // Crear usuario - requiere permiso de creación
    Route::post('users', [AdminUserController::class, 'store'])
        ->middleware('permission:usuarios.crear')
        ->name('users.store');
    
    // Editar usuario - requiere permiso de edición
    Route::put('users/{user}', [AdminUserController::class, 'update'])
        ->middleware('permission:usuarios.editar')
        ->name('users.update');
    
    // Eliminar usuario - requiere permiso de eliminación
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])
        ->middleware('permission:usuarios.eliminar')
        ->name('users.destroy');
});
```

### 2. Proteger en el Frontend

```typescript
// pages/admin/users.tsx
import { usePermissions } from '@/hooks/usePermissions';

export default function UsersPage() {
    const { hasPermission } = usePermissions();

    // Verificar permiso al cargar la página
    if (!hasPermission('usuarios.ver')) {
        return (
            <div>
                <h1>Acceso Denegado</h1>
                <p>No tienes permisos para ver esta página.</p>
            </div>
        );
    }

    return (
        <div>
            <h1>Gestión de Usuarios</h1>
            
            {/* Botón crear solo si tiene permiso */}
            {hasPermission('usuarios.crear') && (
                <Button onClick={handleCreate}>
                    Crear Usuario
                </Button>
            )}
            
            <UsersTable 
                canEdit={hasPermission('usuarios.editar')}
                canDelete={hasPermission('usuarios.eliminar')}
            />
        </div>
    );
}
```

## Verificación del Sistema

Para verificar que el sistema funciona correctamente:

1. **Crear un usuario con rol "Sin Acceso":**
   ```php
   php artisan tinker
   $user = new App\Models\User();
   $user->name = 'Usuario Prueba';
   $user->email = 'prueba@test.com';
   $user->password = bcrypt('password');
   $user->role_id = Role::where('name', 'Sin Acceso')->first()->id;
   $user->active = true;
   $user->estado_cuenta = 'activo';
   $user->save();
   ```

2. **Iniciar sesión con ese usuario** y verificar que:
   - Solo ve el Dashboard (sin opciones de administración)
   - El sidebar no muestra opciones administrativas
   - Al intentar acceder directamente a rutas protegidas, es redirigido

3. **Asignar permisos específicos** y verificar que aparecen las opciones correspondientes

## Mantenimiento

Al agregar nuevas funcionalidades:

1. **Definir el permiso** en la base de datos (tabla `permisos`)
2. **Asignar el permiso a roles** apropiados
3. **Proteger la ruta** con el middleware
4. **Verificar en el frontend** con usePermissions
5. **Actualizar el sidebar** si es necesario (ya se filtra automáticamente)

## Notas Importantes

- Los usuarios sin rol (role_id = null) **NO** tienen acceso a nada excepto su perfil
- El rol "Sin Acceso" tiene permisos mínimos (solo editar perfil)
- Los permisos se heredan de roles padres cuando está configurado
- Se puede asignar permisos directamente a usuarios para casos especiales
- El sistema verifica permisos en BACKEND (seguridad) y FRONTEND (UX)
