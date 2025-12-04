<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class DashboardPdfService
{
    public static function exportDashboardEjecutivo($data)
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Generar HTML del dashboard
        $html = self::generateDashboardHTML($data);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'dashboard_ejecutivo_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    private static function generateDashboardHTML($data)
    {
        $fechaExportacion = now()->format('d/m/Y H:i:s');
        $metricas = $data['metricas_generales'];
        $kpis = $data['kpis_criticos'];
        $cumplimiento = $data['cumplimiento'];
        $usuarios_activos = $data['usuarios_activos'];
        $distribucion = $data['distribucion_trabajo'];
        $alertas = $data['alertas_criticas'];

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Dashboard Ejecutivo - ArchiveyCloud</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11px;
                    margin: 20px;
                    color: #333;
                    line-height: 1.4;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #2563EB;
                    padding-bottom: 20px;
                }
                .header h1 {
                    color: #2563EB;
                    font-size: 24px;
                    margin: 0 0 10px 0;
                    font-weight: bold;
                }
                .header .subtitle {
                    font-size: 14px;
                    color: #666;
                    margin: 5px 0;
                }
                .info-section {
                    margin-bottom: 20px;
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    border-left: 4px solid #2563EB;
                }
                .info-section p {
                    margin: 5px 0;
                    font-size: 12px;
                }
                .metrics-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 15px;
                    margin-bottom: 30px;
                }
                .metric-card {
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 15px;
                    text-align: center;
                    border-top: 3px solid #2563EB;
                }
                .metric-value {
                    font-size: 18px;
                    font-weight: bold;
                    color: #2563EB;
                    margin-bottom: 5px;
                }
                .metric-label {
                    font-size: 10px;
                    color: #666;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .section-title {
                    font-size: 16px;
                    font-weight: bold;
                    color: #1f2937;
                    margin: 30px 0 15px 0;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #e5e7eb;
                }
                .kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 10px;
                    margin-bottom: 20px;
                }
                .kpi-card {
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    padding: 12px;
                    text-align: center;
                }
                .kpi-value {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 3px;
                }
                .kpi-label {
                    font-size: 9px;
                    color: #666;
                }
                .cumplimiento-section {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                }
                .cumplimiento-porcentaje {
                    font-size: 32px;
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 10px;
                }
                .cumplimiento-excelente { color: #059669; }
                .cumplimiento-bueno { color: #d97706; }
                .cumplimiento-critico { color: #dc2626; }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 10px;
                }
                th {
                    background-color: #1f2937;
                    color: white;
                    padding: 8px 6px;
                    text-align: left;
                    font-weight: bold;
                    border: 1px solid #374151;
                }
                td {
                    padding: 6px;
                    border: 1px solid #d1d5db;
                    vertical-align: top;
                }
                tr:nth-child(even) {
                    background-color: #f9fafb;
                }
                .alerta-critica {
                    background-color: #fef2f2;
                    border-left: 4px solid #dc2626;
                    padding: 10px;
                    margin-bottom: 10px;
                    border-radius: 4px;
                }
                .alerta-alta {
                    background-color: #fffbeb;
                    border-left: 4px solid #d97706;
                    padding: 10px;
                    margin-bottom: 10px;
                    border-radius: 4px;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 9px;
                    color: #666;
                    border-top: 1px solid #e5e7eb;
                    padding-top: 15px;
                }
                .page-break {
                    page-break-before: always;
                }
                .two-column {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>DASHBOARD EJECUTIVO</h1>
                <div class="subtitle">Sistema de Gestión Documental ArchiveyCloud</div>
                <div class="subtitle">Reporte Ejecutivo Integral</div>
            </div>
            
            <div class="info-section">
                <p><strong>Fecha de exportación:</strong> ' . $fechaExportacion . '</p>
                <p><strong>Usuario:</strong> ' . auth()->user()->name . ' (' . auth()->user()->email . ')</p>
                <p><strong>Tipo de reporte:</strong> Dashboard Ejecutivo Completo</p>
            </div>

            <div class="section-title">📊 MÉTRICAS GENERALES DEL SISTEMA</div>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['total_documentos']) . '</div>
                    <div class="metric-label">Total Documentos</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['total_expedientes']) . '</div>
                    <div class="metric-label">Total Expedientes</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['total_usuarios']) . '</div>
                    <div class="metric-label">Usuarios Activos</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['total_series']) . '</div>
                    <div class="metric-label">Series Documentales</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['almacenamiento_total'], 2) . ' GB</div>
                    <div class="metric-label">Almacenamiento Total</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">' . number_format($metricas['indices_generados']) . '</div>
                    <div class="metric-label">Índices Generados</div>
                </div>
            </div>

            <div class="section-title">🎯 INDICADORES CLAVE DE RENDIMIENTO (KPIs)</div>
            <div class="kpi-grid">';

        // KPIs críticos
        $kpiLabels = [
            'documentos_procesados_semana' => 'Docs. Procesados (Semana)',
            'expedientes_creados_semana' => 'Expedientes Creados (Semana)',
            'expedientes_vencidos' => 'Expedientes Vencidos',
            'expedientes_proximo_vencimiento' => 'Próximo Vencimiento',
            'workflows_pendientes' => 'Workflows Pendientes',
            'workflows_vencidos' => 'Workflows Vencidos',
            'prestamos_activos' => 'Préstamos Activos',
            'prestamos_vencidos' => 'Préstamos Vencidos'
        ];

        foreach ($kpiLabels as $key => $label) {
            $value = $kpis[$key] ?? 0;
            $colorClass = ($key === 'expedientes_vencidos' || $key === 'workflows_vencidos' || $key === 'prestamos_vencidos') 
                ? 'color: #dc2626;' : 'color: #2563EB;';
            
            $html .= '
                <div class="kpi-card">
                    <div class="kpi-value" style="' . $colorClass . '">' . number_format($value) . '</div>
                    <div class="kpi-label">' . $label . '</div>
                </div>';
        }

        $html .= '
            </div>

            <div class="section-title">✅ CUMPLIMIENTO NORMATIVO</div>
            <div class="cumplimiento-section">
                <div class="cumplimiento-porcentaje ' . self::getCumplimientoClass($cumplimiento['porcentaje_cumplimiento_general']) . '">
                    ' . number_format($cumplimiento['porcentaje_cumplimiento_general'], 1) . '%
                </div>
                <div style="text-align: center; margin-bottom: 15px;">
                    <strong>Porcentaje de Cumplimiento General</strong>
                </div>
                <div class="two-column">
                    <div>
                        <p><strong>Expedientes en regla:</strong> ' . number_format($cumplimiento['expedientes_en_regla']) . '</p>
                        <p><strong>Expedientes con alertas:</strong> ' . number_format($cumplimiento['expedientes_con_alertas']) . '</p>
                    </div>
                </div>
            </div>';

        // Cumplimiento por series
        if (!empty($cumplimiento['cumplimiento_por_series'])) {
            $html .= '
            <h3 style="margin: 20px 0 10px 0;">Cumplimiento por Series Documentales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Serie Documental</th>
                        <th style="text-align: center;">Total Expedientes</th>
                        <th style="text-align: center;">En Regla</th>
                        <th style="text-align: center;">% Cumplimiento</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($cumplimiento['cumplimiento_por_series'] as $serie) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($serie->nombre) . '</td>
                    <td style="text-align: center;">' . $serie->total_expedientes . '</td>
                    <td style="text-align: center;">' . $serie->en_regla . '</td>
                    <td style="text-align: center; color: ' . self::getCumplimientoColorHex($serie->porcentaje) . ';">
                        <strong>' . number_format($serie->porcentaje, 1) . '%</strong>
                    </td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        // Usuarios más activos
        if (!empty($usuarios_activos)) {
            $html .= '
            <div class="page-break"></div>
            <div class="section-title">👥 USUARIOS MÁS ACTIVOS (ÚLTIMOS 30 DÍAS)</div>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th style="text-align: center;">Docs. Creados</th>
                        <th style="text-align: center;">Expedientes</th>
                        <th style="text-align: center;">Total Actividad</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($usuarios_activos as $usuario) {
                $total = ($usuario->documentos_creados ?? 0) + ($usuario->expedientes_gestionados ?? 0) + ($usuario->workflows_iniciados ?? 0);
                $html .= '<tr>
                    <td><strong>' . htmlspecialchars($usuario->name) . '</strong></td>
                    <td>' . htmlspecialchars($usuario->email) . '</td>
                    <td style="text-align: center;">' . ($usuario->documentos_creados ?? 0) . '</td>
                    <td style="text-align: center;">' . ($usuario->expedientes_gestionados ?? 0) . '</td>
                    <td style="text-align: center;"><strong>' . $total . '</strong></td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        // Distribución por estados de expedientes
        if (!empty($distribucion['expedientes_por_estado'])) {
            $html .= '
            <div class="section-title">📋 DISTRIBUCIÓN DE EXPEDIENTES POR ESTADO</div>
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th style="text-align: center;">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>';

            $totalExpedientes = $distribucion['expedientes_por_estado']->sum('total');
            foreach ($distribucion['expedientes_por_estado'] as $estado) {
                $porcentaje = $totalExpedientes > 0 ? ($estado->total / $totalExpedientes) * 100 : 0;
                $html .= '<tr>
                    <td><strong>' . ucfirst(str_replace('_', ' ', $estado->estado)) . '</strong></td>
                    <td style="text-align: center;">' . number_format($estado->total) . '</td>
                    <td style="text-align: center;">' . number_format($porcentaje, 1) . '%</td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        // Alertas críticas
        if (!empty($alertas['notificaciones_criticas']) && count($alertas['notificaciones_criticas']) > 0) {
            $html .= '
            <div class="section-title">🚨 ALERTAS CRÍTICAS PENDIENTES</div>';

            foreach ($alertas['notificaciones_criticas'] as $alerta) {
                $claseAlerta = $alerta->prioridad === 'critica' ? 'alerta-critica' : 'alerta-alta';
                $html .= '
                <div class="' . $claseAlerta . '">
                    <strong>' . htmlspecialchars($alerta->titulo) . '</strong><br>
                    <small>Prioridad: ' . ucfirst($alerta->prioridad) . ' | Fecha: ' . 
                    \Carbon\Carbon::parse($alerta->created_at)->format('d/m/Y H:i') . '</small><br>
                    ' . htmlspecialchars($alerta->mensaje) . '
                </div>';
            }
        }

        $html .= '
            <div class="footer">
                <p>Documento generado por ArchiveyCloud - Sistema de Gestión Documental</p>
                <p>Dashboard Ejecutivo | Fecha: ' . $fechaExportacion . ' | Usuario: ' . auth()->user()->name . '</p>
                <p>Este reporte contiene información confidencial del sistema de gestión documental</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    private static function getCumplimientoClass($porcentaje)
    {
        if ($porcentaje >= 90) return 'cumplimiento-excelente';
        if ($porcentaje >= 70) return 'cumplimiento-bueno';
        return 'cumplimiento-critico';
    }

    private static function getCumplimientoColorHex($porcentaje)
    {
        if ($porcentaje >= 90) return '#059669';
        if ($porcentaje >= 70) return '#d97706';
        return '#dc2626';
    }
}
