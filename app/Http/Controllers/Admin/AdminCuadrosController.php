<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuadroClasificacionDocumental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminCuadrosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Parámetros de filtrado
            $search = $request->get('search');
            $padreId = $request->get('padre_id');
            $nivel = $request->get('nivel');
            $estado = $request->get('estado');

            // Query base con relaciones
            $query = CuadroClasificacionDocumental::with(['padre', 'hijos', 'creador', 'modificador'])
                ->select('cuadros_clasificacion_documental.*');

            // Aplicar filtros
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            if ($padreId && $padreId !== 'all') {
                if ($padreId === 'root') {
                    $query->whereNull('padre_id');
                } else {
                    $query->where('padre_id', $padreId);
                }
            }

            if ($nivel) {
                $query->where('nivel', $nivel);
            }

            if ($estado && $estado !== 'all') {
                $query->where('estado', $estado);
            }

            // Ordenamiento jerárquico
            $query->orderBy('nivel')
                  ->orderBy('orden')
                  ->orderBy('codigo');

            // Paginación
            $cuadros = $query->paginate(15)->withQueryString();

            // Estadísticas
            $stats = [
                'total' => CuadroClasificacionDocumental::count(),
                'activos' => CuadroClasificacionDocumental::where('activo', true)->count(),
                'inactivos' => CuadroClasificacionDocumental::where('activo', false)->count(),
                'por_nivel' => CuadroClasificacionDocumental::selectRaw('nivel, COUNT(*) as total')
                    ->groupBy('nivel')
                    ->pluck('total', 'nivel')
                    ->toArray(),
            ];

            // Obtener elementos para filtros
            $elementosPadre = CuadroClasificacionDocumental::whereNull('padre_id')
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            return Inertia::render('admin/cuadros/index', [
                'data' => $cuadros,
                'elementosPadre' => $elementosPadre,
                'stats' => $stats,
                'filters' => [
                    'search' => $search,
                    'padre_id' => $padreId,
                    'nivel' => $nivel,
                    'estado' => $estado,
                ],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar los cuadros de clasificación: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $elementosPadre = CuadroClasificacionDocumental::where('activo', true)
            ->orderBy('nivel')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'nivel']);

        return Inertia::render('admin/cuadros/create', [
            'elementosPadre' => $elementosPadre,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'nullable|string|max:50|unique:cuadros_clasificacion_documental,codigo,NULL,id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'padre_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'orden' => 'nullable|integer|min:1',
            'estado' => 'required|in:borrador,activo,inactivo,historico',
            'vocabulario_controlado' => 'nullable|array',
            'metadatos' => 'nullable|array',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();

            // Mapear campos
            $data = [
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'padre_id' => $validated['padre_id'] ?? null,
                'orden' => $validated['orden'] ?? 1,
                'estado' => $validated['estado'],
                'vocabulario_controlado' => $validated['vocabulario_controlado'] ?? null,
                'metadatos' => $validated['metadatos'] ?? null,
                'activo' => $validated['activo'] ?? true,
            ];

            // Calcular nivel basado en el padre
            if ($data['padre_id']) {
                $padre = CuadroClasificacionDocumental::find($data['padre_id']);
                $data['nivel'] = $padre->nivel + 1;
            } else {
                $data['nivel'] = 1; // Nivel raíz
            }

            // Generar código automático si no se proporciona
            if (empty($data['codigo'])) {
                if ($data['padre_id']) {
                    $padre = CuadroClasificacionDocumental::find($data['padre_id']);
                    $ultimoHijo = CuadroClasificacionDocumental::where('padre_id', $data['padre_id'])
                        ->orderBy('orden', 'desc')
                        ->first();
                    $nuevoOrden = $ultimoHijo ? ($ultimoHijo->orden + 1) : 1;
                    $data['codigo'] = $padre->codigo . '.' . str_pad($nuevoOrden, 2, '0', STR_PAD_LEFT);
                    $data['orden'] = $nuevoOrden;
                } else {
                    $ultimoRaiz = CuadroClasificacionDocumental::whereNull('padre_id')
                        ->orderBy('orden', 'desc')
                        ->first();
                    $nuevoOrden = $ultimoRaiz ? ($ultimoRaiz->orden + 1) : 1;
                    $data['codigo'] = str_pad($nuevoOrden, 3, '0', STR_PAD_LEFT);
                    $data['orden'] = $nuevoOrden;
                }
            }

            // Añadir campos de auditoría
            $data['usuario_creador_id'] = Auth::id();
            $data['usuario_modificador_id'] = Auth::id();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $cuadro = CuadroClasificacionDocumental::create($data);

            DB::commit();

            return redirect()->route('admin.cuadros.index')
                ->with('message', 'Cuadro de clasificación creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al crear el cuadro de clasificación: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CuadroClasificacionDocumental $cuadro)
    {
        $cuadro->load(['padre', 'hijos.hijos', 'series', 'creador', 'modificador']);
        
        return Inertia::render('admin/cuadros/show', [
            'cuadro' => $cuadro,
            'ruta_completa' => $cuadro->getRutaCompleta(),
            'codigo_completo' => $cuadro->getCodigoCompleto(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CuadroClasificacionDocumental $cuadro)
    {
        $elementosPadre = CuadroClasificacionDocumental::where('activo', true)
            ->where('id', '!=', $cuadro->id)
            ->orderBy('nivel')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'nivel']);

        return Inertia::render('admin/cuadros/edit', [
            'cuadro' => $cuadro,
            'elementosPadre' => $elementosPadre,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CuadroClasificacionDocumental $cuadro)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'nullable|string|max:50|unique:cuadros_clasificacion_documental,codigo,' . $cuadro->id . ',id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'padre_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'orden' => 'nullable|integer|min:1',
            'estado' => 'required|in:borrador,activo,inactivo,historico',
            'vocabulario_controlado' => 'nullable|array',
            'metadatos' => 'nullable|array',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();
            
            // Validar que no se establezca como padre de sí mismo o de sus descendientes
            if ($validated['padre_id'] && $validated['padre_id'] == $cuadro->id) {
                return redirect()->back()
                    ->with('error', 'Un elemento no puede ser padre de sí mismo.')
                    ->withInput();
            }

            $data = [
                'codigo' => $validated['codigo'] ?? $cuadro->codigo,
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'padre_id' => $validated['padre_id'],
                'orden' => $validated['orden'] ?? $cuadro->orden,
                'estado' => $validated['estado'],
                'vocabulario_controlado' => $validated['vocabulario_controlado'],
                'metadatos' => $validated['metadatos'],
                'activo' => $validated['activo'] ?? true,
                'usuario_modificador_id' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            // Recalcular nivel si cambió el padre
            if ($cuadro->padre_id != $data['padre_id']) {
                if ($data['padre_id']) {
                    $padre = CuadroClasificacionDocumental::find($data['padre_id']);
                    $data['nivel'] = $padre->nivel + 1;
                } else {
                    $data['nivel'] = 1;
                }
            }

            $cuadro->update($data);

            DB::commit();

            return redirect()->route('admin.cuadros.index')
                ->with('message', 'Cuadro de clasificación actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al actualizar el cuadro de clasificación: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CuadroClasificacionDocumental $cuadro)
    {
        try {
            DB::beginTransaction();

            // Verificar si tiene elementos dependientes
            if ($cuadro->hijos->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el cuadro porque tiene elementos hijos.');
            }

            if ($cuadro->series->count() > 0) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar el cuadro porque tiene series documentales asociadas.');
            }

            $cuadro->delete();

            DB::commit();

            return redirect()->route('admin.cuadros.index')
                ->with('message', 'Cuadro de clasificación eliminado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar el cuadro de clasificación: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of the specified resource.
     */
    public function toggleActive(CuadroClasificacionDocumental $cuadro)
    {
        try {
            $cuadro->update([
                'activo' => !$cuadro->activo,
                'usuario_modificador_id' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $estado = $cuadro->activo ? 'activado' : 'desactivado';
            
            return redirect()->back()
                ->with('message', "Cuadro de clasificación {$estado} exitosamente.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al cambiar el estado: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate the specified resource.
     */
    public function duplicate(CuadroClasificacionDocumental $cuadro)
    {
        try {
            DB::beginTransaction();

            $nuevoCuadro = $cuadro->replicate();
            $nuevoCuadro->codigo = null; // Se generará automáticamente
            $nuevoCuadro->nombre = $cuadro->nombre . ' (Copia)';
            $nuevoCuadro->estado = 'borrador';
            $nuevoCuadro->usuario_creador_id = Auth::id();
            $nuevoCuadro->usuario_modificador_id = Auth::id();
            $nuevoCuadro->created_by = Auth::id();
            $nuevoCuadro->updated_by = Auth::id();
            $nuevoCuadro->save();

            DB::commit();

            return redirect()->route('admin.cuadros.edit', $nuevoCuadro)
                ->with('message', 'Cuadro de clasificación duplicado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al duplicar el cuadro de clasificación: ' . $e->getMessage());
        }
    }

    /**
     * Export cuadros data.
     */
    public function export(Request $request)
    {
        $formato = $request->get('formato', 'json');
        
        $cuadros = CuadroClasificacionDocumental::with(['padre', 'hijos'])
            ->orderBy('nivel')
            ->orderBy('orden')
            ->get();

        switch ($formato) {
            case 'csv':
                return $this->exportCsv($cuadros);
            case 'xml':
                return $this->exportXml($cuadros);
            default:
                return $this->exportJson($cuadros);
        }
    }

    private function exportJson($cuadros)
    {
        $data = $cuadros->map(function ($cuadro) {
            return [
                'id' => $cuadro->id,
                'codigo' => $cuadro->codigo,
                'nombre' => $cuadro->nombre,
                'descripcion' => $cuadro->descripcion,
                'nivel' => $cuadro->nivel,
                'padre_codigo' => $cuadro->padre->codigo ?? null,
                'padre_nombre' => $cuadro->padre->nombre ?? null,
                'orden' => $cuadro->orden,
                'estado' => $cuadro->estado,
                'activo' => $cuadro->activo,
                'ruta_completa' => $cuadro->getRutaCompleta(),
                'created_at' => $cuadro->created_at,
            ];
        });

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="cuadros_clasificacion.json"');
    }

    private function exportCsv($cuadros)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cuadros_clasificacion.csv"',
        ];

        $callback = function () use ($cuadros) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'Código', 'Nombre', 'Descripción', 'Nivel', 'Padre Código', 
                'Padre Nombre', 'Orden', 'Estado', 'Activo', 'Ruta Completa', 'Fecha Creación'
            ]);

            foreach ($cuadros as $cuadro) {
                fputcsv($file, [
                    $cuadro->codigo,
                    $cuadro->nombre,
                    $cuadro->descripcion,
                    $cuadro->nivel,
                    $cuadro->padre->codigo ?? '',
                    $cuadro->padre->nombre ?? '',
                    $cuadro->orden,
                    $cuadro->estado,
                    $cuadro->activo ? 'Sí' : 'No',
                    $cuadro->getRutaCompleta(),
                    $cuadro->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXml($cuadros)
    {
        $xml = new \SimpleXMLElement('<cuadros_clasificacion_documental/>');
        
        foreach ($cuadros as $cuadro) {
            $cuadroNode = $xml->addChild('cuadro');
            $cuadroNode->addChild('id', $cuadro->id);
            $cuadroNode->addChild('codigo', htmlspecialchars($cuadro->codigo));
            $cuadroNode->addChild('nombre', htmlspecialchars($cuadro->nombre));
            $cuadroNode->addChild('descripcion', htmlspecialchars($cuadro->descripcion));
            $cuadroNode->addChild('nivel', $cuadro->nivel);
            $cuadroNode->addChild('padre_codigo', htmlspecialchars($cuadro->padre->codigo ?? ''));
            $cuadroNode->addChild('orden', $cuadro->orden);
            $cuadroNode->addChild('estado', htmlspecialchars($cuadro->estado));
            $cuadroNode->addChild('activo', $cuadro->activo ? 'true' : 'false');
            $cuadroNode->addChild('ruta_completa', htmlspecialchars($cuadro->getRutaCompleta()));
            $cuadroNode->addChild('created_at', $cuadro->created_at->toISOString());
        }

        return response($xml->asXML())
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="cuadros_clasificacion.xml"');
    }
}
