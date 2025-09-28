<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportacionDatos;
use App\Services\ImportacionDatosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportacionDatosController extends Controller
{
    protected $importacionService;

    public function __construct(ImportacionDatosService $importacionService)
    {
        $this->importacionService = $importacionService;
    }

    /**
     * Dashboard principal de importaciones
     */
    public function dashboard(Request $request)
    {
        $estadisticas = ImportacionDatos::obtenerEstadisticas();
        $estadisticasPorTipo = ImportacionDatos::obtenerEstadisticasPorTipo();
        $velocidad = ImportacionDatos::obtenerTiempoPromedioVelocidad();

        // Importaciones recientes
        $importacionesRecientes = ImportacionDatos::with('usuario')
            ->latest()
            ->limit(10)
            ->get();

        // Importaciones en proceso
        $importacionesProcesando = ImportacionDatos::procesando()
            ->with('usuario')
            ->get();

        return Inertia::render('admin/importaciones/dashboard', [
            'estadisticas' => $estadisticas,
            'estadisticasPorTipo' => $estadisticasPorTipo,
            'velocidad' => $velocidad,
            'importacionesRecientes' => $importacionesRecientes,
            'importacionesProcesando' => $importacionesProcesando
        ]);
    }

    /**
     * Mostrar listado de importaciones
     */
    public function index(Request $request)
    {
        $query = ImportacionDatos::with('usuario');

        // Filtros
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Orden
        $ordenPor = $request->get('orden_por', 'created_at');
        $ordenDireccion = $request->get('orden_direccion', 'desc');
        $query->orderBy($ordenPor, $ordenDireccion);

        $importaciones = $query->paginate(20)->withQueryString();

        return Inertia::render('admin/importaciones/index', [
            'importaciones' => $importaciones,
            'filtros' => $request->only(['buscar', 'tipo', 'estado', 'usuario_id', 'fecha_desde', 'fecha_hasta']),
            'tipos' => $this->obtenerTipos(),
            'estados' => $this->obtenerEstados()
        ]);
    }

    /**
     * Mostrar formulario para nueva importación
     */
    public function create()
    {
        return Inertia::render('admin/importaciones/crear', [
            'tipos' => $this->obtenerTipos(),
            'formatosPermitidos' => [
                'csv' => 'CSV (.csv)',
                'excel' => 'Excel (.xlsx, .xls)',
                'json' => 'JSON (.json)',
                'xml' => 'XML (.xml)'
            ]
        ]);
    }

    /**
     * Crear nueva importación
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:expedientes,documentos,series,subseries,usuarios,trd,certificados,mixto',
            'archivo' => 'required|file|max:51200', // 50MB máximo
            'descripcion' => 'nullable|string',
            'configuracion' => 'nullable|array',
            'configuracion.mapeo' => 'nullable|array',
            'configuracion.actualizar_existentes' => 'boolean'
        ]);

        try {
            $importacion = $this->importacionService->crearImportacion(
                $request->nombre,
                $request->tipo,
                $request->file('archivo'),
                $request->configuracion ?? [],
                $request->descripcion,
                Auth::id()
            );

            return redirect()
                ->route('admin.importaciones.ver', $importacion)
                ->with('success', 'Importación creada exitosamente. Puede procesar ahora o programar para más tarde.');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['archivo' => 'Error creando importación: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Ver detalles de una importación
     */
    public function show(ImportacionDatos $importacion)
    {
        $importacion->load('usuario');

        return Inertia::render('admin/importaciones/ver', [
            'importacion' => $importacion,
            'archivosGenerados' => $importacion->archivos_generados,
            'puedeEditar' => $this->puedeEditar($importacion),
            'puedeEliminar' => $this->puedeEliminar($importacion)
        ]);
    }

    /**
     * Procesar una importación
     */
    public function procesar(ImportacionDatos $importacion)
    {
        if ($importacion->estado !== ImportacionDatos::ESTADO_PENDIENTE) {
            return back()->withErrors(['error' => 'Solo se pueden procesar importaciones pendientes.']);
        }

        try {
            // Procesar en background si es una importación grande
            if ($importacion->total_registros > 1000) {
                // Aquí se podría usar Laravel Jobs
                dispatch(function () use ($importacion) {
                    $this->importacionService->procesarImportacion($importacion);
                })->onQueue('importaciones');
                
                return back()->with('success', 'Importación enviada a procesamiento en segundo plano.');
            } else {
                // Procesar inmediatamente
                $resultado = $this->importacionService->procesarImportacion($importacion);
                
                if ($resultado['exito']) {
                    return back()->with('success', 'Importación procesada exitosamente.');
                } else {
                    return back()->withErrors(['error' => $resultado['error']]);
                }
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error procesando importación: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancelar una importación
     */
    public function cancelar(ImportacionDatos $importacion)
    {
        if (!in_array($importacion->estado, [ImportacionDatos::ESTADO_PENDIENTE, ImportacionDatos::ESTADO_PROCESANDO])) {
            return back()->withErrors(['error' => 'No se puede cancelar esta importación.']);
        }

        $importacion->cancelarProcesamiento();

        return back()->with('success', 'Importación cancelada exitosamente.');
    }

    /**
     * Eliminar una importación
     */
    public function destroy(ImportacionDatos $importacion)
    {
        if (!$this->puedeEliminar($importacion)) {
            return back()->withErrors(['error' => 'No tiene permisos para eliminar esta importación.']);
        }

        try {
            // Eliminar archivos asociados
            if ($importacion->archivo_origen) {
                Storage::delete($importacion->archivo_origen);
            }
            if ($importacion->archivo_procesado) {
                Storage::delete($importacion->archivo_procesado);
            }
            if ($importacion->archivo_errores) {
                Storage::delete($importacion->archivo_errores);
            }
            if ($importacion->archivo_log) {
                Storage::delete($importacion->archivo_log);
            }

            $importacion->delete();

            return redirect()
                ->route('admin.importaciones.index')
                ->with('success', 'Importación eliminada exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error eliminando importación: ' . $e->getMessage()]);
        }
    }

    /**
     * Descargar archivo original
     */
    public function descargarOriginal(ImportacionDatos $importacion)
    {
        if (!Storage::exists($importacion->archivo_origen)) {
            return back()->withErrors(['error' => 'Archivo no encontrado.']);
        }

        return Storage::download($importacion->archivo_origen);
    }

    /**
     * Descargar archivo de errores
     */
    public function descargarErrores(ImportacionDatos $importacion)
    {
        if (!$importacion->archivo_errores || !Storage::exists($importacion->archivo_errores)) {
            return back()->withErrors(['error' => 'Archivo de errores no encontrado.']);
        }

        return Storage::download($importacion->archivo_errores);
    }

    /**
     * Descargar archivo procesado
     */
    public function descargarProcesado(ImportacionDatos $importacion)
    {
        if (!$importacion->archivo_procesado || !Storage::exists($importacion->archivo_procesado)) {
            return back()->withErrors(['error' => 'Archivo procesado no encontrado.']);
        }

        return Storage::download($importacion->archivo_procesado);
    }

    /**
     * Obtener progreso de una importación (AJAX)
     */
    public function progreso(ImportacionDatos $importacion)
    {
        return response()->json([
            'estado' => $importacion->estado,
            'porcentaje_avance' => $importacion->porcentaje_avance,
            'registros_procesados' => $importacion->registros_procesados,
            'registros_exitosos' => $importacion->registros_exitosos,
            'registros_fallidos' => $importacion->registros_fallidos,
            'total_registros' => $importacion->total_registros,
            'tiempo_transcurrido' => $importacion->fecha_inicio ? 
                now()->diffInSeconds($importacion->fecha_inicio) : 0
        ]);
    }

    /**
     * API: Estadísticas de importaciones
     */
    public function apiEstadisticas()
    {
        return response()->json([
            'estadisticas' => ImportacionDatos::obtenerEstadisticas(),
            'por_tipo' => ImportacionDatos::obtenerEstadisticasPorTipo(),
            'velocidad' => ImportacionDatos::obtenerTiempoPromedioVelocidad()
        ]);
    }

    /**
     * Métodos de utilidad
     */
    private function obtenerTipos(): array
    {
        return [
            'expedientes' => 'Expedientes',
            'documentos' => 'Documentos',
            'series' => 'Series Documentales',
            'subseries' => 'Subseries Documentales',
            'usuarios' => 'Usuarios',
            'trd' => 'Tabla de Retención Documental',
            'certificados' => 'Certificados Digitales',
            'mixto' => 'Importación Mixta'
        ];
    }

    private function obtenerEstados(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'procesando' => 'Procesando',
            'completada' => 'Completada',
            'fallida' => 'Fallida',
            'cancelada' => 'Cancelada'
        ];
    }

    private function puedeEditar(ImportacionDatos $importacion): bool
    {
        return Auth::id() === $importacion->usuario_id || Auth::user()->hasRole('admin');
    }

    private function puedeEliminar(ImportacionDatos $importacion): bool
    {
        return (Auth::id() === $importacion->usuario_id || Auth::user()->hasRole('admin')) &&
               in_array($importacion->estado, [ImportacionDatos::ESTADO_PENDIENTE, ImportacionDatos::ESTADO_FALLIDA, ImportacionDatos::ESTADO_CANCELADA]);
    }
}
