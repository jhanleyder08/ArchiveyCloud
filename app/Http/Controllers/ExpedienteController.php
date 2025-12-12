<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Services\ExpedienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ExpedienteController extends Controller
{
    protected $expedienteService;

    public function __construct(ExpedienteService $expedienteService)
    {
        $this->expedienteService = $expedienteService;
    }

    /**
     * Mostrar listado de expedientes
     */
    public function index(Request $request): Response
    {
        $query = Expediente::with(['serie', 'subserie', 'responsable'])
            ->withCount('documentos');

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('serie_id')) {
            $query->where('serie_id', $request->serie_id);
        }

        if ($request->has('tipo_expediente')) {
            $query->where('tipo_expediente', $request->tipo_expediente);
        }

        if ($request->has('proximidad_vencimiento')) {
            // Lógica para filtrar por proximidad de eliminación (usando fecha_eliminacion)
            $hoy = now();
            switch ($request->proximidad_vencimiento) {
                case 'vencidos':
                    $query->whereNotNull('fecha_eliminacion')
                          ->whereDate('fecha_eliminacion', '<', $hoy);
                    break;
                case 'proximos_30':
                    $query->whereNotNull('fecha_eliminacion')
                          ->whereDate('fecha_eliminacion', '>=', $hoy)
                          ->whereDate('fecha_eliminacion', '<=', $hoy->copy()->addDays(30));
                    break;
                case 'proximos_60':
                    $query->whereNotNull('fecha_eliminacion')
                          ->whereDate('fecha_eliminacion', '>=', $hoy)
                          ->whereDate('fecha_eliminacion', '<=', $hoy->copy()->addDays(60));
                    break;
            }
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->search . '%')
                  ->orWhere('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        $expedientes = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        // Transformar documentos_count a numero_documentos y calcular tamaño total
        $expedientes->getCollection()->transform(function ($expediente) {
            $expediente->numero_documentos = $expediente->documentos_count ?? 0;
            
            // Calcular tamaño total de documentos en bytes
            $expediente->tamano_total_bytes = $expediente->documentos()
                ->sum('tamano_bytes') ?? 0;
            
            return $expediente;
        });

        // Estadísticas
        $estadisticas = [
            'total' => Expediente::count(),
            'en_tramite' => Expediente::where('estado', 'en_tramite')->count(),
            'activos' => Expediente::where('estado', 'activo')->count(),
            'inactivos' => Expediente::where('estado', 'inactivo')->count(),
            'transferidos' => Expediente::where('estado', 'transferido')->count(),
            'proximos_vencer' => Expediente::whereNotNull('fecha_eliminacion')
                ->whereDate('fecha_eliminacion', '>=', now())
                ->whereDate('fecha_eliminacion', '<=', now()->addDays(30))
                ->count(),
            'vencidos' => Expediente::whereNotNull('fecha_eliminacion')
                ->whereDate('fecha_eliminacion', '<', now())
                ->count(),
        ];

        // Opciones para los filtros
        $opciones = [
            'estados' => [
                ['value' => 'en_tramite', 'label' => 'En Trámite'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'semiactivo', 'label' => 'Semiactivo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
                ['value' => 'transferido', 'label' => 'Transferido'],
                ['value' => 'eliminado', 'label' => 'Eliminado'],
            ],
            'tipos' => [
                ['value' => 'administrativo', 'label' => 'Administrativo'],
                ['value' => 'contable', 'label' => 'Contable'],
                ['value' => 'juridico', 'label' => 'Jurídico'],
                ['value' => 'tecnico', 'label' => 'Técnico'],
                ['value' => 'historico', 'label' => 'Histórico'],
                ['value' => 'personal', 'label' => 'Personal'],
            ],
            'proximidad_vencimiento' => [
                ['value' => 'vencidos', 'label' => 'Vencidos'],
                ['value' => 'proximos_30', 'label' => 'Próximos 30 días'],
                ['value' => 'proximos_60', 'label' => 'Próximos 60 días'],
            ],
            'series_disponibles' => \App\Models\SerieDocumental::where('activa', true)
                ->get(['id', 'codigo', 'nombre'])
                ->map(function ($serie) {
                    return [
                        'id' => $serie->id,
                        'codigo' => $serie->codigo,
                        'nombre' => $serie->nombre,
                    ];
                })
                ->toArray(),
            'areas_disponibles' => [], // Campo area_responsable no existe en la tabla
        ];

        return Inertia::render('admin/expedientes/index', [
            'expedientes' => $expedientes,
            'filtros' => $request->only(['search', 'estado', 'tipo_expediente', 'serie_id', 'proximidad_vencimiento']),
            'estadisticas' => $estadisticas,
            'opciones' => $opciones,
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        // Obtener series con sus subseries para el filtrado en frontend
        $series = \App\Models\SerieDocumental::where('activa', true)
            ->get(['id', 'codigo', 'nombre', 'trd_id']);
        
        $subseries = \App\Models\SubserieDocumental::where('activa', true)
            ->get(['id', 'serie_documental_id', 'codigo', 'nombre']);

        return Inertia::render('admin/expedientes/create', [
            'opciones' => [
                'series' => $series,
                'subseries' => $subseries->map(function($s) {
                    return [
                        'id' => $s->id,
                        'serie_id' => $s->serie_documental_id,
                        'codigo' => $s->codigo,
                        'nombre' => $s->nombre,
                    ];
                }),
                'usuarios' => \App\Models\User::all(['id', 'name', 'email']),
                'tipos_expediente' => [
                    ['value' => 'administrativo', 'label' => 'Administrativo'],
                    ['value' => 'contable', 'label' => 'Contable'],
                    ['value' => 'juridico', 'label' => 'Jurídico'],
                    ['value' => 'tecnico', 'label' => 'Técnico'],
                    ['value' => 'historico', 'label' => 'Histórico'],
                    ['value' => 'personal', 'label' => 'Personal'],
                ],
                'niveles_acceso' => [
                    ['value' => 'publico', 'label' => 'Público'],
                    ['value' => 'restringido', 'label' => 'Restringido'],
                    ['value' => 'confidencial', 'label' => 'Confidencial'],
                    ['value' => 'reservado', 'label' => 'Reservado'],
                ],
            ],
        ]);
    }

    /**
     * Almacenar nuevo expediente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:500',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'tipo_expediente' => 'required|in:administrativo,contable,juridico,tecnico,historico,personal',
            'nivel_acceso' => 'required|in:publico,restringido,confidencial,reservado',
            'responsable_id' => 'required|exists:users,id',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'palabras_clave' => 'nullable|array',
            'notas' => 'nullable|string',
        ]);

        try {
            // Agregar campos adicionales
            $validated['estado'] = 'en_tramite';
            $validated['fecha_apertura'] = now();
            $validated['created_by'] = $request->user()->id;
            
            // Generar código automático
            $year = now()->format('Y');
            $count = Expediente::whereYear('created_at', $year)->count() + 1;
            $validated['codigo'] = 'EXP-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $expediente = Expediente::create($validated);

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente creado exitosamente. Código: ' . $expediente->codigo);
        } catch (\Exception $e) {
            Log::error('Error al crear expediente', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear expediente: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar expediente específico
     */
    public function show(Expediente $expediente): Response
    {
        $expediente->load([
            'serie',
            'subserie',
            'responsable',
        ]);

        return Inertia::render('admin/expedientes/show', [
            'expediente' => $expediente,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Expediente $expediente): Response
    {
        if ($expediente->cerrado) {
            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('error', 'No se puede editar un expediente cerrado');
        }

        $series = \App\Models\SerieDocumental::where('activa', true)
            ->get(['id', 'codigo', 'nombre']);
        
        $subseries = \App\Models\SubserieDocumental::where('activa', true)
            ->get(['id', 'serie_documental_id', 'codigo', 'nombre']);

        return Inertia::render('admin/expedientes/edit', [
            'expediente' => $expediente->load(['serie', 'subserie', 'responsable']),
            'opciones' => [
                'series' => $series,
                'subseries' => $subseries->map(function($s) {
                    return [
                        'id' => $s->id,
                        'serie_id' => $s->serie_documental_id,
                        'codigo' => $s->codigo,
                        'nombre' => $s->nombre,
                    ];
                }),
                'usuarios' => \App\Models\User::all(['id', 'name', 'email']),
                'tipos_expediente' => [
                    ['value' => 'administrativo', 'label' => 'Administrativo'],
                    ['value' => 'contable', 'label' => 'Contable'],
                    ['value' => 'juridico', 'label' => 'Jurídico'],
                    ['value' => 'tecnico', 'label' => 'Técnico'],
                    ['value' => 'historico', 'label' => 'Histórico'],
                    ['value' => 'personal', 'label' => 'Personal'],
                ],
                'niveles_acceso' => [
                    ['value' => 'publico', 'label' => 'Público'],
                    ['value' => 'restringido', 'label' => 'Restringido'],
                    ['value' => 'confidencial', 'label' => 'Confidencial'],
                    ['value' => 'reservado', 'label' => 'Reservado'],
                ],
            ],
        ]);
    }

    /**
     * Actualizar expediente
     */
    public function update(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:100|unique:expedientes,codigo,' . $expediente->id,
            'titulo' => 'required|string|max:500',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'tipo_expediente' => 'required|in:administrativo,contable,juridico,tecnico,historico,personal',
            'nivel_acceso' => 'required|in:publico,restringido,confidencial,reservado',
            'responsable_id' => 'required|exists:users,id',
            'ubicacion_fisica' => 'nullable|string|max:500',
            'palabras_clave' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $this->expedienteService->actualizar($expediente, $validated, $request->user());

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar expediente', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado del expediente
     */
    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'estado' => 'required|in:en_tramite,activo,semiactivo,inactivo,historico,en_transferencia,transferido',
            'observaciones' => 'required|string',
        ]);

        try {
            $this->expedienteService->cambiarEstado(
                $expediente,
                $validated['estado'],
                $validated['observaciones'],
                $request->user()
            );

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Estado cambiado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar estado: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar expediente
     */
    public function cerrar(Expediente $expediente, Request $request)
    {
        try {
            $this->expedienteService->cerrar($expediente, $request->user());

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Expediente cerrado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cerrar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Agregar documento al expediente
     */
    public function agregarDocumento(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'orden' => 'nullable|integer',
            'motivo' => 'nullable|string',
            'es_principal' => 'nullable|boolean',
        ]);

        try {
            $this->expedienteService->agregarDocumento(
                $expediente,
                $validated['documento_id'],
                $validated,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento agregado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar documento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear transferencia
     */
    public function crearTransferencia(Request $request, Expediente $expediente)
    {
        $validated = $request->validate([
            'tipo_transferencia' => 'required|in:archivo_gestion_a_central,archivo_central_a_historico,transferencia_entre_dependencias',
            'destino_dependencia_id' => 'required|exists:dependencias,id',
            'ubicacion_destino' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $transferencia = $this->expedienteService->crearTransferencia(
                $expediente,
                $validated,
                $request->user()
            );

            return redirect()
                ->route('admin.expedientes.show', $expediente->id)
                ->with('success', 'Transferencia creada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear transferencia: ' . $e->getMessage());
        }
    }

    /**
     * Verificar integridad
     */
    public function verificarIntegridad(Expediente $expediente)
    {
        try {
            $resultado = $this->expedienteService->verificarIntegridad($expediente);

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar integridad: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar expediente
     */
    public function destroy(Expediente $expediente)
    {
        if ($expediente->cerrado) {
            return back()->with('error', 'No se puede eliminar un expediente cerrado');
        }

        if ($expediente->numero_documentos > 0) {
            return back()->with('error', 'No se puede eliminar un expediente con documentos asociados');
        }

        try {
            $expediente->delete();

            return redirect()
                ->route('admin.expedientes.index')
                ->with('success', 'Expediente eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales(): array
    {
        return [
            'total_expedientes' => Expediente::count(),
            'en_tramite' => Expediente::where('estado', 'en_tramite')->count(),
            'activos' => Expediente::where('estado', 'activo')->count(),
            'cerrados' => Expediente::where('cerrado', true)->count(),
            'por_tipo' => Expediente::selectRaw('tipo_expediente, COUNT(*) as total')
                ->groupBy('tipo_expediente')
                ->pluck('total', 'tipo_expediente')
                ->toArray(),
        ];
    }
}
