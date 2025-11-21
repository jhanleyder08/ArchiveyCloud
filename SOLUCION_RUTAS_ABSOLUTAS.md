# 🔧 Solución: Rutas Absolutas con IP Incorrecta

## 📋 Problema

El código está usando `route()` de Ziggy que genera URLs absolutas con la IP del servidor. Esto causa problemas cuando:
- Diferentes desarrolladores tienen IPs diferentes
- Se trabaja desde localhost pero `ziggy.js` tiene otra IP
- Se genera `ERR_CONNECTION_TIMED_OUT`

## ✅ Solución Aplicada

### 1. Cambiar APP_URL en .env

Cada desarrollador debe configurar su `APP_URL` en `.env`:

```env
# Para desarrollo local
APP_URL=http://127.0.0.1:8000

# O si accedes desde otra máquina
APP_URL=http://TU_IP_LOCAL:8000
```

### 2. Regenerar Ziggy después de cambiar APP_URL

```bash
php artisan ziggy:generate
npm run build
```

### 3. Usar Rutas Relativas con Inertia (RECOMENDADO)

**En lugar de usar `route()` con métodos de Inertia, usa rutas relativas:**

#### ❌ Incorrecto:
```tsx
// Esto genera URL absoluta con IP hardcodeada
router.post(route('admin.plantillas.store'), data);
router.get(route('admin.plantillas.index'));
<Link href={route('admin.plantillas.show', id)}>
```

#### ✅ Correcto:
```tsx
// Usar rutas relativas - Inertia maneja la URL base automáticamente
router.post('/admin/plantillas', data);
router.get('/admin/plantillas');
<Link href={`/admin/plantillas/${id}`}>
```

## 📝 Archivos Corregidos

### ✅ Completamente Corregidos:
- `resources/js/pages/admin/plantillas/index.tsx`
- `resources/js/pages/admin/plantillas/show.tsx`
- `resources/js/pages/admin/prestamos/index.tsx`
- `resources/js/pages/admin/prestamos/create.tsx`

### ⚠️ Pendientes de Revisar (usando `route()`):
- `resources/js/pages/admin/plantillas/create.tsx`
- `resources/js/pages/admin/plantillas/edit.tsx`
- `resources/js/pages/admin/plantillas/editor.tsx`
- Y otros 33 archivos más...

## 🔄 Patrón de Corrección

### Para `router.get()`, `router.post()`, `router.put()`, `router.patch()`, `router.delete()`:
```tsx
// ANTES
router.post(route('admin.plantillas.store'), data);

// DESPUÉS
router.post('/admin/plantillas', data);
```

### Para `<Link>` de Inertia:
```tsx
// ANTES
<Link href={route('admin.plantillas.show', plantilla.id)}>

// DESPUÉS
<Link href={`/admin/plantillas/${plantilla.id}`}>
```

### Para `useForm().post()`, `useForm().put()`, etc.:
```tsx
// ANTES
form.post(route('admin.plantillas.store'));

// DESPUÉS
form.post('/admin/plantillas');
```

### Para `window.location.href` (caso especial):
```tsx
// En este caso SÍ puedes usar route() porque window.location necesita URL absoluta
// PERO asegúrate de que APP_URL esté correcto en .env
window.location.href = route('admin.plantillas.exportar', [id, formato]);

// O mejor aún, construir la URL relativa:
window.location.href = `/admin/plantillas/${id}/exportar/${formato}`;
```

## 🚀 Comandos Rápidos

```bash
# 1. Verificar APP_URL actual
php artisan config:show app.url

# 2. Regenerar Ziggy con APP_URL correcto
php artisan ziggy:generate

# 3. Recompilar assets
npm run build

# 4. Limpiar caché
php artisan config:clear
php artisan cache:clear
```

## 📌 Regla General

**Usa rutas relativas cuando trabajes con Inertia (router, Link, useForm).**

**Usa `route()` de Ziggy solo cuando:**
- Necesites URLs absolutas (como en `window.location.href`)
- Estés fuera del contexto de Inertia
- Y siempre después de verificar que `APP_URL` está correcto

## 🔍 Búsqueda de Archivos con Problemas

Para encontrar todos los archivos que usan `route()`:

```bash
# Buscar todos los usos de route() en páginas admin
grep -r "route('admin\." resources/js/pages/admin

# Buscar específicamente con router.post, router.get, etc.
grep -r "router\.\(post\|get\|put\|patch\|delete\)(route" resources/js/pages
```

## ✅ Checklist de Corrección

Para cada archivo que uses:

- [ ] ¿Usa `router.post/get/put/patch/delete` con `route()`? → Cambiar a ruta relativa
- [ ] ¿Usa `<Link>` con `route()`? → Cambiar a ruta relativa
- [ ] ¿Usa `useForm().post/put/patch` con `route()`? → Cambiar a ruta relativa
- [ ] ¿Usa `window.location.href` con `route()`? → Puede mantenerlo, pero verificar APP_URL

## 🎯 Resultado Esperado

Después de aplicar estas correcciones:
- ✅ No más errores `ERR_CONNECTION_TIMED_OUT`
- ✅ Las rutas funcionan en cualquier IP
- ✅ Cada desarrollador puede trabajar con su propia configuración
- ✅ El código es más portable y mantenible


