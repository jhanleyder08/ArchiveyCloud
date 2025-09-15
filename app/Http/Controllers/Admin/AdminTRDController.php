<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TablaRetencionDocumental;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Controlador para la gestión de Tablas de Retención Documental (TRD)
 * Basado en los requerimientos SGDEA de clasificación y organización documental
 */
class AdminTRDController extends Controller
{
    /**
     * Mostrar listado de TRDs con filtros y paginación
     */
    public function index(Request $request)
    {
        $query = TablaRetencionDocumental::with(['creador', 'modificador'])
            ->withCount(['series', 'expedientes']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('entidad', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Elimino el filtro por 'vigente' ya que esa columna no existe en la migración
        
        // Orden y paginación
        $trds = $query->orderBy('created_at', 'desc')
                     ->paginate(15)
                     ->withQueryString();

        // Estadísticas
        $stats = [
            'total' => TablaRetencionDocumental::count(),
            'vigentes' => TablaRetencionDocumental::where('estado', 'vigente')->count(),
            'borradores' => TablaRetencionDocumental::where('estado', 'borrador')->count(),
            'aprobadas' => TablaRetencionDocumental::where('estado', 'aprobada')->count(),
        ];

        return Inertia::render('admin/trd/index', [
            'trds' => $trds,
            'stats' => $stats,
            'filters' => $request->only(['search', 'estado', 'vigente']),
            'estados' => [
                TablaRetencionDocumental::ESTADO_BORRADOR => 'Borrador',
                TablaRetencionDocumental::ESTADO_REVISION => 'En Revisión',
                TablaRetencionDocumental::ESTADO_APROBADA => 'Aprobada',
                TablaRetencionDocumental::ESTADO_VIGENTE => 'Vigente',
                TablaRetencionDocumental::ESTADO_HISTORICA => 'Histórica',
            ]
        ]);
    }

    /**
     * Mostrar formulario de creación de nueva TRD
     */
    public function create()
    {
        return Inertia::render('admin/trd/create', [
            'estados' => [
                TablaRetencionDocumental::ESTADO_BORRADOR => 'Borrador',
                TablaRetencionDocumental::ESTADO_REVISION => 'En Revisión',
                TablaRetencionDocumental::ESTADO_APROBADA => 'Aprobada',
            ]
        ]);
    }

    /**
     * Almacenar nueva TRD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:tablas_retencion_documental,codigo,NULL,id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'entidad' => 'required|string|max:255',
            'dependencia' => 'nullable|string|max:255',
            'version' => 'required|integer|min:1',
            'fecha_aprobacion' => 'required|date',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'estado' => ['required', Rule::in(['borrador', 'revision', 'aprobada', 'vigente', 'obsoleta'])],
            'observaciones_generales' => 'nullable|string',
            'metadatos_adicionales' => 'nullable'
        ]);

        $validated['created_by'] = Auth::id();
        
        // Si el estado es aprobada, marcar quien lo aprobó
        if ($validated['estado'] === 'aprobada') {
            $validated['aprobado_por'] = Auth::id();
        }

        TablaRetencionDocumental::create($validated);

        return redirect()->route('admin.trd.index')->with('success', 'TRD creada exitosamente.');
    }

    /**
     * Mostrar detalles de una TRD específica
     */
    public function show(TablaRetencionDocumental $trd)
    {
        $trd->load(['creador', 'modificador', 'aprobador']);
        
        // Obtener versiones de la TRD (por ahora simulamos con el mismo TRD)
        $versiones = [$trd]; // Se puede expandir cuando se implemente versionado completo
        
        // Obtener estadísticas relacionadas de forma segura
        $estadisticas = [
            'series_count' => $trd->series()->count(),
            'expedientes_count' => $trd->expedientes()->count(),
            'documentos_count' => 0, // Calcular cuando se implementen relaciones completas
            'estado_actual' => $trd->estado,
            'version_actual' => $trd->version,
        ];

        return Inertia::render('admin/trd/show', [
            'trd' => $trd,
            'versiones' => $versiones,
            'estadisticas' => $estadisticas,
            'tieneDocumentosAsociados' => $trd->tieneDocumentosAsociados(),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(TablaRetencionDocumental $trd)
    {
        $trd->load(['creador', 'modificador', 'aprobador']);
        
        return Inertia::render('admin/trd/edit', [
            'trd' => $trd,
            'estados' => [
                'borrador' => 'Borrador',
                'revision' => 'En Revisión',
                'aprobada' => 'Aprobada',
                'vigente' => 'Vigente',
                'obsoleta' => 'Obsoleta',
            ],
            'tieneDocumentosAsociados' => $trd->tieneDocumentosAsociados(),
        ]);
    }

    /**
     * Actualizar TRD existente
     */
    public function update(Request $request, TablaRetencionDocumental $trd)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('tablas_retencion_documental')->ignore($trd->id)],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'entidad' => 'required|string|max:255',
            'dependencia' => 'nullable|string|max:255',
            'version' => 'required|integer|min:1',
            'fecha_aprobacion' => 'required|date',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'estado' => ['required', Rule::in(['borrador', 'revision', 'aprobada', 'vigente', 'obsoleta'])],
            'observaciones_generales' => 'nullable|string',
            'metadatos_adicionales' => 'nullable'
        ]);

        $validated['updated_by'] = Auth::id();
        
        // Si el estado cambia a aprobada, marcar quien lo aprobó
        if ($validated['estado'] === 'aprobada') {
            $validated['aprobado_por'] = Auth::id();
        }

        $trd->update($validated);

        return redirect()->route('admin.trd.index')->with('success', 'TRD actualizada exitosamente.');
    }

    /**
     * Eliminar TRD (soft delete)
     */
    public function destroy(TablaRetencionDocumental $trd)
    {
        // Verificar si tiene documentos asociados antes de eliminar
        if ($trd->tieneDocumentosAsociados()) {
            return redirect()->back()->with('error', 'No se puede eliminar la TRD porque tiene documentos asociados.');
        }

        $trd->delete();

        return redirect()->route('admin.trd.index')->with('success', 'TRD eliminada exitosamente.');
    }

    /**
     * Duplicar TRD para crear nueva versión
     */
    public function duplicate(TablaRetencionDocumental $trd)
    {
        $nuevaTrd = $trd->replicate();
        
        // Generar código único
        $codigoBase = $trd->codigo . '-COPY';
        $codigoUnico = $codigoBase;
        $contador = 1;
        
        // Buscar un código que no exista
        while (TablaRetencionDocumental::where('codigo', $codigoUnico)->exists()) {
            $codigoUnico = $codigoBase . '-' . $contador;
            $contador++;
        }
        
        // Generar identificador único nuevo
        $year = now()->year;
        $lastTrd = TablaRetencionDocumental::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastTrd && $lastTrd->identificador_unico) {
            // Extraer número del último identificador (formato: TRD-YYYY-XXXXXX)
            preg_match('/TRD-\d{4}-(\d{6})/', $lastTrd->identificador_unico, $matches);
            if (!empty($matches[1])) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }
        
        $identificadorUnico = sprintf('TRD-%d-%06d', $year, $nextNumber);
        
        // Asignar valores únicos
        $nuevaTrd->codigo = $codigoUnico;
        $nuevaTrd->nombre = $trd->nombre . ' (Copia)';
        $nuevaTrd->identificador_unico = $identificadorUnico;
        $nuevaTrd->version = 1;
        $nuevaTrd->estado = 'borrador';
        $nuevaTrd->vigente = false;
        
        // Mantener fecha_aprobacion del original (requerida)
        $nuevaTrd->fecha_aprobacion = $trd->fecha_aprobacion;
        $nuevaTrd->created_by = Auth::id();
        $nuevaTrd->updated_by = null;
        $nuevaTrd->aprobado_por = null;
        
        try {
            $nuevaTrd->save();
            return redirect()->route('admin.trd.index')
                ->with('success', "TRD duplicada exitosamente con código: {$codigoUnico}");
        } catch (\Exception $e) {
            return redirect()->route('admin.trd.index')
                ->with('error', 'Error al duplicar la TRD: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado de vigencia de una TRD
     */
    public function toggleVigencia(TablaRetencionDocumental $trd)
    {
        if ($trd->estado !== 'aprobada' && $trd->estado !== 'vigente') {
            return redirect()->back()->with('error', 'Solo las TRD aprobadas pueden marcarse como vigentes. Estado actual: ' . $trd->estado);
        }

        $nuevoEstado = $trd->estado === 'vigente' ? 'aprobada' : 'vigente';
        $vigente = $nuevoEstado === 'vigente';
        
        $trd->update([
            'estado' => $nuevoEstado,
            'vigente' => $vigente,
            'updated_by' => Auth::id(),
        ]);

        $estadoTexto = $nuevoEstado === 'vigente' ? 'vigente' : 'no vigente';
        
        return redirect()->back()->with('success', "TRD marcada como {$estadoTexto} exitosamente.");
    }

    /**
     * Exportar TRD en formato específico
     */
    public function export(TablaRetencionDocumental $trd, Request $request)
    {
        $formato = $request->get('formato', 'json');
        
        $trd->load(['creador', 'modificador', 'aprobador', 'series', 'expedientes']);
        
        switch ($formato) {
            case 'json':
                return response()->json([
                    'trd' => $trd,
                    'exported_at' => now()->format('Y-m-d H:i:s'),
                    'format' => 'JSON'
                ], 200, [
                    'Content-Disposition' => 'attachment; filename="trd_' . $trd->codigo . '.json"'
                ]);
                
            case 'xml':
                // Exportación XML básica (se puede expandir según estándares AGN)
                $xml = new \SimpleXMLElement('<trd></trd>');
                $xml->addChild('codigo', $trd->codigo);
                $xml->addChild('nombre', htmlspecialchars($trd->nombre));
                $xml->addChild('version', $trd->version);
                $xml->addChild('estado', $trd->estado);
                $xml->addChild('exported_at', now()->format('Y-m-d H:i:s'));
                
                return response($xml->asXML(), 200, [
                    'Content-Type' => 'application/xml',
                    'Content-Disposition' => 'attachment; filename="trd_' . $trd->codigo . '.xml"'
                ]);
                
            default:
                return redirect()->back()->with('error', 'Formato de exportación no soportado.');
        }
    }
}
