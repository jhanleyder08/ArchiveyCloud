<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubserieDocumental;
use App\Models\SerieDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminSubseriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Parámetros de filtrado
            $search = $request->get('search');
            $serieId = $request->get('serie_id');
            $estado = $request->get('estado');
            $area = $request->get('area');

            // Query base con relaciones
            $query = SubserieDocumental::with(['serie.trd'])
                ->select('subseries_documentales.*');

            // Aplicar filtros
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($serieId) {
                $query->where('serie_id', $serieId);
            }

            if ($estado) {
                if ($estado === 'activa') {
                    $query->where('activa', true);
                } elseif ($estado === 'inactiva') {
                    $query->where('activa', false);
                }
            }

            if ($area) {
                $query->where('area_responsable', 'like', "%{$area}%");
            }

            // Ordenamiento
            $query->orderBy('created_at', 'desc');

            // Paginación
            $subseries = $query->paginate(15)->withQueryString();

            // Estadísticas
            $stats = [
                'total' => SubserieDocumental::count(),
                'activas' => SubserieDocumental::where('activa', true)->count(),
                'inactivas' => SubserieDocumental::where('activa', false)->count(),
                'con_expedientes' => SubserieDocumental::whereHas('expedientes')->count(),
            ];

            // Obtener series para el filtro (sin filtro activa por ahora)
            $series = SerieDocumental::with('trd')
                ->orderBy('codigo')
                ->get();

            // Áreas únicas - comentado temporalmente hasta que se agregue la columna
            $areas = collect();

            return Inertia::render('admin/subseries/index', [
                'data' => $subseries,
                'series' => $series,
                'areas' => $areas,
                'stats' => $stats,
                'filters' => [
                    'search' => $search,
                    'serie_id' => $serieId,
                    'estado' => $estado,
                    'area' => $area,
                ],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar las subseries documentales: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $series = SerieDocumental::with('trd')
            ->where('activa', true)
            ->orderBy('codigo')
            ->get();

        return Inertia::render('admin/subseries/create', [
            'series' => $series,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'nullable|string|max:50|unique:subseries_documentales,codigo,NULL,id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'tiempo_archivo_gestion' => 'nullable|integer|min:0',
            'tiempo_archivo_central' => 'nullable|integer|min:0',
            'disposicion_final' => 'nullable|string|in:conservacion_total,eliminacion,seleccion,transferencia,migracion',
            'area_responsable' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'activa' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();

            // Generar código automático si no se proporciona
            if (empty($data['codigo'])) {
                $serie = SerieDocumental::find($data['serie_id']);
                $lastSubserie = SubserieDocumental::where('serie_id', $data['serie_id'])
                    ->whereRaw("codigo REGEXP '^{$serie->codigo}-[0-9]+$'")
                    ->orderByRaw('CAST(SUBSTRING(codigo, LENGTH("' . $serie->codigo . '") + 2) AS UNSIGNED) DESC')
                    ->first();

                if ($lastSubserie) {
                    $lastNumber = (int) str_replace($serie->codigo . '-', '', $lastSubserie->codigo);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $data['codigo'] = $serie->codigo . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }

            // Heredar datos de la serie si no se proporcionan
            $serie = SerieDocumental::find($data['serie_id']);
            if (!isset($data['tiempo_archivo_gestion'])) {
                $data['tiempo_archivo_gestion'] = $serie->tiempo_archivo_gestion;
            }
            if (!isset($data['tiempo_archivo_central'])) {
                $data['tiempo_archivo_central'] = $serie->tiempo_archivo_central;
            }
            if (!isset($data['disposicion_final'])) {
                $data['disposicion_final'] = $serie->disposicion_final;
            }
            if (!isset($data['area_responsable'])) {
                $data['area_responsable'] = $serie->area_responsable;
            }

            $subserie = SubserieDocumental::create($data);

            DB::commit();

            return redirect()->route('admin.subseries.index')
                ->with('message', 'Subserie documental creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al crear la subserie documental: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubserieDocumental $subserie)
    {
        $subserie->load(['serie.trd', 'expedientes']);

        return Inertia::render('admin/subseries/show', [
            'subserie' => $subserie,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubserieDocumental $subserie)
    {
        $subserie->load(['serie.trd']);

        $series = SerieDocumental::with('trd')
            ->where('activa', true)
            ->orderBy('codigo')
            ->get();

        return Inertia::render('admin/subseries/edit', [
            'subserie' => $subserie,
            'series' => $series,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubserieDocumental $subserie)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:50|unique:subseries_documentales,codigo,' . $subserie->id . ',id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'tiempo_archivo_gestion' => 'nullable|integer|min:0',
            'tiempo_archivo_central' => 'nullable|integer|min:0',
            'disposicion_final' => 'nullable|string|in:conservacion_total,eliminacion,seleccion,transferencia,migracion',
            'area_responsable' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'activa' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $subserie->update($data);

            DB::commit();

            return redirect()->route('admin.subseries.index')
                ->with('message', 'Subserie documental actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al actualizar la subserie documental: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubserieDocumental $subserie)
    {
        try {
            // Verificar si tiene expedientes asociados
            if ($subserie->expedientes()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar la subserie documental porque tiene expedientes asociados.');
            }

            DB::beginTransaction();

            $subserie->delete();

            DB::commit();

            return redirect()->route('admin.subseries.index')
                ->with('message', 'Subserie documental eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar la subserie documental: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate the specified resource.
     */
    public function duplicate(SubserieDocumental $subserie)
    {
        try {
            DB::beginTransaction();

            // Obtener datos de la subserie original
            $originalData = $subserie->toArray();
            unset($originalData['id'], $originalData['created_at'], $originalData['updated_at'], $originalData['deleted_at']);

            // Generar nuevo código único
            $serie = $subserie->serie;
            $baseCode = $serie->codigo;
            $counter = 1;
            
            do {
                $newCode = $baseCode . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
                $exists = SubserieDocumental::where('codigo', $newCode)->exists();
                $counter++;
            } while ($exists);

            $originalData['codigo'] = $newCode;
            $originalData['nombre'] = $originalData['nombre'] . ' (Copia)';
            $originalData['activa'] = false; // Las copias inician inactivas

            $newSubserie = SubserieDocumental::create($originalData);

            DB::commit();

            return redirect()->route('admin.subseries.index')
                ->with('message', 'Subserie documental duplicada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al duplicar la subserie documental: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of the specified resource.
     */
    public function toggleActive(SubserieDocumental $subserie)
    {
        try {
            DB::beginTransaction();

            $subserie->activa = !$subserie->activa;
            $subserie->save();

            DB::commit();

            $status = $subserie->activa ? 'activada' : 'desactivada';
            return redirect()->back()
                ->with('message', "Subserie documental {$status} exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al cambiar el estado de la subserie documental: ' . $e->getMessage());
        }
    }

    /**
     * Export subseries data.
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'json');
            
            $subseries = SubserieDocumental::with(['serie.trd'])
                ->orderBy('codigo')
                ->get();

            switch ($format) {
                case 'csv':
                    return $this->exportCsv($subseries);
                case 'xml':
                    return $this->exportXml($subseries);
                default:
                    return $this->exportJson($subseries);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al exportar las subseries documentales: ' . $e->getMessage());
        }
    }

    private function exportJson($subseries)
    {
        $data = $subseries->map(function ($subserie) {
            return [
                'codigo' => $subserie->codigo,
                'nombre' => $subserie->nombre,
                'descripcion' => $subserie->descripcion,
                'serie_codigo' => $subserie->serie->codigo,
                'serie_nombre' => $subserie->serie->nombre,
                'trd_codigo' => $subserie->serie->trd->codigo,
                'tiempo_archivo_gestion' => $subserie->tiempo_archivo_gestion,
                'tiempo_archivo_central' => $subserie->tiempo_archivo_central,
                'disposicion_final' => $subserie->disposicion_final,
                'area_responsable' => $subserie->area_responsable,
                'activa' => $subserie->activa,
                'created_at' => $subserie->created_at,
                'updated_at' => $subserie->updated_at,
            ];
        });

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="subseries_documentales.json"');
    }

    private function exportCsv($subseries)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subseries_documentales.csv"',
        ];

        $callback = function () use ($subseries) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'Código', 'Nombre', 'Descripción', 'Serie Código', 'Serie Nombre', 
                'TRD Código', 'Tiempo AG', 'Tiempo AC', 'Disposición Final',
                'Área Responsable', 'Activa', 'Fecha Creación'
            ]);

            foreach ($subseries as $subserie) {
                fputcsv($file, [
                    $subserie->codigo,
                    $subserie->nombre,
                    $subserie->descripcion,
                    $subserie->serie->codigo,
                    $subserie->serie->nombre,
                    $subserie->serie->trd->codigo,
                    $subserie->tiempo_archivo_gestion,
                    $subserie->tiempo_archivo_central,
                    $subserie->disposicion_final,
                    $subserie->area_responsable,
                    $subserie->activa ? 'Sí' : 'No',
                    $subserie->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXml($subseries)
    {
        $xml = new \SimpleXMLElement('<subseries_documentales/>');
        
        foreach ($subseries as $subserie) {
            $subserieNode = $xml->addChild('subserie');
            $subserieNode->addChild('codigo', htmlspecialchars($subserie->codigo));
            $subserieNode->addChild('nombre', htmlspecialchars($subserie->nombre));
            $subserieNode->addChild('descripcion', htmlspecialchars($subserie->descripcion));
            $subserieNode->addChild('serie_codigo', htmlspecialchars($subserie->serie->codigo));
            $subserieNode->addChild('serie_nombre', htmlspecialchars($subserie->serie->nombre));
            $subserieNode->addChild('trd_codigo', htmlspecialchars($subserie->serie->trd->codigo));
            $subserieNode->addChild('tiempo_archivo_gestion', $subserie->tiempo_archivo_gestion);
            $subserieNode->addChild('tiempo_archivo_central', $subserie->tiempo_archivo_central);
            $subserieNode->addChild('disposicion_final', htmlspecialchars($subserie->disposicion_final));
            $subserieNode->addChild('area_responsable', htmlspecialchars($subserie->area_responsable));
            $subserieNode->addChild('activa', $subserie->activa ? 'true' : 'false');
            $subserieNode->addChild('created_at', $subserie->created_at->toISOString());
        }

        return response($xml->asXML())
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="subseries_documentales.xml"');
    }
}
