<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TablaRetencionDocumental;
use App\Models\SerieDocumental;
use App\Models\SubserieDocumental;
use App\Models\CCD;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        $query = TablaRetencionDocumental::with(['creador', 'modificador', 'cuadroClasificacion'])
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
            'descripcion' => 'nullable|string',
            'ccd_id' => 'required|exists:cuadros_clasificacion,id',
            // Campos del formato oficial FOR-GDI-GDO-002
            'codigo_unidad_administrativa' => 'required|string|max:50',
            'nombre_unidad_administrativa' => 'required|string|max:255',
            'codigo_dependencia' => 'nullable|string|max:50',
            'nombre_dependencia' => 'nullable|string|max:255',
            'version' => ['required', function ($attribute, $value, $fail) {
                if (!is_numeric($value)) {
                    $fail('El campo versión debe ser un número.');
                } elseif ((int)$value < 1) {
                    $fail('El campo versión debe ser al menos 1.');
                }
            }],
            'fecha_aprobacion' => 'nullable|date',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'estado' => ['required', Rule::in(['borrador', 'revision', 'aprobada', 'vigente', 'obsoleta'])],
            'observaciones_generales' => 'nullable|string',
            'metadatos_adicionales' => 'nullable'
        ])->validate();
        
        // Convertir version a entero después de validar
        $validated['version'] = (int)$validated['version'];

        // Asegurar que vigente esté definido
        if (!isset($validated['vigente'])) {
            $validated['vigente'] = false;
        }
        
        // Si el estado es aprobada, marcar quien lo aprobó
        if ($validated['estado'] === 'aprobada') {
            $validated['aprobado_por'] = Auth::id();
        }

        try {
            $trd = TablaRetencionDocumental::create($validated);
            
            // Actualizar created_by después de crear (si la columna existe)
            if (Schema::hasColumn('tablas_retencion_documental', 'created_by')) {
                $trd->created_by = Auth::id();
                $trd->save();
            }

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
     * Mostrar detalles de una TRD específica con estructura del CCD y tiempos de retención
     */
    public function show(TablaRetencionDocumental $trd)
    {
        $trd->load(['creador', 'modificador', 'aprobador', 'cuadroClasificacion']);
        
        // Obtener la estructura jerárquica del CCD asociado
        $estructura = [];
        if ($trd->ccd_id) {
            $estructura = \App\Models\CCDNivel::where('ccd_id', $trd->ccd_id)
                ->whereNull('parent_id')
                ->with(['hijos' => function($query) {
                    $query->orderBy('orden')->with('hijos.hijos.hijos');
                }])
                ->orderBy('orden')
                ->get();
        }
        
        // Obtener los tiempos de retención ya configurados para esta TRD
        $tiemposRetencion = $trd->tiemposRetencion()
            ->with('ccdNivel')
            ->get()
            ->keyBy('ccd_nivel_id');
        
        // Obtener estadísticas relacionadas de forma segura
        $estadisticas = [
            'series_count' => $trd->series()->count(),
            'expedientes_count' => $trd->expedientes()->count(),
            'documentos_count' => 0, // Calcular cuando se implementen relaciones completas
            'estado_actual' => $trd->estado,
            'version_actual' => $trd->version,
            'niveles_con_tiempos' => $tiemposRetencion->count(),
            'niveles_totales' => \App\Models\CCDNivel::where('ccd_id', $trd->ccd_id)->count(),
        ];

        return Inertia::render('admin/trd/show', [
            'trd' => $trd,
            'estructura' => $estructura,
            'tiemposRetencion' => $tiemposRetencion,
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

    /**
     * Exportar TRD a PDF con formato oficial FOR-GDI-GDO-002
     * Basado en el formato del Hospital Universitario del Valle "Evaristo García" E.S.E.
     */
    public function exportarPDF(TablaRetencionDocumental $trd)
    {
        try {
            // Cargar relaciones necesarias para el PDF
            $trd->load([
                'series.subseries.tiposDocumentales',
                'series.tiposDocumentales',
            ]);

            $pdf = Pdf::loadView('pdf.trd-formato-oficial', [
                'trd' => $trd
            ]);
            
            // Configurar página horizontal para mejor visualización de la tabla
            $pdf->setPaper('letter', 'landscape');
            
            $nombreArchivo = 'TRD_' . $trd->codigo . '_v' . str_pad($trd->version, 2, '0', STR_PAD_LEFT) . '.pdf';

            return $pdf->download($nombreArchivo);
        } catch (\Exception $e) {
            Log::error('Error al exportar TRD a PDF', [
                'trd_id' => $trd->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Importar series desde archivo Excel o CSV
     */
    public function importarSeries(Request $request, TablaRetencionDocumental $trd)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.file' => 'Debe ser un archivo válido',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
            'archivo.max' => 'El archivo no debe superar 10MB',
        ]);

        try {
            $archivo = $request->file('archivo');
            $extension = strtolower($archivo->getClientOriginalExtension());
            
            if ($extension === 'csv') {
                $datos = $this->parsearCSV($archivo->getPathname());
            } else {
                $datos = $this->parsearExcel($archivo->getPathname());
            }
            
            if (empty($datos)) {
                return back()->with('error', 'El archivo está vacío o no tiene el formato correcto.');
            }
            
            $resultado = $this->procesarDatosImportacion($trd, $datos);
            
            Log::info('Importación de series completada', [
                'trd_id' => $trd->id,
                'series_creadas' => $resultado['series_creadas'],
                'subseries_creadas' => $resultado['subseries_creadas'],
                'errores' => $resultado['errores'],
            ]);
            
            $mensaje = "Importación completada: {$resultado['series_creadas']} series y {$resultado['subseries_creadas']} subseries creadas.";
            if (!empty($resultado['errores'])) {
                $mensaje .= " Se encontraron " . count($resultado['errores']) . " errores.";
            }
            
            return back()->with('success', $mensaje);
            
        } catch (\Exception $e) {
            Log::error('Error al importar series', [
                'trd_id' => $trd->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
    
    /**
     * Parsear archivo Excel
     */
    private function parsearExcel(string $rutaArchivo): array
    {
        $spreadsheet = IOFactory::load($rutaArchivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $datos = [];
        
        $filas = $worksheet->toArray();
        
        // Detectar encabezados (primera fila)
        $encabezados = array_shift($filas);
        $mapeoColumnas = $this->mapearColumnas($encabezados);
        
        foreach ($filas as $indice => $fila) {
            if ($this->filaVacia($fila)) continue;
            
            $datos[] = [
                'codigo_serie' => trim($fila[$mapeoColumnas['codigo_serie']] ?? ''),
                'nombre_serie' => trim($fila[$mapeoColumnas['nombre_serie']] ?? ''),
                'codigo_subserie' => trim($fila[$mapeoColumnas['codigo_subserie']] ?? ''),
                'nombre_subserie' => trim($fila[$mapeoColumnas['nombre_subserie']] ?? ''),
                'soporte_fisico' => $this->interpretarBooleano($fila[$mapeoColumnas['soporte_fisico']] ?? ''),
                'soporte_electronico' => $this->interpretarBooleano($fila[$mapeoColumnas['soporte_electronico']] ?? ''),
                'retencion_gestion' => intval($fila[$mapeoColumnas['retencion_gestion']] ?? 0),
                'retencion_central' => intval($fila[$mapeoColumnas['retencion_central']] ?? 0),
                'disposicion_final' => strtoupper(trim($fila[$mapeoColumnas['disposicion_final']] ?? '')),
                'procedimiento' => trim($fila[$mapeoColumnas['procedimiento']] ?? ''),
                'fila_original' => $indice + 2, // +2 porque quitamos encabezado y arrays empiezan en 0
            ];
        }
        
        return $datos;
    }
    
    /**
     * Parsear archivo CSV
     */
    private function parsearCSV(string $rutaArchivo): array
    {
        $datos = [];
        $handle = fopen($rutaArchivo, 'r');
        
        // Detectar delimitador
        $primeraLinea = fgets($handle);
        rewind($handle);
        $delimitador = (substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) ? ';' : ',';
        
        // Leer encabezados
        $encabezados = fgetcsv($handle, 0, $delimitador);
        $mapeoColumnas = $this->mapearColumnas($encabezados);
        
        $indice = 1;
        while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
            $indice++;
            if ($this->filaVacia($fila)) continue;
            
            $datos[] = [
                'codigo_serie' => trim($fila[$mapeoColumnas['codigo_serie']] ?? ''),
                'nombre_serie' => trim($fila[$mapeoColumnas['nombre_serie']] ?? ''),
                'codigo_subserie' => trim($fila[$mapeoColumnas['codigo_subserie']] ?? ''),
                'nombre_subserie' => trim($fila[$mapeoColumnas['nombre_subserie']] ?? ''),
                'soporte_fisico' => $this->interpretarBooleano($fila[$mapeoColumnas['soporte_fisico']] ?? ''),
                'soporte_electronico' => $this->interpretarBooleano($fila[$mapeoColumnas['soporte_electronico']] ?? ''),
                'retencion_gestion' => intval($fila[$mapeoColumnas['retencion_gestion']] ?? 0),
                'retencion_central' => intval($fila[$mapeoColumnas['retencion_central']] ?? 0),
                'disposicion_final' => strtoupper(trim($fila[$mapeoColumnas['disposicion_final']] ?? '')),
                'procedimiento' => trim($fila[$mapeoColumnas['procedimiento']] ?? ''),
                'fila_original' => $indice,
            ];
        }
        
        fclose($handle);
        return $datos;
    }
    
    /**
     * Mapear columnas del archivo a campos esperados
     */
    private function mapearColumnas(array $encabezados): array
    {
        $mapeo = [
            'codigo_serie' => 0,
            'nombre_serie' => 1,
            'codigo_subserie' => 2,
            'nombre_subserie' => 3,
            'soporte_fisico' => 4,
            'soporte_electronico' => 5,
            'retencion_gestion' => 6,
            'retencion_central' => 7,
            'disposicion_final' => 8,
            'procedimiento' => 9,
        ];
        
        // Buscar columnas por nombre
        foreach ($encabezados as $indice => $encabezado) {
            $encabezadoLimpio = strtolower(trim($encabezado ?? ''));
            
            if (str_contains($encabezadoLimpio, 'código serie') || str_contains($encabezadoLimpio, 'codigo serie') || $encabezadoLimpio === 'código' || $encabezadoLimpio === 'codigo') {
                $mapeo['codigo_serie'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'serie') && !str_contains($encabezadoLimpio, 'subserie') && !str_contains($encabezadoLimpio, 'código')) {
                $mapeo['nombre_serie'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'código subserie') || str_contains($encabezadoLimpio, 'codigo subserie')) {
                $mapeo['codigo_subserie'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'subserie') && !str_contains($encabezadoLimpio, 'código')) {
                $mapeo['nombre_subserie'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'físico') || str_contains($encabezadoLimpio, 'fisico') || $encabezadoLimpio === 'f') {
                $mapeo['soporte_fisico'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'electrónico') || str_contains($encabezadoLimpio, 'electronico') || $encabezadoLimpio === 'e') {
                $mapeo['soporte_electronico'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'gestión') || str_contains($encabezadoLimpio, 'gestion') || str_contains($encabezadoLimpio, 'ag')) {
                $mapeo['retencion_gestion'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'central') || str_contains($encabezadoLimpio, 'ac')) {
                $mapeo['retencion_central'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'disposición') || str_contains($encabezadoLimpio, 'disposicion') || str_contains($encabezadoLimpio, 'ct') || str_contains($encabezadoLimpio, 'final')) {
                $mapeo['disposicion_final'] = $indice;
            } elseif (str_contains($encabezadoLimpio, 'procedimiento')) {
                $mapeo['procedimiento'] = $indice;
            }
        }
        
        return $mapeo;
    }
    
    /**
     * Verificar si una fila está vacía
     */
    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $celda) {
            if (!empty(trim($celda ?? ''))) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Interpretar valores booleanos de la plantilla
     */
    private function interpretarBooleano($valor): bool
    {
        if (is_bool($valor)) return $valor;
        
        $valorLimpio = strtolower(trim($valor ?? ''));
        return in_array($valorLimpio, ['x', 'si', 'sí', 'yes', '1', 'true', 'f', 'e', 'v', '✓', '✔']);
    }
    
    /**
     * Procesar datos de importación y crear series/subseries
     */
    private function procesarDatosImportacion(TablaRetencionDocumental $trd, array $datos): array
    {
        $resultado = [
            'series_creadas' => 0,
            'subseries_creadas' => 0,
            'errores' => [],
        ];
        
        DB::beginTransaction();
        
        try {
            $seriesCreadas = [];
            
            foreach ($datos as $fila) {
                // Validar que tenga código y nombre de serie
                if (empty($fila['codigo_serie']) || empty($fila['nombre_serie'])) {
                    $resultado['errores'][] = "Fila {$fila['fila_original']}: Código y nombre de serie son requeridos.";
                    continue;
                }
                
                // Buscar o crear serie
                $claveSerieUnica = $trd->id . '-' . $fila['codigo_serie'];
                
                if (!isset($seriesCreadas[$claveSerieUnica])) {
                    // Verificar si la serie ya existe
                    $serie = SerieDocumental::where('trd_id', $trd->id)
                        ->where('codigo', $fila['codigo_serie'])
                        ->first();
                    
                    // Determinar disposición final basada en el valor (CT, E, D, S)
                    $disposicion = strtoupper($fila['disposicion_final'] ?? '');
                    
                    if (!$serie) {
                        $serie = SerieDocumental::create([
                            'trd_id' => $trd->id,
                            'codigo' => $fila['codigo_serie'],
                            'nombre' => $fila['nombre_serie'],
                            'descripcion' => $fila['nombre_serie'],
                            'soporte_fisico' => $fila['soporte_fisico'],
                            'soporte_electronico' => $fila['soporte_electronico'],
                            'retencion_gestion' => $fila['retencion_gestion'],
                            'retencion_central' => $fila['retencion_central'],
                            'disposicion_ct' => $disposicion === 'CT',
                            'disposicion_e' => $disposicion === 'E',
                            'disposicion_d' => $disposicion === 'D',
                            'disposicion_s' => $disposicion === 'S',
                            'procedimiento' => $fila['procedimiento'],
                            'activa' => true,
                        ]);
                        $resultado['series_creadas']++;
                    } else {
                        // Actualizar serie existente
                        $serie->update([
                            'soporte_fisico' => $fila['soporte_fisico'],
                            'soporte_electronico' => $fila['soporte_electronico'],
                            'retencion_gestion' => $fila['retencion_gestion'],
                            'retencion_central' => $fila['retencion_central'],
                            'disposicion_ct' => $disposicion === 'CT',
                            'disposicion_e' => $disposicion === 'E',
                            'disposicion_d' => $disposicion === 'D',
                            'disposicion_s' => $disposicion === 'S',
                            'procedimiento' => $fila['procedimiento'],
                        ]);
                    }
                    
                    $seriesCreadas[$claveSerieUnica] = $serie;
                }
                
                // Crear subserie si existe
                if (!empty($fila['codigo_subserie']) && !empty($fila['nombre_subserie'])) {
                    $serie = $seriesCreadas[$claveSerieUnica];
                    $disposicionSub = strtoupper($fila['disposicion_final'] ?? '');
                    
                    $subserieExiste = SubserieDocumental::where('serie_documental_id', $serie->id)
                        ->where('codigo', $fila['codigo_subserie'])
                        ->exists();
                    
                    if (!$subserieExiste) {
                        SubserieDocumental::create([
                            'serie_documental_id' => $serie->id,
                            'codigo' => $fila['codigo_subserie'],
                            'nombre' => $fila['nombre_subserie'],
                            'descripcion' => $fila['nombre_subserie'],
                            'soporte_fisico' => $fila['soporte_fisico'],
                            'soporte_electronico' => $fila['soporte_electronico'],
                            'retencion_gestion' => $fila['retencion_gestion'],
                            'retencion_central' => $fila['retencion_central'],
                            'disposicion_ct' => $disposicionSub === 'CT',
                            'disposicion_e' => $disposicionSub === 'E',
                            'disposicion_d' => $disposicionSub === 'D',
                            'disposicion_s' => $disposicionSub === 'S',
                            'procedimiento' => $fila['procedimiento'],
                            'activa' => true,
                        ]);
                        $resultado['subseries_creadas']++;
                    }
                }
            }
            
            DB::commit();
            return $resultado;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Descargar plantilla Excel para importación
     */
    public function descargarPlantilla()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla TRD');
        
        // Encabezados
        $encabezados = [
            'A1' => 'Código Serie',
            'B1' => 'Nombre Serie',
            'C1' => 'Código Subserie',
            'D1' => 'Nombre Subserie',
            'E1' => 'Soporte Físico (F)',
            'F1' => 'Soporte Electrónico (E)',
            'G1' => 'Retención Gestión (años)',
            'H1' => 'Retención Central (años)',
            'I1' => 'Disposición Final (CT/E/D/S)',
            'J1' => 'Procedimiento',
        ];
        
        foreach ($encabezados as $celda => $valor) {
            $sheet->setCellValue($celda, $valor);
        }
        
        // Estilos para encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Datos de ejemplo
        $ejemplos = [
            ['001', 'ACTAS', '001.01', 'Actas de Comité', 'X', 'X', 2, 8, 'CT', 'Conservación total por valor histórico'],
            ['001', 'ACTAS', '001.02', 'Actas de Reunión', 'X', '', 1, 5, 'S', 'Selección según criterio archivístico'],
            ['002', 'CONTRATOS', '', '', 'X', 'X', 3, 10, 'CT', 'Conservación total por valor legal'],
            ['003', 'CORRESPONDENCIA', '003.01', 'Correspondencia Enviada', '', 'X', 1, 4, 'E', 'Eliminación después del tiempo de retención'],
            ['003', 'CORRESPONDENCIA', '003.02', 'Correspondencia Recibida', 'X', 'X', 1, 4, 'D', 'Digitalización antes de eliminar'],
        ];
        
        $fila = 2;
        foreach ($ejemplos as $ejemplo) {
            $sheet->setCellValue("A{$fila}", $ejemplo[0]);
            $sheet->setCellValue("B{$fila}", $ejemplo[1]);
            $sheet->setCellValue("C{$fila}", $ejemplo[2]);
            $sheet->setCellValue("D{$fila}", $ejemplo[3]);
            $sheet->setCellValue("E{$fila}", $ejemplo[4]);
            $sheet->setCellValue("F{$fila}", $ejemplo[5]);
            $sheet->setCellValue("G{$fila}", $ejemplo[6]);
            $sheet->setCellValue("H{$fila}", $ejemplo[7]);
            $sheet->setCellValue("I{$fila}", $ejemplo[8]);
            $sheet->setCellValue("J{$fila}", $ejemplo[9]);
            $fila++;
        }
        
        // Ajustar anchos de columna
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Agregar hoja de instrucciones
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instrucciones');
        
        $instrucciones = [
            ['INSTRUCCIONES PARA IMPORTAR TRD'],
            [''],
            ['Columnas del archivo:'],
            [''],
            ['Código Serie: Código único de la serie (requerido)'],
            ['Nombre Serie: Nombre descriptivo de la serie (requerido)'],
            ['Código Subserie: Código de la subserie (opcional)'],
            ['Nombre Subserie: Nombre de la subserie (opcional)'],
            ['Soporte Físico (F): Marcar con X si tiene soporte físico'],
            ['Soporte Electrónico (E): Marcar con X si tiene soporte electrónico'],
            ['Retención Gestión: Años de retención en archivo de gestión (número)'],
            ['Retención Central: Años de retención en archivo central (número)'],
            ['Disposición Final: CT (Conservación Total), E (Eliminación), D (Digitalización), S (Selección)'],
            ['Procedimiento: Descripción del procedimiento de disposición final'],
            [''],
            ['NOTAS:'],
            ['- Las series sin subserie se registran dejando vacíos los campos de subserie'],
            ['- Los valores booleanos aceptan: X, Si, Sí, Yes, 1, V'],
            ['- El archivo puede ser .xlsx, .xls o .csv'],
        ];
        
        $fila = 1;
        foreach ($instrucciones as $linea) {
            $instructionsSheet->setCellValue("A{$fila}", $linea[0] ?? '');
            $fila++;
        }
        
        $instructionsSheet->getColumnDimension('A')->setWidth(80);
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Volver a la primera hoja
        $spreadsheet->setActiveSheetIndex(0);
        
        // Generar archivo
        $writer = new Xlsx($spreadsheet);
        $nombreArchivo = 'plantilla_trd_importacion.xlsx';
        $rutaTemporal = storage_path("app/temp/{$nombreArchivo}");
        
        // Asegurar que existe el directorio
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer->save($rutaTemporal);
        
        return response()->download($rutaTemporal, $nombreArchivo)->deleteFileAfterSend(true);
    }

    /**
     * Guardar o actualizar tiempos de retención para un nivel del CCD en la TRD
     */
    public function guardarTiempoRetencion(Request $request, TablaRetencionDocumental $trd)
    {
        $validated = $request->validate([
            'ccd_nivel_id' => 'required|exists:ccd_niveles,id',
            'retencion_archivo_gestion' => 'required|integer|min:0',
            'retencion_archivo_central' => 'required|integer|min:0',
            'disposicion_final' => 'required|in:CT,E,D,S,M',
            'soporte_fisico' => 'boolean',
            'soporte_electronico' => 'boolean',
            'soporte_hibrido' => 'boolean',
            'procedimiento' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $validated['trd_id'] = $trd->id;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        // Buscar si ya existe un tiempo de retención para este nivel
        $tiempoRetencion = \App\Models\TRDTiempoRetencion::updateOrCreate(
            [
                'trd_id' => $trd->id,
                'ccd_nivel_id' => $validated['ccd_nivel_id'],
            ],
            $validated
        );

        return back()->with('success', 'Tiempo de retención guardado exitosamente');
    }

    /**
     * Eliminar tiempo de retención de un nivel
     */
    public function eliminarTiempoRetencion(TablaRetencionDocumental $trd, $nivelId)
    {
        \App\Models\TRDTiempoRetencion::where('trd_id', $trd->id)
            ->where('ccd_nivel_id', $nivelId)
            ->delete();

        return back()->with('success', 'Tiempo de retención eliminado exitosamente');
    }
}
