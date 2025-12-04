<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Disposición Final #{{ $disposicion->id }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            color: #333; 
            line-height: 1.5;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 3px solid #2a3d83; 
        }
        .header h1 { 
            color: #2a3d83; 
            font-size: 22px; 
            margin-bottom: 5px; 
        }
        .header p { 
            color: #666; 
            margin: 5px 0; 
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pendiente { background-color: #f3f4f6; color: #4b5563; }
        .badge-en_revision { background-color: #dbeafe; color: #1e40af; }
        .badge-aprobado { background-color: #dcfce7; color: #166534; }
        .badge-rechazado { background-color: #fee2e2; color: #991b1b; }
        .badge-ejecutado { background-color: #f3e8ff; color: #7c3aed; }
        .badge-cancelado { background-color: #f3f4f6; color: #6b7280; }
        
        .info-box { 
            background-color: #f8f9fa; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-left: 4px solid #2a3d83; 
        }
        .section-title { 
            background-color: #2a3d83; 
            color: white; 
            padding: 10px 15px; 
            margin-top: 25px; 
            margin-bottom: 15px; 
            font-weight: bold; 
            font-size: 14px;
        }
        .data-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-grid td {
            padding: 10px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .data-grid .label {
            width: 35%;
            font-weight: bold;
            color: #4b5563;
            background-color: #f9fafb;
        }
        .data-grid .value {
            width: 65%;
        }
        .text-box {
            background-color: #f9fafb;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .text-box h4 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 13px;
        }
        .text-box p {
            margin: 0;
            color: #4b5563;
        }
        .alert-box {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
        }
        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        .footer { 
            text-align: center; 
            font-size: 10px; 
            color: #666; 
            margin-top: 40px; 
            padding-top: 15px; 
            border-top: 1px solid #dee2e6; 
        }
        .signature-area {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid #333;
        }
        .signature-box p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DISPOSICIÓN FINAL DOCUMENTAL</h1>
        <p>Propuesta de Disposición #{{ $disposicion->id }}</p>
        <p style="margin-top: 10px;">
            <span class="badge badge-{{ $disposicion->estado }}">{{ $disposicion->estado_label }}</span>
        </p>
    </div>

    <div class="info-box">
        <p><strong>Fecha de Generación:</strong> {{ $fecha_generacion }}</p>
        <p><strong>Tipo de Disposición:</strong> {{ $disposicion->tipo_disposicion_label }}</p>
    </div>

    @if($disposicion->esta_vencida)
    <div class="alert-box alert-danger">
        <strong>ATENCIÓN:</strong> Esta disposición está vencida y requiere atención inmediata.
    </div>
    @elseif($disposicion->dias_para_vencimiento <= 30 && $disposicion->dias_para_vencimiento > 0)
    <div class="alert-box alert-warning">
        <strong>AVISO:</strong> Esta disposición vence en {{ $disposicion->dias_para_vencimiento }} días.
    </div>
    @endif

    <div class="section-title">INFORMACIÓN DEL ITEM AFECTADO</div>
    
    <table class="data-grid">
        <tr>
            <td class="label">Tipo de Item</td>
            <td class="value">{{ $disposicion->expediente ? 'Expediente' : 'Documento' }}</td>
        </tr>
        @if($disposicion->expediente)
        <tr>
            <td class="label">Código del Expediente</td>
            <td class="value">{{ $disposicion->expediente->codigo }}</td>
        </tr>
        <tr>
            <td class="label">Título</td>
            <td class="value">{{ $disposicion->expediente->titulo }}</td>
        </tr>
        <tr>
            <td class="label">Serie Documental</td>
            <td class="value">
                @if($disposicion->expediente->serie)
                    {{ $disposicion->expediente->serie->codigo }} - {{ $disposicion->expediente->serie->nombre }}
                @else
                    No especificada
                @endif
            </td>
        </tr>
        @if($disposicion->expediente->subserie)
        <tr>
            <td class="label">Subserie Documental</td>
            <td class="value">{{ $disposicion->expediente->subserie->codigo }} - {{ $disposicion->expediente->subserie->nombre }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Ubicación Física</td>
            <td class="value">{{ $disposicion->expediente->ubicacion_fisica ?? 'No especificada' }}</td>
        </tr>
        <tr>
            <td class="label">Estado del Ciclo de Vida</td>
            <td class="value">{{ ucfirst($disposicion->expediente->estado ?? 'No definido') }}</td>
        </tr>
        @elseif($disposicion->documento)
        <tr>
            <td class="label">Nombre del Documento</td>
            <td class="value">{{ $disposicion->documento->nombre ?? $disposicion->documento->titulo }}</td>
        </tr>
        <tr>
            <td class="label">Expediente Asociado</td>
            <td class="value">{{ $disposicion->documento->expediente->codigo ?? 'N/A' }} - {{ $disposicion->documento->expediente->titulo ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Serie Documental</td>
            <td class="value">
                @if($disposicion->documento->expediente && $disposicion->documento->expediente->serie)
                    {{ $disposicion->documento->expediente->serie->codigo }} - {{ $disposicion->documento->expediente->serie->nombre }}
                @else
                    No especificada
                @endif
            </td>
        </tr>
        @if($disposicion->documento->expediente && $disposicion->documento->expediente->subserie)
        <tr>
            <td class="label">Subserie Documental</td>
            <td class="value">{{ $disposicion->documento->expediente->subserie->codigo }} - {{ $disposicion->documento->expediente->subserie->nombre }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Ubicación Física</td>
            <td class="value">{{ $disposicion->documento->ubicacion_fisica ?? 'No especificada' }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">DETALLES DE LA DISPOSICIÓN</div>
    
    <table class="data-grid">
        <tr>
            <td class="label">Tipo de Disposición</td>
            <td class="value">{{ $disposicion->tipo_disposicion_label }}</td>
        </tr>
        <tr>
            <td class="label">Estado Actual</td>
            <td class="value">{{ $disposicion->estado_label }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Propuesta</td>
            <td class="value">{{ \Carbon\Carbon::parse($disposicion->fecha_propuesta)->format('d/m/Y') }}</td>
        </tr>
        @if($disposicion->fecha_vencimiento_retencion)
        <tr>
            <td class="label">Fecha de Vencimiento de Retención</td>
            <td class="value">{{ \Carbon\Carbon::parse($disposicion->fecha_vencimiento_retencion)->format('d/m/Y') }}</td>
        </tr>
        @endif
        @if($disposicion->fecha_aprobacion)
        <tr>
            <td class="label">Fecha de Aprobación</td>
            <td class="value">{{ \Carbon\Carbon::parse($disposicion->fecha_aprobacion)->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
        @if($disposicion->fecha_ejecucion)
        <tr>
            <td class="label">Fecha de Ejecución</td>
            <td class="value">{{ \Carbon\Carbon::parse($disposicion->fecha_ejecucion)->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">JUSTIFICACIÓN Y OBSERVACIONES</div>
    
    <div class="text-box">
        <h4>Justificación</h4>
        <p>{{ $disposicion->justificacion }}</p>
    </div>

    @if($disposicion->observaciones)
    <div class="text-box">
        <h4>Observaciones Generales</h4>
        <p>{{ $disposicion->observaciones }}</p>
    </div>
    @endif

    @if($disposicion->observaciones_aprobacion)
    <div class="text-box" style="background-color: #dcfce7; border-color: #22c55e;">
        <h4 style="color: #166534;">Observaciones de Aprobación</h4>
        <p>{{ $disposicion->observaciones_aprobacion }}</p>
    </div>
    @endif

    @if($disposicion->observaciones_rechazo)
    <div class="text-box" style="background-color: #fee2e2; border-color: #ef4444;">
        <h4 style="color: #991b1b;">Observaciones de Rechazo</h4>
        <p>{{ $disposicion->observaciones_rechazo }}</p>
    </div>
    @endif

    <div class="section-title">RESPONSABLES</div>
    
    <table class="data-grid">
        <tr>
            <td class="label">Responsable de la Propuesta</td>
            <td class="value">
                @if($disposicion->responsable)
                    {{ $disposicion->responsable->name }}<br>
                    <small style="color: #6b7280;">{{ $disposicion->responsable->email }}</small>
                @else
                    No asignado
                @endif
            </td>
        </tr>
        @if($disposicion->aprobadoPor)
        <tr>
            <td class="label">Aprobado por</td>
            <td class="value">
                {{ $disposicion->aprobadoPor->name }}<br>
                <small style="color: #6b7280;">{{ $disposicion->aprobadoPor->email }}</small>
            </td>
        </tr>
        @endif
    </table>

    @if($disposicion->estado === 'aprobado' || $disposicion->estado === 'ejecutado')
    <div class="signature-area">
        <table style="width: 100%;">
            <tr>
                <td style="width: 45%; text-align: center; padding-top: 60px;">
                    <div style="border-top: 1px solid #333; padding-top: 10px; margin: 0 20px;">
                        <p style="margin: 5px 0; font-weight: bold;">Responsable</p>
                        <p style="margin: 0; font-size: 11px; color: #666;">{{ $disposicion->responsable->name ?? 'N/A' }}</p>
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; text-align: center; padding-top: 60px;">
                    <div style="border-top: 1px solid #333; padding-top: 10px; margin: 0 20px;">
                        <p style="margin: 5px 0; font-weight: bold;">Aprobador</p>
                        <p style="margin: 0; font-size: 11px; color: #666;">{{ $disposicion->aprobadoPor->name ?? 'N/A' }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Sistema de Gestión Documental y Archivos - Disposición Final #{{ $disposicion->id }}</p>
        <p>Documento generado el {{ $fecha_generacion }}</p>
        <p>Este documento es confidencial y solo debe ser usado para fines autorizados</p>
    </div>
</body>
</html>
