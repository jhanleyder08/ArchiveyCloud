# 🔧 Configuración para Trabajo en Equipo

## 📋 Problema Identificado

El proyecto estaba usando una IP hardcodeada (`192.168.2.202:8000`) en el archivo `ziggy.js`, lo cual impide que diferentes desarrolladores trabajen en el mismo proyecto, ya que cada uno tiene una IP diferente.

## ✅ Solución Implementada

### 1. Configuración de Variables de Entorno

Cada desarrollador debe configurar su propia `APP_URL` en el archivo `.env`:

```env
# Opción 1: Desarrollo local (recomendado)
APP_URL=http://127.0.0.1:8000

# Opción 2: Acceso desde red local
APP_URL=http://TU_IP_LOCAL:8000
```

### 2. Crear Archivo .env desde .env.example

**IMPORTANTE:** Cada desarrollador debe:

1. Copiar `.env.example` a `.env`:
   ```bash
   cp .env.example .env
   ```

2. Configurar `APP_URL` según su entorno

3. Generar la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```

4. Regenerar las rutas de Ziggy:
   ```bash
   php artisan ziggy:generate
   ```

5. Compilar los assets:
   ```bash
   npm run build
   ```

## 🔍 Verificación de Configuración

### Verificar tu IP local

**Windows:**
```powershell
ipconfig
# Buscar "IPv4 Address" en la salida
```

**Linux/Mac:**
```bash
ifconfig
# O
ip addr
```

### Verificar APP_URL configurado

```bash
php artisan config:show app.url
```

### Verificar que Ziggy esté actualizado

El archivo `resources/js/ziggy.js` debe contener la URL correcta. Si no coincide, ejecuta:

```bash
php artisan ziggy:generate
```

## 📝 Estructura de Archivos para GitHub

### Archivos que SÍ deben estar en el repositorio:
- ✅ `.env.example` - Plantilla de configuración
- ✅ `README.md` - Documentación del proyecto
- ✅ `CONFIGURACION_EQUIPO.md` - Este archivo
- ✅ Todos los archivos de código fuente

### Archivos que NO deben estar en el repositorio:
- ❌ `.env` - Configuración local (ya está en `.gitignore`)
- ❌ `ziggy.js` - Se genera automáticamente (ya está en `.gitignore`)
- ❌ `node_modules/` - Dependencias de Node (ya está en `.gitignore`)
- ❌ `vendor/` - Dependencias de PHP (ya está en `.gitignore`)

## 🚀 Flujo de Trabajo para Nuevos Desarrolladores

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd ArchiveyCloud
   ```

2. **Crear archivo .env**
   ```bash
   cp .env.example .env
   ```

3. **Configurar APP_URL**
   - Abrir `.env`
   - Cambiar `APP_URL=http://127.0.0.1:8000` (o tu IP local)
   - Guardar

4. **Configurar base de datos**
   - Editar `.env` con tus credenciales de BD
   - Crear la base de datos

5. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

6. **Configurar aplicación**
   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan ziggy:generate
   npm run build
   ```

7. **Iniciar servidor**
   ```bash
   php artisan serve
   ```

## 🔄 Cuando Recibas Cambios del Repositorio

1. **Actualizar código**
   ```bash
   git pull origin main
   ```

2. **Actualizar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Regenerar rutas (si es necesario)**
   ```bash
   php artisan ziggy:generate
   npm run build
   ```

4. **Ejecutar migraciones**
   ```bash
   php artisan migrate
   ```

## 🐛 Solución de Problemas Comunes

### Error: "ERR_CONNECTION_TIMED_OUT"
**Causa:** La URL en `ziggy.js` no coincide con la URL actual
**Solución:**
```bash
# 1. Verificar APP_URL en .env
# 2. Regenerar Ziggy
php artisan ziggy:generate
# 3. Recompilar assets
npm run build
```

### Error: "Could not resolve ./ziggy"
**Causa:** El archivo `ziggy.js` no existe
**Solución:**
```bash
php artisan ziggy:generate
```

### Las rutas no funcionan correctamente
**Causa:** `ziggy.js` está desactualizado
**Solución:**
```bash
php artisan ziggy:generate
npm run build
php artisan config:clear
php artisan cache:clear
```

## 📌 Notas Importantes

1. **NUNCA** subas tu archivo `.env` al repositorio
2. **SIEMPRE** usa `.env.example` como base
3. **RECUERDA** ejecutar `php artisan ziggy:generate` después de cambiar `APP_URL`
4. El archivo `ziggy.js` se genera automáticamente y NO debe editarse manualmente
5. Cada desarrollador debe tener su propia configuración en `.env`

## ✅ Checklist para Nuevos Desarrolladores

- [ ] Repositorio clonado
- [ ] Archivo `.env` creado desde `.env.example`
- [ ] `APP_URL` configurado correctamente
- [ ] Base de datos configurada
- [ ] `php artisan key:generate` ejecutado
- [ ] `composer install` ejecutado
- [ ] `npm install` ejecutado
- [ ] `php artisan migrate` ejecutado
- [ ] `php artisan ziggy:generate` ejecutado
- [ ] `npm run build` ejecutado
- [ ] Servidor iniciado y funcionando

