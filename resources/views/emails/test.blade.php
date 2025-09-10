<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba SMTP - Archivey Cloud</title>
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
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .title {
            color: #2a3d83;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .message {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .details {
            background-color: #f1f5f9;
            border-left: 4px solid #2a3d83;
            padding: 20px;
            margin: 20px 0;
        }
        .details h3 {
            color: #2a3d83;
            margin-top: 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
        .timestamp {
            color: #94a3b8;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📁 Archivey Cloud</div>
            <div>Sistema de Gestión Documental y Archivos</div>
        </div>
        
        <div class="content">
            <div class="success-icon">✅</div>
            
            <h1 class="title">¡Configuración SMTP Exitosa!</h1>
            
            <div class="message">
                <p>¡Excelente! El sistema de correos SMTP de <strong>Archivey Cloud SGDEA</strong> está funcionando correctamente.</p>
                
                <p>Este correo de prueba confirma que:</p>
                <ul>
                    <li>✅ La conexión con Gmail SMTP está establecida</li>
                    <li>✅ Las credenciales de autenticación son correctas</li>
                    <li>✅ El sistema puede enviar correos exitosamente</li>
                    <li>✅ Los templates de email funcionan correctamente</li>
                </ul>
            </div>
            
            <div class="details">
                <h3>📧 Detalles de Configuración</h3>
                <p><strong>Servidor SMTP:</strong> Gmail (smtp.gmail.com:587)</p>
                <p><strong>Remitente:</strong> cloudarchivey@gmail.com</p>
                <p><strong>Encriptación:</strong> TLS</p>
                <p><strong>Estado:</strong> ✅ Configurado y funcional</p>
            </div>
            
            <div class="message">
                <p>Ahora puedes usar el sistema de correos para:</p>
                <ul>
                    <li>🔐 Recuperación de contraseñas</li>
                    <li>👥 Notificaciones de registro</li>
                    <li>📨 Notificaciones del sistema</li>
                    <li>📋 Alertas del SGDEA</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Archivey Cloud SGDEA</strong> - Sistema de Gestión Documental</p>
            <p class="timestamp">Enviado el {{ date('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
