<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisposicionFinal;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use App\Models\PistaAuditoria;
use App\Services\DisposicionAutomaticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminDisposicionController extends Controller
{
    protected DisposicionAutomaticaService $disposicionService;

    public function __construct(DisposicionAutomaticaService $disposicionService)
    {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->disposicionService = $disposicionService;
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

        // Agregar atributos computados a cada disposición
        $disposiciones->getCollection()->transform(function ($disposicion) {
            $disposicion->append(['tipo_disposicion_label', 'estado_label', 'item_afectado']);
            $disposicion->dias_para_vencimiento = $disposicion->diasParaVencimiento();
            $disposicion->esta_vencida = $disposicion->estaVencida();
            return $disposicion;
        });

        // Estadísticas
        $estadisticas = [
            'total_disposiciones' => DisposicionFinal::count(),
            'pendientes' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_PENDIENTE)->count(),
            'en_revision' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_EN_REVISION)->count(),
            'aprobadas' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_APROBADO)->count(),
            'ejecutadas' => DisposicionFinal::where('estado', DisposicionFinal::ESTADO_EJECUTADO)->count(),
            'vencidas' => DisposicionFinal::vencidas()->count(),
            'proximas_vencer' => DisposicionFinal::proximasAVencer(30)->count(),
        ];

        // Estadísticas de expedientes pendientes de disposición (desde TRD)
        $estadisticasTRD = $this->disposicionService->getEstadisticasDisposicionesPendientes();

        // Próximas a vencer (30 días)
        $proximasVencer = DisposicionFinal::with(['expediente', 'documento', 'responsable'])
            ->proximasAVencer(30)
            ->where('estado', '!=', DisposicionFinal::ESTADO_EJECUTADO)
            ->orderBy('fecha_vencimiento_retencion')
            ->limit(10)
            ->get()
            ->map(function ($disposicion) {
                $disposicion->append(['tipo_disposicion_label', 'estado_label', 'item_afectado']);
                $disposicion->dias_para_vencimiento = $disposicion->diasParaVencimiento();
                $disposicion->esta_vencida = $disposicion->estaVencida();
                return $disposicion;
            });

        // Expedientes que requieren disposición según TRD
        $expedientesPendientesTRD = $this->disposicionService->getExpedientesParaDisposicion(90)
            ->take(10)
            ->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'codigo' => $exp->codigo,
                    'titulo' => $exp->titulo,
                    'fecha_cierre' => $exp->fecha_cierre?->format('Y-m-d'),
                    'fecha_eliminacion' => $exp->fecha_eliminacion?->format('Y-m-d'),
                    'dias_para_disposicion' => $exp->dias_para_disposicion,
                    'ya_vencido' => $exp->ya_vencido,
                    'tipo_disposicion_sugerido' => $exp->tipo_disposicion_sugerido,
                    'serie' => $exp->serie?->nombre,
                    'subserie' => $exp->subserie?->nombre,
                    'responsable' => $exp->responsable?->name,
                ];
            });

        return Inertia::render('admin/disposiciones/index', [
            'disposiciones' => $disposiciones,
            'estadisticas' => $estadisticas,
            'estadisticasTRD' => $estadisticasTRD,
            'expedientesPendientesTRD' => $expedientesPendientesTRD,
            'proximasVencer' => $proximasVencer,
            'filtros' => $request->only(['estado', 'tipo_disposicion', 'fecha_inicio', 'fecha_fin', 'responsable']),
        ]);
    }

    /**
     * Crear nueva disposición final
     */
    public function create()
    {
        // Todos los expedientes sin disposición final
        $expedientesVencimiento = Expediente::whereDoesntHave('disposicionFinal')
            ->select('id', 'codigo', 'titulo', 'fecha_cierre')
            ->orderBy('fecha_cierre', 'desc')
            ->limit(100)
            ->get();

        // Todos los documentos sin disposición final
        $documentosVencimiento = Documento::whereDoesntHave('disposicionFinal')
            ->select('id', 'titulo', 'expediente_id', 'created_at')
            ->with('expediente:id,codigo,titulo')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $usuarios = User::select('id', 'name', 'email')
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
        try {
            \Log::info('Disposicion store - Request data:', $request->all());
            
            $validated = $request->validate([
            'tipo_item' => 'required|in:expediente,documento',
            'expediente_id' => 'required_if:tipo_item,expediente|nullable|exists:expedientes,id',
            'documento_id' => 'required_if:tipo_item,documento|nullable|exists:documentos,id',
            'tipo_disposicion' => 'required|in:conservacion_permanente,eliminacion_controlada,transferencia_historica,digitalizacion,microfilmacion',
            'fecha_propuesta' => 'required|date',
            'justificacion' => 'required|string|min:10|max:2000',
            'observaciones' => 'nullable|string|max:1000',
            'responsable_id' => 'nullable|exists:users,id',
            'responsable_externo_nombre' => 'nullable|string|max:255',
            'responsable_externo_cargo' => 'nullable|string|max:255',
            'responsable_externo_entidad' => 'nullable|string|max:255',
            'responsable_externo_email' => 'nullable|email|max:255',
        ]);

        // Validar que haya un responsable (registrado o externo)
        if (empty($validated['responsable_id']) && empty($validated['responsable_externo_nombre'])) {
            return back()->withErrors(['responsable_id' => 'Debe seleccionar un responsable registrado o ingresar datos de un responsable externo.']);
        }

        DB::transaction(function () use ($validated, $request) {
            // Preparar datos del responsable externo si aplica
            $datosResponsableExterno = null;
            if (!empty($validated['responsable_externo_nombre'])) {
                $datosResponsableExterno = [
                    'nombre' => $validated['responsable_externo_nombre'],
                    'cargo' => $validated['responsable_externo_cargo'] ?? null,
                    'entidad' => $validated['responsable_externo_entidad'] ?? null,
                    'email' => $validated['responsable_externo_email'] ?? null,
                ];
            }

            $disposicion = DisposicionFinal::create([
                'expediente_id' => $validated['expediente_id'] ?? null,
                'documento_id' => $validated['documento_id'] ?? null,
                'responsable_id' => $validated['responsable_id'] ?? auth()->id(),
                'tipo_disposicion' => $validated['tipo_disposicion'],
                'estado' => DisposicionFinal::ESTADO_PENDIENTE,
                'fecha_propuesta' => $validated['fecha_propuesta'],
                'justificacion' => $validated['justificacion'],
                'observaciones' => $validated['observaciones'] ?? null,
                'datos_responsable_externo' => $datosResponsableExterno, // Laravel lo convierte automáticamente a JSON
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'crear_disposicion_final',
                'accion' => 'crear',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Nueva disposición final propuesta: {$disposicion->tipo_disposicion}",
                'valores_nuevos' => $disposicion->toJson(),
            ]);
        });

        return redirect()
            ->route('admin.disposiciones.index')
            ->with('success', 'Disposición final creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Disposicion store - Validation errors:', $e->errors());
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Disposicion store - Error:', ['message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al crear la disposición: ' . $e->getMessage()]);
        }
    }

    /**
     * Editar disposición
     */
    public function edit(DisposicionFinal $disposicion)
    {
        // Solo se pueden editar disposiciones pendientes
        if ($disposicion->estado !== DisposicionFinal::ESTADO_PENDIENTE) {
            return redirect()->route('admin.disposiciones.show', $disposicion)
                ->withErrors(['error' => 'Solo se pueden editar disposiciones en estado pendiente.']);
        }

        $disposicion->load(['expediente', 'documento.expediente', 'responsable']);

        // Agregar atributos computados
        $disposicion->append([
            'tipo_disposicion_label',
            'estado_label',
            'item_afectado',
        ]);

        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/disposiciones/edit', [
            'disposicion' => $disposicion,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Actualizar disposición
     */
    public function update(DisposicionFinal $disposicion, Request $request)
    {
        // Solo se pueden editar disposiciones pendientes
        if ($disposicion->estado !== DisposicionFinal::ESTADO_PENDIENTE) {
            return back()->withErrors(['error' => 'Solo se pueden editar disposiciones en estado pendiente.']);
        }

        $validated = $request->validate([
            'tipo_disposicion' => 'required|in:conservacion_permanente,eliminacion_controlada,transferencia_historica,digitalizacion,microfilmacion',
            'fecha_propuesta' => 'required|date',
            'justificacion' => 'required|string|min:10|max:2000',
            'observaciones' => 'nullable|string|max:1000',
            'responsable_id' => 'nullable|exists:users,id',
        ]);

        DB::transaction(function () use ($disposicion, $validated) {
            $datosAnteriores = $disposicion->toArray();

            $disposicion->update([
                'tipo_disposicion' => $validated['tipo_disposicion'],
                'fecha_propuesta' => $validated['fecha_propuesta'],
                'justificacion' => $validated['justificacion'],
                'observaciones' => $validated['observaciones'] ?? null,
                'responsable_id' => $validated['responsable_id'] ?? $disposicion->responsable_id,
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'actualizar_disposicion_final',
                'accion' => 'actualizar',
                'tabla_afectada' => 'disposicion_finals',
                'registro_id' => $disposicion->id,
                'descripcion' => "Disposición final actualizada: {$disposicion->tipo_disposicion}",
                'valores_anteriores' => json_encode($datosAnteriores),
                'valores_nuevos' => $disposicion->toJson(),
            ]);
        });

        return redirect()
            ->route('admin.disposiciones.show', $disposicion)
            ->with('success', 'Disposición actualizada exitosamente.');
    }

    /**
     * Exportar disposición a PDF
     */
    public function exportarPdf(DisposicionFinal $disposicion)
    {
        $disposicion->load([
            'expediente.serie', 
            'expediente.subserie', 
            'documento.expediente.serie', 
            'documento.expediente.subserie',
            'responsable', 
            'aprobadoPor'
        ]);

        // Agregar atributos computados
        $disposicion->append([
            'tipo_disposicion_label',
            'estado_label',
            'item_afectado',
        ]);

        $disposicion->dias_para_vencimiento = $disposicion->diasParaVencimiento();
        $disposicion->esta_vencida = $disposicion->estaVencida();

        $pdf = Pdf::loadView('pdf.disposicion', [
            'disposicion' => $disposicion,
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("disposicion-final-{$disposicion->id}.pdf");
    }

    /**
     * Ver detalles de disposición
     */
    public function show(DisposicionFinal $disposicion)
    {
        $disposicion->load([
            'expediente.serie', 
            'expediente.subserie', 
            'documento.expediente.serie', 
            'documento.expediente.subserie',
            'responsable', 
            'aprobadoPor'
        ]);

        // Agregar atributos computados
        $disposicion->append([
            'tipo_disposicion_label',
            'estado_label',
            'item_afectado',
        ]);

        // Agregar atributos adicionales
        $disposicion->dias_para_vencimiento = $disposicion->diasParaVencimiento();
        $disposicion->esta_vencida = $disposicion->estaVencida();

        // Historial de cambios
        $historial = PistaAuditoria::where('tabla_afectada', 'disposicion_finals')
            ->where('registro_id', $disposicion->id)
            ->with('usuario')
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
                'evento' => 'enviar_revision_disposicion',
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

            // Registrar auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'aprobar_disposicion',
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

            // Registrar auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'rechazar_disposicion',
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

            // Registrar auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'evento' => 'ejecutar_disposicion',
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

    /**
     * Vista de expedientes pendientes de disposición según TRD
     */
    public function pendientesTRD(Request $request)
    {
        $diasAnticipacion = $request->input('dias', 90);
        
        $expedientes = $this->disposicionService->getExpedientesParaDisposicion($diasAnticipacion)
            ->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'codigo' => $exp->codigo,
                    'titulo' => $exp->titulo,
                    'fecha_cierre' => $exp->fecha_cierre?->format('Y-m-d'),
                    'fecha_eliminacion' => $exp->fecha_eliminacion?->format('Y-m-d'),
                    'dias_para_disposicion' => $exp->dias_para_disposicion,
                    'ya_vencido' => $exp->ya_vencido,
                    'tipo_disposicion_sugerido' => $exp->tipo_disposicion_sugerido,
                    'serie' => $exp->serie?->nombre,
                    'subserie' => $exp->subserie?->nombre,
                    'responsable' => $exp->responsable?->name,
                    'info_retencion' => $exp->info_retencion,
                ];
            });

        $estadisticasTRD = $this->disposicionService->getEstadisticasDisposicionesPendientes();

        return Inertia::render('admin/disposiciones/pendientes-trd', [
            'expedientes' => $expedientes,
            'estadisticasTRD' => $estadisticasTRD,
            'diasAnticipacion' => $diasAnticipacion,
        ]);
    }

    /**
     * Generar disposiciones automáticas desde TRD
     */
    public function generarAutomaticas(Request $request)
    {
        $validated = $request->validate([
            'expediente_ids' => 'nullable|array',
            'expediente_ids.*' => 'exists:expedientes,id',
            'generar_todos_vencidos' => 'nullable|boolean',
        ]);

        $expedienteIds = $validated['expediente_ids'] ?? [];
        $generarTodos = $validated['generar_todos_vencidos'] ?? false;

        // Si no se especifican IDs y se quiere generar todos, dejar vacío el array
        if ($generarTodos) {
            $expedienteIds = [];
        }

        $resultados = $this->disposicionService->generarDisposicionesAutomaticas(
            $expedienteIds,
            auth()->id()
        );

        $mensaje = "Proceso completado: {$resultados['exitosos']} disposiciones creadas";
        if ($resultados['fallidos'] > 0) {
            $mensaje .= ", {$resultados['fallidos']} con errores";
        }
        if ($resultados['omitidos'] > 0) {
            $mensaje .= ", {$resultados['omitidos']} omitidos";
        }

        return redirect()->back()->with([
            'success' => $mensaje,
            'resultados' => $resultados,
        ]);
    }

    /**
     * Generar disposición para un expediente específico (AJAX)
     */
    public function generarParaExpediente(Request $request, Expediente $expediente)
    {
        try {
            $resultado = $this->disposicionService->crearDisposicionParaExpediente(
                $expediente,
                auth()->id()
            );

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Disposición creada exitosamente',
                    'disposicion_id' => $resultado['disposicion_id'],
                    'tipo_disposicion' => $resultado['tipo_disposicion'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['razon'],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear disposición: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener configuración TRD de un expediente (AJAX)
     */
    public function getConfiguracionTRD(Expediente $expediente)
    {
        $resumen = $this->disposicionService->getResumenConfiguracionTRD($expediente);
        
        return response()->json($resumen);
    }
}
