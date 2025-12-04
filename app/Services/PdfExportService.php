<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExportService
{
    public static function exportIndicesElectronicos($indices, $filtros = [])
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Generar HTML
        $html = self::generateIndicesHTML($indices, $filtros);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = 'indices_electronicos_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    private static function generateIndicesHTML($indices, $filtros = [])
    {
        $filtrosTexto = '';
        if (!empty($filtros)) {
            $filtrosArray = [];
            foreach ($filtros as $key => $value) {
                if (!empty($value) && $value !== 'all') {
                    $filtrosArray[] = ucfirst($key) . ': ' . $value;
                }
            }
            if (!empty($filtrosArray)) {
                $filtrosTexto = '<p style="margin: 10px 0; font-size: 12px; color: #666;"><strong>Filtros aplicados:</strong> ' . implode(' | ', $filtrosArray) . '</p>';
            }
        }

        $totalRegistros = $indices->count();
        $fechaExportacion = now()->format('d/m/Y H:i:s');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Índices Electrónicos - ArchiveyCloud</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #2563EB;
                    padding-bottom: 15px;
                }
                .header h1 {
                    color: #2563EB;
                    font-size: 18px;
                    margin: 0 0 5px 0;
                    font-weight: bold;
                }
                .header .subtitle {
                    font-size: 12px;
                    color: #666;
                    margin: 5px 0;
                }
                .info-section {
                    margin-bottom: 20px;
                    background-color: #f8f9fa;
                    padding: 10px;
                    border-radius: 5px;
                }
                .info-section p {
                    margin: 5px 0;
                    font-size: 11px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 9px;
                }
                th {
                    background-color: #1F2937;
                    color: white;
                    padding: 8px 4px;
                    text-align: center;
                    font-weight: bold;
                    border: 1px solid #374151;
                }
                td {
                    padding: 6px 4px;
                    border: 1px solid #D1D5DB;
                    vertical-align: top;
                    word-wrap: break-word;
                }
                tr:nth-child(even) {
                    background-color: #f9fafb;
                }
                tr:nth-child(odd) {
                    background-color: white;
                }
                .text-center { text-align: center; }
                .text-vital { color: #DC2626; font-weight: bold; }
                .text-historico { color: #059669; font-weight: bold; }
                .nivel-publico { background-color: #DBEAFE; color: #1E40AF; }
                .nivel-reservado { background-color: #FEF3C7; color: #92400E; }
                .nivel-confidencial { background-color: #FEE2E2; color: #DC2626; }
                .nivel-secreto { background-color: #F3E8FF; color: #7C3AED; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 8px;
                    color: #666;
                    border-top: 1px solid #E5E7EB;
                    padding-top: 10px;
                }
                .page-break {
                    page-break-before: always;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ÍNDICES ELECTRÓNICOS</h1>
                <div class="subtitle">Sistema de Gestión Documental ArchiveyCloud</div>
            </div>
            
            <div class="info-section">
                <p><strong>Fecha de exportación:</strong> ' . $fechaExportacion . '</p>
                <p><strong>Total de registros:</strong> ' . $totalRegistros . '</p>
                ' . $filtrosTexto . '
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 3%;">ID</th>
                        <th style="width: 6%;">Tipo</th>
                        <th style="width: 8%;">Código</th>
                        <th style="width: 20%;">Título</th>
                        <th style="width: 12%;">Serie</th>
                        <th style="width: 10%;">Responsable</th>
                        <th style="width: 6%;">Acceso</th>
                        <th style="width: 5%;">Folios</th>
                        <th style="width: 6%;">Tamaño</th>
                        <th style="width: 4%;">Vital</th>
                        <th style="width: 5%;">Histórico</th>
                        <th style="width: 8%;">F. Indexación</th>
                        <th style="width: 7%;">Ubicación</th>
                    </tr>
                </thead>
                <tbody>';

        $contador = 0;
        foreach ($indices as $indice) {
            // Salto de página cada 30 registros
            if ($contador > 0 && $contador % 30 === 0) {
                $html .= '</tbody></table><div class="page-break"></div><table><thead>
                    <tr>
                        <th style="width: 3%;">ID</th>
                        <th style="width: 6%;">Tipo</th>
                        <th style="width: 8%;">Código</th>
                        <th style="width: 20%;">Título</th>
                        <th style="width: 12%;">Serie</th>
                        <th style="width: 10%;">Responsable</th>
                        <th style="width: 6%;">Acceso</th>
                        <th style="width: 5%;">Folios</th>
                        <th style="width: 6%;">Tamaño</th>
                        <th style="width: 4%;">Vital</th>
                        <th style="width: 5%;">Histórico</th>
                        <th style="width: 8%;">F. Indexación</th>
                        <th style="width: 7%;">Ubicación</th>
                    </tr>
                </thead><tbody>';
            }

            $nivelAccesoClass = '';
            $nivelAcceso = $indice->getEtiquetaNivelAcceso();
            switch ($indice->nivel_acceso) {
                case 'publico':
                    $nivelAccesoClass = 'nivel-publico';
                    break;
                case 'reservado':
                    $nivelAccesoClass = 'nivel-reservado';
                    break;
                case 'confidencial':
                    $nivelAccesoClass = 'nivel-confidencial';
                    break;
                case 'secreto':
                    $nivelAccesoClass = 'nivel-secreto';
                    break;
            }

            $html .= '<tr>
                <td class="text-center">' . $indice->id . '</td>
                <td class="text-center">' . ucfirst($indice->tipo_entidad) . '</td>
                <td>' . htmlspecialchars($indice->getCodigoCompleto()) . '</td>
                <td>' . htmlspecialchars($indice->titulo) . '</td>
                <td>' . htmlspecialchars($indice->serie_documental ?? '') . '</td>
                <td>' . htmlspecialchars($indice->responsable ?? '') . '</td>
                <td class="text-center ' . $nivelAccesoClass . '">' . $nivelAcceso . '</td>
                <td class="text-center">' . ($indice->cantidad_folios ?? 0) . '</td>
                <td class="text-center">' . htmlspecialchars($indice->getTamaño()) . '</td>
                <td class="text-center ' . ($indice->es_vital ? 'text-vital' : '') . '">' . ($indice->es_vital ? 'SÍ' : 'No') . '</td>
                <td class="text-center ' . ($indice->es_historico ? 'text-historico' : '') . '">' . ($indice->es_historico ? 'SÍ' : 'No') . '</td>
                <td class="text-center">' . ($indice->fecha_indexacion ? $indice->fecha_indexacion->format('d/m/Y') : '') . '</td>
                <td>' . htmlspecialchars($indice->ubicacion_fisica ?? '') . '</td>
            </tr>';

            $contador++;
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>Documento generado por ArchiveyCloud - Sistema de Gestión Documental</p>
                <p>Total de registros exportados: ' . $totalRegistros . ' | Fecha: ' . $fechaExportacion . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}
