<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerieDocumental;
use App\Models\TablaRetencionDocumental;
use App\Models\CuadroClasificacionDocumental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminSeriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SerieDocumental::with(['tablaRetencion', 'usuarioResponsable']);

        // Filtros de búsqueda
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por TRD - usando nombre de columna correcto y manejando "all"
        if ($request->filled('tablaRetencion') && $request->get('tablaRetencion') !== 'all') {
            $query->where('tabla_retencion_id', $request->get('tablaRetencion'));
        }

        // Filtro por estado - manejando "all"
        if ($request->filled('estado') && $request->get('estado') !== 'all') {
            $estado = $request->get('estado');
            if ($estado === 'activa') {
                $query->where('activa', true);
            } elseif ($estado === 'inactiva') {
                $query->where('activa', false);
            }
        }

        // Filtro por área - deshabilitado hasta que se agregue la columna
        // Nota: No se aplica ningún filtro de área por ahora

        // Paginación
        $series = $query->orderBy('codigo')
                       ->paginate(15)
                       ->withQueryString();

        // Estadísticas
        $stats = [
            'total' => SerieDocumental::count(),
            'activas' => SerieDocumental::where('activa', true)->count(),
            'inactivas' => SerieDocumental::where('activa', false)->count(),
            'con_subseries' => SerieDocumental::has('subseries')->count(),
            'con_expedientes' => SerieDocumental::has('expedientes')->count(),
        ];

        // Datos adicionales para filtros
        $trds = TablaRetencionDocumental::whereIn('estado', ['aprobada', 'vigente'])
                                       ->orderBy('nombre')
                                       ->get(['id', 'nombre', 'codigo']);

        // Áreas responsables - comentado temporalmente hasta que se agregue la columna
        $areas = collect();

        return Inertia::render('admin/series/index', [
            'data' => $series,
            'stats' => $stats,
            'trds' => $trds,
            'areas' => $areas,
            'filters' => $request->only(['search', 'tablaRetencion', 'estado', 'area']),
            'flash' => session()->only(['success', 'error'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('STORE SERIE - Datos recibidos:', $request->all());
        
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:series_documentales,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'tabla_retencion_id' => 'required|exists:tablas_retencion_documental,id',
            'cuadro_clasificacion_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'tiempo_archivo_gestion' => 'required|integer|min:0',
            'tiempo_archivo_central' => 'required|integer|min:0',
            'disposicion_final' => 'required|in:conservacion_permanente,eliminacion,seleccion,microfilmacion',
            'procedimiento' => 'nullable|string',
            'area_responsable' => 'nullable|string|max:255',
            'usuario_responsable_id' => 'nullable|exists:users,id',
            'palabras_clave' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'activa' => 'boolean'
        ]);

        // Agregar campos de auditoría
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        try {
            \Log::info('STORE SERIE - Datos validados:', $validated);
            
            $serie = SerieDocumental::create($validated);
            
            \Log::info('STORE SERIE - Serie creada:', $serie->toArray());

            return redirect()->route('admin.series.index')
                           ->with('success', "Serie documental '{$serie->nombre}' creada exitosamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error al crear la serie documental: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SerieDocumental $serie)
    {
        $serie->load([
            'tablaRetencion',
            'cuadroClasificacion',
            'usuarioResponsable',
            'subseries' => function ($query) {
                $query->where('activa', true)->orderBy('codigo');
            },
            'expedientes' => function ($query) {
                $query->orderBy('codigo')->limit(10);
            }
        ]);

        $estadisticas = $serie->getEstadisticas();

        return Inertia::render('admin/series/show', [
            'serie' => $serie,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SerieDocumental $series)
    {
        \Log::info('UPDATE SERIE - ID: ' . ($series->id ?? 'NULL'));
        \Log::info('UPDATE SERIE - Datos recibidos:', $request->all());
        \Log::info('UPDATE SERIE - Codigo actual: ' . ($series->codigo ?? 'NULL'));
        \Log::info('UPDATE SERIE - Serie completa:', $series->toArray());
        
        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:50', Rule::unique('series_documentales', 'codigo')->ignore($series->id)],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'tabla_retencion_id' => 'required|exists:tablas_retencion_documental,id',
            'cuadro_clasificacion_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'tiempo_archivo_gestion' => 'required|integer|min:0',
            'tiempo_archivo_central' => 'required|integer|min:0',
            'disposicion_final' => 'required|in:conservacion_permanente,eliminacion,seleccion,microfilmacion',
            'procedimiento' => 'nullable|string',
            'area_responsable' => 'nullable|string|max:255',
            'usuario_responsable_id' => 'nullable|exists:users,id',
            'palabras_clave' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'activa' => 'boolean'
        ]);

        try {
            \Log::info('UPDATE SERIE - Datos validados:', $validated);
            
            $result = $series->update($validated);
            
            \Log::info('UPDATE SERIE - Resultado update: ' . ($result ? 'true' : 'false'));

            return redirect()->route('admin.series.index')
                           ->with('success', "Serie documental '{$series->nombre}' actualizada exitosamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error al actualizar la serie documental: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SerieDocumental $serie)
    {
        try {
            // Verificar si puede ser eliminada
            if (!$serie->puedeSerEliminada()) {
                return redirect()->back()
                               ->with('error', 'No se puede eliminar la serie porque tiene expedientes o subseries asociadas.');
            }

            $nombreSerie = $serie->nombre;
            $serie->delete();

            return redirect()->route('admin.series.index')
                           ->with('success', "Serie documental '{$nombreSerie}' eliminada exitosamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error al eliminar la serie documental: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a series documental
     */
    public function duplicate(SerieDocumental $serie)
    {
        try {
            $nuevaSerie = $serie->replicate();
            
            // Generar nuevo código único
            $codigoBase = $serie->codigo . '-COPY';
            $codigoUnico = $codigoBase;
            $contador = 1;
            
            while (SerieDocumental::where('codigo', $codigoUnico)->exists()) {
                $codigoUnico = $codigoBase . '-' . $contador;
                $contador++;
            }
            
            $nuevaSerie->codigo = $codigoUnico;
            $nuevaSerie->nombre = $serie->nombre . ' (Copia)';
            $nuevaSerie->activa = false; // Inicia como inactiva
            $nuevaSerie->save();

            return redirect()->route('admin.series.index')
                           ->with('success', "Serie documental duplicada exitosamente con código: {$codigoUnico}");

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error al duplicar la serie documental: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(SerieDocumental $serie)
    {
        try {
            $serie->update(['activa' => !$serie->activa]);
            
            $estado = $serie->activa ? 'activada' : 'desactivada';
            return redirect()->back()
                           ->with('success', "Serie documental {$estado} exitosamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error al cambiar el estado de la serie: ' . $e->getMessage());
        }
    }

    /**
     * Export series list
     */
    public function export(Request $request)
    {
        $formato = $request->get('formato', 'json');
        
        $query = SerieDocumental::with(['tablaRetencion', 'usuarioResponsable']);
        
        // Aplicar mismo filtros que en index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tablaRetencion')) {
            $query->where('tabla_retencion_id', $request->get('tablaRetencion'));
        }

        if ($request->filled('estado')) {
            $estado = $request->get('estado');
            if ($estado === 'activa') {
                $query->where('activa', true);
            } elseif ($estado === 'inactiva') {
                $query->where('activa', false);
            }
        }

        $series = $query->orderBy('codigo')->get();

        switch ($formato) {
            case 'csv':
                return $this->exportCSV($series);
            case 'xml':
                return $this->exportXML($series);
            default:
                return $this->exportJSON($series);
        }
    }

    private function exportJSON($series)
    {
        $data = $series->map(function ($serie) {
            return [
                'codigo' => $serie->codigo,
                'nombre' => $serie->nombre,
                'descripcion' => $serie->descripcion,
                'tablaRetencion' => $serie->tablaRetencion->nombre ?? null,
                'tiempo_archivo_gestion' => $serie->tiempo_archivo_gestion,
                'tiempo_archivo_central' => $serie->tiempo_archivo_central,
                'disposicion_final' => $serie->disposicion_final,
                'area_responsable' => $serie->area_responsable,
                'activa' => $serie->activa,
                'fecha_creacion' => $serie->created_at->format('Y-m-d'),
            ];
        });

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="series_documentales_' . now()->format('Y-m-d') . '.json"'
        ]);
    }

    private function exportCSV($series)
    {
        $filename = 'series_documentales_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($series) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'Código', 'Nombre', 'Descripción', 'TRD', 'T. Archivo Gestión', 
                'T. Archivo Central', 'Disposición Final', 'Área Responsable', 
                'Estado', 'Fecha Creación'
            ]);

            // Data
            foreach ($series as $serie) {
                fputcsv($file, [
                    $serie->codigo,
                    $serie->nombre,
                    $serie->descripcion,
                    $serie->tablaRetencion->nombre ?? '',
                    $serie->tiempo_archivo_gestion,
                    $serie->tiempo_archivo_central,
                    $serie->disposicion_final,
                    $serie->area_responsable,
                    $serie->activa ? 'Activa' : 'Inactiva',
                    $serie->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXML($series)
    {
        $xml = new \SimpleXMLElement('<series_documentales/>');
        
        foreach ($series as $serie) {
            $serieXml = $xml->addChild('serie');
            $serieXml->addChild('codigo', htmlspecialchars($serie->codigo));
            $serieXml->addChild('nombre', htmlspecialchars($serie->nombre));
            $serieXml->addChild('descripcion', htmlspecialchars($serie->descripcion));
            $serieXml->addChild('tablaRetencion', htmlspecialchars($serie->tablaRetencion->nombre ?? ''));
            $serieXml->addChild('tiempo_archivo_gestion', $serie->tiempo_archivo_gestion);
            $serieXml->addChild('tiempo_archivo_central', $serie->tiempo_archivo_central);
            $serieXml->addChild('disposicion_final', $serie->disposicion_final);
            $serieXml->addChild('area_responsable', htmlspecialchars($serie->area_responsable));
            $serieXml->addChild('activa', $serie->activa ? 'true' : 'false');
            $serieXml->addChild('fecha_creacion', $serie->created_at->format('Y-m-d'));
        }

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="series_documentales_' . now()->format('Y-m-d') . '.xml"'
        ]);
    }
}
