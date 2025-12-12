<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>TRD - {{ $trd->codigo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
        }
        
        .page {
            padding: 10mm;
        }
        
        /* ENCABEZADO PRINCIPAL */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }
        
        .header-table td, .header-table th {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }
        
        .logo-cell {
            width: 15%;
            text-align: center;
            padding: 5px;
        }
        
        .logo-cell img {
            max-width: 60px;
            max-height: 50px;
        }
        
        .title-cell {
            width: 50%;
            text-align: center;
        }
        
        .title-main {
            font-size: 11pt;
            font-weight: bold;
        }
        
        .title-sub {
            font-size: 10pt;
            font-weight: bold;
        }
        
        .info-cell {
            width: 35%;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8pt;
        }
        
        .info-label {
            font-weight: bold;
            background-color: #f0f0f0;
            width: 60%;
        }
        
        /* INFORMACIÓN DE UNIDAD Y DEPENDENCIA */
        .unit-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }
        
        .unit-info td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 8pt;
        }
        
        .unit-label {
            font-weight: bold;
            width: 50%;
        }
        
        .unit-code {
            font-weight: bold;
            width: 10%;
            text-align: center;
        }
        
        .unit-value {
            width: 10%;
            text-align: center;
        }
        
        /* CONVENCIONES */
        .conventions {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }
        
        .conventions td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 7pt;
        }
        
        .conventions-title {
            font-weight: bold;
            background-color: #e0e0e0;
            text-align: center;
        }
        
        .convention-code {
            font-weight: bold;
            text-align: center;
            width: 8%;
        }
        
        .convention-desc {
            width: 42%;
        }
        
        /* TABLA PRINCIPAL DE TRD */
        .trd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }
        
        .trd-table th {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 7pt;
            font-weight: bold;
            background-color: #d0d0d0;
            text-align: center;
            vertical-align: middle;
        }
        
        .trd-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 7pt;
            vertical-align: top;
        }
        
        .trd-table .col-codigo {
            width: 8%;
            text-align: center;
        }
        
        .trd-table .col-series {
            width: 22%;
        }
        
        .trd-table .col-soporte {
            width: 5%;
            text-align: center;
        }
        
        .trd-table .col-retencion {
            width: 6%;
            text-align: center;
        }
        
        .trd-table .col-disposicion {
            width: 4%;
            text-align: center;
        }
        
        .trd-table .col-procedimiento {
            width: 30%;
        }
        
        .subserie-row td {
            padding-left: 15px;
        }
        
        .tipo-row td {
            padding-left: 30px;
            font-style: italic;
        }
        
        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 10mm;
            right: 10mm;
            font-size: 7pt;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 3mm;
        }
        
        .page-number {
            text-align: right;
        }
        
        /* MARCADORES DE DISPOSICIÓN */
        .check-mark {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .header-group {
            text-align: center;
            font-size: 6pt;
        }
        
        .header-subgroup {
            font-size: 6pt;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- ENCABEZADO PRINCIPAL -->
        <table class="header-table">
            <tr>
                <td class="logo-cell" rowspan="3">
                    @if(file_exists(public_path('logo.png')))
                        <img src="{{ public_path('logo.png') }}" alt="Logo">
                    @else
                        <div style="font-size: 8pt; font-weight: bold;">LOGO<br>ENTIDAD</div>
                    @endif
                </td>
                <td class="title-cell" rowspan="2">
                    <div class="title-main">{{ $trd->nombre_unidad_administrativa ?? 'HOSPITAL UNIVERSITARIO DEL VALLE "EVARISTO GARCÍA" E.S.E.' }}</div>
                    <div class="title-sub">TABLAS DE RETENCIÓN DOCUMENTAL</div>
                </td>
                <td class="info-cell">
                    <table class="info-table">
                        <tr>
                            <td class="info-label">CÓDIGO</td>
                            <td>{{ $trd->codigo }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="info-cell">
                    <table class="info-table">
                        <tr>
                            <td class="info-label">VERSIÓN</td>
                            <td>{{ str_pad($trd->version, 2, '0', STR_PAD_LEFT) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="title-cell" style="font-size: 8pt;">
                    <!-- Espacio adicional si se necesita -->
                </td>
                <td class="info-cell">
                    <table class="info-table">
                        <tr>
                            <td class="info-label">FECHA DE EMISIÓN</td>
                            <td colspan="3" style="text-align: center;">
                                @if($trd->fecha_aprobacion)
                                    <table style="width: 100%; border: none;">
                                        <tr>
                                            <td style="border: none; text-align: center; font-weight: bold;">DÍA</td>
                                            <td style="border: none; text-align: center; font-weight: bold;">MES</td>
                                            <td style="border: none; text-align: center; font-weight: bold;">AÑO</td>
                                        </tr>
                                        <tr>
                                            <td style="border: none; text-align: center;">{{ $trd->fecha_aprobacion->format('d') }}</td>
                                            <td style="border: none; text-align: center;">{{ $trd->fecha_aprobacion->format('m') }}</td>
                                            <td style="border: none; text-align: center;">{{ $trd->fecha_aprobacion->format('Y') }}</td>
                                        </tr>
                                    </table>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- INFORMACIÓN DE UNIDAD ADMINISTRATIVA Y DEPENDENCIA -->
        <table class="unit-info">
            <tr>
                <td class="unit-label">UNIDAD ADMINISTRATIVA: {{ $trd->nombre_unidad_administrativa ?? '-' }}</td>
                <td class="unit-code">CÓDIGO</td>
                <td class="unit-value">{{ $trd->codigo_unidad_administrativa ?? '-' }}</td>
                <td rowspan="2" style="width: 25%; vertical-align: top; padding: 0;">
                    <!-- CONVENCIONES -->
                    <table class="conventions" style="margin: 0;">
                        <tr>
                            <td colspan="2" class="conventions-title">CONVENCIONES</td>
                        </tr>
                        <tr>
                            <td class="convention-code">CT</td>
                            <td class="convention-desc">Conservación Total</td>
                        </tr>
                        <tr>
                            <td class="convention-code">E</td>
                            <td class="convention-desc">Eliminación</td>
                        </tr>
                        <tr>
                            <td class="convention-code">D</td>
                            <td class="convention-desc">Digitalización</td>
                        </tr>
                        <tr>
                            <td class="convention-code">S</td>
                            <td class="convention-desc">Selección</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="unit-label">DEPENDENCIA PRODUCTORA: {{ $trd->nombre_dependencia ?? '-' }}</td>
                <td class="unit-code">CÓDIGO</td>
                <td class="unit-value">{{ $trd->codigo_dependencia ?? '-' }}</td>
            </tr>
        </table>
        
        <!-- TABLA PRINCIPAL DE TRD -->
        <table class="trd-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-codigo">CÓDIGO</th>
                    <th rowspan="2" class="col-series">SERIES, SUBSERIES Y TIPOS DOCUMENTALES</th>
                    <th colspan="2" class="header-group">SOPORTE</th>
                    <th colspan="2" class="header-group">RETENCIÓN</th>
                    <th colspan="4" class="header-group">Disposición Final</th>
                    <th rowspan="2" class="col-procedimiento">PROCEDIMIENTO</th>
                </tr>
                <tr>
                    <th class="col-soporte header-subgroup">Doc.<br>Físico</th>
                    <th class="col-soporte header-subgroup">Doc.<br>Elect.</th>
                    <th class="col-retencion header-subgroup">Archivo<br>Gestión</th>
                    <th class="col-retencion header-subgroup">Archivo<br>Central</th>
                    <th class="col-disposicion header-subgroup">CT</th>
                    <th class="col-disposicion header-subgroup">E</th>
                    <th class="col-disposicion header-subgroup">D</th>
                    <th class="col-disposicion header-subgroup">S</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trd->series as $serie)
                    <tr>
                        <td class="col-codigo">{{ $serie->codigo }}</td>
                        <td class="col-series"><strong>{{ $serie->nombre }}</strong></td>
                        <td class="col-soporte">{{ $serie->soporte_fisico ? 'X' : '' }}</td>
                        <td class="col-soporte">{{ $serie->soporte_electronico ? 'X' : '' }}</td>
                        <td class="col-retencion">{{ $serie->retencion_gestion ?? '-' }}</td>
                        <td class="col-retencion">{{ $serie->retencion_central ?? '-' }}</td>
                        <td class="col-disposicion">{{ $serie->disposicion_ct ? 'X' : '' }}</td>
                        <td class="col-disposicion">{{ $serie->disposicion_e ? 'X' : '' }}</td>
                        <td class="col-disposicion">{{ $serie->disposicion_d ? 'X' : '' }}</td>
                        <td class="col-disposicion">{{ $serie->disposicion_s ? 'X' : '' }}</td>
                        <td class="col-procedimiento">{{ $serie->procedimiento ?? '' }}</td>
                    </tr>
                    
                    @foreach($serie->subseries as $subserie)
                        <tr class="subserie-row">
                            <td class="col-codigo">{{ $subserie->codigo }}</td>
                            <td class="col-series">{{ $subserie->nombre }}</td>
                            <td class="col-soporte">{{ $subserie->soporte_fisico ? 'X' : '' }}</td>
                            <td class="col-soporte">{{ $subserie->soporte_electronico ? 'X' : '' }}</td>
                            <td class="col-retencion">{{ $subserie->retencion_gestion ?? '' }}</td>
                            <td class="col-retencion">{{ $subserie->retencion_central ?? '' }}</td>
                            <td class="col-disposicion">{{ $subserie->disposicion_ct ? 'X' : '' }}</td>
                            <td class="col-disposicion">{{ $subserie->disposicion_e ? 'X' : '' }}</td>
                            <td class="col-disposicion">{{ $subserie->disposicion_d ? 'X' : '' }}</td>
                            <td class="col-disposicion">{{ $subserie->disposicion_s ? 'X' : '' }}</td>
                            <td class="col-procedimiento">{{ $subserie->procedimiento ?? '' }}</td>
                        </tr>
                        
                        @foreach($subserie->tiposDocumentales ?? [] as $tipo)
                            <tr class="tipo-row">
                                <td class="col-codigo"></td>
                                <td class="col-series">- {{ $tipo->nombre }}</td>
                                <td class="col-soporte">{{ $tipo->soporte_fisico ? 'X' : '' }}</td>
                                <td class="col-soporte">{{ $tipo->soporte_electronico ? 'X' : '' }}</td>
                                <td class="col-retencion"></td>
                                <td class="col-retencion"></td>
                                <td class="col-disposicion"></td>
                                <td class="col-disposicion"></td>
                                <td class="col-disposicion"></td>
                                <td class="col-disposicion"></td>
                                <td class="col-procedimiento"></td>
                            </tr>
                        @endforeach
                    @endforeach
                    
                    @foreach($serie->tiposDocumentales ?? [] as $tipo)
                        <tr class="tipo-row">
                            <td class="col-codigo"></td>
                            <td class="col-series">- {{ $tipo->nombre }}</td>
                            <td class="col-soporte">{{ $tipo->soporte_fisico ? 'X' : '' }}</td>
                            <td class="col-soporte">{{ $tipo->soporte_electronico ? 'X' : '' }}</td>
                            <td class="col-retencion"></td>
                            <td class="col-retencion"></td>
                            <td class="col-disposicion"></td>
                            <td class="col-disposicion"></td>
                            <td class="col-disposicion"></td>
                            <td class="col-disposicion"></td>
                            <td class="col-procedimiento"></td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 20px;">
                            No hay series documentales registradas en esta TRD
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($trd->observaciones_generales)
        <div style="margin-top: 5mm; padding: 3mm; border: 1px solid #000; font-size: 8pt;">
            <strong>OBSERVACIONES GENERALES:</strong><br>
            {{ $trd->observaciones_generales }}
        </div>
        @endif
    </div>
    
    <div class="footer">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: left;">
                    Generado por ArchiveyCloud SGDEA - {{ now()->format('d/m/Y H:i') }}
                </td>
                <td style="border: none; text-align: right;">
                    Página 1 de 1
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
