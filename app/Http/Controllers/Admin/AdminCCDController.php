<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuadroClasificacionDocumental;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCCDController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CuadroClasificacionDocumental::orderBy('codigo');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('activo') && $request->activo !== 'all') {
            $query->where('activo', $request->activo === 'true');
        }

        $ccd = $query->paginate(10)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total' => CuadroClasificacionDocumental::count(),
            'activos' => CuadroClasificacionDocumental::where('activo', true)->count(),
            'inactivos' => CuadroClasificacionDocumental::where('activo', false)->count(),
        ];

        // Opciones para los filtros y formularios
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
        ];

        return Inertia::render('admin/ccd/index', [
            'data' => $ccd,
            'estadisticas' => $estadisticas,
            'opciones' => $opciones,
            'filtros' => $request->only(['search', 'activo'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
        ];

        return Inertia::render('admin/ccd/create', [
            'opciones' => $opciones
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cuadros_clasificacion_documental')->whereNull('deleted_at')
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            'fecha_aprobacion' => 'nullable|date',
            'version' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
        ]);

        $ccd = CuadroClasificacionDocumental::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->boolean('activo', true),
            'fecha_aprobacion' => $request->fecha_aprobacion,
            'version' => $request->version ?? '1.0',
            'observaciones' => $request->observaciones,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CuadroClasificacionDocumental $ccd)
    {
        return response()->json($ccd);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CuadroClasificacionDocumental $ccd)
    {
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'historico', 'label' => 'Histórico'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
        ];

        return Inertia::render('admin/ccd/edit', [
            'ccd' => $ccd,
            'opciones' => $opciones
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CuadroClasificacionDocumental $ccd)
    {
        $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cuadros_clasificacion_documental')->ignore($ccd->id)->whereNull('deleted_at')
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
            'fecha_aprobacion' => 'nullable|date',
            'version' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
        ]);

        $ccd->update([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->boolean('activo'),
            'fecha_aprobacion' => $request->fecha_aprobacion,
            'version' => $request->version,
            'observaciones' => $request->observaciones,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CuadroClasificacionDocumental $ccd)
    {
        $ccd->delete();
        
        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental eliminado exitosamente.');
    }

    /**
     * Duplicate a CCD
     */
    public function duplicate(CuadroClasificacionDocumental $ccd)
    {
        try {
            // Generar código único
            $baseCodigo = $ccd->codigo . '_copia';
            $contador = 1;
            $nuevoCodigo = $baseCodigo;
            
            while (CuadroClasificacionDocumental::where('codigo', $nuevoCodigo)->whereNull('deleted_at')->exists()) {
                $nuevoCodigo = $baseCodigo . '_' . $contador;
                $contador++;
            }

            $nuevoCcd = $ccd->replicate();
            $nuevoCcd->codigo = $nuevoCodigo;
            $nuevoCcd->nombre = $ccd->nombre . ' (Copia)';
            $nuevoCcd->activo = true;
            $nuevoCcd->created_by = auth()->id();
            $nuevoCcd->updated_by = null;
            $nuevoCcd->save();

            return redirect()->back()->with('success', 'CCD duplicado exitosamente como: ' . $nuevoCodigo);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al duplicar el CCD: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(CuadroClasificacionDocumental $ccd)
    {
        $ccd->update([
            'activo' => !$ccd->activo,
            'updated_by' => auth()->id(),
        ]);

        $estado = $ccd->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Cuadro de Clasificación Documental {$estado} exitosamente.");
    }
}
