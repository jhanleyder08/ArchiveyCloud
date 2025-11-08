# 🚀 Guía Rápida de Configuración para Equipo

## ⚡ Configuración Rápida (5 minutos)

### 1. Clonar y Configurar

```bash
# Clonar repositorio
git clone [URL_DEL_REPOSITORIO]
cd ArchiveyCloud

# Crear archivo .env
cp .env.example .env

# Editar .env y configurar APP_URL
# APP_URL=http://127.0.0.1:8000  (para desarrollo local)
# O
# APP_URL=http://TU_IP:8000  (si accedes desde otra máquina)
```

### 2. Instalar y Configurar

```bash
# Instalar dependencias
composer install
npm install

# Generar clave
php artisan key:generate

# Configurar base de datos en .env y luego:
php artisan migrate

# Generar rutas (IMPORTANTE después de configurar APP_URL)
php artisan ziggy:generate

# Compilar assets
npm run build
```

### 3. Iniciar Servidor

```bash
php artisan serve
```

## 🔑 Puntos Clave

1. **APP_URL debe ser única por desarrollador**
   - Cada uno configura su propia IP o usa `localhost`
   
2. **Siempre regenerar Ziggy después de cambiar APP_URL**
   ```bash
   php artisan ziggy:generate
   npm run build
   ```

3. **Nunca subir .env al repositorio**
   - Está en `.gitignore` automáticamente

4. **ziggy.js se genera automáticamente**
   - NO editarlo manualmente
   - Se regenera con `php artisan ziggy:generate`

## 🐛 Problemas Comunes

**Error de conexión:**
```bash
# Verificar APP_URL
php artisan config:show app.url

# Regenerar rutas
php artisan ziggy:generate
npm run build
```

**Archivos faltantes después de git pull:**
```bash
# Regenerar ziggy.js
php artisan ziggy:generate

# Recompilar assets
npm run build
```

## 📚 Documentación Completa

Ver `CONFIGURACION_EQUIPO.md` para más detalles.

