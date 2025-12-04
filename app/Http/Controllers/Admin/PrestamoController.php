<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Expediente;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PrestamoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Prestamo::with([
            'expediente:id,codigo,titulo,ubicacion_fisica',
            'documento:id,titulo',
            'documento.expediente:id,codigo,titulo',
            'solicitante:id,name,email',
            'prestamista:id,name,email'
        ])->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_prestamo')) {
            $query->where('tipo_prestamo', $request->tipo_prestamo);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('motivo', 'LIKE', "%{$search}%")
                  ->orWhereHas('solicitante', function($sq) use ($search) {
                      $sq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('expediente', function($eq) use ($search) {
                      $eq->where('codigo', 'LIKE', "%{$search}%")
                         ->orWhere('titulo', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('documento', function($dq) use ($search) {
                      $dq->where('titulo', 'LIKE', "%{$search}%");
                  });
            });
        }

        $prestamos = $query->paginate(20);

        // Agregar campos calculados a cada préstamo
        $prestamos->getCollection()->transform(function ($prestamo) {
            $prestamo->nombre_solicitante = $prestamo->nombre_solicitante;
            $prestamo->contacto_solicitante = $prestamo->contacto_solicitante;
            $prestamo->esta_vencido = $prestamo->esta_vencido;
            $prestamo->dias_restantes = $prestamo->dias_restantes;
            return $prestamo;
        });

        // Estadísticas
        $estadisticas = [
            'total' => Prestamo::count(),
            'activos' => Prestamo::activos()->count(),
            'vencidos' => Prestamo::vencidos()->count(),
            'proximos_vencer' => Prestamo::proximosVencer(7)->count(),
        ];

        return Inertia::render('admin/prestamos/index', [
            'prestamos' => $prestamos,
            'estadisticas' => $estadisticas,
            'filtros' => $request->only(['estado', 'tipo_prestamo', 'search'])
        ]);
    }

    /**
     * Mostrar reportes y estadísticas de préstamos
     */
    public function reportes(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->subMonths(3)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));

        // Estadísticas generales
        $estadisticas = [
            'total' => Prestamo::count(),
            'activos' => Prestamo::activos()->count(),
            'vencidos' => Prestamo::vencidos()->count(),
            'devueltos' => Prestamo::where('estado', 'devuelto')->count(),
            'proximos_vencer' => Prestamo::proximosVencer(7)->count(),
        ];

        // Préstamos por tipo
        $prestamosPorTipo = Prestamo::selectRaw('tipo_prestamo, COUNT(*) as total')
            ->groupBy('tipo_prestamo')
            ->get()
            ->mapWithKeys(fn($item) => [$item->tipo_prestamo => $item->total]);

        // Préstamos por estado
        $prestamosPorEstado = Prestamo::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn($item) => [$item->estado => $item->total]);

        // Préstamos por mes (últimos 6 meses)
        $prestamosPorMes = Prestamo::selectRaw('DATE_FORMAT(fecha_prestamo, "%Y-%m") as mes, COUNT(*) as total')
            ->where('fecha_prestamo', '>=', now()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Top 10 expedientes más prestados
        $expedientesMasPrestados = Prestamo::select('expediente_id')
            ->selectRaw('COUNT(*) as total_prestamos')
            ->with('expediente:id,codigo,titulo')
            ->where('tipo_prestamo', 'expediente')
            ->whereNotNull('expediente_id')
            ->groupBy('expediente_id')
            ->orderByDesc('total_prestamos')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'expediente' => $item->expediente ? "{$item->expediente->codigo} - {$item->expediente->titulo}" : 'N/A',
                    'total' => $item->total_prestamos
                ];
            });

        // Top 10 usuarios que más solicitan
        $usuariosMasSolicitan = Prestamo::select('solicitante_id')
            ->selectRaw('COUNT(*) as total_prestamos')
            ->with('solicitante:id,name,email')
            ->where('tipo_solicitante', 'usuario')
            ->whereNotNull('solicitante_id')
            ->groupBy('solicitante_id')
            ->orderByDesc('total_prestamos')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'usuario' => $item->solicitante ? $item->solicitante->name : 'N/A',
                    'email' => $item->solicitante ? $item->solicitante->email : 'N/A',
                    'total' => $item->total_prestamos
                ];
            });

        // Préstamos vencidos con detalles
        $prestamosVencidos = Prestamo::with([
                'expediente:id,codigo,titulo',
                'documento:id,titulo',
                'solicitante:id,name,email'
            ])
            ->vencidos()
            ->orderBy('fecha_devolucion_esperada')
            ->limit(20)
            ->get()
            ->map(function($prestamo) {
                return [
                    'id' => $prestamo->id,
                    'tipo' => $prestamo->tipo_prestamo,
                    'item' => $prestamo->tipo_prestamo === 'expediente' 
                        ? ($prestamo->expediente ? "{$prestamo->expediente->codigo} - {$prestamo->expediente->titulo}" : 'N/A')
                        : ($prestamo->documento ? $prestamo->documento->titulo : 'N/A'),
                    'solicitante' => $prestamo->nombre_solicitante,
                    'fecha_prestamo' => $prestamo->fecha_prestamo,
                    'fecha_devolucion_esperada' => $prestamo->fecha_devolucion_esperada,
                    'dias_vencido' => now()->diffInDays($prestamo->fecha_devolucion_esperada)
                ];
            });

        // Tiempo promedio de préstamo
        $tiempoPromedio = Prestamo::where('estado', 'devuelto')
            ->whereNotNull('fecha_devolucion_real')
            ->get()
            ->avg(function($prestamo) {
                return \Carbon\Carbon::parse($prestamo->fecha_prestamo)
                    ->diffInDays(\Carbon\Carbon::parse($prestamo->fecha_devolucion_real));
            });

        return Inertia::render('admin/prestamos/reportes', [
            'estadisticas' => $estadisticas,
            'prestamosPorTipo' => $prestamosPorTipo,
            'prestamosPorEstado' => $prestamosPorEstado,
            'prestamosPorMes' => $prestamosPorMes,
            'expedientesMasPrestados' => $expedientesMasPrestados,
            'usuariosMasSolicitan' => $usuariosMasSolicitan,
            'prestamosVencidos' => $prestamosVencidos,
            'tiempoPromedio' => round($tiempoPromedio ?? 0, 1),
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ]);
    }

    /**
     * Exportar reportes a PDF
     */
    public function exportarReportesPDF(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->subMonths(3)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));

        // Estadísticas generales
        $estadisticas = [
            'total' => Prestamo::count(),
            'activos' => Prestamo::activos()->count(),
            'vencidos' => Prestamo::vencidos()->count(),
            'devueltos' => Prestamo::where('estado', 'devuelto')->count(),
            'proximos_vencer' => Prestamo::proximosVencer(7)->count(),
        ];

        // Préstamos por tipo
        $prestamosPorTipo = Prestamo::selectRaw('tipo_prestamo, COUNT(*) as total')
            ->groupBy('tipo_prestamo')
            ->get()
            ->mapWithKeys(fn($item) => [$item->tipo_prestamo => $item->total]);

        // Préstamos por estado
        $prestamosPorEstado = Prestamo::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn($item) => [$item->estado => $item->total]);

        // Top 10 expedientes más prestados
        $expedientesMasPrestados = Prestamo::select('expediente_id')
            ->selectRaw('COUNT(*) as total_prestamos')
            ->with('expediente:id,codigo,titulo')
            ->where('tipo_prestamo', 'expediente')
            ->whereNotNull('expediente_id')
            ->groupBy('expediente_id')
            ->orderByDesc('total_prestamos')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'expediente' => $item->expediente ? "{$item->expediente->codigo} - {$item->expediente->titulo}" : 'N/A',
                    'total' => $item->total_prestamos
                ];
            });

        // Top 10 usuarios que más solicitan
        $usuariosMasSolicitan = Prestamo::select('solicitante_id')
            ->selectRaw('COUNT(*) as total_prestamos')
            ->with('solicitante:id,name,email')
            ->where('tipo_solicitante', 'usuario')
            ->whereNotNull('solicitante_id')
            ->groupBy('solicitante_id')
            ->orderByDesc('total_prestamos')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'usuario' => $item->solicitante ? $item->solicitante->name : 'N/A',
                    'email' => $item->solicitante ? $item->solicitante->email : 'N/A',
                    'total' => $item->total_prestamos
                ];
            });

        // Préstamos vencidos con detalles
        $prestamosVencidos = Prestamo::with([
                'expediente:id,codigo,titulo',
                'documento:id,titulo',
                'solicitante:id,name,email'
            ])
            ->vencidos()
            ->orderBy('fecha_devolucion_esperada')
            ->limit(20)
            ->get()
            ->map(function($prestamo) {
                return [
                    'id' => $prestamo->id,
                    'tipo' => $prestamo->tipo_prestamo,
                    'item' => $prestamo->tipo_prestamo === 'expediente' 
                        ? ($prestamo->expediente ? "{$prestamo->expediente->codigo} - {$prestamo->expediente->titulo}" : 'N/A')
                        : ($prestamo->documento ? $prestamo->documento->titulo : 'N/A'),
                    'solicitante' => $prestamo->nombre_solicitante,
                    'fecha_prestamo' => $prestamo->fecha_prestamo,
                    'fecha_devolucion_esperada' => $prestamo->fecha_devolucion_esperada,
                    'dias_vencido' => now()->diffInDays($prestamo->fecha_devolucion_esperada)
                ];
            });

        // Tiempo promedio de préstamo
        $tiempoPromedio = Prestamo::where('estado', 'devuelto')
            ->whereNotNull('fecha_devolucion_real')
            ->get()
            ->avg(function($prestamo) {
                return \Carbon\Carbon::parse($prestamo->fecha_prestamo)
                    ->diffInDays(\Carbon\Carbon::parse($prestamo->fecha_devolucion_real));
            });

        $data = [
            'estadisticas' => $estadisticas,
            'prestamosPorTipo' => $prestamosPorTipo,
            'prestamosPorEstado' => $prestamosPorEstado,
            'expedientesMasPrestados' => $expedientesMasPrestados,
            'usuariosMasSolicitan' => $usuariosMasSolicitan,
            'prestamosVencidos' => $prestamosVencidos,
            'tiempoPromedio' => round($tiempoPromedio ?? 0, 1),
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
            'usuario' => auth()->user()->name,
        ];

        $pdf = Pdf::loadView('pdf.reportes-prestamos', $data);
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->download('reporte-prestamos-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $expedientes = Expediente::select('id', 'codigo', 'titulo')
            ->orderBy('codigo')
            ->get();

        $documentos = Documento::select('id', 'titulo', 'codigo_documento')
            ->where('activo', true)
            ->orderBy('titulo')
            ->get();

        $usuarios = User::select('id', 'name', 'email')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/prestamos/create', [
            'expedientes' => $expedientes,
            'documentos' => $documentos,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Datos recibidos en store:', $request->all());
        
        $validated = $request->validate([
            'tipo_prestamo' => 'required|in:expediente,documento',
            'expediente_id' => 'nullable|exists:expedientes,id',
            'documento_id' => 'nullable|exists:documentos,id',
            'tipo_solicitante' => 'required|in:usuario,externo',
            'solicitante_id' => 'nullable|exists:users,id',
            'motivo' => 'required|string|max:500',
            'fecha_prestamo' => 'required|date',
            'fecha_devolucion_esperada' => 'required|date|after:today',
            'observaciones' => 'nullable|string|max:1000',
            // Campos para solicitante externo
            'nombre_completo' => 'nullable|string|max:255',
            'tipo_documento' => 'nullable|string|max:10',
            'numero_documento' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:100',
            'dependencia' => 'nullable|string|max:255',
        ]);

        // Validar que se seleccione expediente o documento según el tipo
        if ($validated['tipo_prestamo'] === 'expediente' && !$validated['expediente_id']) {
            return back()->withErrors(['expediente_id' => 'Debe seleccionar un expediente']);
        }

        if ($validated['tipo_prestamo'] === 'documento' && !$validated['documento_id']) {
            return back()->withErrors(['documento_id' => 'Debe seleccionar un documento']);
        }

        // Validar solicitante según el tipo
        if ($validated['tipo_solicitante'] === 'usuario' && !$validated['solicitante_id']) {
            return back()->withErrors(['solicitante_id' => 'Debe seleccionar un usuario registrado']);
        }

        if ($validated['tipo_solicitante'] === 'externo') {
            // Validar campos requeridos para solicitante externo
            $requiredFields = ['nombre_completo', 'tipo_documento', 'numero_documento', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($validated[$field])) {
                    return back()->withErrors([$field => 'Este campo es requerido para solicitantes externos']);
                }
            }
        }

        $validated['prestamista_id'] = auth()->id();
        $validated['estado'] = 'prestado';
        $validated['renovaciones'] = 0;
        $validated['fecha_prestamo'] = now();

        // Si es solicitante externo, guardar los datos como JSON en un campo adicional
        if ($validated['tipo_solicitante'] === 'externo') {
            $validated['datos_solicitante_externo'] = json_encode([
                'nombre_completo' => $validated['nombre_completo'],
                'tipo_documento' => $validated['tipo_documento'],
                'numero_documento' => $validated['numero_documento'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'cargo' => $validated['cargo'],
                'dependencia' => $validated['dependencia'],
            ]);
            
            // Limpiar campos individuales ya que se guardan en JSON
            unset($validated['nombre_completo'], $validated['tipo_documento'], 
                  $validated['numero_documento'], $validated['email'], 
                  $validated['telefono'], $validated['cargo'], $validated['dependencia']);
        }

        \Log::info('Datos validados:', $validated);
        
        $prestamo = Prestamo::create($validated);
        
        \Log::info('Préstamo creado:', $prestamo->toArray());

        return redirect()->route('admin.prestamos.index')
            ->with('success', 'Préstamo registrado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Prestamo $prestamo)
    {
        $prestamo->load([
            'expediente.serie',
            'documento.tipologia',
            'solicitante',
            'prestamista'
        ]);

        return Inertia::render('admin/prestamos/show', [
            'prestamo' => $prestamo
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prestamo $prestamo)
    {
        $expedientes = Expediente::select('id', 'codigo', 'titulo')
            ->orderBy('codigo')
            ->get();

        $documentos = Documento::select('id', 'titulo', 'codigo_documento')
            ->where('activo', true)
            ->orderBy('titulo')
            ->get();

        $usuarios = User::select('id', 'name', 'email')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/prestamos/edit', [
            'prestamo' => $prestamo,
            'expedientes' => $expedientes,
            'documentos' => $documentos,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Prestamo $prestamo)
    {
        $validated = $request->validate([
            'tipo_prestamo' => 'required|in:expediente,documento',
            'expediente_id' => 'nullable|exists:expedientes,id',
            'documento_id' => 'nullable|exists:documentos,id',
            'solicitante_id' => 'required|exists:users,id',
            'motivo' => 'required|string|max:500',
            'fecha_prestamo' => 'required|date',
            'fecha_devolucion_esperada' => 'required|date|after:fecha_prestamo',
            'observaciones' => 'nullable|string|max:1000',
            'estado' => 'required|in:prestado,devuelto,vencido',
        ]);

        $prestamo->update($validated);

        return redirect()->route('admin.prestamos.index')
            ->with('success', 'Préstamo actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prestamo $prestamo)
    {
        $prestamo->delete();

        return redirect()->route('admin.prestamos.index')
            ->with('success', 'Préstamo eliminado exitosamente');
    }

    /**
     * Marcar préstamo como devuelto
     */
    public function devolver(Request $request, Prestamo $prestamo)
    {
        $validated = $request->validate([
            'fecha_devolucion_real' => 'required|date',
            'observaciones_devolucion' => 'nullable|string|max:1000',
            'estado_devolucion' => 'required|in:completa,parcial,con_daños',
        ]);

        $prestamo->update([
            'fecha_devolucion_real' => $validated['fecha_devolucion_real'],
            'observaciones_devolucion' => $validated['observaciones_devolucion'],
            'estado_devolucion' => $validated['estado_devolucion'],
            'estado' => 'devuelto',
        ]);

        return back()->with('success', 'Préstamo marcado como devuelto');
    }

    /**
     * Renovar préstamo
     */
    public function renovar(Request $request, Prestamo $prestamo)
    {
        $validated = $request->validate([
            'nueva_fecha_devolucion' => 'required|date|after:today',
            'motivo_renovacion' => 'required|string|max:500',
        ]);

        $prestamo->update([
            'fecha_devolucion_esperada' => $validated['nueva_fecha_devolucion'],
            'renovaciones' => $prestamo->renovaciones + 1,
            'observaciones' => $prestamo->observaciones . "\n\nRenovación " . ($prestamo->renovaciones + 1) . ": " . $validated['motivo_renovacion'],
        ]);

        return back()->with('success', 'Préstamo renovado exitosamente');
    }
}
