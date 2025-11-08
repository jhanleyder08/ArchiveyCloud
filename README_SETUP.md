# Configuración para Desarrolladores

## Configuración de IP Local

Cada desarrollador debe configurar su propia IP en el archivo `.env`:

1. **Edita el archivo `.env`** y configura tu IP local:
   ```env
   APP_URL=http://TU_IP_LOCAL:8000
   VITE_DEV_SERVER_URL=http://localhost:5173
   ```

2. **Regenera el archivo Ziggy** con tu configuración:
   ```bash
   npm run ziggy
   ```
   O manualmente:
   ```bash
   php artisan ziggy:generate
   ```

3. **Reinicia el servidor de desarrollo**:
   ```bash
   npm run dev
   ```

## Pasos Iniciales al Clonar el Repositorio

1. Copia el archivo `.env.example` a `.env`:
   ```bash
   cp .env.example .env
   ```

2. Configura tu `APP_URL` en `.env` con tu IP local

3. Instala las dependencias:
   ```bash
   npm install
   composer install
   ```

4. El script `postinstall` regenerará automáticamente `ziggy.js` con tu configuración

## Nota Importante

- El archivo `ziggy.js` está en `.gitignore` porque cada desarrollador tiene una IP diferente
- Si cambias tu `APP_URL`, siempre ejecuta `npm run ziggy` para regenerar las rutas
- Nunca subas tu archivo `.env` al repositorio

