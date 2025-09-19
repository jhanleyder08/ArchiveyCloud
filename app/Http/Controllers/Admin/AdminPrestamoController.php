<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use App\Models\PistaAuditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminPrestamoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Dashboard de préstamos
     */
    public function index(Request $request)
    {
        $query = Prestamo::with(['expediente', 'documento', 'solicitante', 'prestamista'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_prestamo')) {
            $query->where('tipo_prestamo', $request->tipo_prestamo);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_prestamo', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_prestamo', '<=', $request->fecha_fin);
        }

        if ($request->filled('solicitante')) {
            $query->whereHas('solicitante', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->solicitante . '%');
            });
        }

        $prestamos = $query->paginate(20)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total_prestamos' => Prestamo::count(),
            'prestamos_activos' => Prestamo::where('estado', 'prestado')->count(),
            'prestamos_vencidos' => Prestamo::where('estado', 'prestado')
                ->where('fecha_devolucion_esperada', '<', Carbon::now())
                ->count(),
            'prestamos_este_mes' => Prestamo::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count(),
        ];

        // Préstamos próximos a vencer (7 días)
        $proximosVencer = Prestamo::with(['expediente', 'documento', 'solicitante'])
            ->where('estado', 'prestado')
            ->whereBetween('fecha_devolucion_esperada', [
                Carbon::now(),
                Carbon::now()->addDays(7)
            ])
            ->orderBy('fecha_devolucion_esperada')
            ->get();

        return Inertia::render('admin/prestamos/index', [
            'prestamos' => $prestamos,
            'estadisticas' => $estadisticas,
            'proximosVencer' => $proximosVencer,
            'filtros' => $request->only(['estado', 'tipo_prestamo', 'fecha_inicio', 'fecha_fin', 'solicitante']),
        ]);
    }

    /**
     * Crear nuevo préstamo
     */
    public function create()
    {
        $expedientes = Expediente::select('id', 'numero_expediente', 'titulo', 'estado_ciclo_vida', 'ubicacion_fisica')
            ->where('estado_ciclo_vida', '!=', 'eliminado')
            ->orderBy('numero_expediente')
            ->get();

        $documentos = Documento::with('expediente:id,numero_expediente,titulo')
            ->select('id', 'nombre', 'expediente_id', 'ubicacion_fisica')
            ->orderBy('nombre')
            ->get();

        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/prestamos/create', [
            'expedientes' => $expedientes,
            'documentos' => $documentos,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Almacenar nuevo préstamo
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo_prestamo' => 'required|in:expediente,documento',
            'expediente_id' => 'required_if:tipo_prestamo,expediente|exists:expedientes,id',
            'documento_id' => 'required_if:tipo_prestamo,documento|exists:documentos,id',
            'solicitante_id' => 'required|exists:users,id',
            'motivo' => 'required|string|max:500',
            'fecha_devolucion_esperada' => 'required|date|after:today',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request) {
            // Verificar disponibilidad
            if ($request->tipo_prestamo === 'expediente') {
                $existePrestamo = Prestamo::where('expediente_id', $request->expediente_id)
                    ->where('estado', 'prestado')
                    ->exists();
                    
                if ($existePrestamo) {
                    throw new \Exception('El expediente ya está prestado.');
                }
                
                $expediente = Expediente::findOrFail($request->expediente_id);
                // No cambiar estado del expediente para préstamos
                // $expediente->update(['estado_ciclo_vida' => 'prestado']);
            }

            if ($request->tipo_prestamo === 'documento') {
                $existePrestamo = Prestamo::where('documento_id', $request->documento_id)
                    ->where('estado', 'prestado')
                    ->exists();
                    
                if ($existePrestamo) {
                    throw new \Exception('El documento ya está prestado.');
                }
            }

            // Crear préstamo
            $prestamo = Prestamo::create([
                'tipo_prestamo' => $request->tipo_prestamo,
                'expediente_id' => $request->expediente_id,
                'documento_id' => $request->documento_id,
                'solicitante_id' => $request->solicitante_id,
                'prestamista_id' => auth()->id(),
                'motivo' => $request->motivo,
                'fecha_prestamo' => Carbon::now(),
                'fecha_devolucion_esperada' => $request->fecha_devolucion_esperada,
                'observaciones' => $request->observaciones,
                'estado' => 'prestado',
            ]);

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'crear_prestamo',
                'tabla_afectada' => 'prestamos',
                'registro_id' => $prestamo->id,
                'descripcion' => "Préstamo creado para " . ($request->tipo_prestamo === 'expediente' ? 'expediente' : 'documento'),
                'datos_anteriores' => null,
                'datos_nuevos' => $prestamo->toJson(),
            ]);
        });

        return redirect()->route('admin.prestamos.index')
            ->with('success', 'Préstamo creado exitosamente.');
    }

    /**
     * Ver detalles del préstamo
     */
    public function show(Prestamo $prestamo)
    {
        $prestamo->load(['expediente', 'documento.expediente', 'solicitante', 'prestamista']);

        // Historial de movimientos
        $historial = PistaAuditoria::where('tabla_afectada', 'prestamos')
            ->where('registro_id', $prestamo->id)
            ->with('usuario:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/prestamos/show', [
            'prestamo' => $prestamo,
            'historial' => $historial,
        ]);
    }

    /**
     * Devolver préstamo
     */
    public function devolver(Request $request, Prestamo $prestamo)
    {
        $request->validate([
            'observaciones_devolucion' => 'nullable|string|max:1000',
            'estado_devolucion' => 'required|in:bueno,dañado,perdido',
        ]);

        if ($prestamo->estado !== 'prestado') {
            return back()->withErrors(['error' => 'El préstamo no está en estado prestado.']);
        }

        DB::transaction(function () use ($request, $prestamo) {
            // Actualizar préstamo
            $prestamo->update([
                'fecha_devolucion_real' => Carbon::now(),
                'observaciones_devolucion' => $request->observaciones_devolucion,
                'estado_devolucion' => $request->estado_devolucion,
                'estado' => 'devuelto',
            ]);

            // No es necesario restaurar estado del expediente para préstamos
            // if ($prestamo->expediente_id) {
            //     $prestamo->expediente->update(['estado_ciclo_vida' => 'tramite']);
            // }

            // Registrar en auditoría
            PistaAuditoria::create([
                'usuario_id' => auth()->id(),
                'accion' => 'devolver_prestamo',
                'tabla_afectada' => 'prestamos',
                'registro_id' => $prestamo->id,
                'descripcion' => "Préstamo devuelto en estado: {$request->estado_devolucion}",
                'datos_anteriores' => $prestamo->getOriginal(),
                'datos_nuevos' => $prestamo->toJson(),
            ]);
        });

        return redirect()->route('admin.prestamos.show', $prestamo)
            ->with('success', 'Préstamo devuelto exitosamente.');
    }

    /**
     * Renovar préstamo
     */
    public function renovar(Request $request, Prestamo $prestamo)
    {
        $request->validate([
            'nueva_fecha_devolucion' => 'required|date|after:today',
            'motivo_renovacion' => 'required|string|max:500',
        ]);

        if ($prestamo->estado !== 'prestado') {
            return back()->withErrors(['error' => 'Solo se pueden renovar préstamos activos.']);
        }

        $fechaAnterior = $prestamo->fecha_devolucion_esperada;

        $prestamo->update([
            'fecha_devolucion_esperada' => $request->nueva_fecha_devolucion,
            'renovaciones' => ($prestamo->renovaciones ?? 0) + 1,
            'observaciones' => $prestamo->observaciones . "\n\nRenovación: " . $request->motivo_renovacion,
        ]);

        // Registrar en auditoría
        PistaAuditoria::create([
            'usuario_id' => auth()->id(),
            'accion' => 'renovar_prestamo',
            'tabla_afectada' => 'prestamos',
            'registro_id' => $prestamo->id,
            'descripcion' => "Préstamo renovado hasta {$request->nueva_fecha_devolucion}",
            'datos_anteriores' => ['fecha_devolucion_esperada' => $fechaAnterior],
            'datos_nuevos' => ['fecha_devolucion_esperada' => $request->nueva_fecha_devolucion],
        ]);

        return redirect()->route('admin.prestamos.show', $prestamo)
            ->with('success', 'Préstamo renovado exitosamente.');
    }

    /**
     * Reporte de préstamos
     */
    public function reportes(Request $request)
    {
        $periodo = $request->input('periodo', '30');
        $fechaInicio = Carbon::now()->subDays($periodo);

        // Estadísticas generales
        $estadisticas = [
            'total_prestamos' => Prestamo::where('created_at', '>=', $fechaInicio)->count(),
            'prestamos_por_tipo' => Prestamo::where('created_at', '>=', $fechaInicio)
                ->selectRaw('tipo_prestamo, COUNT(*) as total')
                ->groupBy('tipo_prestamo')
                ->get(),
            'prestamos_por_estado' => Prestamo::where('created_at', '>=', $fechaInicio)
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get(),
            'usuarios_mas_activos' => Prestamo::with('solicitante:id,name,email')
                ->where('created_at', '>=', $fechaInicio)
                ->selectRaw('solicitante_id, COUNT(*) as total_prestamos')
                ->groupBy('solicitante_id')
                ->orderBy('total_prestamos', 'desc')
                ->limit(10)
                ->get(),
        ];

        // Préstamos por día
        $prestamosPorDia = Prestamo::selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->where('created_at', '>=', $fechaInicio)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Tiempo promedio de préstamo
        $tiempoPromedio = Prestamo::whereNotNull('fecha_devolucion_real')
            ->where('created_at', '>=', $fechaInicio)
            ->get()
            ->map(function ($prestamo) {
                return Carbon::parse($prestamo->fecha_devolucion_real)
                    ->diffInDays(Carbon::parse($prestamo->fecha_prestamo));
            })
            ->avg();

        return Inertia::render('admin/prestamos/reportes', [
            'estadisticas' => $estadisticas,
            'prestamosPorDia' => $prestamosPorDia,
            'tiempoPromedio' => round($tiempoPromedio ?? 0, 1),
            'periodo' => $periodo,
        ]);
    }

    /**
     * Buscar elementos para préstamo
     */
    public function buscar(Request $request)
    {
        $query = $request->input('q');
        $tipo = $request->input('tipo', 'expediente');

        if ($tipo === 'expediente') {
            $resultados = Expediente::where('numero_expediente', 'like', "%{$query}%")
                ->orWhere('titulo', 'like', "%{$query}%")
                ->where('estado_ciclo_vida', '!=', 'eliminado')
                ->whereNotIn('id', function ($q) {
                    $q->select('expediente_id')
                        ->from('prestamos')
                        ->where('estado', 'prestado')
                        ->whereNotNull('expediente_id');
                })
                ->select('id', 'numero_expediente', 'titulo', 'ubicacion_fisica', 'estado_ciclo_vida')
                ->limit(10)
                ->get();
        } else {
            $resultados = Documento::with('expediente:id,numero_expediente,titulo')
                ->where('nombre', 'like', "%{$query}%")
                ->whereNotIn('id', function ($q) {
                    $q->select('documento_id')
                        ->from('prestamos')
                        ->where('estado', 'prestado')
                        ->whereNotNull('documento_id');
                })
                ->select('id', 'nombre', 'expediente_id', 'ubicacion_fisica')
                ->limit(10)
                ->get();
        }

        return response()->json($resultados);
    }
}
