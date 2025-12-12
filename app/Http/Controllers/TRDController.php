<?php

namespace App\Http\Controllers;

use App\Models\TRD;
use App\Models\CCD;
use App\Services\TRDService;
use App\Services\TRDImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TRDController extends Controller
{
    protected $trdService;
    protected $importService;

    public function __construct(TRDService $trdService, TRDImportService $importService)
    {
        $this->trdService = $trdService;
        $this->importService = $importService;
    }

    /**
     * Mostrar listado de TRDs
     */
    public function index(Request $request): Response
    {
        $query = TRD::with(['creador', 'series', 'cuadroClasificacion'])
            ->withCount('series');

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

        $trds = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        // Obtener TODOS los CCDs disponibles (sin filtro de estado)
        $ccds = CCD::orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'version', 'estado']);

        return Inertia::render('admin/trd/index', [
            'trds' => $trds,
            'ccds' => $ccds,
            'filters' => $request->only(['estado', 'search']),
            'estadisticas' => $this->trdService->obtenerEstadisticasGenerales(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        // Obtener TODOS los CCDs disponibles (sin filtro de estado)
        $ccds = CCD::orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'version', 'estado']);

        return Inertia::render('admin/trd/create', [
            'ccds' => $ccds,
        ]);
    }

    /**
     * Almacenar nueva TRD
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:trds,codigo',
            'ccd_id' => 'required|exists:cuadros_clasificacion,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            // Campos del formato oficial FOR-GDI-GDO-002
            'codigo_unidad_administrativa' => 'nullable|string|max:20',
            'nombre_unidad_administrativa' => 'nullable|string|max:255',
            'codigo_dependencia' => 'nullable|string|max:20',
            'nombre_dependencia' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:20',
            'fecha_aprobacion' => 'nullable|date',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'metadata' => 'nullable|array',
        ], [
            'ccd_id.required' => 'Debe seleccionar un Cuadro de Clasificación Documental (CCD)',
            'ccd_id.exists' => 'El CCD seleccionado no existe',
        ]);

        try {
            $trd = $this->trdService->crear($validated, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear TRD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al crear TRD: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar TRD específica
     */
    public function show(TRD $trd): Response
    {
        $trd->load([
            'series.subseries',
            'series.retenciones',
            'versiones.modificador',
            'creador',
            'aprobador'
        ]);

        return Inertia::render('admin/trd/show', [
            'trd' => $trd,
            'estadisticas' => $trd->getEstadisticas(),
            'errores_validacion' => $this->trdService->validar($trd),
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(TRD $trd): Response
    {
        return Inertia::render('admin/trd/edit', [
            'trd' => $trd,
        ]);
    }

    /**
     * Actualizar TRD
     */
    public function update(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:trds,codigo,' . $trd->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            // Campos del formato oficial FOR-GDI-GDO-002
            'codigo_unidad_administrativa' => 'nullable|string|max:20',
            'nombre_unidad_administrativa' => 'nullable|string|max:255',
            'codigo_dependencia' => 'nullable|string|max:20',
            'nombre_dependencia' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:20',
            'fecha_aprobacion' => 'nullable|date',
            'fecha_vigencia_inicio' => 'nullable|date',
            'fecha_vigencia_fin' => 'nullable|date|after:fecha_vigencia_inicio',
            'metadata' => 'nullable|array',
        ]);

        try {
            $trd = $this->trdService->actualizar($trd, $validated, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar TRD', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar TRD
     */
    public function aprobar(TRD $trd, Request $request)
    {
        try {
            // Validar estructura antes de aprobar
            $errores = $this->trdService->validar($trd);
            if (!empty($errores)) {
                return back()->with('error', 'No se puede aprobar la TRD: ' . implode(', ', $errores));
            }

            $trd = $this->trdService->aprobar($trd, $request->user());

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'TRD aprobada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al aprobar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al aprobar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Archivar TRD
     */
    public function archivar(TRD $trd)
    {
        try {
            $trd->archivar();

            return redirect()
                ->route('admin.trd.index')
                ->with('success', 'TRD archivada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al archivar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al archivar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:20',
            'cambios' => 'required|string',
        ]);

        try {
            $this->trdService->crearVersion(
                $trd,
                $validated['version'],
                $validated['cambios'],
                $request->user()
            );

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', 'Nueva versión creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear versión', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al crear versión: ' . $e->getMessage());
        }
    }

    /**
     * Agregar serie a TRD
     */
    public function agregarSerie(Request $request, TRD $trd)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'dependencia' => 'nullable|string|max:255',
            'orden' => 'nullable|integer',
        ]);

        try {
            $serie = $this->trdService->agregarSerie($trd, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Serie agregada exitosamente',
                'serie' => $serie,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar serie', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar serie: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importar TRD desde XML
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xml|max:10240', // 10MB max
        ]);

        try {
            $archivo = $request->file('archivo');
            $ruta = $archivo->store('trd_importaciones', 'local');

            $importacion = $this->trdService->importarDesdeXML(
                storage_path('app/' . $ruta),
                $request->user()
            );

            return redirect()
                ->route('admin.trd.show', $importacion->trd_id)
                ->with('success', 'TRD importada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al importar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al importar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Exportar TRD a XML
     */
    public function exportar(TRD $trd)
    {
        try {
            $xml = $this->trdService->exportarAXML($trd);

            $nombreArchivo = 'TRD_' . $trd->codigo . '_v' . $trd->version . '.xml';

            return response($xml, 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
        } catch (\Exception $e) {
            Log::error('Error al exportar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al exportar TRD: ' . $e->getMessage());
        }
    }

    /**
     * Exportar TRD a PDF con formato oficial FOR-GDI-GDO-002
     * Basado en el formato del Hospital Universitario del Valle
     */
    public function exportarPDF(TRD $trd)
    {
        try {
            // Cargar relaciones necesarias
            $trd->load([
                'series.subseries.tiposDocumentales',
                'series.tiposDocumentales',
                'series.retenciones',
                'series.subseries.retenciones',
            ]);

            $pdf = Pdf::loadView('pdf.trd-formato-oficial', [
                'trd' => $trd
            ]);
            
            // Configurar página horizontal para mejor visualización de la tabla
            $pdf->setPaper('letter', 'landscape');
            
            $nombreArchivo = 'TRD_' . $trd->codigo . '_v' . str_pad($trd->version, 2, '0', STR_PAD_LEFT) . '.pdf';

            return $pdf->download($nombreArchivo);
        } catch (\Exception $e) {
            Log::error('Error al exportar TRD a PDF', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Importar series desde Excel/CSV a una TRD existente
     */
    public function importarSeries(Request $request, TRD $trd)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'archivo.required' => 'Debe seleccionar un archivo',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
            'archivo.max' => 'El archivo no puede superar 10MB',
        ]);

        try {
            $archivo = $request->file('archivo');
            $extension = strtolower($archivo->getClientOriginalExtension());

            if ($extension === 'csv') {
                $results = $this->importService->importFromCSV($archivo, $trd);
            } else {
                $results = $this->importService->importFromExcel($archivo, $trd);
            }

            $mensaje = "Importación completada: {$results['series_creadas']} series y {$results['subseries_creadas']} subseries creadas.";
            
            if (!empty($results['errores'])) {
                $mensaje .= " Se encontraron " . count($results['errores']) . " errores.";
                Log::warning('Errores en importación TRD', ['errores' => $results['errores']]);
            }

            return redirect()
                ->route('admin.trd.show', $trd->id)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al importar series', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    /**
     * Descargar plantilla Excel para importación
     */
    public function descargarPlantilla()
    {
        try {
            $spreadsheet = $this->importService->generateTemplate();
            
            $writer = new Xlsx($spreadsheet);
            $filename = 'Plantilla_TRD_Importacion.xlsx';
            
            $temp = tempnam(sys_get_temp_dir(), 'trd');
            $writer->save($temp);
            
            return response()->download($temp, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Error al generar plantilla', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar TRD
     */
    public function destroy(TRD $trd)
    {
        // Solo se puede eliminar si está en borrador y no tiene datos asociados
        if ($trd->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar TRDs en estado borrador');
        }

        if ($trd->series()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una TRD que tiene series asociadas');
        }

        try {
            $trd->delete();

            return redirect()
                ->route('admin.trd.index')
                ->with('success', 'TRD eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar TRD', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar TRD: ' . $e->getMessage());
        }
    }
}
