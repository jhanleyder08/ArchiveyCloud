<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TablaRetencionDocumental;
use App\Models\CCD;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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

        // Obtener TODOS los CCDs disponibles para el formulario de creación
        $ccds = CCD::orderBy('nombre')->get(['id', 'codigo', 'nombre', 'version', 'estado']);

        return Inertia::render('admin/trd/index', [
            'trds' => $trds,
            'stats' => $stats,
            'ccds' => $ccds,
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
        Log::info('Iniciando creación de TRD', ['data' => $request->all()]);
        
        // Preparar datos: convertir version a entero si es string
        $requestData = $request->all();
        if (isset($requestData['version'])) {
            $requestData['version'] = is_numeric($requestData['version']) ? (int)$requestData['version'] : $requestData['version'];
        }
        
        Log::info('Datos procesados para validación', ['data' => $requestData]);
        
        // Validar version como string o integer, luego convertir
        $validated = validator($requestData, [
            'codigo' => 'required|string|max:50|unique:tablas_retencion_documental,codigo,NULL,id,deleted_at,NULL',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'entidad' => 'required|string|max:255',
            'dependencia' => 'nullable|string|max:255',
            'ccd_id' => 'required|exists:cuadros_clasificacion,id',
            'version' => ['required', function ($attribute, $value, $fail) {
                if (!is_numeric($value)) {
                    $fail('El campo versión debe ser un número.');
                } elseif ((int)$value < 1) {
                    $fail('El campo versión debe ser al menos 1.');
                }
            }],
            'fecha_aprobacion' => 'required|date',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'estado' => ['required', Rule::in(['borrador', 'revision', 'aprobada', 'vigente', 'obsoleta'])],
            'observaciones_generales' => 'nullable|string',
            'metadatos_adicionales' => 'nullable'
        ])->validate();
        
        // Convertir version a entero después de validar
        $validated['version'] = (int)$validated['version'];

        $validated['created_by'] = Auth::id();
        
        // Si el estado es aprobada, marcar quien lo aprobó
        if ($validated['estado'] === 'aprobada') {
            $validated['aprobado_por'] = Auth::id();
        }

        try {
            // Asegurar que vigente esté definido
            if (!isset($validated['vigente'])) {
                $validated['vigente'] = false;
            }
            
            $trd = TablaRetencionDocumental::create($validated);

            Log::info('TRD creada exitosamente', ['trd_id' => $trd->id, 'codigo' => $trd->codigo]);

            // Usar Inertia location para forzar recarga completa de la página
            return Inertia::location(route('admin.trd.index'));
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación - dejar que Laravel los maneje
            Log::warning('Error de validación al crear TRD', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al crear TRD', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated
            ]);
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al crear la TRD: ' . $e->getMessage()]);
        }
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
            'codigo' => ['required', 'string', 'max:50', Rule::unique('tablas_retencion_documental')->ignore($trd->id)->whereNull('deleted_at')],
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
        ], [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya está en uso por otra TRD',
            'nombre.required' => 'El nombre es obligatorio',
            'descripcion.required' => 'La descripción es obligatoria',
            'entidad.required' => 'La entidad es obligatoria',
            'version.required' => 'La versión es obligatoria',
            'version.integer' => 'La versión debe ser un número entero',
            'version.min' => 'La versión debe ser al menos 1',
            'fecha_aprobacion.required' => 'La fecha de aprobación es obligatoria',
            'fecha_aprobacion.date' => 'La fecha de aprobación debe ser una fecha válida',
            'fecha_vigencia_inicio.required' => 'La fecha de inicio de vigencia es obligatoria',
            'fecha_vigencia_inicio.date' => 'La fecha de inicio de vigencia debe ser una fecha válida',
            'fecha_vigencia_fin.date' => 'La fecha de fin de vigencia debe ser una fecha válida',
            'fecha_vigencia_fin.after' => 'La fecha de fin de vigencia debe ser posterior a la fecha de inicio',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'El estado seleccionado no es válido',
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
            return back()->with('error', 'Solo las TRD aprobadas pueden marcarse como vigentes. Estado actual: ' . $trd->estado);
        }

        $nuevoEstado = $trd->estado === 'vigente' ? 'aprobada' : 'vigente';
        $vigente = $nuevoEstado === 'vigente';
        
        $trd->update([
            'estado' => $nuevoEstado,
            'vigente' => $vigente,
            'updated_by' => Auth::id(),
        ]);

        $estadoTexto = $nuevoEstado === 'vigente' ? 'vigente' : 'no vigente';
        
        return back()->with('success', "TRD marcada como {$estadoTexto} exitosamente.");
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
