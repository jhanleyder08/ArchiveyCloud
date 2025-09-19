<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisposicionFinal;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use App\Models\PistaAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDisposicionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Dashboard de disposiciones finales
     */
    public function index(Request $request)
    {
        $query = DisposicionFinal::with(['expediente', 'documento', 'responsable', 'aprobadoPor'])
            ->orderBy('fecha_vencimiento_retencion', 'asc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_disposicion')) {
            $query->where('tipo_disposicion', $request->tipo_disposicion);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_vencimiento_retencion', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_vencimiento_retencion', '<=', $request->fecha_fin);
        }

        if ($request->filled('responsable')) {
            $query->whereHas('responsable', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->responsable . '%');
            });
        }

        $disposiciones = $query->paginate(20)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total_disposiciones' => DisposicionFinal::count(),
            'pendientes' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_PENDIENTE)->count(),
            'en_revision' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_EN_REVISION)->count(),
            'vencidas' => DisposicionFinal::vencidas()->count(),
            'proximas_vencer' => DisposicionFinal::proximasAVencer(30)->count(),
        ];

        // Próximas a vencer (30 días)
        $proximasVencer = DisposicionFinal::with(['expediente', 'documento', 'responsable'])
            ->proximasAVencer(30)
            ->where('estado', '!=', DisposicionFinal::ESTADO_EJECUTADO)
            ->orderBy('fecha_vencimiento_retencion')
            ->limit(10)
            ->get();

        return Inertia::render('admin/disposiciones/index', [
            'disposiciones' => $disposiciones,
            'estadisticas' => $estadisticas,
            'proximasVencer' => $proximasVencer,
            'filtros' => $request->only(['estado', 'tipo_disposicion', 'fecha_inicio', 'fecha_fin', 'responsable']),
        ]);
    }

    /**
     * Crear nueva disposición final
     */
    public function create()
    {
        // Expedientes próximos a vencer su retención (90 días)
        $expedientesVencimiento = Expediente::where('fecha_cierre', '<=', Carbon::now()->subYears(5))
            ->whereDoesntHave('disposicionFinal')
            ->select('id', 'numero_expediente', 'titulo', 'fecha_cierre')
            ->orderBy('fecha_cierre')
            ->get();

        // Documentos próximos a vencer
        $documentosVencimiento = Documento::where('created_at', '<=', Carbon::now()->subYears(3))
            ->whereDoesntHave('disposicionFinal')
            ->select('id', 'nombre', 'expediente_id', 'created_at')
            ->with('expediente:id,numero_expediente,titulo')
            ->orderBy('created_at')
            ->get();

        $usuarios = User::select('id', 'name', 'email')
            ->where('estado', 'activo')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/disposiciones/create', [
            'expedientesVencimiento' => $expedientesVencimiento,
            'documentosVencimiento' => $documentosVencimiento,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Almacenar nueva disposición
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_item' => 'required|in:expediente,documento',
            'expediente_id' => 'required_if:tipo_item,expediente|exists:expedientes,id',
            'documento_id' => 'required_if:tipo_item,documento|exists:documentos,id',
            'tipo_disposicion' => 'required|in:conservacion_permanente,eliminacion_controlada,transferencia_historica,digitalizacion,microfilmacion',
            'fecha_vencimiento_retencion' => 'required|date',
            'justificacion' => 'required|string|min:50|max:2000',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated) {
            $disposicion = DisposicionFinal::create([
                'expediente_id' => $validated['expediente_id'] ?? null,
                'documento_id' => $validated['documento_id'] ?? null,
                'responsable_id' => auth()->id(),
                'tipo_disposicion' => $validated['tipo_disposicion'],
                'estado' => DisposicionFinal::ESTADO_PENDIENTE,
                'fecha_vencimiento_retencion' => $validated['fecha_vencimiento_retencion'],
                'fecha_propuesta' => Carbon::now(),
                'justificacion' => $validated['justificacion'],
                'observaciones' => $validated['observaciones'],
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'crear_disposicion_final',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Nueva disposición final propuesta: {$disposicion->tipo_disposicion_label}",
                'datos_nuevos' => $disposicion->toJson(),
            ]);
        });

        return redirect()
            ->route('admin.disposiciones.index')
            ->with('success', 'Disposición final creada exitosamente.');
    }

    /**
     * Ver detalles de disposición
     */
    public function show(DisposicionFinal $disposicion)
    {
        $disposicion->load(['expediente', 'documento.expediente', 'responsable', 'aprobadoPor']);

        // Historial de cambios
        $historial = PistaAuditoria::where('tabla_afectada', 'disposicion_finals')
            ->where('registro_id', $disposicion->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/disposiciones/show', [
            'disposicion' => $disposicion,
            'historial' => $historial,
        ]);
    }

    /**
     * Enviar a revisión
     */
    public function enviarRevision(DisposicionFinal $disposicion, Request $request)
    {
        if ($disposicion->estado !== DisposicionFinal::ESTADO_PENDIENTE) {
            return back()->withErrors(['error' => 'Solo se pueden enviar a revisión las disposiciones pendientes.']);
        }

        $validated = $request->validate([
            'documentos_soporte' => 'nullable|array',
            'validacion_legal' => 'required|string|min:20',
            'cumple_normativa' => 'required|boolean',
        ]);

        DB::transaction(function () use ($disposicion, $validated) {
            $disposicion->update([
                'estado' => DisposicionFinal::ESTADO_EN_REVISION,
                'documentos_soporte' => $validated['documentos_soporte'] ?? [],
                'validacion_legal' => $validated['validacion_legal'],
                'cumple_normativa' => $validated['cumple_normativa'],
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'enviar_revision_disposicion',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => 'Disposición enviada a revisión con documentación legal',
                'datos_anteriores' => $disposicion->getOriginal(),
                'datos_nuevos' => $disposicion->toJson(),
            ]);
        });

        return back()->with('success', 'Disposición enviada a revisión exitosamente.');
    }

    /**
     * Aprobar disposición
     */
    public function aprobar(DisposicionFinal $disposicion, Request $request)
    {
        if (!$disposicion->puedeSerAprobada()) {
            return back()->withErrors(['error' => 'Esta disposición no puede ser aprobada en su estado actual.']);
        }

        $validated = $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'acta_comite' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($disposicion, $validated) {
            $disposicion->update([
                'estado' => DisposicionFinal::ESTADO_APROBADO,
                'fecha_aprobacion' => Carbon::now(),
                'aprobado_por' => auth()->id(),
                'observaciones' => $validated['observaciones'],
                'acta_comite' => $validated['acta_comite'],
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'aprobar_disposicion',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Disposición aprobada. Acta: {$validated['acta_comite']}",
                'datos_anteriores' => $disposicion->getOriginal(),
                'datos_nuevos' => $disposicion->toJson(),
            ]);
        });

        return back()->with('success', 'Disposición aprobada exitosamente.');
    }

    /**
     * Rechazar disposición
     */
    public function rechazar(DisposicionFinal $disposicion, Request $request)
    {
        if ($disposicion->estado !== DisposicionFinal::ESTADO_EN_REVISION) {
            return back()->withErrors(['error' => 'Solo se pueden rechazar disposiciones en revisión.']);
        }

        $validated = $request->validate([
            'observaciones_rechazo' => 'required|string|min:20|max:1000',
        ]);

        DB::transaction(function () use ($disposicion, $validated) {
            $disposicion->update([
                'estado' => DisposicionFinal::ESTADO_RECHAZADO,
                'aprobado_por' => auth()->id(),
                'observaciones_rechazo' => $validated['observaciones_rechazo'],
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'rechazar_disposicion',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => 'Disposición rechazada',
                'datos_anteriores' => $disposicion->getOriginal(),
                'datos_nuevos' => $disposicion->toJson(),
            ]);
        });

        return back()->with('success', 'Disposición rechazada.');
    }

    /**
     * Ejecutar disposición
     */
    public function ejecutar(DisposicionFinal $disposicion, Request $request)
    {
        if (!$disposicion->puedeSerEjecutada()) {
            return back()->withErrors(['error' => 'Esta disposición no puede ser ejecutada.']);
        }

        $validated = $request->validate([
            'metodo_eliminacion' => 'required_if:tipo_disposicion,eliminacion_controlada|string|max:100',
            'empresa_ejecutora' => 'required_if:tipo_disposicion,eliminacion_controlada|string|max:100',
            'certificado_destruccion' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($disposicion, $validated) {
            $disposicion->update([
                'estado' => DisposicionFinal::ESTADO_EJECUTADO,
                'fecha_ejecucion' => Carbon::now(),
                'metodo_eliminacion' => $validated['metodo_eliminacion'] ?? null,
                'empresa_ejecutora' => $validated['empresa_ejecutora'] ?? null,
                'certificado_destruccion' => $validated['certificado_destruccion'] ?? null,
                'observaciones' => $validated['observaciones'],
            ]);

            // Actualizar el estado del expediente/documento si es eliminación
            if ($disposicion->tipo_disposicion === DisposicionFinal::TIPO_ELIMINACION_CONTROLADA) {
                if ($disposicion->expediente) {
                    $disposicion->expediente->update(['estado_ciclo_vida' => 'eliminado']);
                }
                if ($disposicion->documento) {
                    $disposicion->documento->delete(); // Soft delete
                }
            }

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'ejecutar_disposicion',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Disposición ejecutada: {$disposicion->tipo_disposicion_label}",
                'datos_anteriores' => $disposicion->getOriginal(),
                'datos_nuevos' => $disposicion->toJson(),
            ]);
        });

        return back()->with('success', 'Disposición ejecutada exitosamente.');
    }

    /**
     * Reportes de disposiciones
     */
    public function reportes()
    {
        // Estadísticas generales
        $estadisticasGenerales = [
            'total_disposiciones' => DisposicionFinal::count(),
            'ejecutadas_este_año' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_EJECUTADO)
                ->whereYear('fecha_ejecucion', Carbon::now()->year)
                ->count(),
            'vencidas_sin_procesar' => DisposicionFinal::vencidas()
                ->whereIn('estado', [DisposicionFinal::ESTADO_PENDIENTE, DisposicionFinal::ESTADO_EN_REVISION])
                ->count(),
        ];

        // Disposiciones por tipo (últimos 12 meses)
        $disposicionesPorTipo = DisposicionFinal::selectRaw('
                tipo_disposicion,
                COUNT(*) as total,
                COUNT(CASE WHEN estado = "ejecutado" THEN 1 END) as ejecutadas
            ')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('tipo_disposicion')
            ->get();

        // Disposiciones por estado
        $disposicionesPorEstado = DisposicionFinal::selectRaw('
                estado,
                COUNT(*) as total
            ')
            ->groupBy('estado')
            ->get();

        // Timeline de ejecuciones (últimos 12 meses)
        $timelineEjecuciones = DisposicionFinal::selectRaw('
                DATE_FORMAT(fecha_ejecucion, "%Y-%m") as mes,
                tipo_disposicion,
                COUNT(*) as total
            ')
            ->where('fecha_ejecucion', '>=', Carbon::now()->subMonths(12))
            ->whereNotNull('fecha_ejecucion')
            ->groupBy('mes', 'tipo_disposicion')
            ->orderBy('mes')
            ->get();

        return Inertia::render('admin/disposiciones/reportes', [
            'estadisticasGenerales' => $estadisticasGenerales,
            'disposicionesPorTipo' => $disposicionesPorTipo,
            'disposicionesPorEstado' => $disposicionesPorEstado,
            'timelineEjecuciones' => $timelineEjecuciones,
        ]);
    }
}
