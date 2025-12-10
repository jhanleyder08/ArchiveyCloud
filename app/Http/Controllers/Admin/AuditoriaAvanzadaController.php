<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PistaAuditoria;
use App\Models\User;
use App\Services\AuditoriaAvanzadaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class AuditoriaAvanzadaController extends Controller
{
    protected AuditoriaAvanzadaService $auditoriaService;

    public function __construct(AuditoriaAvanzadaService $auditoriaService)
    {
        $this->middleware('auth');
        $this->middleware('role:Super Administrador,Administrador SGDEA,Auditor');
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * Dashboard principal de auditoría
     */
    public function index(Request $request): Response
    {
        $filtros = $request->only([
            'fecha_inicio', 'fecha_fin', 'usuario_id', 'accion', 
            'resultado', 'modulo', 'ip_address', 'buscar'
        ]);

        // Aplicar filtros de fecha por defecto (últimos 7 días)
        if (empty($filtros['fecha_inicio'])) {
            $filtros['fecha_inicio'] = now()->subDays(7)->format('Y-m-d');
        }
        if (empty($filtros['fecha_fin'])) {
            $filtros['fecha_fin'] = now()->format('Y-m-d');
        }

        $query = PistaAuditoria::query()
            ->with(['usuario:id,name,email']);

        // Aplicar filtros
        if (!empty($filtros['fecha_inicio'])) {
            $query->where('fecha_hora', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->where('fecha_hora', '<=', $filtros['fecha_fin'] . ' 23:59:59');
        }

        if (!empty($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }

        if (!empty($filtros['accion'])) {
            $query->where('accion', $filtros['accion']);
        }

        if (!empty($filtros['resultado'])) {
            $query->where('resultado', $filtros['resultado']);
        }

        if (!empty($filtros['modulo'])) {
            $query->where('modulo', $filtros['modulo']);
        }

        if (!empty($filtros['ip_address'])) {
            $query->where('ip_address', 'like', '%' . $filtros['ip_address'] . '%');
        }

        if (!empty($filtros['buscar'])) {
            $buscar = $filtros['buscar'];
            $query->where(function($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                  ->orWhere('accion', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhereHas('usuario', function($subQ) use ($buscar) {
                      $subQ->where('name', 'like', "%{$buscar}%")
                           ->orWhere('email', 'like', "%{$buscar}%");
                  });
            });
        }

        $eventos = $query->orderBy('fecha_hora', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Estadísticas generales
        $estadisticas = $this->obtenerEstadisticasGenerales($filtros);

        // Usuarios para filtros
        $usuarios = User::select('id', 'name', 'email')
            ->whereHas('auditoria')
            ->orderBy('name')
            ->get();

        // Acciones disponibles
        $acciones = PistaAuditoria::select('accion')
            ->distinct()
            ->whereNotNull('accion')
            ->orderBy('accion')
            ->pluck('accion');

        // Módulos disponibles
        $modulos = PistaAuditoria::select('modulo')
            ->distinct()
            ->whereNotNull('modulo')
            ->orderBy('modulo')
            ->pluck('modulo');

        return Inertia::render('admin/auditoria/index', [
            'eventos' => $eventos,
            'estadisticas' => $estadisticas,
            'usuarios' => $usuarios,
            'acciones' => $acciones,
            'filtros' => $filtros,
            'resultados' => ['exitoso', 'fallido', 'bloqueado'],
            'modulos' => $modulos
        ]);
    }

    /**
     * Ver evento específico de auditoría
     */
    public function show(PistaAuditoria $auditoria): Response
    {
        $auditoria->load(['usuario:id,name,email']);

        // Convertir fecha_hora a Carbon si es necesario
        $fechaEvento = $auditoria->fecha_hora instanceof \Carbon\Carbon 
            ? $auditoria->fecha_hora 
            : \Carbon\Carbon::parse($auditoria->fecha_hora);

        // Eventos relacionados del mismo usuario
        $eventosRelacionados = PistaAuditoria::where('usuario_id', $auditoria->usuario_id)
            ->where('id', '!=', $auditoria->id)
            ->where('fecha_hora', '>=', $fechaEvento->copy()->subHours(2))
            ->where('fecha_hora', '<=', $fechaEvento->copy()->addHours(2))
            ->with(['usuario:id,name'])
            ->orderBy('fecha_hora', 'desc')
            ->limit(10)
            ->get();

        // Análisis del evento
        $analisisEvento = $this->analizarEvento($auditoria);

        return Inertia::render('admin/auditoria/show', [
            'evento' => $auditoria,
            'eventos_relacionados' => $eventosRelacionados,
            'analisis' => $analisisEvento
        ]);
    }

    /**
     * Dashboard de análisis avanzado
     */
    public function analytics(Request $request): Response
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'periodo']);

        // Período por defecto: últimos 30 días
        $periodo = $filtros['periodo'] ?? '30d';
        
        switch ($periodo) {
            case '7d':
                $fechaInicio = now()->subDays(7);
                break;
            case '30d':
                $fechaInicio = now()->subDays(30);
                break;
            case '90d':
                $fechaInicio = now()->subDays(90);
                break;
            case 'custom':
                $fechaInicio = Carbon::parse($filtros['fecha_inicio'] ?? now()->subDays(30));
                break;
            default:
                $fechaInicio = now()->subDays(30);
        }

        $fechaFin = Carbon::parse($filtros['fecha_fin'] ?? now());

        // Generar análisis avanzado
        $analisis = $this->generarAnalisisAvanzado($fechaInicio, $fechaFin);

        return Inertia::render('admin/auditoria/analytics', [
            'analisis' => $analisis,
            'filtros' => array_merge($filtros, [
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
                'periodo' => $periodo
            ])
        ]);
    }

    /**
     * Dashboard de patrones sospechosos
     */
    public function patrones(): Response
    {
        // Obtener patrones sospechosos recientes
        $patronesSospechosos = PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
            ->with(['usuario:id,name,email'])
            ->orderBy('fecha_hora', 'desc')
            ->take(50)
            ->get();

        // Estadísticas de patrones
        $estadisticasPatrones = $this->obtenerEstadisticasPatrones();

        // Alertas activas
        $alertasActivas = $this->obtenerAlertasActivas();

        return Inertia::render('admin/auditoria/patrones', [
            'patrones_sospechosos' => $patronesSospechosos,
            'estadisticas_patrones' => $estadisticasPatrones,
            'alertas_activas' => $alertasActivas
        ]);
    }

    /**
     * Generar reporte avanzado
     */
    public function reporte(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'usuario_id' => 'nullable|exists:users,id',
            'resultado' => 'nullable|string|in:exitoso,fallido,bloqueado',
            'incluir_estadisticas' => 'boolean',
            'incluir_tendencias' => 'boolean',
            'incluir_recomendaciones' => 'boolean',
            'formato' => 'nullable|in:json,pdf,excel'
        ]);

        try {
            $reporte = $this->auditoriaService->generarReporteAvanzado($validated);

            $formato = $validated['formato'] ?? 'json';

            switch ($formato) {
                case 'pdf':
                    return $this->generarReportePDF($reporte, $validated);
                case 'excel':
                    return $this->generarReporteExcel($reporte, $validated);
                default:
                    return response()->json($reporte);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al generar reporte: ' . $e->getMessage()]);
        }
    }

    /**
     * API para métricas en tiempo real
     */
    public function metricas(): \Illuminate\Http\JsonResponse
    {
        try {
            $metricas = [
                'eventos_hoy' => PistaAuditoria::whereDate('fecha_hora', today())->count(),
                'eventos_criticos_hoy' => PistaAuditoria::where('resultado', 'bloqueado')
                    ->whereDate('fecha_hora', today())->count(),
                'usuarios_activos_hoy' => PistaAuditoria::whereDate('fecha_hora', today())
                    ->distinct('usuario_id')->count(),
                'patrones_detectados_hoy' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
                    ->whereDate('fecha_hora', today())->count(),
                'acciones_por_hora' => $this->obtenerAccionesPorHora(),
                'estado_sistema' => $this->evaluarEstadoSistema()
            ];

            return response()->json($metricas);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error obteniendo métricas'], 500);
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticasGenerales(array $filtros): array
    {
        $query = PistaAuditoria::query();

        // Aplicar mismos filtros que la consulta principal
        if (!empty($filtros['fecha_inicio'])) {
            $query->where('fecha_hora', '>=', $filtros['fecha_inicio']);
        }
        if (!empty($filtros['fecha_fin'])) {
            $query->where('fecha_hora', '<=', $filtros['fecha_fin'] . ' 23:59:59');
        }

        return [
            'total_eventos' => $query->count(),
            'eventos_criticos' => $query->clone()->where('resultado', 'fallido')->count(),
            'eventos_alto_riesgo' => $query->clone()->where('resultado', 'bloqueado')->count(),
            'usuarios_unicos' => $query->clone()->distinct('usuario_id')->count(),
            'ips_unicas' => $query->clone()->distinct('ip_address')->count(),
            'acciones_mas_frecuentes' => $query->clone()
                ->selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->orderBy('total', 'desc')
                ->take(5)
                ->get(),
            'actividad_por_dia' => $query->clone()
                ->selectRaw('DATE(fecha_hora) as fecha, COUNT(*) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get(),
            'distribucion_resultados' => $query->clone()
                ->selectRaw('resultado, COUNT(*) as total')
                ->groupBy('resultado')
                ->get()
        ];
    }

    /**
     * Analizar evento específico
     */
    private function analizarEvento(PistaAuditoria $auditoria): array
    {
        // Convertir fecha_hora a Carbon si es necesario
        $fechaEvento = $auditoria->fecha_hora instanceof \Carbon\Carbon 
            ? $auditoria->fecha_hora 
            : \Carbon\Carbon::parse($auditoria->fecha_hora);

        return [
            'nivel_riesgo' => $auditoria->resultado === 'fallido' ? 'alto' : ($auditoria->resultado === 'bloqueado' ? 'critico' : 'bajo'),
            'categoria' => $auditoria->modulo ?? 'general',
            'contexto_geografico' => [
                'pais' => $auditoria->pais ?? 'Desconocido',
                'ciudad' => 'N/A',
                'ip' => $auditoria->ip_address
            ],
            'contexto_tecnico' => [
                'dispositivo' => $auditoria->dispositivo ?? 'Desconocido',
                'navegador' => $auditoria->navegador ?? 'Desconocido',
                'user_agent' => $auditoria->user_agent
            ],
            'contexto_temporal' => [
                'horario' => $fechaEvento->format('H:i'),
                'dia_semana' => $fechaEvento->locale('es')->dayName,
                'es_horario_laboral' => $this->esHorarioLaboral($fechaEvento)
            ],
            'eventos_similares' => $this->contarEventosSimilares($auditoria),
            'recomendaciones' => $this->generarRecomendacionesEvento($auditoria)
        ];
    }

    /**
     * Generar análisis avanzado
     */
    private function generarAnalisisAvanzado(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $query = PistaAuditoria::whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);

        return [
            'resumen_periodo' => [
                'total_eventos' => $query->count(),
                'promedio_diario' => round($query->count() / $fechaInicio->diffInDays($fechaFin, false)),
                'usuarios_activos' => $query->distinct('usuario_id')->count(),
                'ips_diferentes' => $query->distinct('ip_address')->count()
            ],
            'analisis_riesgos' => [
                'eventos_criticos' => $query->clone()->where('resultado', 'bloqueado')->count(),
                'eventos_alto_riesgo' => $query->clone()->where('resultado', 'fallido')->count(),
                'patrones_sospechosos' => $query->clone()->where('accion', 'patron_sospechoso_detectado')->count(),
                'fallos_autenticacion' => $query->clone()->where('accion', 'login_fallido')->count()
            ],
            'tendencias_actividad' => $this->analizarTendenciasActividad($query),
            'analisis_usuarios' => $this->analizarUsuarios($query),
            'analisis_geografico' => $this->analizarDistribucionGeografica($query),
            'analisis_temporal' => $this->analizarPatronesTemporales($query),
            'anomalias_detectadas' => $this->detectarAnomalias($query)
        ];
    }

    /**
     * Obtener estadísticas de patrones
     */
    private function obtenerEstadisticasPatrones(): array
    {
        return [
            'total_patrones' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')->count(),
            'patrones_ultima_semana' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
                ->where('fecha_hora', '>=', now()->subWeek())->count(),
            'tipos_patrones' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
                ->selectRaw('JSON_EXTRACT(contexto_adicional, "$.tipo") as tipo, COUNT(*) as total')
                ->groupBy('tipo')
                ->orderBy('total', 'desc')
                ->get(),
            'distribución_riesgos' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
                ->selectRaw('resultado, COUNT(*) as total')
                ->groupBy('resultado')
                ->get()
        ];
    }

    /**
     * Obtener alertas activas
     */
    private function obtenerAlertasActivas(): array
    {
        return [
            'ips_bloqueadas' => [],
            'usuarios_suspendidos' => [],
            'patrones_criticos' => PistaAuditoria::where('accion', 'patron_sospechoso_detectado')
                ->where('resultado', 'bloqueado')
                ->where('fecha_hora', '>=', now()->subDay())
                ->count(),
            'investigaciones_pendientes' => PistaAuditoria::whereJsonContains('contexto_adicional->requiere_investigacion', true)
                ->where('fecha_hora', '>=', now()->subWeek())
                ->count()
        ];
    }

    /**
     * Métodos auxiliares
     */
    private function esHorarioLaboral(Carbon $fecha): bool
    {
        $hora = $fecha->hour;
        $diaSemana = $fecha->dayOfWeek;
        
        // Lunes a viernes (1-5), de 7 AM a 6 PM
        return $diaSemana >= 1 && $diaSemana <= 5 && $hora >= 7 && $hora <= 18;
    }

    private function contarEventosSimilares(PistaAuditoria $auditoria): int
    {
        return PistaAuditoria::where('accion', $auditoria->accion)
            ->where('usuario_id', $auditoria->usuario_id)
            ->where('fecha_hora', '>=', now()->subDay())
            ->count();
    }

    private function generarRecomendacionesEvento(PistaAuditoria $auditoria): array
    {
        $recomendaciones = [];

        // Convertir fecha_hora a Carbon
        $fechaEvento = $auditoria->fecha_hora instanceof \Carbon\Carbon 
            ? $auditoria->fecha_hora 
            : \Carbon\Carbon::parse($auditoria->fecha_hora);

        // Nivel de riesgo basado en resultado
        $esAltoRiesgo = in_array($auditoria->resultado, ['fallido', 'bloqueado']);

        if ($esAltoRiesgo) {
            $recomendaciones[] = 'Investigar inmediatamente este evento';
        }

        if (!$this->esHorarioLaboral($fechaEvento)) {
            $recomendaciones[] = 'Verificar autorización para acceso fuera del horario laboral';
        }

        return $recomendaciones;
    }

    private function obtenerAccionesPorHora(): array
    {
        return PistaAuditoria::whereDate('fecha_hora', today())
            ->selectRaw('HOUR(fecha_hora) as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->toArray();
    }

    private function evaluarEstadoSistema(): string
    {
        $eventosCriticos = PistaAuditoria::where('resultado', 'bloqueado')
            ->whereDate('fecha_hora', today())
            ->count();

        if ($eventosCriticos > 5) return 'crítico';
        if ($eventosCriticos > 0) return 'advertencia';
        return 'normal';
    }

    private function analizarTendenciasActividad($query): array
    {
        return [
            'crecimiento_semanal' => 0, // Simplificado
            'horarios_pico' => [9, 14, 16], // Horas más activas
            'dias_mas_activos' => ['lunes', 'martes', 'miércoles']
        ];
    }

    private function analizarUsuarios($query): array
    {
        return [
            'mas_activos' => $query->clone()
                ->selectRaw('usuario_id, COUNT(*) as actividad')
                ->groupBy('usuario_id')
                ->orderBy('actividad', 'desc')
                ->take(10)
                ->with('usuario:id,name')
                ->get(),
            'con_mas_riesgos' => $query->clone()
                ->where('resultado', 'fallido')
                ->selectRaw('usuario_id, COUNT(*) as eventos_riesgo')
                ->groupBy('usuario_id')
                ->orderBy('eventos_riesgo', 'desc')
                ->take(5)
                ->with('usuario:id,name')
                ->get()
        ];
    }

    private function analizarDistribucionGeografica($query): array
    {
        return [
            'paises' => $query->clone()
                ->selectRaw('pais, COUNT(*) as total')
                ->groupBy('pais')
                ->orderBy('total', 'desc')
                ->get(),
            'ips_mas_activas' => $query->clone()
                ->selectRaw('ip_address, COUNT(*) as total')
                ->groupBy('ip_address')
                ->orderBy('total', 'desc')
                ->take(10)
                ->get()
        ];
    }

    private function analizarPatronesTemporales($query): array
    {
        return [
            'actividad_por_hora' => $query->clone()
                ->selectRaw('HOUR(fecha_hora) as hora, COUNT(*) as total')
                ->groupBy('hora')
                ->orderBy('hora')
                ->get(),
            'actividad_por_dia_semana' => $query->clone()
                ->selectRaw('DAYOFWEEK(fecha_hora) as dia, COUNT(*) as total')
                ->groupBy('dia')
                ->orderBy('dia')
                ->get()
        ];
    }

    private function detectarAnomalias($query): array
    {
        return [
            'accesos_multiples_simultaneos' => $query->clone()
                ->selectRaw('usuario_id, ip_address, COUNT(*) as total')
                ->groupBy('usuario_id', 'ip_address')
                ->having('total', '>', 100)
                ->get(),
            'cambios_masivos' => $query->clone()
                ->whereIn('accion', ['crear', 'actualizar', 'eliminar'])
                ->selectRaw('usuario_id, COUNT(*) as total')
                ->groupBy('usuario_id')
                ->having('total', '>', 50)
                ->get()
        ];
    }

    private function generarReportePDF($reporte, $filtros)
    {
        // Implementación para generar PDF
        return response()->json(['message' => 'Generación de PDF no implementada aún']);
    }

    private function generarReporteExcel($reporte, $filtros)
    {
        // Implementación para generar Excel
        return response()->json(['message' => 'Generación de Excel no implementada aún']);
    }
}
