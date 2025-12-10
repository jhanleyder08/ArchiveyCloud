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
        
        @page {
            margin: 2cm 2cm 3cm 2cm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        
        .page-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 30px;
            border: 2px solid #000;
        }
        
        th {
            background: #d9d9d9;
            color: #000;
            padding: 8px 5px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            border: 1px solid #000;
            vertical-align: middle;
            line-height: 1.2;
        }
        
        td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 8pt;
            vertical-align: middle;
            line-height: 1.3;
            background: #fff;
        }
        
        .col-no { 
            width: 30px; 
            text-align: center;
            font-size: 7pt;
        }
        .col-seccion { width: 95px; padding-left: 6px; }
        .col-codigo-sec { width: 50px; text-align: center; }
        .col-subseccion { width: 95px; padding-left: 6px; }
        .col-codigo-sub { width: 50px; text-align: center; }
        .col-serie { width: 90px; padding-left: 6px; }
        .col-codigo-ser { width: 50px; text-align: center; }
        .col-subserie { width: 115px; padding-left: 6px; }
        .col-codigo-subs { width: 50px; text-align: center; }
        
        .text-center { text-align: center; }
        
        .footer {
            position: fixed;
            bottom: 2cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #000;
        }
        
        .page-number:after {
            content: "Página " counter(page);
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="page-title">CUADROS DE CLASIFICACIÓN DOCUMENTAL</div>
    </div>
    
    @if(count($estructura) > 0)
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-seccion">SECCIÓN</th>
                <th class="col-codigo-sec">CÓDIGO</th>
                <th class="col-subseccion">SUBSECCIÓN</th>
                <th class="col-codigo-sub">CÓDIGO</th>
                <th class="col-serie">SERIE</th>
                <th class="col-codigo-ser">CÓDIGO</th>
                <th class="col-subserie">SUBSERIE</th>
                <th class="col-codigo-subs">CÓDIGO</th>
            </tr>
        </thead>
        <tbody>
            @php
                $contador = 1;
                
                function renderFilasTabla(&$contador, $niveles, $seccion = '', $codigoSec = '', $subseccion = '', $codigoSub = '', $serie = '', $codigoSer = '') {
                    $html = '';
                    
                    foreach ($niveles as $nivel) {
                        $nombre = e($nivel['nombre']);
                        $codigo = e($nivel['codigo']);
                        $tipo = $nivel['tipo_nivel'];
                        
                        if ($tipo === 'seccion' || $tipo === 'fondo') {
                            // Nueva sección - mostrar en primera fila
                            $html .= '<tr>';
                            $html .= '<td class="col-no text-center">' . $contador++ . '</td>';
                            $html .= '<td class="col-seccion">' . $nombre . '</td>';
                            $html .= '<td class="col-codigo-sec text-center">' . $codigo . '</td>';
                            $html .= '<td class="col-subseccion"></td>';
                            $html .= '<td class="col-codigo-sub"></td>';
                            $html .= '<td class="col-serie"></td>';
                            $html .= '<td class="col-codigo-ser"></td>';
                            $html .= '<td class="col-subserie"></td>';
                            $html .= '<td class="col-codigo-subs"></td>';
                            $html .= '</tr>';
                            
                            if (!empty($nivel['hijos'])) {
                                $html .= renderFilasTabla($contador, $nivel['hijos'], $nombre, $codigo, '', '', '', '');
                            }
                            
                        } elseif ($tipo === 'subseccion') {
                            // Nueva subsección
                            $html .= '<tr>';
                            $html .= '<td class="col-no text-center">' . $contador++ . '</td>';
                            $html .= '<td class="col-seccion"></td>';
                            $html .= '<td class="col-codigo-sec"></td>';
                            $html .= '<td class="col-subseccion">' . $nombre . '</td>';
                            $html .= '<td class="col-codigo-sub text-center">' . $codigo . '</td>';
                            $html .= '<td class="col-serie"></td>';
                            $html .= '<td class="col-codigo-ser"></td>';
                            $html .= '<td class="col-subserie"></td>';
                            $html .= '<td class="col-codigo-subs"></td>';
                            $html .= '</tr>';
                            
                            if (!empty($nivel['hijos'])) {
                                $html .= renderFilasTabla($contador, $nivel['hijos'], $seccion, $codigoSec, $nombre, $codigo, '', '');
                            }
                            
                        } elseif ($tipo === 'serie') {
                            // Nueva serie
                            $html .= '<tr>';
                            $html .= '<td class="col-no text-center">' . $contador++ . '</td>';
                            $html .= '<td class="col-seccion"></td>';
                            $html .= '<td class="col-codigo-sec"></td>';
                            $html .= '<td class="col-subseccion"></td>';
                            $html .= '<td class="col-codigo-sub"></td>';
                            $html .= '<td class="col-serie">' . $nombre . '</td>';
                            $html .= '<td class="col-codigo-ser text-center">' . $codigo . '</td>';
                            $html .= '<td class="col-subserie"></td>';
                            $html .= '<td class="col-codigo-subs"></td>';
                            $html .= '</tr>';
                            
                            if (!empty($nivel['hijos'])) {
                                $html .= renderFilasTabla($contador, $nivel['hijos'], $seccion, $codigoSec, $subseccion, $codigoSub, $nombre, $codigo);
                            }
                            
                        } elseif ($tipo === 'subserie') {
                            // Subserie
                            $html .= '<tr>';
                            $html .= '<td class="col-no text-center">' . $contador++ . '</td>';
                            $html .= '<td class="col-seccion"></td>';
                            $html .= '<td class="col-codigo-sec"></td>';
                            $html .= '<td class="col-subseccion"></td>';
                            $html .= '<td class="col-codigo-sub"></td>';
                            $html .= '<td class="col-serie"></td>';
                            $html .= '<td class="col-codigo-ser"></td>';
                            $html .= '<td class="col-subserie">' . $nombre . '</td>';
                            $html .= '<td class="col-codigo-subs text-center">' . $codigo . '</td>';
                            $html .= '</tr>';
                            
                            if (!empty($nivel['hijos'])) {
                                $html .= renderFilasTabla($contador, $nivel['hijos'], $seccion, $codigoSec, $subseccion, $codigoSub, $serie, $codigo);
                            }
                        }
                    }
                    
                    return $html;
                }
            @endphp
            {!! renderFilasTabla($contador, $estructura) !!}
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #666; padding: 15px; font-size: 8pt;">
        No hay niveles configurados en este CCD.
    </p>
    @endif
    
    <div class="footer">
        <p class="page-number"></p>
    </div>
</body>
</html>
