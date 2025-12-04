<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\User;
use App\Models\PistaAuditoria;
use App\Models\SerieDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Obtener datos del dashboard ejecutivo
     */
    public function executive(Request $request)
    {
        $period = $request->get('period', 30); // días
        $startDate = Carbon::now()->subDays($period);
        
        // Calcular período anterior para comparación
        $previousStartDate = Carbon::now()->subDays($period * 2);
        $previousEndDate = Carbon::now()->subDays($period);

        // KPIs principales
        $kpis = $this->getKPIs($startDate, $previousStartDate, $previousEndDate);

        // Gráficos
        $charts = $this->getCharts($startDate, $period);

        // Distribución por series
        $seriesDistribution = $this->getSeriesDistribution();

        // Actividad reciente
        $recentActivity = $this->getRecentActivity();

        // Métricas de cumplimiento
        $compliance = $this->getComplianceMetrics();

        return response()->json([
            'success' => true,
            'kpis' => $kpis,
            'charts' => $charts,
            'series_distribution' => $seriesDistribution,
            'recent_activity' => $recentActivity,
            'compliance' => $compliance,
        ]);
    }

    /**
     * Calcular KPIs
     */
    private function getKPIs($startDate, $previousStartDate, $previousEndDate)
    {
        // Período actual
        $totalDocumentos = Documento::count();
        $documentosActuales = Documento::where('created_at', '>=', $startDate)->count();
        
        // Período anterior
        $documentosAnteriores = Documento::whereBetween('created_at', [
            $previousStartDate,
            $previousEndDate
        ])->count();

        // Calcular cambio porcentual
        $documentosChange = $documentosAnteriores > 0 
            ? round((($documentosActuales - $documentosAnteriores) / $documentosAnteriores) * 100, 1)
            : 100;

        // Expedientes
        $totalExpedientes = Expediente::count();
        $expedientesActuales = Expediente::where('created_at', '>=', $startDate)->count();
        $expedientesAnteriores = Expediente::whereBetween('created_at', [
            $previousStartDate,
            $previousEndDate
        ])->count();
        $expedientesChange = $expedientesAnteriores > 0 
            ? round((($expedientesActuales - $expedientesAnteriores) / $expedientesAnteriores) * 100, 1)
            : 100;

        // Usuarios activos (con sesiones en el período)
        $usuariosActivos = User::where('last_login_at', '>=', $startDate)
            ->where('active', true)
            ->count();
        $usuariosAnteriores = User::whereBetween('last_login_at', [
            $previousStartDate,
            $previousEndDate
        ])->count();
        $usuariosChange = $usuariosAnteriores > 0 
            ? round((($usuariosActivos - $usuariosAnteriores) / $usuariosAnteriores) * 100, 1)
            : 100;

        // Tasa de cumplimiento (documentos con TRD y metadatos completos)
        $documentosConTRD = Documento::whereNotNull('serie_documental_id')->count();
        $cumplimientoRate = $totalDocumentos > 0 
            ? round(($documentosConTRD / $totalDocumentos) * 100, 1)
            : 0;

        return [
            'total_documentos' => $totalDocumentos,
            'documentos_change' => $documentosChange,
            'total_expedientes' => $totalExpedientes,
            'expedientes_change' => $expedientesChange,
            'usuarios_activos' => $usuariosActivos,
            'usuarios_change' => $usuariosChange,
            'cumplimiento_rate' => $cumplimientoRate,
            'cumplimiento_change' => 0, // Calcular si hay histórico
        ];
    }

    /**
     * Obtener datos para gráficos
     */
    private function getCharts($startDate, $period)
    {
        // Documentos capturados por día
        $documentsOverTime = Documento::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $documentsLabels = $documentsOverTime->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('d/m');
        })->toArray();

        $documentsData = $documentsOverTime->pluck('count')->toArray();

        // Actividad de usuarios (pistas de auditoría por día)
        $userActivity = PistaAuditoria::select(
                DB::raw('DATE(fecha_hora) as date'),
                DB::raw('COUNT(DISTINCT usuario_id) as count')
            )
            ->where('fecha_hora', '>=', $startDate)
            ->whereNotNull('usuario_id')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $activityLabels = $userActivity->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('d/m');
        })->toArray();

        $activityData = $userActivity->pluck('count')->toArray();

        return [
            'documents_over_time' => [
                'labels' => $documentsLabels,
                'datasets' => [
                    [
                        'label' => 'Documentos capturados',
                        'data' => $documentsData,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                    ]
                ]
            ],
            'user_activity' => [
                'labels' => $activityLabels,
                'datasets' => [
                    [
                        'label' => 'Usuarios activos',
                        'data' => $activityData,
                        'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                        'borderColor' => 'rgb(16, 185, 129)',
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtener distribución por series documentales
     */
    private function getSeriesDistribution()
    {
        return SerieDocumental::select('series_documentales.*')
            ->selectRaw('COUNT(documentos.id) as count')
            ->leftJoin('documentos', 'series_documentales.id', '=', 'documentos.serie_documental_id')
            ->groupBy('series_documentales.id')
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->map(function ($serie) {
                return [
                    'nombre' => $serie->nombre,
                    'codigo' => $serie->codigo,
                    'count' => $serie->count ?? 0,
                ];
            });
    }

    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity()
    {
        return PistaAuditoria::with('usuario:id,name')
            ->select('pistas_auditoria.*')
            ->orderBy('fecha_hora', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($pista) {
                return [
                    'descripcion' => $pista->accion_detalle ?? $pista->descripcion,
                    'usuario' => $pista->usuario->name ?? 'Sistema',
                    'modulo' => $pista->modulo,
                    'fecha_hora' => $pista->fecha_hora,
                    'tiempo_relativo' => $pista->fecha_hora->diffForHumans(),
                ];
            });
    }

    /**
     * Calcular métricas de cumplimiento normativo
     */
    private function getComplianceMetrics()
    {
        $totalDocumentos = Documento::count();
        
        if ($totalDocumentos === 0) {
            return [
                'trd_compliance' => 0,
                'metadata_compliance' => 0,
                'signature_compliance' => 0,
                'audit_compliance' => 100,
            ];
        }

        // Documentos con TRD asignado
        $documentosConTRD = Documento::whereNotNull('serie_documental_id')->count();
        $trdCompliance = round(($documentosConTRD / $totalDocumentos) * 100, 1);

        // Documentos con metadatos completos (tienen descripción y tipo)
        $documentosConMetadatos = Documento::whereNotNull('descripcion')
            ->whereNotNull('tipo_documento')
            ->count();
        $metadataCompliance = round(($documentosConMetadatos / $totalDocumentos) * 100, 1);

        // Documentos con firmas digitales
        $documentosFirmados = Documento::whereHas('firmas')->count();
        $signatureCompliance = round(($documentosFirmados / $totalDocumentos) * 100, 1);

        // Trazabilidad (documentos con pistas de auditoría)
        $documentosConAuditoria = Documento::whereHas('pistasAuditoria')->count();
        $auditCompliance = round(($documentosConAuditoria / $totalDocumentos) * 100, 1);

        return [
            'trd_compliance' => $trdCompliance,
            'metadata_compliance' => $metadataCompliance,
            'signature_compliance' => $signatureCompliance,
            'audit_compliance' => $auditCompliance,
        ];
    }

    /**
     * Exportar reporte del dashboard
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'pdf'); // pdf o excel
        $period = $request->get('period', 30);

        // Obtener los mismos datos que el dashboard
        $data = $this->executive($request)->getData();

        if ($format === 'pdf') {
            return $this->exportPDF($data);
        } else {
            return $this->exportExcel($data);
        }
    }

    /**
     * Exportar a PDF
     */
    private function exportPDF($data)
    {
        $pdf = \PDF::loadView('reports.executive-dashboard', [
            'data' => $data,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download('reporte-ejecutivo-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Exportar a Excel
     */
    private function exportExcel($data)
    {
        return \Excel::download(
            new \App\Exports\ExecutiveDashboardExport($data),
            'reporte-ejecutivo-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
