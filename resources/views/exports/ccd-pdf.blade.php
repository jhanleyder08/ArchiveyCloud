<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCD - {{ $ccd->nombre }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid #2a3d83;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #2a3d83;
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 10pt;
        }
        
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-section h2 {
            color: #2a3d83;
            font-size: 12pt;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        
        .estructura-section {
            margin-top: 20px;
        }
        
        .estructura-section h2 {
            color: #2a3d83;
            font-size: 14pt;
            margin-bottom: 15px;
            border-bottom: 2px solid #2a3d83;
            padding-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background: #2a3d83;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9pt;
        }
        
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .nivel-fondo { background-color: #e3f2fd !important; }
        .nivel-seccion { background-color: #e8f5e9 !important; }
        .nivel-subseccion { background-color: #fff8e1 !important; }
        .nivel-serie { background-color: #f3e5f5 !important; }
        .nivel-subserie { background-color: #fce4ec !important; }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .badge-activo { background: #d4edda; color: #155724; }
        .badge-borrador { background: #e2e3e5; color: #383d41; }
        .badge-inactivo { background: #f8d7da; color: #721c24; }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8pt;
            color: #999;
        }
        
        .indent-1 { padding-left: 10px; }
        .indent-2 { padding-left: 25px; }
        .indent-3 { padding-left: 40px; }
        .indent-4 { padding-left: 55px; }
        .indent-5 { padding-left: 70px; }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CUADRO DE CLASIFICACIÓN DOCUMENTAL</h1>
        <p>{{ $ccd->nombre }}</p>
    </div>
    
    <div class="info-section">
        <h2>Información General</h2>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Código:</span>
                <span class="info-value">{{ $ccd->codigo }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value">{{ $ccd->nombre }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Versión:</span>
                <span class="info-value">{{ $ccd->version }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="info-value">
                    <span class="badge badge-{{ $ccd->estado }}">{{ ucfirst($ccd->estado) }}</span>
                </span>
            </div>
            @if($ccd->descripcion)
            <div class="info-row">
                <span class="info-label">Descripción:</span>
                <span class="info-value">{{ $ccd->descripcion }}</span>
            </div>
            @endif
            @if($ccd->fecha_aprobacion)
            <div class="info-row">
                <span class="info-label">Fecha Aprobación:</span>
                <span class="info-value">{{ $ccd->fecha_aprobacion->format('d/m/Y') }}</span>
            </div>
            @endif
            @if($ccd->fecha_vigencia_inicio)
            <div class="info-row">
                <span class="info-label">Vigencia Inicio:</span>
                <span class="info-value">{{ $ccd->fecha_vigencia_inicio->format('d/m/Y') }}</span>
            </div>
            @endif
            @if($ccd->fecha_vigencia_fin)
            <div class="info-row">
                <span class="info-label">Vigencia Fin:</span>
                <span class="info-value">{{ $ccd->fecha_vigencia_fin->format('d/m/Y') }}</span>
            </div>
            @endif
            @if($ccd->creador)
            <div class="info-row">
                <span class="info-label">Creado por:</span>
                <span class="info-value">{{ $ccd->creador->name }}</span>
            </div>
            @endif
            @if($ccd->aprobador)
            <div class="info-row">
                <span class="info-label">Aprobado por:</span>
                <span class="info-value">{{ $ccd->aprobador->name }}</span>
            </div>
            @endif
        </div>
    </div>
    
    <div class="estructura-section">
        <h2>Estructura Jerárquica</h2>
        
        @if(count($estructura) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Nivel</th>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 35%;">Nombre</th>
                    <th style="width: 12%;">Tipo</th>
                    <th style="width: 25%;">Descripción</th>
                    <th style="width: 8%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @php
                    function renderNiveles($niveles, $profundidad = 0) {
                        $html = '';
                        foreach ($niveles as $nivel) {
                            $tipoClass = 'nivel-' . $nivel['tipo_nivel'];
                            $indentClass = 'indent-' . min($profundidad + 1, 5);
                            $estado = ($nivel['activo'] ?? true) ? 'Activo' : 'Inactivo';
                            
                            $html .= '<tr class="' . $tipoClass . '">';
                            $html .= '<td style="text-align: center;">' . ($profundidad + 1) . '</td>';
                            $html .= '<td>' . e($nivel['codigo']) . '</td>';
                            $html .= '<td class="' . $indentClass . '">' . e($nivel['nombre']) . '</td>';
                            $html .= '<td>' . ucfirst($nivel['tipo_nivel']) . '</td>';
                            $html .= '<td>' . e($nivel['descripcion'] ?? '-') . '</td>';
                            $html .= '<td style="text-align: center;">' . $estado . '</td>';
                            $html .= '</tr>';
                            
                            if (!empty($nivel['hijos'])) {
                                $html .= renderNiveles($nivel['hijos'], $profundidad + 1);
                            }
                        }
                        return $html;
                    }
                @endphp
                {!! renderNiveles($estructura) !!}
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #666; padding: 20px;">
            No hay niveles configurados en este CCD.
        </p>
        @endif
    </div>
    
    <div class="footer">
        <p>Documento generado el {{ $fecha_exportacion }} | Sistema de Gestión Documental ArchiveyCloud</p>
    </div>
</body>
</html>
