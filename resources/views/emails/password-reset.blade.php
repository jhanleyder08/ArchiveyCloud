<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Archivey Cloud</title>
    <style>
        /* Inter font fallback */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: 0;
            padding: 24px;
            line-height: 1.6;
        }
        
        .email-container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        /* Header con gradiente institucional */
        .header {
            background: linear-gradient(135deg, #2a3d83 0%, #1e40af 50%, #3730a3 100%);
            padding: 48px 32px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="80" cy="40" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="40" cy="80" r="1" fill="%23ffffff" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
        }
        
        .brand {
            position: relative;
            z-index: 1;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .brand-title {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        
        .brand-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-size: 16px;
            font-weight: 500;
            margin: 0;
        }
        
        /* Contenido principal */
        .main-content {
            padding: 48px 32px;
        }
        
        .security-badge {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #dc2626, #ef4444);
            border-radius: 18px;
            margin: 0 auto 32px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.25);
        }
        
        .title {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
            margin-bottom: 16px;
            letter-spacing: -0.025em;
        }
        
        .subtitle {
            font-size: 18px;
            color: #64748b;
            text-align: center;
            margin-bottom: 32px;
            font-weight: 500;
        }
        
        .greeting {
            font-size: 16px;
            color: #334155;
            margin-bottom: 24px;
        }
        
        .message {
            font-size: 16px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 40px;
        }
        
        .message p {
            margin-bottom: 16px;
        }
        
        .message p:last-child {
            margin-bottom: 0;
        }
        
        /* Alerta de seguridad */
        .security-notice {
            background: linear-gradient(135deg, #fef2f2 0%, #fde8e8 100%);
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin: 32px 0;
            border-left: 4px solid #dc2626;
        }
        
        .security-title {
            font-size: 14px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-text {
            font-size: 14px;
            color: #7f1d1d;
            margin: 0;
        }
        
        /* Botón principal - estilo shadcn */
        .cta-button {
            text-align: center;
            margin: 40px 0;
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            padding: 16px 32px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.25), 0 2px 4px -1px rgba(220, 38, 38, 0.1);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 48px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px -5px rgba(220, 38, 38, 0.35);
        }
        
        /* Sección alternativa */
        .alternative-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
        }
        
        .alternative-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }
        
        .alternative-link {
            font-size: 14px;
            color: #6b7280;
            word-break: break-all;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        
        .alternative-link a {
            color: #dc2626;
            text-decoration: none;
        }
        
        .alternative-link a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        .footer {
            background: #f1f5f9;
            padding: 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-brand {
            font-weight: 600;
            color: #374151;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .footer-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .footer-note {
            font-size: 12px;
            color: #9ca3af;
            padding: 16px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            body {
                padding: 16px;
            }
            
            .email-container {
                border-radius: 12px;
            }
            
            .header {
                padding: 32px 24px;
            }
            
            .main-content {
                padding: 32px 24px;
            }
            
            .brand-title {
                font-size: 28px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .btn-primary {
                padding: 14px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header con branding -->
        <div class="header">
            <div class="brand">
                <div class="logo-icon">📁</div>
                <h1 class="brand-title">Archivey Cloud</h1>
                <p class="brand-subtitle">Sistema de Gestión Documental Empresarial Avanzado</p>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <div class="security-badge">🔑</div>
            
            <h2 class="title">Restablecer contraseña</h2>
            <p class="subtitle">Solicitud de cambio de contraseña</p>
            
            <div class="greeting">
                ¡Hola <strong>{{ $user->name }}</strong>! 👋
            </div>
            
            <div class="message">
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>Archivey Cloud SGDEA</strong>.</p>
                
                <p>Si fuiste tú quien hizo esta solicitud, haz clic en el botón de abajo para crear una nueva contraseña:</p>
            </div>
            
            <div class="cta-button">
                <a href="{{ $resetUrl }}" class="btn-primary">
                    🔒 Restablecer mi contraseña
                </a>
            </div>
            
            <div class="security-notice">
                <div class="security-title">
                    ⚠️ Aviso de seguridad importante
                </div>
                <p class="security-text">Si no solicitaste este cambio de contraseña, puedes ignorar este correo de forma segura. Tu contraseña actual seguirá siendo válida.</p>
            </div>
            
            <div class="alternative-section">
                <div class="alternative-title">¿El botón no funciona?</div>
                <div class="alternative-link">
                    <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
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
