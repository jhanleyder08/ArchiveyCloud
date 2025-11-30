<?php

namespace App\Http\Controllers;

use App\Models\ProcesoRetencionDisposicion;
use App\Models\HistorialAccionDisposicion;
use App\Models\AlertaRetencion;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\TablaRetencionDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

/**
 * Controlador para módulo de Retención y Disposición del SGDEA
 * 
 * Implementa requerimientos:
 * - REQ-RD-001: Gestión de tiempos de retención
 * - REQ-RD-002: Auditoría y trazabilidad
 * - REQ-RD-005: Acciones de disposición
 * - REQ-RD-007: Sistema de alertas
 * - REQ-RD-008: Gestión de aplazamientos
 * - REQ-RD-010: Reportes y seguimiento
 */
class AdminRetencionDisposicionController extends Controller
{
    /**
     * Mostrar lista de procesos de retención y disposición
     */
    public function index(Request $request): Response
    {
        $query = ProcesoRetencionDisposicion::with([
            'documento:id,codigo_documento,titulo,estado',
            'expediente:id,codigo,titulo,estado',
            'trd:id,codigo,nombre,version',
            'serieDocumental:id,nombre,codigo',
            'usuarioCreador:id,name'
        ]);

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_proceso', 'like', "%{$search}%")
                  ->orWhereHas('documento', function($subQ) use ($search) {
                      $subQ->where('codigo_documento', 'like', "%{$search}%")
                           ->orWhere('titulo', 'like', "%{$search}%");
                  })
                  ->orWhereHas('expediente', function($subQ) use ($search) {
                      $subQ->where('codigo', 'like', "%{$search}%")
                           ->orWhere('titulo', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_entidad')) {
            $query->where('tipo_entidad', $request->tipo_entidad);
        }

        if ($request->filled('prioridad')) {
            if ($request->prioridad === 'criticos') {
                $query->where(function($q) {
                    $q->whereIn('estado', ['vencido', 'alerta_previa'])
                      ->orWhere('fecha_vencimiento_gestion', '<=', now()->addDays(7));
                });
            }
        }

        if ($request->filled('trd_id')) {
            $query->where('trd_id', $request->trd_id);
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validar campo de ordenamiento
        $allowedSorts = ['codigo_proceso', 'estado', 'fecha_vencimiento_gestion', 'tipo_entidad', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $procesos = $query->paginate(15)->withQueryString();

        // Estadísticas para dashboard
        $stats = [
            'total' => ProcesoRetencionDisposicion::count(),
            'activos' => ProcesoRetencionDisposicion::where('estado', 'activo')->count(),
            'vencidos' => ProcesoRetencionDisposicion::where('estado', 'vencido')->count(),
            'en_alerta' => ProcesoRetencionDisposicion::where('estado', 'alerta_previa')->count(),
            'transferidos' => ProcesoRetencionDisposicion::where('estado', 'transferido')->count(),
            'eliminados' => ProcesoRetencionDisposicion::where('estado', 'eliminado')->count(),
            'conservados' => ProcesoRetencionDisposicion::where('estado', 'conservado')->count(),
            'criticos' => ProcesoRetencionDisposicion::whereIn('estado', ['vencido', 'alerta_previa'])
                ->orWhere('fecha_vencimiento_gestion', '<=', now()->addDays(7))->count()
        ];

        // Opciones para filtros
        $trdOptions = TablaRetencionDocumental::select('id', 'codigo', 'nombre')
            ->where('estado', 'vigente')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('admin/retencion-disposicion/index', [
            'procesos' => $procesos,
            'stats' => $stats,
            'filtros' => $request->only(['search', 'estado', 'tipo_entidad', 'prioridad', 'trd_id']),
            'trdOptions' => $trdOptions,
            'flash' => session()->only(['success', 'error'])
        ]);
    }

    /**
     * Mostrar detalle de un proceso de retención
     */
    public function show(ProcesoRetencionDisposicion $proceso): Response
    {
        $proceso->load([
            'documento.expediente',
            'expediente.documentos',
            'trd',
            'serieDocumental',
            'subserieDocumental',
            'usuarioCreador',
            'usuarioModificador',
            'usuarioAplazamiento',
            'historialAcciones.usuario',
            'alertas' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        // Calcular información adicional
        $diasRestantes = $proceso->dias_hasta_vencimiento;
        $alertasActivas = $proceso->alertas()->requierenAtencion()->count();
        
        // Verificar integridad
        $integridadValida = $proceso->verificarIntegridad();

        return Inertia::render('admin/retencion-disposicion/show', [
            'proceso' => $proceso,
            'diasRestantes' => $diasRestantes,
            'alertasActivas' => $alertasActivas,
            'integridadValida' => $integridadValida,
            'acciones_disponibles' => $this->obtenerAccionesDisponibles($proceso),
            'flash' => session()->only(['success', 'error'])
        ]);
    }

    /**
     * REQ-RD-005: Ejecutar acción de disposición
     */
    public function ejecutarDisposicion(Request $request, ProcesoRetencionDisposicion $proceso)
    {
        $request->validate([
            'accion' => ['required', Rule::in([
                'conservacion_permanente',
                'eliminacion',
                'transferencia_historico',
                'seleccion_documental',
                'microfilmacion',
                'digitalizacion_permanente'
            ])],
            'observaciones' => 'nullable|string|max:1000',
            'confirmacion' => 'required|boolean|accepted'
        ]);

        try {
            DB::beginTransaction();

            $exitoso = $proceso->ejecutarDisposicion(
                $request->accion,
                auth()->user(),
                $request->observaciones
            );

            if (!$exitoso) {
                throw new \Exception('No se pudo ejecutar la acción de disposición');
            }

            // Marcar alertas relacionadas como atendidas
            $proceso->alertas()
                ->requierenAtencion()
                ->get()
                ->each(function($alerta) use ($request) {
                    $alerta->marcarComoAtendida(auth()->user(), $request->observaciones);
                });

            DB::commit();

            return back()->with('success', 'Acción de disposición ejecutada correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'error' => 'Error al ejecutar disposición: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * REQ-RD-008: Aplazar disposición
     */
    public function aplazarDisposicion(Request $request, ProcesoRetencionDisposicion $proceso)
    {
        $request->validate([
            'fecha_fin_aplazamiento' => 'required|date|after:today',
            'razon_aplazamiento' => 'required|string|max:500',
            'confirmacion' => 'required|boolean|accepted'
        ]);

        try {
            $fechaFin = Carbon::parse($request->fecha_fin_aplazamiento);
            
            $exitoso = $proceso->aplazarDisposicion(
                $fechaFin,
                $request->razon_aplazamiento,
                auth()->user()
            );

            if (!$exitoso) {
                throw new \Exception('No se pudo aplazar la disposición');
            }

            return back()->with('success', 'Disposición aplazada hasta ' . $fechaFin->format('d/m/Y'));

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al aplazar disposición: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reactivar proceso aplazado
     */
    public function reactivarProceso(Request $request, ProcesoRetencionDisposicion $proceso)
    {
        if (!$proceso->aplazado) {
            return back()->withErrors(['error' => 'El proceso no está aplazado']);
        }

        try {
            $proceso->aplazado = false;
            $proceso->fecha_fin_aplazamiento = null;
            $proceso->estado = ProcesoRetencionDisposicion::ESTADO_ACTIVO;
            $proceso->updated_by = auth()->id();
            
            // Actualizar estado basado en fechas actuales
            $proceso->actualizarEstado();
            $proceso->save();

            return back()->with('success', 'Proceso reactivado correctamente');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al reactivar proceso: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * REQ-RD-009: Bloquear eliminación
     */
    public function bloquearEliminacion(Request $request, ProcesoRetencionDisposicion $proceso)
    {
        $request->validate([
            'razon_bloqueo' => 'required|string|max:500'
        ]);

        $proceso->bloqueado_eliminacion = true;
        $proceso->razon_bloqueo = $request->razon_bloqueo;
        $proceso->updated_by = auth()->id();
        $proceso->save();

        return back()->with('success', 'Eliminación bloqueada correctamente');
    }

    /**
     * Desbloquear eliminación
     */
    public function desbloquearEliminacion(ProcesoRetencionDisposicion $proceso)
    {
        $proceso->bloqueado_eliminacion = false;
        $proceso->razon_bloqueo = null;
        $proceso->updated_by = auth()->id();
        $proceso->save();

        return back()->with('success', 'Eliminación desbloqueada correctamente');
    }

    /**
     * REQ-RD-007: Gestionar alertas
     */
    public function gestionarAlertas(Request $request)
    {
        $query = AlertaRetencion::with([
            'procesoRetencion.documento:id,codigo_documento,titulo',
            'procesoRetencion.expediente:id,codigo,titulo'
        ]);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado_alerta', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('nivel_prioridad', $request->prioridad);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_alerta', $request->tipo);
        }

        $alertas = $query->orderBy('created_at', 'desc')->paginate(20);

        $statsAlertas = [
            'pendientes' => AlertaRetencion::pendientes()->count(),
            'criticas' => AlertaRetencion::where('nivel_prioridad', 'critica')->requierenAtencion()->count(),
            'vencidas' => AlertaRetencion::where('fecha_vencimiento_relacionada', '<', now())
                ->requierenAtencion()->count()
        ];

        return Inertia::render('admin/retencion-disposicion/alertas', [
            'alertas' => $alertas,
            'stats' => $statsAlertas,
            'filtros' => $request->only(['estado', 'prioridad', 'tipo'])
        ]);
    }

    /**
     * Marcar alerta como leída
     */
    public function marcarAlertaLeida(AlertaRetencion $alerta)
    {
        $alerta->marcarComoLeida(auth()->user());
        
        return response()->json(['success' => true]);
    }

    /**
     * Marcar alerta como atendida
     */
    public function marcarAlertaAtendida(Request $request, AlertaRetencion $alerta)
    {
        $request->validate([
            'observaciones' => 'nullable|string|max:500'
        ]);

        $alerta->marcarComoAtendida(auth()->user(), $request->observaciones);
        
        return back()->with('success', 'Alerta marcada como atendida');
    }

    /**
     * REQ-RD-010: Reportes y estadísticas
     */
    public function reportes(Request $request): Response
    {
        // Estadísticas generales
        $estadisticasGenerales = [
            'total_procesos' => ProcesoRetencionDisposicion::count(),
            'procesos_activos' => ProcesoRetencionDisposicion::activos()->count(),
            'procesos_vencidos' => ProcesoRetencionDisposicion::vencidos()->count(),
            'procesos_criticos' => ProcesoRetencionDisposicion::whereIn('estado', ['vencido', 'alerta_previa'])->count(),
            'documentos_gestionados' => ProcesoRetencionDisposicion::where('tipo_entidad', 'documento')->count(),
            'expedientes_gestionados' => ProcesoRetencionDisposicion::where('tipo_entidad', 'expediente')->count()
        ];

        // Distribución por estado
        $distribucionEstados = ProcesoRetencionDisposicion::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Distribución por acción de disposición
        $distribucionAcciones = ProcesoRetencionDisposicion::whereNotNull('accion_disposicion')
            ->selectRaw('accion_disposicion, COUNT(*) as total')
            ->groupBy('accion_disposicion')
            ->pluck('total', 'accion_disposicion')
            ->toArray();

        // Tendencia mensual (últimos 12 meses)
        $tendenciaMensual = ProcesoRetencionDisposicion::selectRaw('
                YEAR(created_at) as año,
                MONTH(created_at) as mes,
                COUNT(*) as total_procesos,
                SUM(CASE WHEN estado = "vencido" THEN 1 ELSE 0 END) as vencidos
            ')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('año', 'mes')
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get();

        // Alertas por prioridad
        $alertasPorPrioridad = AlertaRetencion::selectRaw('nivel_prioridad, COUNT(*) as total')
            ->where('created_at', '>=', now()->subMonth())
            ->groupBy('nivel_prioridad')
            ->pluck('total', 'nivel_prioridad')
            ->toArray();

        // Top TRDs con más procesos vencidos
        $trdConMasVencidos = ProcesoRetencionDisposicion::join('tablas_retencion_documental', 'procesos_retencion_disposicion.trd_id', '=', 'tablas_retencion_documental.id')
            ->where('procesos_retencion_disposicion.estado', 'vencido')
            ->selectRaw('tablas_retencion_documental.codigo, tablas_retencion_documental.nombre, COUNT(*) as vencidos')
            ->groupBy('tablas_retencion_documental.id', 'tablas_retencion_documental.codigo', 'tablas_retencion_documental.nombre')
            ->orderBy('vencidos', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('admin/retencion-disposicion/reportes', [
            'estadisticas_generales' => $estadisticasGenerales,
            'distribucion_estados' => $distribucionEstados,
            'distribucion_acciones' => $distribucionAcciones,
            'tendencia_mensual' => $tendenciaMensual,
            'alertas_por_prioridad' => $alertasPorPrioridad,
            'trd_con_mas_vencidos' => $trdConMasVencidos
        ]);
    }

    /**
     * REQ-RD-003: Procesar actualizaciones masivas de estados
     */
    public function procesarActualizacionesMasivas()
    {
        try {
            $procesosActualizados = 0;
            $alertasGeneradas = 0;

            // Obtener procesos que requieren actualización de estado
            $procesos = ProcesoRetencionDisposicion::whereIn('estado', ['activo', 'alerta_previa', 'aplazado'])
                ->get();

            foreach ($procesos as $proceso) {
                $estadoAnterior = $proceso->estado;
                
                // Actualizar estado basado en fechas
                if ($proceso->actualizarEstado()) {
                    $procesosActualizados++;
                }

                // Generar alertas si es necesario
                if ($proceso->requiereAlerta()) {
                    $alerta = AlertaRetencion::generarAlertaAutomatica($proceso);
                    if ($alerta) {
                        $alertasGeneradas++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'procesos_actualizados' => $procesosActualizados,
                'alertas_generadas' => $alertasGeneradas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear proceso de retención para documento/expediente
     */
    public function crearProceso(Request $request)
    {
        $request->validate([
            'tipo_entidad' => 'required|in:documento,expediente',
            'entidad_id' => 'required|integer',
            'trd_id' => 'required|exists:tablas_retencion_documental,id'
        ]);

        try {
            DB::beginTransaction();

            // Verificar que no exista ya un proceso para esta entidad
            $procesoExistente = ProcesoRetencionDisposicion::where('tipo_entidad', $request->tipo_entidad)
                ->where($request->tipo_entidad . '_id', $request->entidad_id)
                ->first();

            if ($procesoExistente) {
                return back()->withErrors(['error' => 'Ya existe un proceso de retención para esta entidad']);
            }

            // Obtener la entidad y sus datos
            $entidad = $request->tipo_entidad === 'documento' 
                ? Documento::find($request->entidad_id)
                : Expediente::find($request->entidad_id);

            if (!$entidad) {
                return back()->withErrors(['error' => 'Entidad no encontrada']);
            }

            // Crear el proceso
            $proceso = ProcesoRetencionDisposicion::create([
                'tipo_entidad' => $request->tipo_entidad,
                $request->tipo_entidad . '_id' => $request->entidad_id,
                'trd_id' => $request->trd_id,
                'fecha_creacion_documento' => $entidad->created_at->toDateString(),
                'periodo_retencion_archivo_gestion' => 5, // Valor por defecto, debe obtenerse de TRD
                'periodo_retencion_archivo_central' => 10, // Valor por defecto, debe obtenerse de TRD
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return back()->with('success', 'Proceso de retención creado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'error' => 'Error al crear proceso: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener acciones disponibles para un proceso
     */
    private function obtenerAccionesDisponibles(ProcesoRetencionDisposicion $proceso): array
    {
        $acciones = [];

        // Acciones básicas siempre disponibles
        if (in_array($proceso->estado, ['vencido', 'alerta_previa'])) {
            $acciones[] = [
                'key' => 'conservacion_permanente',
                'label' => 'Conservación Permanente',
                'description' => 'Conservar el documento de forma permanente',
                'icon' => 'archive',
                'color' => 'green'
            ];

            if (!$proceso->bloqueado_eliminacion) {
                $acciones[] = [
                    'key' => 'eliminacion',
                    'label' => 'Eliminación',
                    'description' => 'Eliminar el documento según disposición',
                    'icon' => 'trash-2',
                    'color' => 'red'
                ];
            }

            $acciones[] = [
                'key' => 'transferencia_historico',
                'label' => 'Transferencia a Histórico',
                'description' => 'Transferir a archivo histórico',
                'icon' => 'send',
                'color' => 'blue'
            ];
        }

        // Acción de aplazamiento si no está aplazado
        if (!$proceso->aplazado && in_array($proceso->estado, ['vencido', 'alerta_previa'])) {
            $acciones[] = [
                'key' => 'aplazar',
                'label' => 'Aplazar Disposición',
                'description' => 'Postponer la disposición temporalmente',
                'icon' => 'pause-circle',
                'color' => 'orange'
            ];
        }

        return $acciones;
    }
}
