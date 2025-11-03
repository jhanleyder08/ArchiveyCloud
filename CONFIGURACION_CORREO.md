# Configuración para solucionar el error SSL en el envío de correos

## Problema
Error SSL al intentar enviar correos de verificación durante el registro:
```
Unable to connect with STARTTLS: stream_socket_enable_crypto(): SSL operation failed with code 1.
```

## Solución

### 1. Agrega estas líneas a tu archivo `.env`

Abre tu archivo `.env` y busca la sección de configuración de correo (MAIL_*). Actualiza o agrega las siguientes líneas:

```env
# Configuración de correo
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Opciones SSL (solo para desarrollo)
MAIL_VERIFY_PEER=false
MAIL_VERIFY_PEER_NAME=false
```

**Importante:** Estas configuraciones desactivan la verificación SSL. Esto está bien para desarrollo local, pero **NO uses esto en producción**.

### 2. Opciones de configuración

#### Opción A: Usar el driver "log" (Recomendado para desarrollo)
Esta opción guarda los correos en archivos de log en lugar de enviarlos realmente.

```env
MAIL_MAILER=log
```

Los correos se guardarán en `storage/logs/laravel.log`.

#### Opción B: Usar Mailtrap (Recomendado para pruebas)
Mailtrap es un servicio gratuito que captura todos los correos enviados sin entregarlos realmente.

1. Regístrate en [Mailtrap.io](https://mailtrap.io/)
2. Crea un inbox
3. Copia las credenciales SMTP
4. Actualiza tu `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username_de_mailtrap
MAIL_PASSWORD=tu_password_de_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_VERIFY_PEER=false
MAIL_VERIFY_PEER_NAME=false
```

#### Opción C: Usar Gmail (Para producción)
Si quieres usar Gmail para enviar correos reales:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="tu-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_VERIFY_PEER=true
MAIL_VERIFY_PEER_NAME=true
```

**Nota:** Para Gmail necesitas crear una "Contraseña de aplicación" en tu configuración de Google si tienes 2FA activado.

### 3. Después de actualizar el .env

Ejecuta estos comandos para limpiar la caché:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 4. Prueba el registro

1. Reinicia el servidor si está corriendo:
   ```bash
   php artisan serve
   ```

2. Visita: http://127.0.0.1:8000/register

3. Intenta registrar un nuevo usuario

### 5. Verificar los correos

- Si usas `MAIL_MAILER=log`: Revisa el archivo `storage/logs/laravel.log`
- Si usas Mailtrap: Revisa tu inbox en mailtrap.io
- Si usas Gmail: El correo se enviará realmente

## Notas importantes

1. **Desarrollo:** Usa `MAIL_MAILER=log` o Mailtrap
2. **Producción:** Configura un servidor SMTP real con `MAIL_VERIFY_PEER=true`
3. **Seguridad:** Nunca subas tu archivo `.env` a un repositorio público

## ¿Necesitas más ayuda?

Si el error persiste después de aplicar estos cambios, verifica:
- Que no haya espacios extra en las líneas del `.env`
- Que hayas limpiado la caché
- Que hayas reiniciado el servidor

