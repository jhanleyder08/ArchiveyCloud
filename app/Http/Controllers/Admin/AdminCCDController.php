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
        $query = CuadroClasificacionDocumental::with(['padre', 'hijos', 'usuarioCreador'])
            ->orderBy('nivel')
            ->orderBy('orden')
            ->orderBy('codigo');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('estado') && $request->estado !== 'all') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('nivel') && $request->nivel !== 'all') {
            $query->where('nivel', $request->nivel);
        }

        if ($request->filled('activo') && $request->activo !== 'all') {
            $query->where('activo', $request->activo === 'true');
        }

        $ccd = $query->paginate(10)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total' => CuadroClasificacionDocumental::count(),
            'activos' => CuadroClasificacionDocumental::where('activo', true)->count(),
            'borradores' => CuadroClasificacionDocumental::where('estado', 'borrador')->count(),
            'vigentes' => CuadroClasificacionDocumental::where('estado', 'activo')->count(),
        ];

        // Opciones para filtros
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
            'filtros' => $request->only(['search', 'estado', 'nivel', 'activo'])
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
            'nivel' => 'required|integer|min:1|max:5',
            'padre_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'orden' => 'nullable|integer|min:0',
            'estado' => 'required|in:borrador,activo,inactivo,historico',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
            'vocabulario_controlado' => 'nullable|array',
            'metadatos' => 'nullable|array',
        ]);

        $ccd = CuadroClasificacionDocumental::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'nivel' => $request->nivel,
            'padre_id' => $request->padre_id,
            'orden' => $request->orden ?? 0,
            'estado' => $request->estado,
            'activo' => $request->boolean('activo', true),
            'observaciones' => $request->observaciones,
            'vocabulario_controlado' => $request->vocabulario_controlado,
            'metadatos' => $request->metadatos,
            'usuario_creador_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CuadroClasificacionDocumental $ccd)
    {
        $ccd->load(['padre', 'hijos', 'usuarioCreador', 'usuarioModificador']);
        
        return response()->json($ccd);
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
            'nivel' => 'required|integer|min:1|max:5',
            'padre_id' => 'nullable|exists:cuadros_clasificacion_documental,id',
            'orden' => 'nullable|integer|min:0',
            'estado' => 'required|in:borrador,activo,inactivo,historico',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
            'vocabulario_controlado' => 'nullable|array',
            'metadatos' => 'nullable|array',
        ]);

        $ccd->update([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'nivel' => $request->nivel,
            'padre_id' => $request->padre_id,
            'orden' => $request->orden ?? $ccd->orden,
            'estado' => $request->estado,
            'activo' => $request->boolean('activo'),
            'observaciones' => $request->observaciones,
            'vocabulario_controlado' => $request->vocabulario_controlado,
            'metadatos' => $request->metadatos,
            'usuario_modificador_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CuadroClasificacionDocumental $ccd)
    {
        // Verificar si tiene hijos
        if ($ccd->hijos()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar el CCD porque tiene elementos hijos.');
        }

        $ccd->delete();
        
        return redirect()->back()->with('success', 'Cuadro de Clasificación Documental eliminado exitosamente.');
    }

    /**
     * Duplicate a CCD
     */
    public function duplicate(CuadroClasificacionDocumental $ccd)
    {
        DB::beginTransaction();
        
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
            $nuevoCcd->estado = 'borrador';
            $nuevoCcd->activo = true;
            $nuevoCcd->usuario_creador_id = auth()->id();
            $nuevoCcd->usuario_modificador_id = null;
            $nuevoCcd->save();

            DB::commit();
            
            return redirect()->back()->with('success', 'Cuadro de Clasificación Documental duplicado exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al duplicar el CCD: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(CuadroClasificacionDocumental $ccd)
    {
        // Validar estado antes de activar/desactivar
        if (!$ccd->activo && $ccd->estado === 'borrador') {
            return redirect()->back()->with('error', 'No se puede activar un CCD en estado Borrador.');
        }

        $ccd->update([
            'activo' => !$ccd->activo,
            'usuario_modificador_id' => auth()->id(),
        ]);

        $estado = $ccd->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Cuadro de Clasificación Documental {$estado} exitosamente.");
    }
}
