# 📧 INSTRUCCIONES MANUALES - Configurar Email

## Paso 1: Editar el archivo .env

1. Abre el archivo `.env` en la raíz del proyecto (al lado de `.env.example`)
2. Busca las líneas que empiezan con `MAIL_`
3. Reemplázalas con estas líneas (cambia **TU_EMAIL@gmail.com** por tu email real):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=TU_EMAIL@gmail.com
MAIL_PASSWORD=dkrnhloiknzfzohw
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=TU_EMAIL@gmail.com
MAIL_FROM_NAME="Archivey Cloud SGDEA"
```

## Paso 2: Limpiar cache de Laravel

Abre tu terminal en la carpeta del proyecto y ejecuta:

```bash
php artisan config:clear
php artisan cache:clear
```

## Paso 3: Probar que funciona

### Opción A: Registrar un usuario nuevo
1. Ve a `http://localhost/register` (o tu URL del proyecto)
2. Crea un usuario de prueba
3. Verás la pantalla "Verify Email"
4. Revisa tu bandeja de Gmail - deberías recibir el correo

### Opción B: Comando de diagnóstico
```bash
php artisan email:diagnose
```

### Opción C: Enviar email de prueba
```bash
php artisan tinker
```

Luego ejecuta (cambia el email):
```php
Mail::raw('Test desde Archivey', function ($message) {
    $message->to('TU_EMAIL@gmail.com')->subject('Test');
});
```

Presiona `Ctrl+C` para salir de tinker.

## ⚠️ IMPORTANTE

- **DEBES** usar tu email de Gmail real donde dice `TU_EMAIL@gmail.com`
- La contraseña `dkrnhloiknzfzohw` es tu contraseña de aplicación (ya está correcta)
- NO uses tu contraseña normal de Gmail, solo la contraseña de aplicación

## 🐛 Si no funciona

1. Verifica que tu email de Gmail sea correcto
2. Verifica que la contraseña de aplicación sea correcta
3. Verifica que tengas verificación en 2 pasos activada en Gmail
4. Revisa los logs: `storage/logs/laravel.log`

## 📝 Ejemplo Completo

Si tu email es `juan.perez@gmail.com`, tu configuración sería:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=juan.perez@gmail.com
MAIL_PASSWORD=dkrnhloiknzfzohw
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=juan.perez@gmail.com
MAIL_FROM_NAME="Archivey Cloud SGDEA"
```
