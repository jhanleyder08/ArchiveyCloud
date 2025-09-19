<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\TablaRetencionDocumental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controlador para Expedientes Electrónicos
 * 
 * Implementa requerimientos:
 * REQ-CL-019: Generación automática de expedientes electrónicos
 * REQ-CL-020: Gestión del ciclo de vida de expedientes
 * REQ-CL-021: Expedientes híbridos
 * REQ-CL-025: Control de volúmenes de expedientes
 * REQ-CL-037: Exportación de directorios
 */
class AdminExpedienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expediente::with(['serie', 'subserie', 'usuarioResponsable'])
                          ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_expediente')) {
            $query->where('tipo_expediente', $request->tipo_expediente);
        }

        if ($request->filled('serie_id')) {
            $query->where('serie_id', $request->serie_id);
        }

        if ($request->filled('area_responsable')) {
            $query->where('area_responsable', $request->area_responsable);
        }

        if ($request->filled('proximidad_vencimiento')) {
            $dias = $request->proximidad_vencimiento;
            if ($dias === 'vencidos') {
                $query->vencidos();
            } else {
                $query->proximosVencer(intval($dias));
            }
        }

        // Paginación
        $expedientes = $query->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => Expediente::count(),
            'abiertos' => Expediente::where('estado', Expediente::ESTADO_ABIERTO)->count(),
            'cerrados' => Expediente::where('estado', Expediente::ESTADO_CERRADO)->count(),
            'electronicos' => Expediente::where('tipo_expediente', Expediente::TIPO_ELECTRONICO)->count(),
            'fisicos' => Expediente::where('tipo_expediente', Expediente::TIPO_FISICO)->count(),
            'hibridos' => Expediente::where('tipo_expediente', Expediente::TIPO_HIBRIDO)->count(),
            'proximos_vencer' => Expediente::proximosVencer(30)->count(),
            'vencidos' => Expediente::vencidos()->count(),
        ];

        // Opciones para filtros
        $opciones = [
            'estados' => [
                ['value' => Expediente::ESTADO_ABIERTO, 'label' => 'Abierto'],
                ['value' => Expediente::ESTADO_CERRADO, 'label' => 'Cerrado'],
                ['value' => Expediente::ESTADO_TRANSFERIDO, 'label' => 'Transferido'],
                ['value' => Expediente::ESTADO_ARCHIVADO, 'label' => 'Archivado'],
                ['value' => Expediente::ESTADO_EN_DISPOSICION, 'label' => 'En Disposición'],
            ],
            'tipos' => [
                ['value' => Expediente::TIPO_ELECTRONICO, 'label' => 'Electrónico'],
                ['value' => Expediente::TIPO_FISICO, 'label' => 'Físico'],
                ['value' => Expediente::TIPO_HIBRIDO, 'label' => 'Híbrido'],
            ],
            'proximidad_vencimiento' => [
                ['value' => '7', 'label' => 'Próximos 7 días'],
                ['value' => '30', 'label' => 'Próximos 30 días'],
                ['value' => '90', 'label' => 'Próximos 90 días'],
                ['value' => 'vencidos', 'label' => 'Vencidos'],
            ],
            'series_disponibles' => SerieDocumental::select('id', 'codigo', 'nombre')->get(),
            'areas_disponibles' => $this->getAreasDisponibles(),
        ];

        return Inertia::render('admin/expedientes/index', [
            'data' => $expedientes,
            'estadisticas' => $estadisticas,
            'opciones' => $opciones,
            'filtros' => $request->only(['search', 'estado', 'tipo_expediente', 'serie_id', 'area_responsable', 'proximidad_vencimiento'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $opciones = [
            'series' => SerieDocumental::with('subseries')
                                      ->where('activo', true)
                                      ->select('id', 'codigo', 'nombre')
                                      ->get(),
            'subseries' => SubserieDocumental::where('activo', true)
                                             ->select('id', 'serie_id', 'codigo', 'nombre')
                                             ->get(),
            'trds' => TablaRetencionDocumental::where('activo', true)
                                             ->select('id', 'codigo', 'version', 'nombre')
                                             ->get(),
            'usuarios' => User::where('activo', true)
                             ->select('id', 'name', 'email')
                             ->get(),
            'tipos_expediente' => [
                ['value' => Expediente::TIPO_ELECTRONICO, 'label' => 'Electrónico'],
                ['value' => Expediente::TIPO_FISICO, 'label' => 'Físico'],
                ['value' => Expediente::TIPO_HIBRIDO, 'label' => 'Híbrido'],
            ],
            'confidencialidad' => [
                ['value' => Expediente::CONFIDENCIALIDAD_PUBLICA, 'label' => 'Pública'],
                ['value' => Expediente::CONFIDENCIALIDAD_INTERNA, 'label' => 'Interna'],
                ['value' => Expediente::CONFIDENCIALIDAD_CONFIDENCIAL, 'label' => 'Confidencial'],
                ['value' => Expediente::CONFIDENCIALIDAD_RESERVADA, 'label' => 'Reservada'],
                ['value' => Expediente::CONFIDENCIALIDAD_CLASIFICADA, 'label' => 'Clasificada'],
            ],
            'areas_disponibles' => $this->getAreasDisponibles(),
        ];

        return Inertia::render('admin/expedientes/create', [
            'opciones' => $opciones
        ]);
    }

    /**
     * REQ-CL-019: Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'trd_id' => 'nullable|exists:tablas_retencion_documental,id',
            'tipo_expediente' => 'required|in:' . implode(',', [
                Expediente::TIPO_ELECTRONICO,
                Expediente::TIPO_FISICO,
                Expediente::TIPO_HIBRIDO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Expediente::CONFIDENCIALIDAD_PUBLICA,
                Expediente::CONFIDENCIALIDAD_INTERNA,
                Expediente::CONFIDENCIALIDAD_CONFIDENCIAL,
                Expediente::CONFIDENCIALIDAD_RESERVADA,
                Expediente::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'usuario_responsable_id' => 'required|exists:users,id',
            'area_responsable' => 'required|string|max:255',
            'volumen_maximo' => 'nullable|integer|min:1',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'ubicacion_digital' => 'nullable|string|max:255',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'acceso_publico' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            $expediente = new Expediente();
            $expediente->fill($request->all());
            $expediente->estado = Expediente::ESTADO_ABIERTO;
            $expediente->fecha_apertura = now();
            
            // Validar relación serie-subserie
            if ($request->subserie_id) {
                $subserie = SubserieDocumental::find($request->subserie_id);
                if ($subserie->serie_id !== $request->serie_id) {
                    return redirect()->back()
                                   ->withInput()
                                   ->withErrors(['subserie_id' => 'La subserie seleccionada no pertenece a la serie indicada.']);
                }
            }
            
            $expediente->save();
            
            DB::commit();
            
            return redirect()->route('admin.expedientes.index')
                           ->with('success', 'Expediente creado exitosamente.');
                           
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Error al crear expediente: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expediente $expediente)
    {
        $expediente->load([
            'serie',
            'subserie',
            'trd',
            'usuarioResponsable',
            'documentos.tipologia',
            'documentos.usuarioCreador'
        ]);

        // Obtener estadísticas del expediente
        $estadisticas = $expediente->getEstadisticas();
        
        // Verificar integridad
        $erroresIntegridad = $expediente->validarIntegridad();
        
        // Documentos recientes
        $documentosRecientes = $expediente->documentos()
                                        ->orderBy('created_at', 'desc')
                                        ->limit(10)
                                        ->get();

        return Inertia::render('admin/expedientes/show', [
            'expediente' => [
                ...$expediente->toArray(),
                'estadisticas' => $estadisticas,
                'errores_integridad' => $erroresIntegridad,
                'puede_editar' => $this->puedeEditar($expediente),
                'puede_cerrar' => $this->puedeCerrar($expediente),
                'puede_cambiar_estado' => $this->puedeCambiarEstado($expediente),
                'estados_disponibles' => $this->getEstadosDisponibles($expediente->estado),
            ],
            'documentos_recientes' => $documentosRecientes
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expediente $expediente)
    {
        $expediente->load(['serie', 'subserie', 'usuarioResponsable']);
        
        $opciones = [
            'series' => SerieDocumental::with('subseries')
                                      ->where('activo', true)
                                      ->select('id', 'codigo', 'nombre')
                                      ->get(),
            'subseries' => SubserieDocumental::where('activo', true)
                                             ->select('id', 'serie_id', 'codigo', 'nombre')
                                             ->get(),
            'trds' => TablaRetencionDocumental::where('activo', true)
                                             ->select('id', 'codigo', 'version', 'nombre')
                                             ->get(),
            'usuarios' => User::where('activo', true)
                             ->select('id', 'name', 'email')
                             ->get(),
            'tipos_expediente' => [
                ['value' => Expediente::TIPO_ELECTRONICO, 'label' => 'Electrónico'],
                ['value' => Expediente::TIPO_FISICO, 'label' => 'Físico'],
                ['value' => Expediente::TIPO_HIBRIDO, 'label' => 'Híbrido'],
            ],
            'confidencialidad' => [
                ['value' => Expediente::CONFIDENCIALIDAD_PUBLICA, 'label' => 'Pública'],
                ['value' => Expediente::CONFIDENCIALIDAD_INTERNA, 'label' => 'Interna'],
                ['value' => Expediente::CONFIDENCIALIDAD_CONFIDENCIAL, 'label' => 'Confidencial'],
                ['value' => Expediente::CONFIDENCIALIDAD_RESERVADA, 'label' => 'Reservada'],
                ['value' => Expediente::CONFIDENCIALIDAD_CLASIFICADA, 'label' => 'Clasificada'],
            ],
            'areas_disponibles' => $this->getAreasDisponibles(),
        ];

        return Inertia::render('admin/expedientes/edit', [
            'expediente' => $expediente,
            'opciones' => $opciones
        ]);
    }

    /**
     * REQ-CL-020: Update the specified resource in storage.
     */
    public function update(Request $request, Expediente $expediente)
    {
        if (!$this->puedeEditar($expediente)) {
            return redirect()->back()
                           ->withErrors(['error' => 'No tienes permisos para editar este expediente.']);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'subserie_id' => 'nullable|exists:subseries_documentales,id',
            'trd_id' => 'nullable|exists:tablas_retencion_documental,id',
            'tipo_expediente' => 'required|in:' . implode(',', [
                Expediente::TIPO_ELECTRONICO,
                Expediente::TIPO_FISICO,
                Expediente::TIPO_HIBRIDO
            ]),
            'confidencialidad' => 'required|in:' . implode(',', [
                Expediente::CONFIDENCIALIDAD_PUBLICA,
                Expediente::CONFIDENCIALIDAD_INTERNA,
                Expediente::CONFIDENCIALIDAD_CONFIDENCIAL,
                Expediente::CONFIDENCIALIDAD_RESERVADA,
                Expediente::CONFIDENCIALIDAD_CLASIFICADA
            ]),
            'usuario_responsable_id' => 'required|exists:users,id',
            'area_responsable' => 'required|string|max:255',
            'volumen_maximo' => 'nullable|integer|min:1',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'ubicacion_digital' => 'nullable|string|max:255',
            'palabras_clave' => 'nullable|array',
            'palabras_clave.*' => 'string|max:50',
            'acceso_publico' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        // Validar relación serie-subserie
        if ($request->subserie_id) {
            $subserie = SubserieDocumental::find($request->subserie_id);
            if ($subserie->serie_id !== $request->serie_id) {
                return redirect()->back()
                               ->withInput()
                               ->withErrors(['subserie_id' => 'La subserie seleccionada no pertenece a la serie indicada.']);
            }
        }

        $expediente->fill($request->all());
        $expediente->save();

        return redirect()->route('admin.expedientes.index')
                       ->with('success', 'Expediente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expediente $expediente)
    {
        if (!$this->puedeEliminar($expediente)) {
            return redirect()->back()
                           ->withErrors(['error' => 'No tienes permisos para eliminar este expediente.']);
        }

        $expediente->delete();

        return redirect()->route('admin.expedientes.index')
                       ->with('success', 'Expediente eliminado exitosamente.');
    }

    /**
     * REQ-CL-020: Cambiar estado del expediente
     */
    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        $request->validate([
            'estado' => 'required|in:' . implode(',', [
                Expediente::ESTADO_ABIERTO,
                Expediente::ESTADO_CERRADO,
                Expediente::ESTADO_TRANSFERIDO,
                Expediente::ESTADO_ARCHIVADO,
                Expediente::ESTADO_EN_DISPOSICION
            ]),
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            $expediente->cambiarEstado($request->estado, $request->observaciones);
            
            return response()->json([
                'success' => true,
                'mensaje' => 'Estado del expediente actualizado exitosamente.',
                'nuevo_estado' => $request->estado
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * REQ-CL-037: Exportar directorio del expediente
     */
    public function exportarDirectorio(Request $request, Expediente $expediente)
    {
        $formato = $request->get('formato', 'json');
        $incluirDocumentos = $request->boolean('incluir_documentos', true);
        
        try {
            $directorio = $expediente->exportarDirectorio($formato, $incluirDocumentos);
            
            $nombreArchivo = "directorio_expediente_{$expediente->codigo}";
            
            switch ($formato) {
                case 'xml':
                    return response($directorio)
                            ->header('Content-Type', 'application/xml')
                            ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}.xml\"");
                            
                case 'csv':
                    return response($directorio)
                            ->header('Content-Type', 'text/csv')
                            ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}.csv\"");
                            
                default:
                    return response($directorio)
                            ->header('Content-Type', 'application/json')
                            ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}.json\"");
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al exportar directorio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar integridad del expediente
     */
    public function verificarIntegridad(Expediente $expediente)
    {
        $errores = $expediente->validarIntegridad();
        
        return response()->json([
            'success' => count($errores) === 0,
            'errores' => $errores,
            'mensaje' => count($errores) === 0 ? 
                        'Expediente íntegro' : 
                        'Se encontraron ' . count($errores) . ' errores de integridad'
        ]);
    }

    /**
     * Dashboard de expedientes
     */
    public function dashboard()
    {
        $estadisticas = [
            'total_expedientes' => Expediente::count(),
            'expedientes_abiertos' => Expediente::abiertos()->count(),
            'expedientes_cerrados' => Expediente::cerrados()->count(),
            'proximos_vencer_7d' => Expediente::proximosVencer(7)->count(),
            'proximos_vencer_30d' => Expediente::proximosVencer(30)->count(),
            'vencidos' => Expediente::vencidos()->count(),
            'por_tipo' => [
                'electronicos' => Expediente::porTipo(Expediente::TIPO_ELECTRONICO)->count(),
                'fisicos' => Expediente::porTipo(Expediente::TIPO_FISICO)->count(),
                'hibridos' => Expediente::porTipo(Expediente::TIPO_HIBRIDO)->count(),
            ],
            'volumen_total_mb' => round(Expediente::sum('volumen_actual') / 1024 / 1024, 2),
            'promedio_documentos_por_expediente' => Expediente::withCount('documentos')
                                                             ->get()
                                                             ->avg('documentos_count'),
        ];

        // Expedientes recientes
        $expedientesRecientes = Expediente::with(['serie', 'usuarioResponsable'])
                                         ->orderBy('created_at', 'desc')
                                         ->limit(10)
                                         ->get();

        // Expedientes próximos a vencer
        $proximosVencer = Expediente::with(['serie', 'usuarioResponsable'])
                                   ->proximosVencer(30)
                                   ->orderBy('fecha_vencimiento_disposicion')
                                   ->limit(10)
                                   ->get();

        return Inertia::render('admin/expedientes/dashboard', [
            'estadisticas' => $estadisticas,
            'expedientes_recientes' => $expedientesRecientes,
            'proximos_vencer' => $proximosVencer
        ]);
    }

    /**
     * Métodos auxiliares
     */
    
    /**
     * Verificar permisos de edición
     */
    private function puedeEditar(Expediente $expediente)
    {
        $user = auth()->user();
        return $user->isAdmin() || 
               $expediente->usuario_responsable_id === $user->id ||
               $user->hasPermission('expedientes.editar');
    }

    /**
     * Verificar si se puede cerrar el expediente
     */
    private function puedeCerrar(Expediente $expediente)
    {
        return $expediente->estado === Expediente::ESTADO_ABIERTO && 
               $this->puedeEditar($expediente);
    }

    /**
     * Verificar si se puede cambiar el estado
     */
    private function puedeCambiarEstado(Expediente $expediente)
    {
        $user = auth()->user();
        return $user->isAdmin() || 
               $expediente->usuario_responsable_id === $user->id ||
               $user->hasPermission('expedientes.cambiar_estado');
    }

    /**
     * Verificar permisos de eliminación
     */
    private function puedeEliminar(Expediente $expediente)
    {
        $user = auth()->user();
        
        // No se puede eliminar si tiene documentos
        if ($expediente->documentos()->count() > 0) {
            return false;
        }
        
        return $user->isAdmin() || 
               ($expediente->usuario_responsable_id === $user->id && $expediente->estado === Expediente::ESTADO_ABIERTO) ||
               $user->hasPermission('expedientes.eliminar');
    }

    /**
     * Obtener estados disponibles según el estado actual
     */
    private function getEstadosDisponibles($estadoActual)
    {
        $transicionesValidas = [
            Expediente::ESTADO_ABIERTO => [
                Expediente::ESTADO_CERRADO,
                Expediente::ESTADO_ARCHIVADO
            ],
            Expediente::ESTADO_CERRADO => [
                Expediente::ESTADO_TRANSFERIDO,
                Expediente::ESTADO_ARCHIVADO,
                Expediente::ESTADO_EN_DISPOSICION
            ],
            Expediente::ESTADO_TRANSFERIDO => [
                Expediente::ESTADO_ARCHIVADO,
                Expediente::ESTADO_EN_DISPOSICION
            ],
            Expediente::ESTADO_ARCHIVADO => [
                Expediente::ESTADO_EN_DISPOSICION
            ],
            Expediente::ESTADO_EN_DISPOSICION => []
        ];

        return $transicionesValidas[$estadoActual] ?? [];
    }

    /**
     * Obtener áreas disponibles
     */
    private function getAreasDisponibles()
    {
        return Expediente::select('area_responsable')
                        ->distinct()
                        ->whereNotNull('area_responsable')
                        ->pluck('area_responsable')
                        ->map(function($area) {
                            return ['value' => $area, 'label' => $area];
                        });
    }
}
