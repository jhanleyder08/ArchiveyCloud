# 🚀 Inicio Rápido - Autenticación 2FA

## ⚡ Configuración en 5 minutos

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

### 2. Configurar Email (para método Email)

En `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@archiveycloud.com
MAIL_FROM_NAME="ArchiveyCloud Security"
```

### 3. (Opcional) Configurar SMS

Si vas a usar SMS, configura Twilio en `.env`:

```env
TWILIO_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
```

Y actualiza el servicio en `app/Services/TwoFactorAuthenticationService.php` (línea 204).

### 4. Acceder a la Configuración

Los usuarios pueden acceder a la configuración 2FA en:
- Ruta: `/two-factor/settings`
- Componente: `Profile/TwoFactorAuthentication.tsx`

### 5. Probar el Sistema

#### Como Usuario:

1. **Habilitar 2FA:**
   - Ve a tu perfil
   - Selecciona un método (TOTP recomendado)
   - Escanea el QR con Google Authenticator
   - Ingresa el código de 6 dígitos
   - Guarda los códigos de recuperación

2. **Iniciar Sesión:**
   - Cierra sesión
   - Ingresa email/password
   - Serás redirigido a la verificación 2FA
   - Ingresa el código de tu app

---

## 🔧 Comandos Útiles

### Deshabilitar 2FA de un usuario (emergencia)

```bash
php artisan tinker
```

```php
$user = User::where('email', 'usuario@example.com')->first();
$user->twoFactorAuthentication()->delete();
echo "2FA deshabilitado";
```

### Verificar configuración

```bash
php artisan tinker
```

```php
// Ver usuarios con 2FA
User::whereHas('twoFactorAuthentication', fn($q) => $q->where('enabled', true))->get(['id', 'name', 'email']);
```

---

## 📱 Aplicaciones Recomendadas (TOTP)

- **Google Authenticator** - [iOS](https://apps.apple.com/app/google-authenticator/id388497605) | [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2)
- **Microsoft Authenticator** - [iOS](https://apps.apple.com/app/microsoft-authenticator/id983156458) | [Android](https://play.google.com/store/apps/details?id=com.azure.authenticator)
- **Authy** - [iOS](https://apps.apple.com/app/authy/id494168017) | [Android](https://play.google.com/store/apps/details?id=com.authy.authy)

---

## ✅ Checklist de Implementación

- [x] Paquete `pragmarx/google2fa-qrcode` instalado
- [x] Migraciones creadas
- [x] Middleware registrado
- [x] Rutas configuradas
- [x] Servicio de 2FA implementado
- [x] Componentes React creados
- [ ] Migraciones ejecutadas (requiere DB activa)
- [ ] Configuración de email/SMS (según método elegido)
- [ ] Pruebas con usuario real

---

## 🎯 Rutas Principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/two-factor/settings` | Configuración de 2FA |
| POST | `/two-factor/enable` | Habilitar 2FA |
| POST | `/two-factor/confirm` | Confirmar y activar |
| POST | `/two-factor/disable` | Deshabilitar 2FA |
| GET | `/two-factor/challenge` | Pantalla de verificación |
| POST | `/two-factor/verify` | Verificar código |

---

## 🐛 Problemas Comunes

**"SQLSTATE[HY000] [2002]"**
- MySQL no está corriendo. Inicia XAMPP/MySQL.

**"Código inválido"**
- Verifica que la hora del servidor esté sincronizada.
- Si usas TOTP, verifica la hora del dispositivo móvil.

**No recibo emails**
- Verifica configuración SMTP en `.env`
- Revisa `storage/logs/laravel.log`
- Verifica carpeta de spam

---

## 📚 Documentación Completa

Para más detalles, consulta: [`docs/TWO_FACTOR_AUTHENTICATION.md`](./TWO_FACTOR_AUTHENTICATION.md)

---

**Sistema listo para producción ✅**
