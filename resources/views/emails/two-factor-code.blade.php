<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación 2FA</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .code-box {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #667eea;
            font-family: 'Courier New', monospace;
            margin: 0;
        }
        .code-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: #ffc107;
            color: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-weight: bold;
            margin-right: 10px;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .security-tips {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #dee2e6;
        }
        .security-tips h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
        }
        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        .security-tips li {
            margin-bottom: 8px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 20px;
            }
            .code {
                font-size: 32px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Verificación de Seguridad</h1>
        </div>
        
        <div class="content">
            <p>Hola,</p>
            <p>Has solicitado acceso a tu cuenta de <strong>{{ config('app.name') }}</strong>. Para completar el inicio de sesión, usa el siguiente código de verificación:</p>
            
            <div class="code-box">
                <div class="code">{{ $code }}</div>
                <div class="code-label">Código de Verificación</div>
            </div>
            
            <div class="warning">
                <span class="warning-icon">!</span>
                <strong>Este código expira en {{ $expirationMinutes }} minutos.</strong>
            </div>
            
            <div class="info">
                <strong>💡 Consejo de Seguridad:</strong><br>
                No compartas este código con nadie. Nuestro equipo nunca te pedirá este código por teléfono, email o mensaje.
            </div>
            
            <p style="text-align: center; color: #6c757d; margin-top: 30px;">
                Si no intentaste iniciar sesión, ignora este correo y considera cambiar tu contraseña.
            </p>
            
            <div class="security-tips">
                <h3>🛡️ Consejos de Seguridad</h3>
                <ul>
                    <li>Nunca compartas tus códigos de verificación</li>
                    <li>Usa contraseñas únicas y seguras</li>
                    <li>Habilita notificaciones de inicio de sesión</li>
                    <li>Mantén tu información de contacto actualizada</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Sistema de Gestión Documental y Archivo Electrónico</p>
            <p style="margin-top: 15px;">
                Este es un correo automático, por favor no respondas.<br>
                Si necesitas ayuda, contacta a nuestro equipo de soporte.
            </p>
        </div>
    </div>
</body>
</html>
