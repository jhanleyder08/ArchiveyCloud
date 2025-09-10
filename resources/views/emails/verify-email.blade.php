<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu correo electrónico - Archivey Cloud</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: #2a3d83;
            color: #ffffff;
            padding: 40px 32px;
            text-align: center;
        }
        
        .brand-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .brand-subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .content {
            padding: 48px 32px;
        }
        
        .title {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
            text-align: center;
            margin-bottom: 16px;
        }
        
        .greeting {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 24px;
        }
        
        .message {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .message p {
            margin-bottom: 16px;
        }
        
        .message p:last-child {
            margin-bottom: 0;
        }
        
        .cta-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .btn-verify {
            display: inline-block;
            background: #2a3d83 !important;
            color: #ffffff !important;
            text-decoration: none !important;
            font-weight: 500;
            font-size: 16px;
            padding: 14px 32px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        
        .btn-verify:hover {
            background: #1e2a5e !important;
            color: #ffffff !important;
        }
        
        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 32px 0;
        }
        
        .alternative {
            background: #f7fafc;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
        }
        
        .alternative-title {
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .alternative-text {
            font-size: 14px;
            color: #718096;
            word-break: break-all;
        }
        
        .alternative-text a {
            color: #2a3d83;
            text-decoration: none;
        }
        
        .alternative-text a:hover {
            text-decoration: underline;
        }
        
        .footer {
            background: #f7fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-text {
            font-size: 14px;
            color: #718096;
            margin-bottom: 8px;
        }
        
        .footer-brand {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .security-notice {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 16px;
            padding: 12px;
            background: #ffffff;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 20px 16px;
            }
            
            .header {
                padding: 32px 24px;
            }
            
            .content {
                padding: 32px 24px;
            }
            
            .brand-title {
                font-size: 20px;
            }
            
            .title {
                font-size: 20px;
            }
            
            .btn-verify {
                padding: 12px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="brand-title">Archivey Cloud</h1>
            <p class="brand-subtitle">Sistema de Gestión Documental Empresarial Avanzado</p>
        </div>
        
        <div class="content">
            <h2 class="title">Confirma tu correo electrónico</h2>
            
            <div class="greeting">
                Hola <strong>{{ $user->name }}</strong>,
            </div>
            
            <div class="message">
                <p>Gracias por registrarte en Archivey Cloud. Para completar la configuración de tu cuenta, necesitamos verificar tu dirección de correo electrónico.</p>
                
                <p>Haz clic en el siguiente botón para confirmar tu cuenta:</p>
            </div>
            
            <div class="cta-section">
                <a href="{{ $verificationUrl }}" class="btn-verify">Verificar correo electrónico</a>
            </div>
            
            <div class="divider"></div>
            
            <div class="alternative">
                <div class="alternative-title">Si el botón no funciona</div>
                <div class="alternative-text">
                    Copia y pega este enlace en tu navegador:<br>
                    <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-brand">Archivey Cloud SGDEA</div>
            <div class="footer-text">Sistema de Gestión Documental Empresarial Avanzado</div>
            
            <div class="security-notice">
                Si no creaste esta cuenta, puedes ignorar este correo de forma segura.
            </div>
        </div>
    </div>
</body>
</html>
