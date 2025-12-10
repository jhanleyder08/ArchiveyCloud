<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ApiTokenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Solo roles administrativos pueden gestionar API tokens
        $this->middleware('role:Super Administrador,Administrador SGDEA')->except(['index', 'show']);
    }

    /**
     * Mostrar lista de tokens API
     */
    public function index(Request $request)
    {
        $query = ApiToken::with(['usuario:id,name,email'])
            ->withCount('logs');

        // Filtros
        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->filled('estado')) {
            switch ($request->estado) {
                case 'activo':
                    $query->validos();
                    break;
                case 'inactivo':
                    $query->where('activo', false);
                    break;
                case 'expirado':
                    $query->where('fecha_expiracion', '<', now());
                    break;
            }
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $tokens = $query->latest()
            ->paginate(15)
            ->appends($request->query());

        // Estadísticas generales con manejo de errores
        try {
            $estadisticas = [
                'total' => ApiToken::count(),
                'activos' => ApiToken::validos()->count(),
                'expirados' => ApiToken::where('fecha_expiracion', '<', now())->count(),
                'inactivos' => ApiToken::where('activo', false)->count(),
                'usos_ultimo_mes' => ApiToken::whereHas('logs', function($q) {
                    $q->where('created_at', '>=', now()->subMonth());
                })->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error generando estadisticas de API tokens:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Estadísticas por defecto
            $estadisticas = [
                'total' => 0,
                'activos' => 0,
                'expirados' => 0,
                'inactivos' => 0,
                'usos_ultimo_mes' => 0,
            ];
        }

        $data = [
            'tokens' => $tokens,
            'estadisticas' => $estadisticas,
            'filtros' => $request->only(['buscar', 'estado', 'usuario_id']),
            'usuarios' => User::select('id', 'name', 'email')->get(),
        ];
        
        \Log::info('Enviando data a frontend:', [
            'tokens_count' => count($tokens->items()),
            'estadisticas' => $estadisticas,
            'tokens_meta' => $tokens->toArray()['meta'] ?? 'No meta',
            'has_estadisticas' => isset($estadisticas),
            'has_tokens' => isset($tokens)
        ]);
        
        return Inertia::render('admin/api-tokens/index', $data);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return Inertia::render('admin/api-tokens/create', [
            'usuarios' => User::select('id', 'name', 'email')->get(),
            'permisos_disponibles' => ApiToken::permisosDisponibles(),
        ]);
    }

    /**
     * Crear nuevo token API
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'usuario_id' => 'required|exists:users,id',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'string|in:' . implode(',', array_keys(ApiToken::permisosDisponibles())),
            'fecha_expiracion' => 'nullable|date|after:today',
            'limite_usos' => 'nullable|integer|min:1|max:999999',
            'ips_permitidas' => 'nullable|array',
            'ips_permitidas.*' => 'ip',
        ]);

        try {
            $resultado = ApiToken::crearToken($validated);

            return response()->json([
                'success' => true,
                'message' => 'Token API creado exitosamente',
                'token' => $resultado['token']->load('usuario:id,name,email'),
                'plain_token' => $resultado['plain_token'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creando token API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creando el token API'
            ], 500);
        }
    }

    /**
     * Mostrar detalles del token
     */
    public function show(ApiToken $apiToken)
    {
        $token = $apiToken->load(['usuario:id,name,email']);
        
        // Solo el propietario o administradores pueden ver detalles
        $isAdmin = Auth::user()->hasRole('Super Administrador') || 
                   Auth::user()->hasRole('Administrador SGDEA');
                   
        if (!$isAdmin && $token->usuario_id !== Auth::id()) {
            abort(403, 'No tienes permisos para ver este token');
        }

        $estadisticas = $token->estadisticasUso();

        return Inertia::render('admin/api-tokens/show', [
            'token' => array_merge($token->toArray(), [
                'estado' => $token->estado,
                'dias_hasta_expiracion' => $token->dias_hasta_expiracion,
                'porcentaje_uso' => $token->porcentaje_uso,
            ]),
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(ApiToken $apiToken)
    {
        return Inertia::render('admin/api-tokens/edit', [
            'token' => $apiToken->load('usuario:id,name,email'),
            'usuarios' => User::select('id', 'name', 'email')->get(),
            'permisos_disponibles' => ApiToken::permisosDisponibles(),
        ]);
    }

    /**
     * Actualizar token API
     */
    public function update(Request $request, ApiToken $apiToken)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'string|in:' . implode(',', array_keys(ApiToken::permisosDisponibles())),
            'fecha_expiracion' => 'nullable|date|after:today',
            'limite_usos' => 'nullable|integer|min:1|max:999999',
            'ips_permitidas' => 'nullable|array',
            'ips_permitidas.*' => 'ip',
            'activo' => 'boolean',
        ]);

        try {
            $apiToken->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Token API actualizado exitosamente',
                'token' => $apiToken->fresh()->load('usuario:id,name,email'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error actualizando token API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando el token API'
            ], 500);
        }
    }

    /**
     * Revocar token
     */
    public function revocar(ApiToken $apiToken)
    {
        try {
            $apiToken->revocar();

            return response()->json([
                'success' => true,
                'message' => 'Token revocado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error revocando token API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error revocando el token'
            ], 500);
        }
    }

    /**
     * Renovar token (generar nuevo)
     */
    public function renovar(ApiToken $apiToken)
    {
        try {
            $resultado = $apiToken->renovar();

            return response()->json([
                'success' => true,
                'message' => 'Token renovado exitosamente',
                'token' => $resultado['token']->load('usuario:id,name,email'),
                'plain_token' => $resultado['plain_token'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error renovando token API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error renovando el token'
            ], 500);
        }
    }

    /**
     * Eliminar token permanentemente
     */
    public function destroy(ApiToken $apiToken)
    {
        try {
            $apiToken->delete();

            return response()->json([
                'success' => true,
                'message' => 'Token eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error eliminando token API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error eliminando el token'
            ], 500);
        }
    }

    /**
     * Dashboard con estadísticas avanzadas
     */
    public function dashboard()
    {
        $estadisticas = [
            'tokens' => [
                'total' => ApiToken::count(),
                'activos' => ApiToken::validos()->count(),
                'expirados' => ApiToken::where('fecha_expiracion', '<', now())->count(),
                'inactivos' => ApiToken::where('activo', false)->count(),
            ],
            'uso_reciente' => [
                'requests_hoy' => ApiToken::whereHas('logs', function($q) {
                    $q->whereDate('created_at', today());
                })->count(),
                'requests_semana' => ApiToken::whereHas('logs', function($q) {
                    $q->where('created_at', '>=', now()->subWeek());
                })->count(),
                'usuarios_activos' => ApiToken::validos()
                    ->whereHas('logs', function($q) {
                        $q->where('created_at', '>=', now()->subWeek());
                    })
                    ->distinct('usuario_id')
                    ->count(),
            ],
        ];

        // Tokens más usados (últimos 30 días)
        $tokensMasUsados = ApiToken::with(['usuario:id,name'])
            ->withCount(['logs' => function($q) {
                $q->where('created_at', '>=', now()->subMonth());
            }])
            ->orderByDesc('logs_count')
            ->limit(10)
            ->get();

        // Actividad por día (últimos 30 días)
        $actividadDiaria = ApiToken::join('api_token_logs', 'api_tokens.id', '=', 'api_token_logs.api_token_id')
            ->selectRaw('DATE(api_token_logs.created_at) as fecha, COUNT(*) as requests')
            ->where('api_token_logs.created_at', '>=', now()->subDays(30))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::parse($item->fecha)->format('Y-m-d') => $item->requests];
            });

        return Inertia::render('admin/api-tokens/dashboard', [
            'estadisticas' => $estadisticas,
            'tokens_mas_usados' => $tokensMasUsados,
            'actividad_diaria' => $actividadDiaria,
        ]);
    }
}
