<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a Archivey Cloud!</title>
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
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-icon {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }
        .title {
            color: #2a3d83;
            font-size: 28px;
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
        .user-info {
            background-color: #f1f5f9;
            border-left: 4px solid #2a3d83;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .user-info h3 {
            color: #2a3d83;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .cta-button {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 15px 25px;
            background: linear-gradient(135deg, #2a3d83, #3d4fb8);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        .features {
            margin: 30px 0;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 8px;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 40px;
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
            <div class="welcome-icon">🎉</div>
            
            <h1 class="title">¡Bienvenido, {{ $user->name }}!</h1>
            
            <div class="message">
                <p>¡Nos complace darte la bienvenida a <strong>Archivey Cloud SGDEA</strong>!</p>
                
                <p>Tu cuenta ha sido creada exitosamente y ya puedes comenzar a utilizar nuestro sistema de gestión documental y archivos.</p>
            </div>
            
            <div class="user-info">
                <h3>📋 Información de tu Cuenta</h3>
                <p><strong>Nombre:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Rol:</strong> {{ $user->role->name ?? 'Usuario' }}</p>
                <p><strong>Fecha de registro:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <a href="{{ config('app.url') }}" class="cta-button">
                Acceder al Sistema
            </a>
            
            <div class="features">
                <h3 style="color: #2a3d83; text-align: center; margin-bottom: 25px;">🚀 ¿Qué puedes hacer ahora?</h3>
                
                <div class="feature-item">
                    <div class="feature-icon">📄</div>
                    <div>
                        <strong>Gestionar Documentos</strong><br>
                        <span style="color: #64748b;">Sube, organiza y gestiona tus documentos de forma segura</span>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🔍</div>
                    <div>
                        <strong>Búsqueda Avanzada</strong><br>
                        <span style="color: #64748b;">Encuentra rápidamente cualquier documento o archivo</span>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">👥</div>
                    <div>
                        <strong>Colaboración</strong><br>
                        <span style="color: #64748b;">Comparte y colabora en documentos con tu equipo</span>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <div>
                        <strong>Seguridad</strong><br>
                        <span style="color: #64748b;">Tus documentos están protegidos con encriptación de nivel empresarial</span>
                    </div>
                </div>
            </div>
            
            <div class="message">
                <p><strong>¿Necesitas ayuda?</strong> Nuestro equipo está aquí para apoyarte. Si tienes alguna pregunta, no dudes en contactarnos.</p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Archivey Cloud SGDEA</strong> - Sistema de Gestión Documental</p>
            <p>Gracias por confiar en nosotros para gestionar tus documentos importantes.</p>
            <p class="timestamp">Enviado el {{ date('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
