<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu correo electrónico - Archivey Cloud</title>
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
        .verification-icon {
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
        .verify-button {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button a {
            background: linear-gradient(135deg, #2a3d83, #3d4fb8);
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .verify-button a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(42, 61, 131, 0.3);
        }
        .security-notice {
            background-color: #f8fafc;
            border-left: 4px solid #2a3d83;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice h4 {
            margin: 0 0 10px 0;
            color: #2a3d83;
            font-size: 14px;
        }
        .security-notice p {
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
            color: #2a3d83;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="{{ config('app.url') }}/images/Logo2.PNG" alt="Archivey Cloud SGDEA" class="logo-img">
            </div>
            <div class="logo">Archivey Cloud SGDEA</div>
            <p style="margin: 0; font-size: 16px; opacity: 0.9;">Sistema de Gestión Documental Empresarial Avanzado</p>
        </div>

        <div class="content">
            <div class="verification-icon">📧</div>
            <div class="title">Confirma tu dirección de correo electrónico</div>

            <div class="message">
                <p>¡Hola <strong>{{ $user->name }}</strong>!</p>
                
                <p>Gracias por registrarte en <strong>Archivey Cloud SGDEA</strong>. Para completar tu registro y acceder a todas las funcionalidades de nuestro sistema de gestión documental, necesitamos verificar tu dirección de correo electrónico.</p>
                
                <p>Haz clic en el botón de abajo para confirmar tu cuenta:</p>
            </div>

            <div class="verify-button">
                <a href="{{ $verificationUrl }}" target="_blank">✅ Verificar mi correo electrónico</a>
            </div>

            <div class="security-notice">
                <h4>🔒 Aviso de seguridad</h4>
                <p>Si no te registraste en Archivey Cloud, puedes ignorar este correo. Tu cuenta no será activada hasta que confirmes tu dirección de correo electrónico.</p>
            </div>

            <div class="alternative-link">
                <p>¿No puedes hacer clic en el botón? Copia y pega este enlace en tu navegador:</p>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
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
