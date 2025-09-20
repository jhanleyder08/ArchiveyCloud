<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndiceElectronico;
use App\Models\Expediente;
use App\Models\Documento;
use App\Services\IndiceElectronicoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndiceElectronicoController extends Controller
{
    protected $indiceService;

    public function __construct(IndiceElectronicoService $indiceService)
    {
        $this->middleware('auth');
        $this->middleware('verified');
        $this->indiceService = $indiceService;
    }

    /**
     * Dashboard principal de índices electrónicos
     */
    public function index(Request $request)
    {
        // Filtros de búsqueda
        $filtros = $request->only([
            'busqueda_texto',
            'tipo_entidad',
            'serie_documental',
            'nivel_acceso',
            'fecha_inicio',
            'fecha_fin',
            'palabras_clave',
            'solo_vitales',
            'solo_historicos',
            'orden_por',
            'direccion'
        ]);

        // Obtener índices paginados
        $indices = $this->indiceService->busquedaAvanzada($filtros);

        // Obtener estadísticas
        $estadisticas = $this->indiceService->obtenerEstadisticas();

        // Opciones para filtros
        $opcionesFiltros = [
            'tipos_entidad' => ['expediente' => 'Expedientes', 'documento' => 'Documentos'],
            'niveles_acceso' => [
                'publico' => 'Público',
                'restringido' => 'Restringido', 
                'confidencial' => 'Confidencial',
                'secreto' => 'Secreto'
            ],
            'series_documentales' => IndiceElectronico::selectRaw('DISTINCT serie_documental')
                ->whereNotNull('serie_documental')
                ->orderBy('serie_documental')
                ->pluck('serie_documental')
                ->toArray(),
            'estados_conservacion' => [
                'excelente' => 'Excelente',
                'bueno' => 'Bueno',
                'regular' => 'Regular',
                'malo' => 'Malo',
                'critico' => 'Crítico'
            ]
        ];

        return Inertia::render('admin/indices/index', [
            'indices' => $indices,
            'estadisticas' => $estadisticas,
            'filtros' => $filtros,
            'opcionesFiltros' => $opcionesFiltros,
        ]);
    }

    /**
     * Mostrar detalles de un índice específico
     */
    public function show(IndiceElectronico $indice)
    {
        $indice->load(['usuarioIndexacion', 'usuarioActualizacion']);

        // Obtener la entidad relacionada
        $entidadRelacionada = null;
        switch ($indice->tipo_entidad) {
            case 'expediente':
                $entidadRelacionada = Expediente::with(['serie', 'subserie', 'usuarioResponsable', 'documentos'])
                    ->find($indice->entidad_id);
                break;
            case 'documento':
                $entidadRelacionada = Documento::with(['expediente.serie', 'expediente.subserie'])
                    ->find($indice->entidad_id);
                break;
        }

        return Inertia::render('admin/indices/show', [
            'indice' => [
                'id' => $indice->id,
                'tipo_entidad' => $indice->tipo_entidad,
                'entidad_id' => $indice->entidad_id,
                'codigo_clasificacion' => $indice->codigo_clasificacion,
                'titulo' => $indice->titulo,
                'descripcion' => $indice->descripcion,
                'metadatos' => $indice->metadatos,
                'palabras_clave' => $indice->palabras_clave,
                'serie_documental' => $indice->serie_documental,
                'subserie_documental' => $indice->subserie_documental,
                'fecha_inicio' => $indice->fecha_inicio,
                'fecha_fin' => $indice->fecha_fin,
                'responsable' => $indice->responsable,
                'ubicacion_fisica' => $indice->ubicacion_fisica,
                'ubicacion_digital' => $indice->ubicacion_digital,
                'nivel_acceso' => $indice->nivel_acceso,
                'estado_conservacion' => $indice->estado_conservacion,
                'cantidad_folios' => $indice->cantidad_folios,
                'formato_archivo' => $indice->formato_archivo,
                'tamaño_bytes' => $indice->tamaño_bytes,
                'hash_integridad' => $indice->hash_integridad,
                'es_vital' => $indice->es_vital,
                'es_historico' => $indice->es_historico,
                'fecha_indexacion' => $indice->fecha_indexacion,
                'fecha_ultima_actualizacion' => $indice->fecha_ultima_actualizacion,
                'usuario_indexacion' => $indice->usuarioIndexacion,
                'usuario_actualizacion' => $indice->usuarioActualizacion,
                // Métodos calculados
                'tamaño_formateado' => $indice->getTamaño(),
                'etiqueta_nivel_acceso' => $indice->getEtiquetaNivelAcceso(),
                'etiqueta_estado_conservacion' => $indice->getEtiquetaEstadoConservacion(),
                'codigo_completo' => $indice->getCodigoCompleto(),
                'periodo_conservacion' => $indice->getPeriodoConservacion(),
                'es_reciente' => $indice->esReciente(),
                'necesita_actualizacion' => $indice->necesitaActualizacion(),
            ],
            'entidadRelacionada' => $entidadRelacionada,
        ]);
    }

    /**
     * Regenerar índices masivamente
     */
    public function regenerar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string|in:expedientes,documentos,todos',
            'solo_faltantes' => 'boolean'
        ]);

        $resultados = [];

        if ($request->tipo === 'todos') {
            $resultadosExpedientes = $this->indiceService->regenerarIndices('expedientes', auth()->user(), $request->boolean('solo_faltantes'));
            $resultadosDocumentos = $this->indiceService->regenerarIndices('documentos', auth()->user(), $request->boolean('solo_faltantes'));
            
            $resultados = [
                'expedientes' => $resultadosExpedientes,
                'documentos' => $resultadosDocumentos,
                'total_procesados' => $resultadosExpedientes['procesados'] + $resultadosDocumentos['procesados'],
                'total_creados' => $resultadosExpedientes['creados'] + $resultadosDocumentos['creados'],
                'total_actualizados' => $resultadosExpedientes['actualizados'] + $resultadosDocumentos['actualizados'],
                'total_errores' => count($resultadosExpedientes['errores']) + count($resultadosDocumentos['errores']),
            ];
        } else {
            $resultados = $this->indiceService->regenerarIndices($request->tipo, auth()->user(), $request->boolean('solo_faltantes'));
        }

        return back()->with('success', [
            'message' => 'Regeneración de índices completada',
            'resultados' => $resultados
        ]);
    }

    /**
     * Indexar una entidad específica
     */
    public function indexarEntidad(Request $request)
    {
        $request->validate([
            'tipo_entidad' => 'required|string|in:expediente,documento',
            'entidad_id' => 'required|integer'
        ]);

        try {
            if ($request->tipo_entidad === 'expediente') {
                $expediente = Expediente::findOrFail($request->entidad_id);
                $indice = $this->indiceService->indexarExpediente($expediente, auth()->user());
                $mensaje = "Expediente indexado correctamente";
            } else {
                $documento = Documento::findOrFail($request->entidad_id);
                $indice = $this->indiceService->indexarDocumento($documento, auth()->user());
                $mensaje = "Documento indexado correctamente";
            }

            return redirect()
                ->route('admin.indices.show', $indice)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al indexar: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualizar un índice específico
     */
    public function actualizar(Request $request, IndiceElectronico $indice)
    {
        try {
            // Obtener la entidad relacionada
            switch ($indice->tipo_entidad) {
                case 'expediente':
                    $entidad = Expediente::findOrFail($indice->entidad_id);
                    break;
                case 'documento':
                    $entidad = Documento::findOrFail($indice->entidad_id);
                    break;
                default:
                    throw new \Exception('Tipo de entidad no válido');
            }

            $indiceActualizado = $this->indiceService->actualizarIndice($indice, $entidad, auth()->user());

            return back()->with('success', 'Índice actualizado correctamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar índice: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar un índice
     */
    public function destroy(IndiceElectronico $indice)
    {
        try {
            $indice->delete();

            return redirect()
                ->route('admin.indices.index')
                ->with('success', 'Índice eliminado correctamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar índice: ' . $e->getMessage()]);
        }
    }

    /**
     * Exportar índices en diferentes formatos
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'formato' => 'required|string|in:csv,excel,pdf',
            'filtros' => 'array'
        ]);

        // Aplicar los mismos filtros que en el índice
        $filtros = $request->get('filtros', []);
        $indices = $this->indiceService->busquedaAvanzada(array_merge($filtros, ['per_page' => 10000]));

        // Preparar datos para exportación
        $datosExportar = $indices->map(function ($indice) {
            return [
                'ID' => $indice->id,
                'Tipo' => ucfirst($indice->tipo_entidad),
                'Código' => $indice->getCodigoCompleto(),
                'Título' => $indice->titulo,
                'Serie' => $indice->serie_documental,
                'Subserie' => $indice->subserie_documental,
                'Fecha Inicio' => $indice->fecha_inicio?->format('d/m/Y'),
                'Fecha Fin' => $indice->fecha_fin?->format('d/m/Y'),
                'Responsable' => $indice->responsable,
                'Nivel Acceso' => $indice->getEtiquetaNivelAcceso(),
                'Estado Conservación' => $indice->getEtiquetaEstadoConservacion(),
                'Folios' => $indice->cantidad_folios,
                'Tamaño' => $indice->getTamaño(),
                'Es Vital' => $indice->es_vital ? 'Sí' : 'No',
                'Es Histórico' => $indice->es_historico ? 'Sí' : 'No',
                'Fecha Indexación' => $indice->fecha_indexacion?->format('d/m/Y H:i'),
                'Ubicación Física' => $indice->ubicacion_fisica,
                'Palabras Clave' => implode(', ', $indice->palabras_clave ?? []),
            ];
        });

        $nombreArchivo = 'indices_electronicos_' . now()->format('Y-m-d_H-i-s');

        switch ($request->formato) {
            case 'csv':
                return $this->exportarCSV($datosExportar, $nombreArchivo);
            case 'excel':
                return $this->exportarExcel($datosExportar, $nombreArchivo);
            case 'pdf':
                return $this->exportarPDF($datosExportar, $nombreArchivo);
        }
    }

    /**
     * Dashboard de estadísticas avanzadas
     */
    public function estadisticas()
    {
        $estadisticas = $this->indiceService->obtenerEstadisticas();
        
        // Estadísticas adicionales para el dashboard
        $estadisticasAvanzadas = [
            'indices_por_mes' => IndiceElectronico::selectRaw('YEAR(fecha_indexacion) as año, MONTH(fecha_indexacion) as mes, COUNT(*) as total')
                ->where('fecha_indexacion', '>=', now()->subYear())
                ->groupBy('año', 'mes')
                ->orderBy('año')
                ->orderBy('mes')
                ->get(),
            
            'top_usuarios_indexadores' => IndiceElectronico::selectRaw('usuario_indexacion_id, COUNT(*) as total')
                ->with('usuarioIndexacion:id,name')
                ->groupBy('usuario_indexacion_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            
            'crecimiento_indices' => [
                'ultima_semana' => IndiceElectronico::where('fecha_indexacion', '>=', now()->subWeek())->count(),
                'ultimo_mes' => IndiceElectronico::where('fecha_indexacion', '>=', now()->subMonth())->count(),
                'ultimo_año' => IndiceElectronico::where('fecha_indexacion', '>=', now()->subYear())->count(),
            ]
        ];

        return Inertia::render('admin/indices/estadisticas', [
            'estadisticas' => array_merge($estadisticas, $estadisticasAvanzadas),
        ]);
    }

    // Métodos privados para exportación
    private function exportarCSV($datos, $nombreArchivo)
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$nombreArchivo}.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($datos) {
            $file = fopen('php://output', 'w');
            
            // Agregar BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Encabezados
            if ($datos->isNotEmpty()) {
                fputcsv($file, array_keys($datos->first()), ';');
                
                // Datos
                foreach ($datos as $fila) {
                    fputcsv($file, array_values($fila), ';');
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportarExcel($datos, $nombreArchivo)
    {
        // Implementación usando maatwebsite/excel si está disponible
        // Por ahora devolvemos CSV con extensión xlsx
        return $this->exportarCSV($datos, $nombreArchivo);
    }

    private function exportarPDF($datos, $nombreArchivo)
    {
        // Implementación básica de PDF
        // En producción se usaría una librería como DomPDF o TCPDF
        return response()->json([
            'message' => 'Exportación PDF en desarrollo',
            'datos_count' => $datos->count()
        ]);
    }
}
