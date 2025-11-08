# ArchiveyCloud - Sistema de Gestión Documental

## 🚀 Configuración para Desarrollo en Equipo

### Requisitos Previos
- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/MariaDB
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd ArchiveyCloud
   ```

2. **Configurar variables de entorno**
   ```bash
   # Copiar el archivo de ejemplo
   cp .env.example .env
   
   # IMPORTANTE: Configurar APP_URL según tu entorno
   # Opción 1 (recomendado para desarrollo local):
   # APP_URL=http://127.0.0.1:8000
   
   # Opción 2 (si accedes desde otra máquina en la red):
   # APP_URL=http://TU_IP_LOCAL:8000
   # Ejemplo: APP_URL=http://192.168.1.100:8000
   ```

3. **Generar clave de aplicación**
   ```bash
   php artisan key:generate
   ```

4. **Configurar base de datos**
   - Editar `.env` con tus credenciales de base de datos
   - Crear la base de datos: `CREATE DATABASE archivey_cloud;`
   - Ejecutar migraciones: `php artisan migrate`

5. **Instalar dependencias**
   ```bash
   # Dependencias de PHP
   composer install
   
   # Dependencias de Node.js
   npm install
   ```

6. **Generar rutas de Ziggy (IMPORTANTE)**
   ```bash
   # Esto genera las rutas con la URL configurada en APP_URL
   php artisan ziggy:generate
   ```

7. **Compilar assets**
   ```bash
   npm run build
   # O para desarrollo con hot reload:
   npm run dev
   ```

8. **Iniciar servidor**
   ```bash
   php artisan serve
   # O con una IP específica:
   php artisan serve --host=0.0.0.0 --port=8000
   ```

### ⚠️ Configuración de APP_URL para Trabajo en Equipo

**Cada desarrollador debe configurar su propia `APP_URL` en el archivo `.env`:**

- **Desarrollo local (recomendado)**: `APP_URL=http://127.0.0.1:8000`
- **Acceso desde red local**: `APP_URL=http://TU_IP_LOCAL:8000`

**Para encontrar tu IP local:**
- **Windows**: `ipconfig` (buscar "IPv4 Address")
- **Linux/Mac**: `ifconfig` o `ip addr`

**Después de cambiar APP_URL, siempre ejecuta:**
```bash
php artisan ziggy:generate
npm run build
```

### 🔄 Actualización del Proyecto

Cuando recibas cambios del repositorio:

1. **Actualizar código**
   ```bash
   git pull origin main
   ```

2. **Actualizar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Regenerar rutas (si APP_URL cambió)**
   ```bash
   php artisan ziggy:generate
   ```

4. **Recompilar assets**
   ```bash
   npm run build
   ```

5. **Ejecutar migraciones (si hay nuevas)**
   ```bash
   php artisan migrate
   ```

### 📝 Notas Importantes

- **NUNCA** subas el archivo `.env` al repositorio (está en `.gitignore`)
- **SIEMPRE** usa `.env.example` como base para tu `.env`
- **RECUERDA** ejecutar `php artisan ziggy:generate` después de cambiar `APP_URL`
- El archivo `ziggy.js` se genera automáticamente y NO debe ser editado manualmente

### 🐛 Solución de Problemas

**Error: "Could not resolve ./ziggy"**
- Ejecuta: `php artisan ziggy:generate`

**Error: "ERR_CONNECTION_TIMED_OUT"**
- Verifica que `APP_URL` en `.env` coincida con la URL que estás usando
- Regenera Ziggy: `php artisan ziggy:generate`
- Recompila assets: `npm run build`

**Las rutas no funcionan correctamente**
- Verifica que `APP_URL` esté configurado correctamente
- Regenera Ziggy: `php artisan ziggy:generate`
- Limpia la caché: `php artisan config:clear && php artisan cache:clear`

---

# ArchiveyCloud