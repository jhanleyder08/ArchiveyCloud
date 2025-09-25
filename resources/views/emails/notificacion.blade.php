<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificacion->titulo }}</title>
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
            background: linear-gradient(135deg, {{ $prioridadColor }}, {{ $prioridadColor }}dd);
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
        .greeting {
            font-size: 16px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 16px;
        }
        .message {
            background-color: #f8fafc;
            border-left: 4px solid {{ $prioridadColor }};
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .message-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .message-body {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.5;
        }
        .details {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 14px;
        }
        .detail-value {
            color: #1f2937;
            font-size: 14px;
        }
        .action-button {
            display: inline-block;
            background-color: {{ $prioridadColor }};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            margin: 20px 0;
            transition: all 0.2s;
        }
        .action-button:hover {
            filter: brightness(110%);
            text-decoration: none;
            color: white;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        .logo {
            color: {{ $prioridadColor }};
            font-weight: 700;
            font-size: 18px;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ $iconoPrioridad }} ArchiveyCloud</div>
            <h1>{{ $notificacion->titulo }}</h1>
            <div class="priority-badge">
                Prioridad: {{ ucfirst($notificacion->prioridad) }}
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hola {{ $usuario->name }},
            </div>

            <div class="message">
                <div class="message-title">{{ $notificacion->titulo }}</div>
                <div class="message-body">{{ $notificacion->mensaje }}</div>
            </div>

            <!-- Details -->
            <div class="details">
                <div class="detail-item">
                    <span class="detail-label">Tipo:</span>
                    <span class="detail-value">{{ ucwords(str_replace('_', ' ', $notificacion->tipo)) }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Prioridad:</span>
                    <span class="detail-value" style="color: {{ $prioridadColor }}">
                        {{ $iconoPrioridad }} {{ ucfirst($notificacion->prioridad) }}
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value">{{ $notificacion->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($notificacion->programada_para)
                <div class="detail-item">
                    <span class="detail-label">Programada para:</span>
                    <span class="detail-value">{{ $notificacion->programada_para->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>

            <!-- Action Button -->
            @if($urlAccion)
            <div style="text-align: center;">
                <a href="{{ $urlAccion }}" class="action-button">
                    Ver en el Sistema 👁️
                </a>
            </div>
            @endif

            <!-- Additional Data -->
            @if($notificacion->datos && is_array($notificacion->datos))
            <div class="details">
                <h4 style="margin-top: 0; color: #1f2937;">Información Adicional:</h4>
                @foreach($notificacion->datos as $key => $value)
                <div class="detail-item">
                    <span class="detail-label">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                    <span class="detail-value">{{ is_array($value) ? json_encode($value) : $value }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <p style="color: #6b7280; font-size: 14px; margin-top: 24px;">
                📧 Este es un mensaje automático del sistema ArchiveyCloud. Si necesitas asistencia, 
                contacta con el administrador del sistema.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                <strong>ArchiveyCloud</strong> - Sistema de Gestión Documental<br>
                © {{ date('Y') }} Todos los derechos reservados.<br>
                <small>
                    Este email fue enviado automáticamente. Por favor no responder a este mensaje.<br>
                    Para soporte técnico, contacta con el administrador del sistema.
                </small>
            </div>
        </div>
    </div>
</body>
</html>
