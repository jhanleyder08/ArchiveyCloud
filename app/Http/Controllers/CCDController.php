<?php

namespace App\Http\Controllers;

use App\Models\CCD;
use App\Models\CCDNivel;
use App\Models\CCDVersion;
use App\Services\CCDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CCDController extends Controller
{
    protected $ccdService;

    public function __construct(CCDService $ccdService)
    {
        $this->ccdService = $ccdService;
    }

    /**
     * Mostrar listado de CCDs
     */
    public function index(Request $request): Response
    {
        $query = CCD::with(['creador', 'niveles', 'versiones' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(5);
        }])
            ->withCount(['niveles', 'versiones']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('codigo', 'like', '%' . $request->search . '%');
            });
        }

        $ccds = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        // Opciones para el formulario de creación
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
            'padres_disponibles' => [], // Se puede poblar con CCDs existentes si es necesario
        ];

        return Inertia::render('admin/ccd/index', [
            'ccds' => $ccds,
            'filters' => $request->only(['estado', 'search']),
            'estadisticas' => $this->getEstadisticasGenerales(),
            'opciones' => $opciones,
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
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
            'padres_disponibles' => [],
        ];

        return Inertia::render('admin/ccd/create', [
            'opciones' => $opciones,
        ]);
    }

    /**
     * Almacenar nuevo CCD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cuadros_clasificacion,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'vocabulario_controlado' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $ccd = $this->ccdService->crear($validated, $request->user());

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', 'CCD creado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear CCD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear CCD: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar CCD específico con estructura jerárquica
     */
    public function show(CCD $ccd): Response
    {
        $ccd->load([
            'niveles',
            'vocabularios',
            'versiones.modificador',
            'creador',
            'aprobador'
        ]);

        // Obtener TRDs relacionadas a través del campo ccd_id
        $trdsRelacionadas = \App\Models\TRD::where('ccd_id', $ccd->id)
            ->withCount('series')
            ->get()
            ->map(function($trd) {
                return [
                    'id' => $trd->id,
                    'codigo' => $trd->codigo,
                    'nombre' => $trd->nombre,
                    'version' => $trd->version,
                    'estado' => $trd->estado,
                    'series_count' => $trd->series_count,
                ];
            });

        // Obtener Series relacionadas (series de las TRDs que pertenecen a este CCD)
        // Usamos la tabla trds directamente ya que tiene el campo ccd_id
        $trdIds = \App\Models\TRD::where('ccd_id', $ccd->id)->pluck('id');
        
        $seriesRelacionadas = collect();
        if ($trdIds->isNotEmpty()) {
            $seriesRelacionadas = \App\Models\SerieDocumental::whereIn('trd_id', $trdIds)
                ->with('trd:id,nombre')
                ->withCount('subseries')
                ->get()
                ->map(function($serie) {
                    return [
                        'id' => $serie->id,
                        'codigo' => $serie->codigo,
                        'nombre' => $serie->nombre,
                        'trd_nombre' => $serie->trd->nombre ?? 'Sin TRD',
                        'subseries_count' => $serie->subseries_count,
                    ];
                });
        }

        return Inertia::render('admin/ccd/show', [
            'ccd' => $ccd,
            'estructura' => $this->ccdService->obtenerEstructuraJerarquica($ccd),
            'estadisticas' => $ccd->getEstadisticas(),
            'errores_validacion' => $ccd->validar(),
            'trds_relacionadas' => $trdsRelacionadas,
            'series_relacionadas' => $seriesRelacionadas,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(CCD $ccd): Response
    {
        $opciones = [
            'estados' => [
                ['value' => 'borrador', 'label' => 'Borrador'],
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
                ['value' => 'archivado', 'label' => 'Archivado'],
            ],
            'niveles' => [
                ['value' => '1', 'label' => 'Nivel 1 - Fondo'],
                ['value' => '2', 'label' => 'Nivel 2 - Sección'],
                ['value' => '3', 'label' => 'Nivel 3 - Subsección'],
                ['value' => '4', 'label' => 'Nivel 4 - Serie'],
                ['value' => '5', 'label' => 'Nivel 5 - Subserie'],
            ],
            'padres_disponibles' => [],
        ];

        return Inertia::render('admin/ccd/edit', [
            'ccd' => $ccd,
            'opciones' => $opciones,
        ]);
    }

    /**
     * Actualizar CCD
     */
    public function update(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cuadros_clasificacion,codigo,' . $ccd->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'vocabulario_controlado' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $this->ccdService->actualizar($ccd, $validated, $request->user());

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'CCD actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar CCD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar CCD
     */
    public function aprobar(CCD $ccd, Request $request)
    {
        try {
            // Validar estructura antes de aprobar
            $errores = $ccd->validar();
            if (!empty($errores)) {
                return back()->with('error', 'No se puede aprobar el CCD: ' . implode(', ', $errores));
            }

            $this->ccdService->aprobar($ccd, $request->user());

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'CCD aprobado exitosamente. Se ha generado automáticamente la TRD con sus Series y Subseries.');
        } catch (\Exception $e) {
            Log::error('Error al aprobar CCD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al aprobar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Archivar CCD
     */
    public function archivar(CCD $ccd)
    {
        try {
            $ccd->archivar();

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', 'CCD archivado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al archivar CCD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al archivar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:20',
            'cambios' => 'required|string',
        ]);

        try {
            $this->ccdService->crearVersion(
                $ccd,
                $validated['version'],
                $validated['cambios'],
                $request->user()
            );

            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', 'Nueva versión creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al crear versión: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar una versión del historial
     */
    public function eliminarVersion(CCDVersion $version)
    {
        try {
            $ccd = $version->ccd;
            $versionEliminada = $version->version_nueva;
            $versionAnterior = $version->version_anterior;
            
            // Si es la versión más reciente (la actual del CCD), revertir a la anterior
            if ($ccd->version === $versionEliminada) {
                $ccd->update([
                    'version' => $versionAnterior,
                    'estado' => 'borrador', // Vuelve a borrador para revisión
                ]);
            }
            
            $version->delete();
            
            Log::info('Versión eliminada', [
                'ccd_id' => $ccd->id,
                'version_eliminada' => $versionEliminada,
                'user_id' => request()->user()->id,
            ]);
            
            return back()->with('success', "Versión {$versionEliminada} eliminada exitosamente");
        } catch (\Exception $e) {
            Log::error('Error al eliminar versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar versión: ' . $e->getMessage());
        }
    }

    /**
     * Revertir a una versión anterior
     */
    public function revertirVersion(CCD $ccd, CCDVersion $version)
    {
        try {
            // Verificar que la versión pertenece al CCD
            if ($version->ccd_id !== $ccd->id) {
                return back()->with('error', 'La versión no pertenece a este CCD');
            }
            
            // Guardar la versión actual antes de revertir
            CCDVersion::create([
                'ccd_id' => $ccd->id,
                'version_anterior' => $ccd->version,
                'version_nueva' => $version->version_anterior,
                'datos_anteriores' => [
                    'codigo' => $ccd->codigo,
                    'nombre' => $ccd->nombre,
                    'descripcion' => $ccd->descripcion,
                    'estado' => $ccd->estado,
                ],
                'cambios' => "Reversión a versión {$version->version_anterior}",
                'modificado_por' => request()->user()->id,
                'fecha_cambio' => now(),
            ]);
            
            // Revertir el CCD a la versión anterior
            $ccd->update([
                'version' => $version->version_anterior,
                'estado' => 'borrador', // Requiere nueva aprobación
                'fecha_aprobacion' => null,
                'aprobado_por' => null,
            ]);
            
            Log::info('CCD revertido a versión anterior', [
                'ccd_id' => $ccd->id,
                'version_revertida' => $version->version_anterior,
                'user_id' => request()->user()->id,
            ]);
            
            return redirect()
                ->route('admin.ccd.show', $ccd->id)
                ->with('success', "CCD revertido a versión {$version->version_anterior}");
        } catch (\Exception $e) {
            Log::error('Error al revertir versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al revertir versión: ' . $e->getMessage());
        }
    }

    /**
     * Agregar nivel al CCD
     */
    public function agregarNivel(Request $request, CCD $ccd)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:ccd_niveles,id',
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_nivel' => 'required|string|in:fondo,seccion,subseccion,serie,subserie',
            'orden' => 'nullable|integer',
            'palabras_clave' => 'nullable|array',
        ]);

        try {
            $nivel = $this->ccdService->agregarNivel($ccd, $validated);

            return back()->with([
                'success' => 'Nivel agregado exitosamente',
                'nivel' => $nivel->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar nivel', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al agregar nivel: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar nivel
     */
    public function actualizarNivel(Request $request, CCDNivel $nivel)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo_nivel' => 'required|string|in:fondo,seccion,subseccion,serie,subserie',
            'orden' => 'nullable|integer',
            'activo' => 'nullable|boolean',
            'palabras_clave' => 'nullable|array',
        ]);

        try {
            $nivel->update($validated);
            $nivel->actualizarRuta();

            return back()->with([
                'success' => 'Nivel actualizado exitosamente',
                'nivel' => $nivel->fresh()->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar nivel', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al actualizar nivel: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar nivel
     */
    public function eliminarNivel($nivelId)
    {
        try {
            $nivel = CCDNivel::find($nivelId);
            
            if (!$nivel) {
                return back()->with('error', 'El nivel no existe o ya fue eliminado');
            }

            if (!$nivel->esHoja()) {
                return back()->with('error', 'No se puede eliminar un nivel que tiene hijos');
            }

            $nivel->delete();

            return back()->with('success', 'Nivel eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar nivel', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar nivel: ' . $e->getMessage());
        }
    }

    /**
     * Mover nivel
     */
    public function moverNivel(Request $request, CCDNivel $nivel)
    {
        $validated = $request->validate([
            'nuevo_padre_id' => 'nullable|exists:ccd_niveles,id',
            'orden' => 'required|integer',
        ]);

        try {
            $nuevoPadre = $validated['nuevo_padre_id'] 
                ? CCDNivel::find($validated['nuevo_padre_id']) 
                : null;

            if (!$nivel->moverA($nuevoPadre)) {
                return back()->with('error', 'No se puede mover el nivel a esa posición');
            }

            $nivel->orden = $validated['orden'];
            $nivel->save();

            return back()->with([
                'success' => 'Nivel movido exitosamente',
                'nivel' => $nivel->fresh()->load('padre'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mover nivel', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al mover nivel: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estructura jerárquica completa
     */
    public function getEstructura(CCD $ccd, Request $request)
    {
        try {
            $estructura = $this->ccdService->obtenerEstructuraJerarquica($ccd);

            // Si es una petición Inertia, usar back()
            if ($request->hasHeader('X-Inertia')) {
                return back()->with([
                    'success' => 'Estructura obtenida exitosamente',
                    'estructura' => $estructura,
                ]);
            }

            // Si es AJAX/API, devolver JSON
            return response()->json([
                'success' => true,
                'estructura' => $estructura,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estructura', ['error' => $e->getMessage()]);
            
            // Si es una petición Inertia, usar back()
            if ($request->hasHeader('X-Inertia')) {
                return back()->with('error', 'Error al obtener estructura: ' . $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estructura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar CCD (con eliminación en cascada de niveles)
     */
    public function destroy(CCD $ccd)
    {
        try {
            DB::beginTransaction();

            // Contar niveles antes de eliminar
            $nivelesCount = $ccd->niveles()->count();
            
            // Eliminar todos los niveles asociados (la migración ya tiene onDelete cascade)
            // Pero por seguridad, los eliminamos explícitamente
            $ccd->niveles()->delete();
            
            // Eliminar vocabularios asociados
            $ccd->vocabularios()->delete();
            
            // Eliminar versiones
            $ccd->versiones()->delete();
            
            // Eliminar importaciones/exportaciones
            $ccd->importaciones()->delete();
            
            // Finalmente eliminar el CCD
            $ccd->delete();

            DB::commit();

            $mensaje = $nivelesCount > 0 
                ? "CCD y {$nivelesCount} nivel(es) asociado(s) eliminados exitosamente"
                : 'CCD eliminado exitosamente';

            return redirect()
                ->route('admin.ccd.index')
                ->with('success', $mensaje);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar CCD', [
                'ccd_id' => $ccd->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error al eliminar CCD: ' . $e->getMessage());
        }
    }

    /**
     * Exportar CCD en diferentes formatos
     */
    public function exportar(CCD $ccd, Request $request)
    {
        $formato = $request->get('formato', 'json');
        
        // Cargar estructura completa
        $ccd->load(['niveles' => function($query) {
            $query->orderBy('nivel')->orderBy('orden');
        }, 'creador', 'aprobador']);
        
        $estructura = $this->ccdService->obtenerEstructuraJerarquica($ccd);
        
        $data = [
            'ccd' => [
                'codigo' => $ccd->codigo,
                'nombre' => $ccd->nombre,
                'descripcion' => $ccd->descripcion,
                'version' => $ccd->version,
                'estado' => $ccd->estado,
                'fecha_aprobacion' => $ccd->fecha_aprobacion?->format('Y-m-d'),
                'fecha_vigencia_inicio' => $ccd->fecha_vigencia_inicio?->format('Y-m-d'),
                'fecha_vigencia_fin' => $ccd->fecha_vigencia_fin?->format('Y-m-d'),
                'creador' => $ccd->creador?->name,
                'aprobador' => $ccd->aprobador?->name,
            ],
            'estructura' => $estructura,
            'exportado_en' => now()->toISOString(),
        ];
        
        $filename = "CCD_{$ccd->codigo}_v{$ccd->version}_" . now()->format('Ymd_His');
        
        switch ($formato) {
            case 'json':
                return response()->json($data, 200, [
                    'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                return $this->exportarCSV($ccd, $estructura, $filename);
                
            case 'excel':
                return $this->exportarExcel($ccd, $estructura, $filename);
                
            case 'pdf':
                return $this->exportarPDF($ccd, $estructura, $filename);
                
            default:
                return response()->json(['error' => 'Formato no soportado'], 400);
        }
    }
    
    /**
     * Exportar a CSV
     */
    private function exportarCSV(CCD $ccd, array $estructura, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];
        
        $callback = function() use ($ccd, $estructura) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezado del CCD
            fputcsv($file, ['CUADRO DE CLASIFICACIÓN DOCUMENTAL']);
            fputcsv($file, ['Código', $ccd->codigo]);
            fputcsv($file, ['Nombre', $ccd->nombre]);
            fputcsv($file, ['Versión', $ccd->version]);
            fputcsv($file, ['Estado', $ccd->estado]);
            fputcsv($file, ['']);
            
            // Encabezados de estructura
            fputcsv($file, ['Nivel', 'Código', 'Nombre', 'Tipo', 'Descripción', 'Ruta']);
            
            // Función recursiva para aplanar estructura
            $this->escribirNivelesCSV($file, $estructura, 0);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Escribir niveles recursivamente en CSV
     */
    private function escribirNivelesCSV($file, array $niveles, int $profundidad): void
    {
        foreach ($niveles as $nivel) {
            $indentacion = str_repeat('  ', $profundidad);
            fputcsv($file, [
                $profundidad + 1,
                $nivel['codigo'],
                $indentacion . $nivel['nombre'],
                $nivel['tipo_nivel'],
                $nivel['descripcion'] ?? '',
                $nivel['ruta'] ?? '',
            ]);
            
            if (!empty($nivel['hijos'])) {
                $this->escribirNivelesCSV($file, $nivel['hijos'], $profundidad + 1);
            }
        }
    }
    
    /**
     * Exportar a Excel (XLSX)
     */
    private function exportarExcel(CCD $ccd, array $estructura, string $filename)
    {
        // Verificar si PhpSpreadsheet está disponible
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            // Fallback a CSV si no está disponible
            return $this->exportarCSV($ccd, $estructura, $filename);
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CCD');
        
        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2a3d83']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
        ];
        
        // Información del CCD
        $sheet->setCellValue('A1', 'CUADRO DE CLASIFICACIÓN DOCUMENTAL');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16]]);
        
        $sheet->setCellValue('A3', 'Código:');
        $sheet->setCellValue('B3', $ccd->codigo);
        $sheet->setCellValue('A4', 'Nombre:');
        $sheet->setCellValue('B4', $ccd->nombre);
        $sheet->setCellValue('A5', 'Versión:');
        $sheet->setCellValue('B5', $ccd->version);
        $sheet->setCellValue('A6', 'Estado:');
        $sheet->setCellValue('B6', $ccd->estado);
        
        // Encabezados de estructura
        $row = 8;
        $headers = ['Nivel', 'Código', 'Nombre', 'Tipo', 'Descripción', 'Estado'];
        $sheet->fromArray($headers, null, 'A' . $row);
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($headerStyle);
        
        // Datos de estructura
        $row = 9;
        $this->escribirNivelesExcel($sheet, $estructura, $row, 0);
        
        // Autoajustar columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Generar archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
        ];
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, "{$filename}.xlsx", $headers);
    }
    
    /**
     * Escribir niveles recursivamente en Excel
     */
    private function escribirNivelesExcel($sheet, array $niveles, int &$row, int $profundidad): void
    {
        $tipoColors = [
            'fondo' => 'E3F2FD',
            'seccion' => 'E8F5E9',
            'subseccion' => 'FFF8E1',
            'serie' => 'F3E5F5',
            'subserie' => 'FCE4EC',
        ];
        
        $columns = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        foreach ($niveles as $nivel) {
            $indentacion = str_repeat('    ', $profundidad);
            
            $sheet->setCellValue($columns[0] . $row, $profundidad + 1);
            $sheet->setCellValue($columns[1] . $row, $nivel['codigo']);
            $sheet->setCellValue($columns[2] . $row, $indentacion . $nivel['nombre']);
            $sheet->setCellValue($columns[3] . $row, ucfirst($nivel['tipo_nivel']));
            $sheet->setCellValue($columns[4] . $row, $nivel['descripcion'] ?? '');
            $sheet->setCellValue($columns[5] . $row, $nivel['activo'] ? 'Activo' : 'Inactivo');
            
            // Color de fondo según tipo
            $color = $tipoColors[$nivel['tipo_nivel']] ?? 'FFFFFF';
            $sheet->getStyle("A{$row}:F{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($color);
            
            $row++;
            
            if (!empty($nivel['hijos'])) {
                $this->escribirNivelesExcel($sheet, $nivel['hijos'], $row, $profundidad + 1);
            }
        }
    }
    
    /**
     * Exportar a PDF
     */
    private function exportarPDF(CCD $ccd, array $estructura, string $filename)
    {
        // Generar HTML para el PDF
        $html = view('exports.ccd-pdf', [
            'ccd' => $ccd,
            'estructura' => $estructura,
            'fecha_exportacion' => now()->format('d/m/Y H:i'),
        ])->render();
        
        // Verificar si Dompdf está disponible
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            return $pdf->download("{$filename}.pdf");
        }
        
        // Fallback: devolver HTML si no hay PDF disponible
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"{$filename}.html\"",
        ]);
    }

    /**
     * Obtener estadísticas generales
     */
    private function getEstadisticasGenerales(): array
    {
        return [
            'total' => CCD::count(),
            'activos' => CCD::where('estado', 'activo')->count(),
            'borradores' => CCD::where('estado', 'borrador')->count(),
            'vigentes' => CCD::where('estado', 'activo')
                ->whereDate('fecha_vigencia_inicio', '<=', now())
                ->where(function($q) {
                    $q->whereNull('fecha_vigencia_fin')
                      ->orWhereDate('fecha_vigencia_fin', '>=', now());
                })
                ->count(),
        ];
    }

    /**
     * Importar series y subseries desde Excel
     */
    public function importarExcel(Request $request, CCD $ccd)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $import = new \App\Imports\CCDImport($ccd->id);
            $import->import($request->file('file')->getRealPath());

            $stats = $import->getStats();

            // Si hay errores de validación, revertir transacción
            if (!empty($stats['errores'])) {
                return back()->with([
                    'error' => 'Se encontraron errores en el archivo Excel',
                    'errores_importacion' => $stats['errores'],
                ]);
            }

            return back()->with([
                'success' => sprintf(
                    'Importación completada: %d series y %d subseries creadas',
                    $stats['series_creadas'],
                    $stats['subseries_creadas']
                ),
                'stats_importacion' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en importación Excel CCD', [
                'ccd_id' => $ccd->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al importar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Descargar plantilla Excel para importación
     */
    public function descargarPlantilla()
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('CCD Importacion');

            // Estilos para encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2a3d83']
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];

            // Instrucciones
            $sheet->setCellValue('A1', 'PLANTILLA DE IMPORTACION - CUADRO DE CLASIFICACION DOCUMENTAL');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

            $sheet->setCellValue('A2', 'INSTRUCCIONES: Complete los datos a partir de la fila 4. Puede omitir SUBSECCION si la SERIE pertenece directamente a la SECCION');

            // Encabezados de columnas (fila 3)
            $headers = [
                'No.',
                'SECCIÓN',
                'CÓDIGO',
                'SUBSECCION',
                'CÓDIGO',
                'SERIE',
                'CÓDIGO',
                'SUBSERIE',
                'CÓDIGO'
            ];

            // Encabezados en la fila 3
            $sheet->fromArray($headers, null, 'A3');
            $sheet->getStyle('A3:I3')->applyFromArray($headerStyle);

            // Datos de ejemplo - Basados en el formato real
            $ejemplos = [
                // GERENCIA GENERAL sin subsección - Series directas
                [1, 'GERENCIA GENERAL', '100', '', '', 'ACTAS', '2', 'Actas de Comité Directivo', '19'],
                [2, 'GERENCIA GENERAL', '100', '', '', 'ACTAS', '2', 'Actas de Junta Directiva', '31'],
                [3, 'GERENCIA GENERAL', '100', '', '', 'ACTOS', '3', 'Acuerdos', '1'],
                [4, 'GERENCIA GENERAL', '100', '', '', 'CIRCULARES', '9', 'Circulares Dispositivas', '1'],
                [5, 'GERENCIA GENERAL', '100', '', '', 'INFORMES', '26', 'Informes de Gestión', '10'],
                // GERENCIA GENERAL con subsección OFICINA ASESORA DE PLANEACIÓN
                [6, 'GERENCIA GENERAL', '100', 'OFICINA ASESORA DE PLANEACIÓN', '101', 'ACTAS', '2', 'Actas de Comité Institucional de Gestión', '22'],
                [7, 'GERENCIA GENERAL', '100', 'OFICINA ASESORA DE PLANEACIÓN', '101', 'INFORMES', '26', 'Informes de Gestión de Indicadores', '11'],
                [8, 'GERENCIA GENERAL', '100', 'OFICINA ASESORA DE PLANEACIÓN', '101', 'PLANES', '33', 'Plan de Desarrollo Institucional', '8'],
                [9, 'GERENCIA GENERAL', '100', 'OFICINA ASESORA DE PLANEACIÓN', '101', 'PLANES', '33', 'Plan de Operativo Anual Institucional', '15'],
            ];

            // Insertar datos de ejemplo
            $sheet->fromArray($ejemplos, null, 'A4');

            // Autoajustar columnas
            foreach (range('A', 'I') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Congelar paneles (después de los encabezados)
            $sheet->freezePane('A4');

            // Generar archivo en memoria
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            $filename = 'Plantilla_CCD_Importacion_' . now()->format('Ymd') . '.xlsx';

            // Usar streamDownload para evitar problemas con archivos temporales
            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar plantilla Excel', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar plantilla: ' . $e->getMessage());
        }
    }
}
