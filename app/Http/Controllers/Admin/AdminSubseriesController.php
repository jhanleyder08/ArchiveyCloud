<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubserieDocumental;
use App\Models\SerieDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            $query = SubserieDocumental::with(['serie.tablaRetencion'])
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
                $query->where('serie_documental_id', $serieId);
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
            $series = SerieDocumental::with('tablaRetencion')
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
        $series = SerieDocumental::with('tablaRetencion')
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
        \Log::info('STORE SUBSERIE - Datos recibidos:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'codigo' => 'nullable|string|max:50|unique:subseries_documentales,codigo,NULL,id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'tiempo_archivo_gestion' => 'nullable|integer|min:0',
            'tiempo_archivo_central' => 'nullable|integer|min:0',
            'disposicion_final' => 'nullable|string|in:conservacion_permanente,eliminacion,seleccion,microfilmacion',
            'procedimiento' => 'nullable|string',
            'metadatos_especificos' => 'nullable|array',
            'tipologias_documentales' => 'nullable|array',
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

            $validated = $validator->validated();
            \Log::info('STORE SUBSERIE - Datos validados:', $validated);

            // Mapear campos del frontend a los de la base de datos
            $data = [
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'serie_documental_id' => $validated['serie_id'], // Mapeo correcto
                'tiempo_archivo_gestion' => $validated['tiempo_archivo_gestion'],
                'tiempo_archivo_central' => $validated['tiempo_archivo_central'],
                'disposicion_final' => $validated['disposicion_final'],
                'procedimiento' => $validated['procedimiento'] ?? null,
                'metadatos_especificos' => $validated['metadatos_especificos'] ?? null,
                'tipologias_documentales' => $validated['tipologias_documentales'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'activa' => $validated['activa'] ?? true,
            ];

            // Generar código automático si no se proporciona
            if (empty($data['codigo'])) {
                $serie = SerieDocumental::find($data['serie_documental_id']);
                $lastSubserie = SubserieDocumental::where('serie_documental_id', $data['serie_documental_id'])
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
            $serie = SerieDocumental::find($data['serie_documental_id']);
            if (empty($data['tiempo_archivo_gestion'])) {
                $data['tiempo_archivo_gestion'] = $serie->tiempo_archivo_gestion;
            }
            if (empty($data['tiempo_archivo_central'])) {
                $data['tiempo_archivo_central'] = $serie->tiempo_archivo_central;
            }
            if (empty($data['disposicion_final'])) {
                $data['disposicion_final'] = $serie->disposicion_final;
            }

            // Añadir campos de auditoría
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            \Log::info('STORE SUBSERIE - Datos finales para crear:', $data);
        
            try {
                $subserie = SubserieDocumental::create($data);
                \Log::info('STORE SUBSERIE - Subserie creada exitosamente:', $subserie->toArray());
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('STORE SUBSERIE - Error de base de datos:', [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings()
                ]);
                throw $e;
            } catch (\Exception $e) {
                \Log::error('STORE SUBSERIE - Error general:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

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
        $subserie->load(['serie.tablaRetencion', 'expedientes']);

        return Inertia::render('admin/subseries/show', [
            'subserie' => $subserie,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubserieDocumental $subserie)
    {
        $subserie->load(['serie.tablaRetencion']);

        $series = SerieDocumental::with('tablaRetencion')
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
    public function update(Request $request, SubserieDocumental $subseries)
    {
        \Log::info('UPDATE SUBSERIE - ID: ' . ($subseries->id ?? 'NULL'));
        \Log::info('UPDATE SUBSERIE - Datos recibidos:', $request->all());
        \Log::info('UPDATE SUBSERIE - Subserie actual:', $subseries->toArray());
        
        $validator = Validator::make($request->all(), [
            'codigo' => ['nullable', 'string', 'max:50', Rule::unique('subseries_documentales', 'codigo')->ignore($subseries->id)],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'serie_id' => 'required|exists:series_documentales,id',
            'tiempo_archivo_gestion' => 'nullable|integer|min:0',
            'tiempo_archivo_central' => 'nullable|integer|min:0',
            'disposicion_final' => 'nullable|string|in:conservacion_permanente,eliminacion,seleccion,microfilmacion',
            'area_responsable' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'activa' => 'boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('UPDATE SUBSERIE - Errores de validación:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();
            \Log::info('UPDATE SUBSERIE - Datos validados:', $validated);
            
            // Mapear serie_id a serie_documental_id
            $data = $validated;
            $data['serie_documental_id'] = $validated['serie_id'];
            unset($data['serie_id']);
            
            \Log::info('UPDATE SUBSERIE - Datos finales para actualizar:', $data);
            $result = $subseries->update($data);
            \Log::info('UPDATE SUBSERIE - Resultado update: ' . ($result ? 'true' : 'false'));

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
    public function destroy(SubserieDocumental $subseries)
    {
        \Log::info('DESTROY SUBSERIE - ID: ' . ($subseries->id ?? 'NULL'));
        \Log::info('DESTROY SUBSERIE - Subserie completa:', $subseries->toArray());
        
        try {
            // Verificar si tiene expedientes asociados
            $expedientesCount = $subseries->expedientes()->count();
            \Log::info('DESTROY SUBSERIE - Expedientes asociados: ' . $expedientesCount);
            
            if ($expedientesCount > 0) {
                \Log::info('DESTROY SUBSERIE - Cancelado por expedientes asociados');
                return redirect()->back()
                    ->with('error', 'No se puede eliminar la subserie documental porque tiene expedientes asociados.');
            }

            DB::beginTransaction();
            \Log::info('DESTROY SUBSERIE - Iniciando transacción');

            try {
                $result = $subseries->delete();
                \Log::info('DESTROY SUBSERIE - Resultado delete: ' . ($result ? 'true' : 'false'));
                if ($subseries->exists) {
                    \Log::info('DESTROY SUBSERIE - Subserie después del delete (aún existe):', $subseries->toArray());
                } else {
                    \Log::info('DESTROY SUBSERIE - Subserie después del delete: NO EXISTE (eliminada correctamente)');
                }
            } catch (\Exception $deleteException) {
                \Log::error('DESTROY SUBSERIE - Error en delete:', [
                    'error' => $deleteException->getMessage(),
                    'trace' => $deleteException->getTraceAsString()
                ]);
                throw $deleteException;
            }

            DB::commit();
            \Log::info('DESTROY SUBSERIE - Transacción commitada exitosamente');

            return redirect()->route('admin.subseries.index')
                ->with('message', 'Subserie documental eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('DESTROY SUBSERIE - Error general:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            
            $subseries = SubserieDocumental::with(['serie.tablaRetencion'])
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
                'trd_codigo' => $subserie->serie->tablaRetencion->codigo,
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
                    $subserie->serie->tablaRetencion->codigo,
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
            $subserieNode->addChild('trd_codigo', htmlspecialchars($subserie->serie->tablaRetencion->codigo));
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
