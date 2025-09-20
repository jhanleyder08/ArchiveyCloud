<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\CuadroClasificacionDocumental;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminExpedienteController extends Controller
{
    public function index(Request $request)
    {
        // Logging removido - funcionando correctamente
        
        try {
            // Consulta con campos reales de la migración
            $query = Expediente::query();

            // Filtros según campos reales
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_expediente', 'like', "%{$search}%")
                      ->orWhere('titulo', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($request->filled('estado')) {
                $query->where('estado_ciclo_vida', $request->estado);
            }

            if ($request->filled('tipo_expediente')) {
                $query->where('tipo_expediente', $request->tipo_expediente);
            }

            if ($request->filled('serie_id')) {
                $query->where('serie_documental_id', $request->serie_id);
            }

            $expedientes = $query->orderBy('created_at', 'desc')->paginate(20);

            // Estadísticas con campos reales
            $estadisticas = [
                'total' => Expediente::count(),
                'tramite' => Expediente::where('estado_ciclo_vida', 'tramite')->count(),
                'gestion' => Expediente::where('estado_ciclo_vida', 'gestion')->count(),
                'central' => Expediente::where('estado_ciclo_vida', 'central')->count(),
                'historico' => Expediente::where('estado_ciclo_vida', 'historico')->count(),
                'electronicos' => Expediente::where('tipo_expediente', 'electronico')->count(),
                'fisicos' => Expediente::where('tipo_expediente', 'fisico')->count(),
                'hibridos' => Expediente::where('tipo_expediente', 'hibrido')->count(),
                'tamaño_total' => (float) (Expediente::sum('tamaño_mb') ?? 0),
            ];

            // Opciones para filtros
            $opciones = [
                'estados' => [
                    ['value' => 'tramite', 'label' => 'En Trámite'],
                    ['value' => 'gestion', 'label' => 'Archivo de Gestión'],
                    ['value' => 'central', 'label' => 'Archivo Central'],
                    ['value' => 'historico', 'label' => 'Archivo Histórico'],
                    ['value' => 'eliminado', 'label' => 'Eliminado'],
                ],
                'tipos' => [
                    ['value' => 'electronico', 'label' => 'Electrónico'],
                    ['value' => 'fisico', 'label' => 'Físico'],
                    ['value' => 'hibrido', 'label' => 'Híbrido'],
                ],
                'series_disponibles' => \DB::table('series_documentales')->where('activa', true)->get(['id', 'codigo', 'nombre']),
            ];

            return Inertia::render('admin/expedientes/index-simple', [
                'expedientes' => $expedientes,
                'estadisticas' => $estadisticas,
                'opciones' => $opciones,
                'filtros' => $request->only(['search', 'estado', 'tipo_expediente', 'serie_id'])
            ]);

        } catch (\Exception $e) {
            \Log::error('=== ERROR EN CONTROLADOR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $series = SerieDocumental::activas()->get();
        
        $subseries = SubserieDocumental::activas()->get();
            
        $ccdOptions = CuadroClasificacionDocumental::where('activo', true)
            ->get(['id', 'codigo', 'nombre']);

        return Inertia::render('admin/expedientes/create', [
            'series' => $series,
            'subseries' => $subseries,
            'ccdOptions' => $ccdOptions,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'codigo' => 'required|string|max:50|unique:expedientes,codigo',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'serie_id' => 'required|exists:series_documentales,id',
                'subserie_id' => 'nullable|exists:subseries_documentales,id',
                'tipo_expediente' => 'required|in:electronico,fisico,hibrido',
                'confidencialidad' => 'required|in:publico,restringido,confidencial,secreto',
                'area_responsable' => 'required|string|max:255',
                'ubicacion_fisica' => 'nullable|string|max:255',
                'palabras_clave' => 'nullable|array',
                'documentos_electronicos' => 'boolean',
                'firma_digital' => 'boolean',
                'control_versiones' => 'boolean',
                'notificaciones' => 'boolean',
            ]);

            $expediente = Expediente::create([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'serie_id' => $request->serie_id,
                'subserie_id' => $request->subserie_id,
                'tipo_expediente' => $request->tipo_expediente,
                'confidencialidad' => $request->confidencialidad,
                'area_responsable' => $request->area_responsable,
                'ubicacion_fisica' => $request->ubicacion_fisica,
                'palabras_clave' => $request->palabras_clave,
                'estado' => 'abierto',
                'fecha_apertura' => now(),
                'usuario_responsable_id' => auth()->id(),
                'volumen_actual' => 1,
                'volumen_maximo' => 1,
                'numero_folios' => 0,
                'documentos_electronicos' => $request->boolean('documentos_electronicos', false),
                'firma_digital' => $request->boolean('firma_digital', false),
                'control_versiones' => $request->boolean('control_versiones', false),
                'notificaciones' => $request->boolean('notificaciones', true),
            ]);

            return redirect()->route('admin.expedientes.index')->with('success', 'Expediente creado exitosamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Expediente $expediente)
    {
        $expediente->load([
            'serie',
            'subserie',
            'usuarioResponsable'
        ]);

        $estadisticas = [
            'documentos_count' => 0,
            'documentos_electronicos' => 0,
            'documentos_fisicos' => 0,
            'tamaño_total' => 0,
            'ultimo_movimiento' => null,
        ];

        return Inertia::render('admin/expedientes/show', [
            'expediente' => $expediente,
            'estadisticas' => $estadisticas,
        ]);
    }

    public function edit(Expediente $expediente)
    {
        $expediente->load(['serie', 'subserie']);
        
        $series = SerieDocumental::activas()->get();
        
        $subseries = SubserieDocumental::activas()->get();

        return Inertia::render('admin/expedientes/edit', [
            'expediente' => $expediente,
            'series' => $series,
            'subseries' => $subseries,
        ]);
    }

    public function update(Request $request, Expediente $expediente)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'serie_documental_id' => 'required|exists:series_documentales,id',
                'subserie_documental_id' => 'nullable|exists:subseries_documentales,id',
                'tipo_expediente' => 'required|in:electronico,fisico,hibrido',
                'confidencialidad' => 'required|in:publico,restringido,confidencial,secreto',
                'area_responsable' => 'required|string|max:255',
                'ubicacion_fisica' => 'nullable|string|max:255',
                'palabras_clave' => 'nullable|array',
                'documentos_electronicos' => 'boolean',
                'firma_digital' => 'boolean',
                'control_versiones' => 'boolean',
                'notificaciones' => 'boolean',
            ]);

            $expediente->update($request->only([
                'nombre', 'descripcion', 'serie_documental_id', 'subserie_documental_id',
                'tipo_expediente', 'confidencialidad', 'area_responsable', 'ubicacion_fisica',
                'palabras_clave', 'documentos_electronicos', 'firma_digital', 'control_versiones',
                'notificaciones'
            ]));

            return redirect()->route('admin.expedientes.index')->with('success', 'Expediente actualizado exitosamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Expediente $expediente)
    {
        try {
            // Verificar si tiene documentos asociados
            if ($expediente->documentos()->exists()) {
                return redirect()->back()->with('error', 'No se puede eliminar un expediente que tiene documentos asociados.');
            }

            $expediente->delete();
            return redirect()->route('admin.expedientes.index')->with('success', 'Expediente eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function cambiarEstado(Request $request, Expediente $expediente)
    {
        try {
            $request->validate([
                'nuevo_estado' => 'required|in:abierto,tramite,revision,cerrado,archivado',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $estadoAnterior = $expediente->estado;
            $expediente->update([
                'estado' => $request->nuevo_estado,
                'fecha_cierre' => $request->nuevo_estado === 'cerrado' ? now() : null,
            ]);

            // Registrar en auditoría
            if (class_exists('App\Models\PistaAuditoria')) {
                \App\Models\PistaAuditoria::registrar($expediente, 'cambio_estado', [
                    'descripcion' => "Estado cambiado de {$estadoAnterior} a {$request->nuevo_estado}",
                    'observaciones' => $request->observaciones,
                ]);
            }

            return redirect()->back()->with('success', 'Estado del expediente actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function dashboard(Request $request)
    {
        $estadisticas = [
            'total_expedientes' => Expediente::count(),
            'expedientes_abiertos' => Expediente::where('estado', 'abierto')->count(),
            'expedientes_cerrados' => Expediente::where('estado', 'cerrado')->count(),
            'expedientes_por_mes' => Expediente::selectRaw('MONTH(fecha_apertura) as mes, COUNT(*) as total')
                ->whereYear('fecha_apertura', now()->year)
                ->groupBy('mes')
                ->orderBy('mes')
                ->get(),
        ];

        return Inertia::render('admin/expedientes/dashboard', [
            'estadisticas' => $estadisticas,
        ]);
    }

    public function exportarDirectorio(Expediente $expediente)
    {
        try {
            $expediente->load(['documentos', 'serieDocumental', 'subserieDocumental']);
            
            $directorio = [
                'expediente' => $expediente->only([
                    'codigo', 'nombre', 'descripcion', 'estado', 'fecha_apertura', 'fecha_cierre'
                ]),
                'serie' => $expediente->serieDocumental?->only(['codigo', 'nombre']),
                'subserie' => $expediente->subserieDocumental?->only(['codigo', 'nombre']),
                'documentos' => $expediente->documentos->map(function($doc) {
                    return $doc->only(['codigo', 'nombre', 'tipo_documento', 'fecha_creacion', 'tamaño']);
                }),
                'fecha_exportacion' => now()->toISOString(),
            ];

            return response()->json($directorio)
                ->header('Content-Disposition', 'attachment; filename="directorio_expediente_'.$expediente->codigo.'.json"');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function verificarIntegridad(Expediente $expediente)
    {
        try {
            $errores = [];
            $expediente->load(['documentos', 'serieDocumental']);

            // Verificar integridad básica
            if (!$expediente->serieDocumental) {
                $errores[] = 'El expediente no tiene serie documental asignada';
            }

            if ($expediente->documentos->isEmpty()) {
                $errores[] = 'El expediente no tiene documentos asociados';
            }

            // Verificar documentos
            foreach ($expediente->documentos as $documento) {
                if (!file_exists(storage_path('app/public/' . $documento->ruta_archivo))) {
                    $errores[] = "Archivo físico no encontrado: {$documento->nombre}";
                }
            }

            $resultado = [
                'integro' => empty($errores),
                'errores' => $errores,
                'verificado_en' => now()->toISOString(),
                'total_documentos' => $expediente->documentos->count(),
            ];

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
