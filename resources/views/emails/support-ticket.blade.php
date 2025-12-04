<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo Ticket de Soporte</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #2a3d83;
            color: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .ticket-info {
            background-color: #f8fafc;
            border-left: 4px solid #2a3d83;
            padding: 20px;
            margin: 20px 0;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .field-value {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .message-content {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 20px;
            border-radius: 6px;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        .meta-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Nuevo Ticket de Soporte</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Archivey Cloud - Sistema de Soporte</p>
        </div>

        <div class="ticket-info">
            <h2 style="margin-top: 0; color: #2a3d83;">Información del Ticket</h2>
            
            <div class="field">
                <div class="field-label">👤 Usuario:</div>
                <div class="field-value">{{ $ticketData['name'] }}</div>
            </div>

            <div class="field">
                <div class="field-label">📧 Email:</div>
                <div class="field-value">{{ $ticketData['email'] }}</div>
            </div>

            <div class="field">
                <div class="field-label">📋 Asunto:</div>
                <div class="field-value">{{ $ticketData['subject'] }}</div>
            </div>

            <div class="field">
                <div class="field-label">🕒 Fecha y Hora:</div>
                <div class="field-value">{{ $ticketData['submitted_at'] }}</div>
            </div>
        </div>

        <div class="field">
            <div class="field-label">💬 Mensaje:</div>
            <div class="message-content">{{ $ticketData['message'] }}</div>
        </div>

        <div class="meta-info">
            <h3 style="margin-bottom: 10px; color: #374151;">📊 Información Técnica:</h3>
            <p><strong>IP del Usuario:</strong> {{ $ticketData['user_ip'] }}</p>
            <p><strong>Navegador:</strong> {{ $ticketData['user_agent'] }}</p>
        </div>

        <div class="footer">
            <p><strong>Archivey Cloud - Soporte Técnico</strong></p>
            <p>Este es un mensaje automático generado por el sistema de tickets de soporte.</p>
            <p>Para responder al usuario, utiliza la dirección: {{ $ticketData['email'] }}</p>
        </div>
    </div>
</body>
</html>
