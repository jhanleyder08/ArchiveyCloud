<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\TrdSeries;
use App\Models\TrdSubseries;
use App\Models\PistaAuditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Redirigir al dashboard de reportes
     */
    public function index()
    {
        return redirect()->route('admin.reportes.dashboard');
    }

    /**
     * Dashboard ejecutivo con métricas clave
     */
    public function dashboard()
    {
        // Métricas principales
        $metricas = [
            'total_expedientes' => Expediente::count(),
            'total_documentos' => 0, // Documento::count(), // Simplificado por ahora
            'expedientes_abiertos' => Expediente::where('estado_ciclo_vida', 'tramite')->count(),
            'expedientes_cerrados' => Expediente::where('estado_ciclo_vida', 'central')->count(),
            'documentos_mes_actual' => 0, // Simplificado por ahora
            'tamaño_total_gb' => round((Expediente::sum('tamaño_mb') ?? 0) / 1024, 2),
        ];

        // Expedientes por estado (últimos 12 meses)
        $expedientesPorEstado = Expediente::selectRaw('
                estado_ciclo_vida as estado,
                DATE_FORMAT(created_at, "%Y-%m") as mes,
                COUNT(*) as total
            ')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('estado_ciclo_vida', 'mes')
            ->orderBy('mes')
            ->get()
            ->groupBy('estado');

        // Documentos por tipo (simplificado)
        $documentosPorTipo = [];

        // Series más utilizadas (simplificado)
        $seriesMasUsadas = [];

        // Actividad reciente (simplificado)
        $actividadReciente = [];

        // Cumplimiento TRD (simplificado)
        $cumplimientoTrd = [
            'series_documentadas' => 0,
            'total_series' => 0,
            'subseries_documentadas' => 0,
            'total_subseries' => 0,
        ];

        // Estadísticas de almacenamiento (simplificado)
        $estadisticasAlmacenamiento = [];

        return Inertia::render('admin/reportes/dashboard', [
            'metricas' => $metricas,
            'expedientesPorEstado' => $expedientesPorEstado,
            'documentosPorTipo' => $documentosPorTipo,
            'seriesMasUsadas' => $seriesMasUsadas,
            'actividadReciente' => $actividadReciente,
            'cumplimientoTrd' => $cumplimientoTrd,
            'estadisticasAlmacenamiento' => $estadisticasAlmacenamiento,
        ]);
    }

    /**
     * Reporte de cumplimiento normativo
     */
    public function cumplimientoNormativo(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', Carbon::now()->subMonths(6)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', Carbon::now()->format('Y-m-d'));

        // Expedientes por cumplimiento de retención
        $expedientesRetencion = Expediente::with(['serie.trd'])
            ->whereBetween('fecha_apertura', [$fechaInicio, $fechaFin])
            ->get()
            ->map(function ($expediente) {
                $serie = $expediente->serie;
                $tiempoRetencion = $serie ? $serie->tiempo_retencion_ag : 0;
                $fechaLimite = Carbon::parse($expediente->fecha_apertura)->addYears($tiempoRetencion);
                
                return [
                    'expediente' => $expediente,
                    'tiempo_retencion' => $tiempoRetencion,
                    'fecha_limite_ag' => $fechaLimite,
                    'dias_restantes' => $fechaLimite->diffInDays(Carbon::now(), false),
                    'estado_cumplimiento' => $fechaLimite->isPast() ? 'vencido' : 'vigente',
                ];
            });

        // Documentos sin clasificar
        $documentosSinClasificar = Documento::whereNull('serie_documental')
            ->orWhereNull('subserie_documental')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with('expediente')
            ->get();

        // Expedientes sin ubicación física
        $expedientesSinUbicacion = Expediente::where(function ($query) {
                $query->whereNull('ubicacion_fisica')
                      ->orWhere('ubicacion_fisica', '');
            })
            ->whereBetween('fecha_apertura', [$fechaInicio, $fechaFin])
            ->get();

        // Resumen de cumplimiento
        $resumenCumplimiento = [
            'total_expedientes' => $expedientesRetencion->count(),
            'expedientes_vigentes' => $expedientesRetencion->where('estado_cumplimiento', 'vigente')->count(),
            'expedientes_vencidos' => $expedientesRetencion->where('estado_cumplimiento', 'vencido')->count(),
            'documentos_sin_clasificar' => $documentosSinClasificar->count(),
            'expedientes_sin_ubicacion' => $expedientesSinUbicacion->count(),
            'porcentaje_cumplimiento' => $expedientesRetencion->count() > 0 
                ? round(($expedientesRetencion->where('estado_cumplimiento', 'vigente')->count() / $expedientesRetencion->count()) * 100, 2)
                : 100,
        ];

        return Inertia::render('admin/reportes/cumplimiento-normativo', [
            'expedientesRetencion' => $expedientesRetencion,
            'documentosSinClasificar' => $documentosSinClasificar,
            'expedientesSinUbicacion' => $expedientesSinUbicacion,
            'resumenCumplimiento' => $resumenCumplimiento,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ]);
    }

    /**
     * Reporte de productividad
     */
    public function productividad(Request $request)
    {
        $periodo = $request->input('periodo', '30'); // días
        $fechaInicio = Carbon::now()->subDays($periodo);

        // Documentos creados por usuario
        $documentosPorUsuario = Documento::select('created_by', DB::raw('COUNT(*) as total'))
            ->with('creator:id,name,email')
            ->where('created_at', '>=', $fechaInicio)
            ->groupBy('created_by')
            ->orderBy('total', 'desc')
            ->get();

        // Expedientes creados por usuario
        $expedientesPorUsuario = Expediente::select('created_by', DB::raw('COUNT(*) as total'))
            ->with('creator:id,name,email')
            ->where('created_at', '>=', $fechaInicio)
            ->groupBy('created_by')
            ->orderBy('total', 'desc')
            ->get();

        // Actividad por día
        $actividadPorDia = PistaAuditoria::selectRaw('
                DATE(created_at) as fecha,
                COUNT(*) as total_acciones
            ')
            ->where('created_at', '>=', $fechaInicio)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Tipos de acciones más frecuentes
        $accionesFrecuentes = PistaAuditoria::select('accion', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $fechaInicio)
            ->groupBy('accion')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('admin/reportes/productividad', [
            'documentosPorUsuario' => $documentosPorUsuario,
            'expedientesPorUsuario' => $expedientesPorUsuario,
            'actividadPorDia' => $actividadPorDia,
            'accionesFrecuentes' => $accionesFrecuentes,
            'periodo' => $periodo,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
        ]);
    }

    /**
     * Reporte de almacenamiento
     */
    public function almacenamiento(Request $request)
    {
        // Espacio usado por tipo de documento
        $espacioPorTipo = Documento::selectRaw('
                tipo_documento,
                COUNT(*) as cantidad,
                SUM(tamaño) as tamaño_total,
                AVG(tamaño) as tamaño_promedio
            ')
            ->groupBy('tipo_documento')
            ->orderBy('tamaño_total', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'tipo' => $item->tipo_documento,
                    'cantidad' => $item->cantidad,
                    'tamaño_total_mb' => round($item->tamaño_total / (1024 * 1024), 2),
                    'tamaño_promedio_mb' => round($item->tamaño_promedio / (1024 * 1024), 2),
                    'porcentaje' => 0, // Se calculará en el frontend
                ];
            });

        // Calcular porcentajes
        $totalEspacio = $espacioPorTipo->sum('tamaño_total_mb');
        $espacioPorTipo = $espacioPorTipo->map(function ($item) use ($totalEspacio) {
            $item['porcentaje'] = $totalEspacio > 0 ? round(($item['tamaño_total_mb'] / $totalEspacio) * 100, 2) : 0;
            return $item;
        });

        // Crecimiento de almacenamiento por mes
        $crecimientoAlmacenamiento = Documento::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as mes,
                COUNT(*) as documentos,
                SUM(tamaño) as tamaño_agregado
            ')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                return [
                    'mes' => $item->mes,
                    'documentos' => $item->documentos,
                    'tamaño_agregado_mb' => round($item->tamaño_agregado / (1024 * 1024), 2),
                ];
            });

        // Documentos más grandes
        $documentosMasGrandes = Documento::orderBy('tamaño', 'desc')
            ->with('expediente:id,codigo,nombre')
            ->limit(20)
            ->get()
            ->map(function ($documento) {
                return [
                    'id' => $documento->id,
                    'nombre' => $documento->nombre,
                    'expediente' => $documento->expediente ? $documento->expediente->codigo . ' - ' . $documento->expediente->nombre : 'Sin expediente',
                    'tipo' => $documento->tipo_documento,
                    'tamaño_mb' => round($documento->tamaño / (1024 * 1024), 2),
                    'fecha_creacion' => $documento->created_at->format('Y-m-d'),
                ];
            });

        // Resumen general
        $resumenAlmacenamiento = [
            'total_documentos' => Documento::count(),
            'tamaño_total_gb' => round(Documento::sum('tamaño') / (1024 * 1024 * 1024), 2),
            'tamaño_promedio_mb' => round(Documento::avg('tamaño') / (1024 * 1024), 2),
            'documentos_este_mes' => Documento::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count(),
        ];

        return Inertia::render('admin/reportes/almacenamiento', [
            'espacioPorTipo' => $espacioPorTipo,
            'crecimientoAlmacenamiento' => $crecimientoAlmacenamiento,
            'documentosMasGrandes' => $documentosMasGrandes,
            'resumenAlmacenamiento' => $resumenAlmacenamiento,
        ]);
    }

    /**
     * Exportar reporte a Excel/PDF
     */
    public function exportar(Request $request)
    {
        $tipo = $request->input('tipo', 'dashboard');
        $formato = $request->input('formato', 'excel');

        // Aquí implementarías la lógica de exportación
        // Por ahora returnamos un JSON con la estructura

        return response()->json([
            'message' => "Reporte $tipo exportado en formato $formato",
            'url' => "/storage/reportes/{$tipo}_" . date('Y-m-d_H-i-s') . ".$formato"
        ]);
    }
}
