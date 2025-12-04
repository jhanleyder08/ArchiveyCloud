<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificacionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Mostrar notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $query = Notificacion::with(['creadoPor', 'relacionado'])
            ->paraUsuario(Auth::id())
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->tipo($request->tipo);
        }

        if ($request->filled('prioridad')) {
            $query->prioridad($request->prioridad);
        }

        $notificaciones = $query->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => Notificacion::paraUsuario(Auth::id())->count(),
            'pendientes' => Notificacion::paraUsuario(Auth::id())->pendientes()->count(),
            'leidas' => Notificacion::paraUsuario(Auth::id())->leidas()->count(),
            'criticas' => Notificacion::paraUsuario(Auth::id())->prioridad('critica')->pendientes()->count(),
        ];

        return Inertia::render('admin/notificaciones/index', [
            'notificaciones' => $notificaciones,
            'estadisticas' => $estadisticas,
            'filtros' => $request->only(['estado', 'tipo', 'prioridad']),
        ]);
    }

    /**
     * Obtener notificaciones no leídas para el navbar
     */
    public function noLeidas()
    {
        $notificaciones = Notificacion::with(['creadoPor', 'relacionado'])
            ->paraUsuario(Auth::id())
            ->pendientes()
            ->orderBy('prioridad', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notificacion) {
                return [
                    'id' => $notificacion->id,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje,
                    'icono' => $notificacion->icono,
                    'prioridad' => $notificacion->prioridad,
                    'color_prioridad' => $notificacion->color_prioridad,
                    'accion_url' => $notificacion->accion_url,
                    'created_at' => $notificacion->created_at->diffForHumans(),
                    'tipo' => $notificacion->tipo,
                ];
            });

        return response()->json([
            'notificaciones' => $notificaciones,
            'total_no_leidas' => Notificacion::paraUsuario(Auth::id())->pendientes()->count(),
        ]);
    }

    /**
     * Crear una nueva notificación
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'tipo' => 'required|string|max:50',
            'prioridad' => 'in:baja,media,alta,critica',
            'accion_url' => 'nullable|string|max:500',
            'programada_para' => 'nullable|date|after:now',
        ]);

        $datos = [
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'tipo' => $request->tipo,
            'prioridad' => $request->prioridad ?? 'media',
            'accion_url' => $request->accion_url,
            'programada_para' => $request->programada_para,
            'es_automatica' => false,
            'creado_por' => Auth::id(),
        ];

        $creadas = Notificacion::crearParaUsuarios($request->user_ids, $datos);

        return back()->with('success', "Se crearon {$creadas} notificaciones exitosamente.");
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida($id)
    {
        $notificacion = Notificacion::paraUsuario(Auth::id())->findOrFail($id);
        $notificacion->marcarComoLeida();

        return response()->json(['success' => true]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas()
    {
        Notificacion::paraUsuario(Auth::id())
            ->pendientes()
            ->update([
                'estado' => 'leida',
                'leida_en' => Carbon::now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Archivar notificación
     */
    public function archivar($id)
    {
        $notificacion = Notificacion::paraUsuario(Auth::id())->findOrFail($id);
        $notificacion->archivar();

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy($id)
    {
        $notificacion = Notificacion::paraUsuario(Auth::id())->findOrFail($id);
        $notificacion->delete();

        return back()->with('success', 'Notificación eliminada exitosamente.');
    }

    /**
     * Panel de administración de notificaciones (solo administradores)
     */
    public function admin(Request $request)
    {
        $query = Notificacion::with(['usuario', 'creadoPor', 'relacionado'])
            ->orderBy('created_at', 'desc');

        // Filtros administrativos
        if ($request->filled('usuario_id')) {
            $query->paraUsuario($request->usuario_id);
        }

        if ($request->filled('tipo')) {
            $query->tipo($request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->prioridad($request->prioridad);
        }

        $notificaciones = $query->paginate(50);

        // Estadísticas generales
        $estadisticas = [
            'total_sistema' => Notificacion::count(),
            'pendientes_sistema' => Notificacion::pendientes()->count(),
            'usuarios_con_notificaciones' => Notificacion::pendientes()
                ->distinct('user_id')
                ->count('user_id'),
            'tipos_populares' => Notificacion::selectRaw('tipo, count(*) as total')
                ->groupBy('tipo')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ];

        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/notificaciones/admin', [
            'notificaciones' => $notificaciones,
            'estadisticas' => $estadisticas,
            'usuarios' => $usuarios,
            'filtros' => $request->only(['usuario_id', 'tipo', 'estado', 'prioridad']),
        ]);
    }

    /**
     * Crear notificación desde el panel administrativo
     */
    public function crear()
    {
        $usuarios = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/notificaciones/crear', [
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public function limpiarAntiguas()
    {
        $eliminadas = Notificacion::limpiarAntiguas(30);

        return back()->with('success', "Se eliminaron {$eliminadas} notificaciones antiguas.");
    }

    /**
     * Enviar notificación masiva a todos los usuarios
     */
    public function enviarMasiva(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string',
            'tipo' => 'required|string|max:50',
            'prioridad' => 'in:baja,media,alta,critica',
        ]);

        $usuarios = User::pluck('id')->toArray();
        
        $datos = [
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'tipo' => $request->tipo,
            'prioridad' => $request->prioridad ?? 'media',
            'accion_url' => $request->accion_url,
            'es_automatica' => false,
            'creado_por' => Auth::id(),
        ];

        $creadas = Notificacion::crearParaUsuarios($usuarios, $datos);

        return back()->with('success', "Se enviaron {$creadas} notificaciones a todos los usuarios.");
    }

    /**
     * Obtener conteo de notificaciones no leídas para el header
     */
    public function conteoNoLeidas()
    {
        $count = Notificacion::paraUsuario(Auth::id())->pendientes()->count();
        
        return response()->json(['count' => $count]);
    }
}
