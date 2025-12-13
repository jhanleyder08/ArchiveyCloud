<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensaje de ArchiveyCloud</title>
    @php
        // Configuración de colores y estilos por prioridad
        $config = match($prioridad ?? 'media') {
            'critica' => [
                'gradient' => 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)',
                'color' => '#dc2626',
                'bgLight' => '#fef2f2',
                'borderColor' => '#fecaca',
                'textColor' => '#991b1b',
                'icon' => '🚨',
                'label' => 'CRÍTICA',
                'description' => 'Requiere atención inmediata'
            ],
            'alta' => [
                'gradient' => 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
                'color' => '#ea580c',
                'bgLight' => '#fff7ed',
                'borderColor' => '#fed7aa',
                'textColor' => '#c2410c',
                'icon' => '⚠️',
                'label' => 'ALTA',
                'description' => 'Requiere atención prioritaria'
            ],
            'media' => [
                'gradient' => 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
                'color' => '#0ea5e9',
                'bgLight' => '#f0f9ff',
                'borderColor' => '#bae6fd',
                'textColor' => '#0369a1',
                'icon' => '📋',
                'label' => 'MEDIA',
                'description' => 'Información importante'
            ],
            default => [
                'gradient' => 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)',
                'color' => '#22c55e',
                'bgLight' => '#f0fdf4',
                'borderColor' => '#bbf7d0',
                'textColor' => '#15803d',
                'icon' => '📌',
                'label' => 'BAJA',
                'description' => 'Información general'
            ]
        };
    @endphp
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f1f5f9;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header con gradiente según prioridad -->
                    <tr>
                        <td style="background: {{ $config['gradient'] }}; padding: 40px 30px; text-align: center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center;">
                                        <!-- Logo/Icono -->
                                        <div style="width: 70px; height: 70px; background-color: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; line-height: 70px; font-size: 32px;">
                                            {{ $config['icon'] }}
                                        </div>
                                        <!-- Título -->
                                        <h1 style="margin: 0 0 8px 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">
                                            ArchiveyCloud
                                        </h1>
                                        <p style="margin: 0 0 16px 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                            Sistema de Gestión Documental
                                        </p>
                                        <!-- Badge de prioridad -->
                                        <span style="display: inline-block; background-color: rgba(255,255,255,0.25); color: #ffffff; padding: 8px 20px; border-radius: 50px; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border: 1px solid rgba(255,255,255,0.3);">
                                            {{ $config['icon'] }} PRIORIDAD {{ $config['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Barra de estado -->
                    <tr>
                        <td style="background-color: {{ $config['bgLight'] }}; padding: 12px 30px; border-bottom: 1px solid {{ $config['borderColor'] }};">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="color: {{ $config['textColor'] }}; font-size: 13px; font-weight: 600;">
                                        {{ $config['icon'] }} {{ $config['description'] }}
                                    </td>
                                    <td style="text-align: right; color: {{ $config['textColor'] }}; font-size: 12px;">
                                        {{ $fecha ?? now()->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Contenido principal -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <!-- Saludo -->
                            <p style="margin: 0 0 24px 0; color: #1e293b; font-size: 16px; line-height: 1.6;">
                                Estimado/a usuario,
                            </p>
                            
                            <p style="margin: 0 0 24px 0; color: #475569; font-size: 15px; line-height: 1.6;">
                                Has recibido una comunicación desde el sistema de gestión documental ArchiveyCloud:
                            </p>

                            <!-- Caja del mensaje -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0;">
                                <tr>
                                    <td style="background-color: {{ $config['bgLight'] }}; border-left: 4px solid {{ $config['color'] }}; border-radius: 0 12px 12px 0; padding: 24px;">
                                        <p style="margin: 0 0 8px 0; color: {{ $config['textColor'] }}; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                            📝 Mensaje
                                        </p>
                                        <div style="color: #334155; font-size: 15px; line-height: 1.8; white-space: pre-wrap; word-wrap: break-word;">
                                            {!! nl2br(e($mensaje)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Información del remitente -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 32px; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td width="40" style="vertical-align: top;">
                                                    <div style="width: 40px; height: 40px; background: {{ $config['gradient'] }}; border-radius: 50%; text-align: center; line-height: 40px; color: white; font-weight: bold; font-size: 16px;">
                                                        {{ strtoupper(substr($remitente ?? 'A', 0, 1)) }}
                                                    </div>
                                                </td>
                                                <td style="padding-left: 16px; vertical-align: middle;">
                                                    <p style="margin: 0; color: #1e293b; font-size: 14px; font-weight: 600;">
                                                        {{ $remitente ?? 'Administrador del Sistema' }}
                                                    </p>
                                                    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px;">
                                                        Administrador ArchiveyCloud
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 24px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="color: #64748b; font-size: 12px;">
                                                    <strong style="color: #475569;">📅 Fecha:</strong> {{ $fecha ?? now()->format('d/m/Y H:i:s') }}
                                                </td>
                                                <td style="text-align: right; color: #64748b; font-size: 12px;">
                                                    <strong style="color: #475569;">🏷️ Prioridad:</strong> 
                                                    <span style="color: {{ $config['color'] }}; font-weight: 600;">{{ $config['label'] }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Botón de acción -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 32px;">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; background: {{ $config['gradient'] }}; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 14px {{ $config['color'] }}40;">
                                            🚀 Ir al Sistema
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 32px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center;">
                                        <!-- Logo footer -->
                                        <p style="margin: 0 0 12px 0; color: #ffffff; font-size: 18px; font-weight: 700;">
                                            📁 ArchiveyCloud
                                        </p>
                                        <p style="margin: 0 0 16px 0; color: #94a3b8; font-size: 13px;">
                                            Sistema de Gestión Documental Electrónica de Archivos
                                        </p>
                                        
                                        <!-- Separador -->
                                        <div style="width: 60px; height: 2px; background-color: {{ $config['color'] }}; margin: 20px auto;"></div>
                                        
                                        <!-- Disclaimer -->
                                        <p style="margin: 0; color: #64748b; font-size: 11px; line-height: 1.6;">
                                            Este es un mensaje automático del sistema ArchiveyCloud.<br>
                                            Si necesitas asistencia, contacta con el administrador del sistema.
                                        </p>
                                        
                                        <!-- Copyright -->
                                        <p style="margin: 16px 0 0 0; color: #475569; font-size: 11px;">
                                            © {{ date('Y') }} ArchiveyCloud. Todos los derechos reservados.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Texto legal fuera del contenedor -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 20px auto 0;">
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-size: 11px; line-height: 1.5;">
                            Este correo fue enviado a tu dirección registrada en ArchiveyCloud.<br>
                            Por favor no respondas directamente a este mensaje.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
