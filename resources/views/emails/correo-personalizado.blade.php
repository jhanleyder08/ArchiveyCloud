<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensaje de ArchiveyCloud</title>
    @php
        $headerColor = match($prioridad ?? 'media') {
            'urgente' => '#dc2626',
            'alta' => '#ea580c',
            'media' => '#2563eb',
            default => '#6b7280'
        };
    @endphp
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, {{ $headerColor }}, {{ $headerColor }}dd);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .priority-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .content {
            padding: 32px 24px;
        }
        .message-box {
            background-color: #f8fafc;
            border-left: 4px solid {{ $headerColor }};
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        .message-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #374151;
            font-size: 15px;
        }
        .footer {
            background-color: #f3f4f6;
            padding: 20px 24px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .footer-logo {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .sender-info {
            background-color: #eff6ff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
        .sender-info strong {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $prioridadEmoji ?? '📧' }} ArchiveyCloud</h1>
            <div class="priority-badge">
                Prioridad: {{ ucfirst($prioridad ?? 'media') }}
            </div>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; color: #374151; margin-bottom: 20px;">
                Has recibido un mensaje del sistema de gestión documental:
            </p>

            <div class="message-box">
                <div class="message-content">{!! nl2br(e($mensaje)) !!}</div>
            </div>

            <div class="sender-info">
                <strong>Enviado por:</strong> {{ $remitente ?? 'Administrador' }}<br>
                <strong>Fecha:</strong> {{ $fecha ?? now()->format('d/m/Y H:i:s') }}
            </div>
        </div>

        <div class="footer">
            <div class="footer-logo">📁 ArchiveyCloud SGDEA</div>
            <p style="margin: 8px 0 0 0;">
                Sistema de Gestión Documental Electrónica de Archivos
            </p>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #9ca3af;">
                Este correo fue enviado desde la plataforma ArchiveyCloud.
                Por favor no responda directamente a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>
