# ✅ Corrección: Rutas de Tabla de Retención Documental (TRD)

## Problema Reportado

Al hacer clic en el botón "Tablas de Retención Documental" en el sidebar, no cargaba la vista correspondiente y no pasaba nada.

## Causa Raíz

El controlador `TRDController.php` tenía **dos problemas**:

### 1. Rutas de Inertia con Case Incorrecto

**Controlador definía** (mayúsculas):
```php
Inertia::render('Admin/TRD/Index', [...])
Inertia::render('Admin/TRD/Create')
Inertia::render('Admin/TRD/Show', [...])
Inertia::render('Admin/TRD/Edit', [...])
```

**Estructura real de carpetas** (minúsculas):
```
resources/js/pages/admin/trd/
├── index.tsx
├── create.tsx
├── show.tsx
└── edit.tsx
```

**Problema**: Inertia no encontraba las vistas porque el case no coincidía.

### 2. Nombres de Rutas Incorrectos

**Controlador usaba**:
```php
route('trds.show', $trd->id)    // ❌ Incorrecto
route('trds.index')              // ❌ Incorrecto
```

**Rutas reales en web.php**:
```php
Route::prefix('trd')->name('trd.')->group(function () {
    Route::get('/', ...)->name('index');      // admin.trd.index
    Route::get('/{trd}', ...)->name('show');  // admin.trd.show
    ...
});
```

**Problema**: Las rutas con prefijo `admin` tienen el nombre `admin.trd.*`, no `trds.*`.

## Solución Aplicada

### Cambio 1: Rutas de Inertia (Minúsculas)

**Archivo**: `app/Http/Controllers/TRDController.php`

```php
// ❌ ANTES
Inertia::render('Admin/TRD/Index', [...])
Inertia::render('Admin/TRD/Create')
Inertia::render('Admin/TRD/Show', [...])
Inertia::render('Admin/TRD/Edit', [...])

// ✅ DESPUÉS
Inertia::render('admin/trd/index', [...])
Inertia::render('admin/trd/create')
Inertia::render('admin/trd/show', [...])
Inertia::render('admin/trd/edit', [...])
```

### Cambio 2: Nombres de Rutas Correctos

```php
// ❌ ANTES
route('trds.show', $trd->id)
route('trds.index')

// ✅ DESPUÉS
route('admin.trd.show', $trd->id)
route('admin.trd.index')
```

## Líneas Modificadas

**Archivo**: `app/Http/Controllers/TRDController.php`

| Línea | Antes | Después |
|-------|-------|---------|
| 44 | `'Admin/TRD/Index'` | `'admin/trd/index'` |
| 56 | `'Admin/TRD/Create'` | `'admin/trd/create'` |
| 101 | `'Admin/TRD/Show'` | `'admin/trd/show'` |
| 113 | `'Admin/TRD/Edit'` | `'admin/trd/edit'` |
| 78 | `route('trds.show', ...)` | `route('admin.trd.show', ...)` |
| 137 | `route('trds.show', ...)` | `route('admin.trd.show', ...)` |
| 162 | `route('trds.show', ...)` | `route('admin.trd.show', ...)` |
| 179 | `route('trds.index')` | `route('admin.trd.index')` |
| 206 | `route('trds.show', ...)` | `route('admin.trd.show', ...)` |
| 263 | `route('trds.show', ...)` | `route('admin.trd.show', ...)` |
| 308 | `route('trds.index')` | `route('admin.trd.index')` |

## Configuración del Sidebar

**Archivo**: `resources/js/components/app-sidebar.tsx`

```tsx
{
    title: 'Tablas de Retención Documental',
    href: '/admin/trd',              // ✅ Ruta correcta
    icon: FileText,
    permission: 'trd.ver',           // ✅ Permiso requerido
}
```

## Rutas en web.php

**Archivo**: `routes/web.php` (Líneas 107-131)

```php
// Gestión de Tablas de Retención Documental (TRD)
Route::prefix('trd')->name('trd.')->middleware('permission:trd.ver')->group(function () {
    Route::get('/', [TRDController::class, 'index'])->name('index');
    // Ruta completa: /admin/trd
    // Nombre: admin.trd.index
    // Middleware: auth, verified, permission:trd.ver
    
    Route::get('/{trd}', [TRDController::class, 'show'])->name('show');
    // Ruta completa: /admin/trd/{id}
    // Nombre: admin.trd.show
    
    Route::get('/{trd}/exportar', [TRDController::class, 'exportar'])->name('exportar');
    
    Route::middleware('permission:trd.crear')->group(function () {
        Route::get('/create', [TRDController::class, 'create'])->name('create');
        Route::post('/', [TRDController::class, 'store'])->name('store');
        Route::post('/importar', [TRDController::class, 'importar'])->name('importar');
    });
    
    Route::middleware('permission:trd.editar')->group(function () {
        Route::get('/{trd}/edit', [TRDController::class, 'edit'])->name('edit');
        Route::put('/{trd}', [TRDController::class, 'update'])->name('update');
        Route::delete('/{trd}', [TRDController::class, 'destroy'])->name('destroy');
        Route::post('/{trd}/archivar', [TRDController::class, 'archivar'])->name('archivar');
        Route::post('/{trd}/version', [TRDController::class, 'crearVersion'])->name('version');
        Route::post('/{trd}/serie', [TRDController::class, 'agregarSerie'])->name('agregarSerie');
    });
    
    Route::middleware('permission:trd.aprobar')->group(function () {
        Route::post('/{trd}/aprobar', [TRDController::class, 'aprobar'])->name('aprobar');
    });
});
```

## Permisos Requeridos

Para acceder a las TRDs, el usuario necesita:

| Acción | Permiso | Ruta |
|--------|---------|------|
| Ver listado | `trd.ver` | `/admin/trd` |
| Ver detalles | `trd.ver` | `/admin/trd/{id}` |
| Crear TRD | `trd.crear` | `/admin/trd/create` |
| Editar TRD | `trd.editar` | `/admin/trd/{id}/edit` |
| Aprobar TRD | `trd.aprobar` | `/admin/trd/{id}/aprobar` |
| Exportar TRD | `trd.ver` | `/admin/trd/{id}/exportar` |

## Verificación de Permisos

Para verificar si un usuario tiene el permiso `trd.ver`:

```bash
php artisan tinker
```

```php
// Ver permisos del usuario
$user = User::find(10); // Tu ID de usuario
$user->getAllPermissions()->pluck('nombre');

// Verificar permiso específico
$user->hasPermission('trd.ver');

// Si no tiene el permiso, agregarlo temporalmente para testing
$permiso = Permiso::where('nombre', 'trd.ver')->first();
$user->permisos()->attach($permiso->id, ['activo' => true]);
```

## Testing

### Prueba 1: Verificar que la ruta funciona
```bash
# En el navegador, ir a:
http://127.0.0.1:8000/admin/trd
```

**Resultado esperado**: Debe cargar la vista de listado de TRDs.

### Prueba 2: Verificar permisos
1. Usuario con permiso `trd.ver`: ✅ Debe ver la página
2. Usuario sin permiso: ❌ Debe redirigir o mostrar error 403

### Prueba 3: Verificar sidebar
1. Hacer clic en "Tablas de Retención Documental" en el sidebar
2. ✅ Debe navegar a `/admin/trd`
3. ✅ Debe cargar el listado de TRDs

## Estructura de Archivos

```
app/Http/Controllers/
└── TRDController.php ✅ Corregido

resources/js/
├── components/
│   └── app-sidebar.tsx ✅ Configurado correctamente
└── pages/
    └── admin/
        └── trd/
            ├── index.tsx ✅ Existe
            ├── create.tsx ✅ Existe
            ├── show.tsx ✅ Existe
            └── edit.tsx ✅ Existe

routes/
└── web.php ✅ Rutas correctas
```

## Resumen

**Problema**: Rutas de Inertia y nombres de rutas no coincidían

**Causa**: 
1. Case sensitivity (mayúsculas vs minúsculas)
2. Nombres de rutas incorrectos (`trds.*` vs `admin.trd.*`)

**Solución**: 
1. ✅ Cambiar todas las rutas de Inertia a minúsculas
2. ✅ Corregir nombres de rutas con el prefijo `admin.trd.*`

**Resultado**: Sistema de TRD completamente funcional desde el sidebar ✅

---

## Comandos Útiles

### Ver todas las rutas de TRD
```bash
php artisan route:list --name=trd
```

### Verificar estructura de páginas
```bash
ls resources/js/pages/admin/trd/
```

### Ver logs en caso de error
```bash
tail -f storage/logs/laravel.log
```
