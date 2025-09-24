<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use App\Models\Notificacion;
use App\Models\DisposicionFinal;
use App\Models\PrestamoConsulta;
use App\Models\WorkflowDocumento;
use App\Models\SerieDocumental;
use App\Models\IndiceElectronico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardEjecutivoController extends Controller
{
    public function __construct()
    {
        \Log::info('DashboardEjecutivoController - Constructor llamado');
        $this->middleware(['auth', 'verified']);
        // Middleware role deshabilitado - problema con roles
        // $this->middleware('role:Super Administrador,Administrador SGDEA,Gestor Documental');
    }

    /**
     * Dashboard ejecutivo principal
     */
    public function index()
    {
        \Log::info('Dashboard Ejecutivo - Método index() llamado');
        \Log::info('Usuario actual: ' . auth()->user()->email);
        
        // Métricas generales del sistema
        $metricas_generales = $this->obtenerMetricasGenerales();
        
        // KPIs críticos
        $kpis_criticos = $this->obtenerKPIsCriticos();
        
        // Alertas y notificaciones críticas
        $alertas_criticas = $this->obtenerAlertasCriticas();
        
        // Estadísticas de cumplimiento
        $cumplimiento = $this->obtenerEstadisticasCumplimiento();
        
        // Tendencias y análisis
        $tendencias = $this->obtenerTendencias();
        
        // Usuarios más activos
        $usuarios_activos = $this->obtenerUsuariosActivos();
        
        // Distribución de trabajo
        $distribucion_trabajo = $this->obtenerDistribucionTrabajo();

        return Inertia::render('admin/dashboard-ejecutivo/index', [
            'metricas_generales' => $metricas_generales,
            'kpis_criticos' => $kpis_criticos,
            'alertas_criticas' => $alertas_criticas,
            'cumplimiento' => $cumplimiento,
            'tendencias' => $tendencias,
            'usuarios_activos' => $usuarios_activos,
            'distribucion_trabajo' => $distribucion_trabajo,
        ]);
    }

    /**
     * Obtener métricas generales del sistema
     */
    private function obtenerMetricasGenerales()
    {
        return [
            'total_documentos' => Documento::count(),
            'total_expedientes' => Expediente::count(),
            'total_usuarios' => User::where('active', true)->count(),
            'total_series' => SerieDocumental::where('activa', true)->count(),
            'almacenamiento_total' => $this->calcularAlmacenamientoTotal(),
            'indices_generados' => 0, // IndiceElectronico::count() - Temporalmente 0 si no existe la tabla
        ];
    }

    /**
     * Obtener KPIs críticos
     */
    private function obtenerKPIsCriticos()
    {
        $hoy = Carbon::now();
        $semana_pasada = $hoy->copy()->subWeek();
        $mes_pasado = $hoy->copy()->subMonth();

        return [
            // Eficiencia de procesamiento
            'documentos_procesados_semana' => Documento::where('created_at', '>=', $semana_pasada)->count(),
            'expedientes_creados_semana' => Expediente::where('created_at', '>=', $semana_pasada)->count(),
            
            // Cumplimiento normativo (usando estados del ciclo de vida)
            'expedientes_vencidos' => Expediente::where('estado_ciclo_vida', 'eliminado')->count(),
            'expedientes_proximo_vencimiento' => Expediente::where('estado_ciclo_vida', 'central')
                ->whereDate('created_at', '<', $hoy->copy()->subYears(2))->count(),
            
            // Flujos de trabajo (temporalmente 0 si no existe WorkflowDocumento)
            'workflows_pendientes' => 0, // WorkflowDocumento::where('estado', 'pendiente')->count(),
            'workflows_vencidos' => 0, // WorkflowDocumento::where('estado', 'pendiente')->count(),
            
            // Préstamos y consultas (temporalmente 0 si no existe PrestamoConsulta) 
            'prestamos_activos' => 0, // PrestamoConsulta::where('estado', 'prestado')->count(),
            'prestamos_vencidos' => 0, // PrestamoConsulta::where('estado', 'prestado')->count(),
            
            // Disposición final (temporalmente 0 si no existe DisposicionFinal)
            'disposiciones_pendientes' => 0, // DisposicionFinal::where('estado', 'pendiente')->count(),
            'disposiciones_vencidas' => 0, // DisposicionFinal::where('estado', 'pendiente')->count(),
        ];
    }

    /**
     * Obtener alertas críticas
     */
    private function obtenerAlertasCriticas()
    {
        return [
            'notificaciones_criticas' => Notificacion::where('prioridad', 'critica')
            ->where('estado', 'pendiente')
            ->latest()
            ->limit(10)
            ->get(),
            
            'expedientes_urgentes' => Expediente::whereIn('estado_ciclo_vida', ['tramite', 'gestion'])
                ->whereDate('created_at', '<=', Carbon::now()->subMonths(6))
                ->latest()
                ->limit(5)
                ->get(),
            
            'workflows_urgentes' => [], // Temporalmente vacío hasta implementar WorkflowDocumento
        ];
    }

    /**
     * Obtener estadísticas de cumplimiento
     */
    private function obtenerEstadisticasCumplimiento()
    {
        $total_expedientes = Expediente::count();
        $expedientes_en_regla = Expediente::whereIn('estado_ciclo_vida', ['tramite', 'gestion'])->count();
        
        $porcentaje_cumplimiento = $total_expedientes > 0 ? 
            round(($expedientes_en_regla / $total_expedientes) * 100, 2) : 100;

        return [
            'porcentaje_cumplimiento_general' => $porcentaje_cumplimiento,
            'expedientes_en_regla' => $expedientes_en_regla,
            'expedientes_con_alertas' => $total_expedientes - $expedientes_en_regla,
            
            // Cumplimiento por series
            'cumplimiento_por_series' => SerieDocumental::select('series_documentales.nombre')
                ->selectRaw('COUNT(expedientes.id) as total_expedientes')
                ->selectRaw('COUNT(CASE WHEN expedientes.estado_ciclo_vida IN ("tramite", "gestion") THEN 1 END) as en_regla')
                ->selectRaw('ROUND((COUNT(CASE WHEN expedientes.estado_ciclo_vida IN ("tramite", "gestion") THEN 1 END) / COUNT(expedientes.id)) * 100, 2) as porcentaje')
                ->leftJoin('expedientes', 'series_documentales.id', '=', 'expedientes.serie_documental_id')
                ->groupBy('series_documentales.id', 'series_documentales.nombre')
                ->having('total_expedientes', '>', 0)
                ->orderByDesc('porcentaje')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Obtener tendencias del sistema
     */
    private function obtenerTendencias()
    {
        $ultimos_6_meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $ultimos_6_meses[] = [
                'mes' => $fecha->format('M Y'),
                'documentos' => Documento::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)
                    ->count(),
                'expedientes' => Expediente::whereYear('created_at', $fecha->year)
                    ->whereMonth('created_at', $fecha->month)
                    ->count(),
                'workflows' => 0, // Temporalmente 0 hasta implementar WorkflowDocumento
            ];
        }

        return [
            'crecimiento_mensual' => $ultimos_6_meses,
            'proyeccion_almacenamiento' => $this->calcularProyeccionAlmacenamiento(),
        ];
    }

    /**
     * Obtener usuarios más activos
     */
    private function obtenerUsuariosActivos()
    {
        return User::select('users.id', 'users.name', 'users.email')
            ->selectRaw('
                (SELECT COUNT(*) FROM documentos WHERE created_by = users.id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as documentos_creados,
                (SELECT COUNT(*) FROM expedientes WHERE productor_id = users.id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as expedientes_gestionados,
                0 as workflows_iniciados
            ')
            ->having(DB::raw('documentos_creados + expedientes_gestionados'), '>', 0)
            ->orderByDesc(DB::raw('documentos_creados + expedientes_gestionados'))
            ->limit(10)
            ->get();
    }

    /**
     * Obtener distribución de trabajo
     */
    private function obtenerDistribucionTrabajo()
    {
        return [
            // Distribución por estados de expedientes
            'expedientes_por_estado' => Expediente::select('estado_ciclo_vida as estado')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('estado_ciclo_vida')
                ->get(),
            
            // Distribución por tipos de documentos
            'documentos_por_tipo' => Documento::select('formato as extension')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('formato')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            
            // Workflows por estado (temporalmente vacío)
            'workflows_por_estado' => [],
        ];
    }

    /**
     * Calcular almacenamiento total en GB
     */
    private function calcularAlmacenamientoTotal()
    {
        $total_bytes = Documento::sum('tamano_bytes') ?? 0;
        return round($total_bytes / (1024 * 1024 * 1024), 2); // Convertir a GB
    }

    /**
     * Calcular proyección de almacenamiento
     */
    private function calcularProyeccionAlmacenamiento()
    {
        // Crecimiento promedio de los últimos 3 meses
        $crecimiento_mensual = [];
        for ($i = 2; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $crecimiento_mensual[] = Documento::whereYear('created_at', $fecha->year)
                ->whereMonth('created_at', $fecha->month)
                ->sum('tamano_bytes') ?? 0;
        }

        $promedio_crecimiento = array_sum($crecimiento_mensual) / 3;
        $almacenamiento_actual = $this->calcularAlmacenamientoTotal();

        return [
            'actual_gb' => $almacenamiento_actual,
            'proyeccion_3_meses' => round($almacenamiento_actual + (($promedio_crecimiento * 3) / (1024 * 1024 * 1024)), 2),
            'proyeccion_6_meses' => round($almacenamiento_actual + (($promedio_crecimiento * 6) / (1024 * 1024 * 1024)), 2),
            'proyeccion_12_meses' => round($almacenamiento_actual + (($promedio_crecimiento * 12) / (1024 * 1024 * 1024)), 2),
        ];
    }

    /**
     * Exportar dashboard a PDF
     */
    public function exportarPDF()
    {
        // Implementar exportación a PDF del dashboard
        // Para futuras implementaciones
        return response()->json(['message' => 'Funcionalidad en desarrollo']);
    }

    /**
     * Obtener datos para gráficos específicos (AJAX)
     */
    public function datosGrafico(Request $request)
    {
        $tipo = $request->get('tipo');
        
        switch ($tipo) {
            case 'cumplimiento_semanal':
                return $this->obtenerCumplimientoSemanal();
            
            case 'productividad_usuarios':
                return $this->obtenerProductividadUsuarios();
            
            case 'distribucion_series':
                return $this->obtenerDistribucionSeries();
            
            default:
                return response()->json(['error' => 'Tipo de gráfico no válido'], 400);
        }
    }

    /**
     * Obtener cumplimiento semanal
     */
    private function obtenerCumplimientoSemanal()
    {
        $ultimas_4_semanas = [];
        for ($i = 3; $i >= 0; $i--) {
            $inicio_semana = Carbon::now()->subWeeks($i)->startOfWeek();
            $fin_semana = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $total = Expediente::whereBetween('created_at', [$inicio_semana, $fin_semana])->count();
            $en_regla = Expediente::whereBetween('created_at', [$inicio_semana, $fin_semana])
                ->whereDate('fecha_vencimiento_retencion', '>', Carbon::now())
                ->count();
            
            $ultimas_4_semanas[] = [
                'semana' => $inicio_semana->format('d M'),
                'total' => $total,
                'en_regla' => $en_regla,
                'porcentaje' => $total > 0 ? round(($en_regla / $total) * 100, 2) : 100,
            ];
        }
        
        return $ultimas_4_semanas;
    }

    /**
     * Obtener productividad de usuarios
     */
    private function obtenerProductividadUsuarios()
    {
        return User::select('users.name')
            ->selectRaw('COUNT(documentos.id) as documentos_creados')
            ->leftJoin('documentos', 'users.id', '=', 'documentos.created_by')
            ->where('documentos.created_at', '>=', Carbon::now()->subMonth())
            ->groupBy('users.id', 'users.name')
            ->having('documentos_creados', '>', 0)
            ->orderByDesc('documentos_creados')
            ->limit(10)
            ->get();
    }

    /**
     * Obtener distribución por series
     */
    private function obtenerDistribucionSeries()
    {
        return SerieDocumental::select('series_documentales.nombre')
            ->selectRaw('COUNT(expedientes.id) as total_expedientes')
            ->leftJoin('expedientes', 'series_documentales.id', '=', 'expedientes.serie_documental_id')
            ->groupBy('series_documentales.id', 'series_documentales.nombre')
            ->having('total_expedientes', '>', 0)
            ->orderByDesc('total_expedientes')
            ->limit(15)
            ->get();
    }
}
