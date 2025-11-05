# 📧 Problema: Correo de Verificación No Se Envía

## 🔍 Diagnóstico Completo

### **Problema Principal**
Los correos de verificación de nuevas cuentas NO se están enviando porque:

1. **MAIL_MAILER configurado en modo 'log'** - Los correos solo se guardan en logs
2. **Flujo de registro incorrecto** - Usuario se redirigía al dashboard directamente
3. **Middleware VerifyUserActive bloquea acceso** - Pero solo DESPUÉS del login

---

## ✅ Soluciones Implementadas

### **1. Corregido el Flujo de Registro**
- ✅ Usuario ahora se redirige a `/verify-email` después del registro
- ✅ No puede acceder al dashboard hasta verificar su email
- ✅ Middleware `verified` bloquea rutas protegidas

**Archivo modificado:** `app/Http/Controllers/Auth/RegisteredUserController.php`

---

## 🔧 Configuración Requerida

### **Paso 1: Configurar SMTP en el archivo .env**

Debes elegir UNA de estas opciones:

#### **Opción A: Gmail (Recomendado para desarrollo)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=xxxx_xxxx_xxxx_xxxx  # Contraseña de aplicación
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="Archivey Cloud SGDEA"
```

**IMPORTANTE:** Para Gmail necesitas:
1. Ir a https://myaccount.google.com/security
2. Activar "Verificación en 2 pasos"
3. Ir a "Contraseñas de aplicaciones"
4. Generar una contraseña para "Correo"
5. Usar esa contraseña de 16 caracteres en `MAIL_PASSWORD`

#### **Opción B: Mailtrap (Para testing/desarrollo)**
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_username_mailtrap
MAIL_PASSWORD=tu_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@archiveycloud.com
MAIL_FROM_NAME="Archivey Cloud SGDEA"
```

Regístrate gratis en: https://mailtrap.io/

#### **Opción C: SendGrid (Para producción)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu_api_key_sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="Archivey Cloud SGDEA"
```

---

### **Paso 2: Limpiar Cache de Configuración**

Después de editar el `.env`, ejecuta:

```bash
php artisan config:clear
php artisan cache:clear
```

---

### **Paso 3: Probar el Envío de Correo**

#### **Opción 1: Crear un nuevo usuario**
Ve a `/register` y crea una cuenta nueva. Deberías:
1. Ver la pantalla "Verify Email"
2. Recibir un correo en tu bandeja de entrada

#### **Opción 2: Comando de prueba**
```bash
php artisan tinker
```

Luego ejecuta:
```php
Mail::raw('Correo de prueba desde Archivey Cloud', function ($message) {
    $message->to('tu_email@gmail.com')
            ->subject('Test de Email');
});
```

---

## 🔍 Verificar que los Correos se Envían

### **1. Revisar Logs (Si usas MAIL_MAILER=log)**
```bash
tail -f storage/logs/laravel.log
```

### **2. Revisar Base de Datos**
Los usuarios registrados deberían tener `email_verified_at = NULL`:
```sql
SELECT id, name, email, email_verified_at FROM users;
```

### **3. Verificar Queue (Si usas colas)**
```bash
php artisan queue:work
```

---

## 🐛 Problemas Comunes

### **"Connection refused" o "Connection timeout"**
- ✅ Verifica que el puerto no esté bloqueado por firewall
- ✅ Para Gmail, usa puerto 587 con TLS (no SSL en puerto 465)
- ✅ Verifica que tu proveedor de internet no bloquee SMTP

### **"Authentication failed"**
- ✅ Verifica usuario y contraseña
- ✅ Para Gmail, DEBES usar contraseña de aplicación (no tu contraseña normal)
- ✅ Verifica que 2FA esté activado en Gmail

### **Los correos van a SPAM**
- ✅ Configura SPF/DKIM en tu dominio (producción)
- ✅ Usa un servicio profesional como SendGrid
- ✅ Verifica que `MAIL_FROM_ADDRESS` sea un email válido

### **"Address in mailbox given [] does not comply"**
- ✅ Asegúrate de que `MAIL_FROM_ADDRESS` tenga un email válido
- ✅ No dejes comillas vacías en el .env

---

## 📊 Estado Actual del Sistema

### **✅ Componentes Funcionando**
- ✅ Evento `Registered` se dispara correctamente
- ✅ Notificación `CustomVerifyEmail` está configurada
- ✅ Template de email existe: `resources/views/emails/verify-email.blade.php`
- ✅ Modelo User implementa `MustVerifyEmail`
- ✅ Rutas de verificación están registradas
- ✅ Middleware `verified` está activo en rutas protegidas

### **⚠️ Requiere Configuración**
- ⚠️ **MAIL_MAILER en modo 'log'** - Cambiar a 'smtp'
- ⚠️ **Credenciales SMTP** - Configurar en .env
- ⚠️ **Testing** - Probar con un registro real

---

## 📝 Resumen de Cambios Realizados

### **RegisteredUserController.php**
```php
// ANTES:
return redirect()->intended(route('dashboard', absolute: false));

// DESPUÉS:
return redirect()->route('verification.notice');
```

**Efecto:** Usuario ve pantalla de verificación en lugar de ir directo al dashboard.

---

## 🎯 Próximos Pasos

1. **[CRÍTICO]** Configurar SMTP en `.env` con uno de los proveedores
2. **[CRÍTICO]** Ejecutar `php artisan config:clear`
3. **[RECOMENDADO]** Probar registro de usuario nuevo
4. **[OPCIONAL]** Configurar queue para envío asíncrono de emails

---

## 📞 Soporte Adicional

Si después de seguir estos pasos los correos siguen sin enviarse:

1. Revisa logs: `storage/logs/laravel.log`
2. Habilita debug SMTP: Agrega a `.env`:
   ```env
   LOG_LEVEL=debug
   ```
3. Ejecuta el comando de diagnóstico:
   ```bash
   php artisan diagnose:email
   ```

---

**Fecha:** 2025-11-04
**Módulo:** Autenticación y Verificación de Email
**Estado:** ⚠️ Requiere configuración SMTP
