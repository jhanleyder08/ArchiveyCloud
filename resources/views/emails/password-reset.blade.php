<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablece tu contraseña - Archivey Cloud</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2a3d83, #3d4fb8);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-img {
            max-width: 120px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 15px;
        }
        .content {
            padding: 40px 30px;
        }
        .reset-icon {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #2a3d83;
            text-align: center;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 30px;
        }
        .reset-button {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button a {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .reset-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        .security-notice {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice h4 {
            margin: 0 0 10px 0;
            color: #dc2626;
            font-size: 14px;
        }
        .security-notice p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
        .expiration-notice {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .expiration-notice h4 {
            margin: 0 0 10px 0;
            color: #f59e0b;
            font-size: 14px;
        }
        .expiration-notice p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        .alternative-link {
            font-size: 14px;
            color: #6b7280;
            margin-top: 20px;
            text-align: center;
        }
        .alternative-link a {
            color: #dc2626;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('images/Logo2.PNG') }}" alt="Archivey Cloud SGDEA" class="logo-img">
            </div>
            <div class="logo">Archivey Cloud SGDEA</div>
            <p style="margin: 0; font-size: 16px; opacity: 0.9;">Sistema de Gestión Documental Empresarial Avanzado</p>
        </div>

        <div class="content">
            <div class="reset-icon">🔐</div>
            <div class="title">Restablece tu contraseña</div>

            <div class="message">
                <p>¡Hola <strong>{{ $user->name }}</strong>!</p>
                
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Archivey Cloud SGDEA</strong>. Si fuiste tú quien solicitó este cambio, haz clic en el botón de abajo para crear una nueva contraseña.</p>
                
                <p>Si no solicitaste este restablecimiento, puedes ignorar este correo y tu contraseña permanecerá sin cambios.</p>
            </div>

            <div class="reset-button">
                <a href="{{ $resetUrl }}" target="_blank">🔒 Restablecer mi contraseña</a>
            </div>

            <div class="expiration-notice">
                <h4>⏰ Tiempo de expiración</h4>
                <p>Este enlace es válido por 60 minutos por razones de seguridad. Si no lo usas dentro de este tiempo, deberás solicitar un nuevo restablecimiento.</p>
            </div>

            <div class="security-notice">
                <h4>🚨 Importante para tu seguridad</h4>
                <p>• Nunca compartas este enlace con nadie<br>
                • Si no solicitaste este cambio, ignora este correo<br>
                • Si tienes dudas sobre la seguridad de tu cuenta, contacta al administrador del sistema</p>
            </div>

            <div class="alternative-link">
                <p>¿No puedes hacer clic en el botón? Copia y pega este enlace en tu navegador:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>Archivey Cloud SGDEA</strong><br>
            Sistema de Gestión Documental Empresarial Avanzado</p>
            <p style="margin-top: 10px; font-size: 12px;">
                Este es un correo automático, por favor no responder directamente.
            </p>
        </div>
    </div>
</body>
</html>
