<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\NotificacionEmailService;
use App\Services\NotificacionSmsService;
use App\Models\User;
use App\Models\Notificacion;
use App\Models\ConfiguracionServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ServiciosExternosController extends Controller
{
    private const CONFIG_CACHE_KEY = 'servicios_externos_config';
    private $emailService;
    private $smsService;

    public function __construct()
    {
        $this->middleware('auth');
        // Solo roles administrativos pueden cambiar configuración o forzar procesos
        $this->middleware('role:Super Administrador,Administrador SGDEA')->only([
            'configuracion', 'actualizarConfiguracion', 'forzarResumenes'
        ]);
        $this->emailService = new NotificacionEmailService();
        $this->smsService = new NotificacionSmsService();
    }

    /**
     * Dashboard principal de servicios externos
     */
    public function index()
    {
        $estadisticas = $this->obtenerEstadisticasGenerales();
        
        return Inertia::render('admin/servicios-externos/index', [
            'estadisticas' => $estadisticas,
            'configuracion' => $this->obtenerConfiguracion(),
            'logs_recientes' => $this->obtenerLogsRecientes()
        ]);
    }

    /**
     * Página de testing de servicios
     */
    public function testing()
    {
        return Inertia::render('admin/servicios-externos/testing', [
            'usuarios' => User::select('id', 'name', 'email', 'telefono')
                ->where('estado_cuenta', 'activo')
                ->orderBy('name')
                ->get()
        ]);
    }

    /**
     * Ejecutar test de email
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $usuario = User::findOrFail($request->user_id);

        // Crear notificación de prueba
        $notificacion = new Notificacion([
            'user_id' => $usuario->id,
            'tipo' => 'test_interfaz',
            'titulo' => 'Prueba desde Interfaz Web - ArchiveyCloud',
            'mensaje' => 'Este es un email de prueba enviado desde la interfaz de administración de servicios externos de ArchiveyCloud.',
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'es_automatica' => true,
            'accion_url' => '/admin/servicios-externos',
            'datos' => [
                'test_interfaz' => true,
                'enviado_por' => auth()->user()->name,
                'timestamp' => now()->toISOString()
            ]
        ]);

        $notificacion->user = $usuario;

        try {
            $enviado = $this->emailService->enviarNotificacion($notificacion);
            
            return response()->json([
                'success' => $enviado,
                'message' => $enviado 
                    ? "Email enviado exitosamente a {$usuario->email}" 
                    : 'Error enviando el email',
                'detalles' => [
                    'destinatario' => $usuario->email,
                    'timestamp' => now()->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar test de SMS
     */
    public function testSms(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|min:10'
        ]);

        try {
            $resultado = $this->smsService->probarConfiguracion($request->telefono);
            
            return response()->json([
                'success' => $resultado['configuracion_ok'],
                'message' => $resultado['configuracion_ok'] 
                    ? 'SMS de prueba procesado correctamente' 
                    : 'Error en configuración SMS',
                'detalles' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Página de estadísticas detalladas
     */
    public function estadisticas()
    {
        $estadisticasEmail = $this->emailService->obtenerEstadisticas();
        $estadisticasSms = $this->smsService->obtenerEstadisticas();
        
        return Inertia::render('admin/servicios-externos/estadisticas', [
            'email' => $estadisticasEmail,
            'sms' => $estadisticasSms,
            'graficos' => $this->obtenerDatosGraficos()
        ]);
    }

    /**
     * Configuración de servicios
     */
    public function configuracion()
    {
        return Inertia::render('admin/servicios-externos/configuracion', [
            'configuracion_actual' => $this->obtenerConfiguracion(),
            'usuarios_admin' => User::whereHas('role', function($query) {
                $query->whereIn('name', ['Super Administrador', 'Administrador SGDEA']);
            })->select('id', 'name', 'email')->get()
        ]);
    }

    /**
     * Actualizar configuración
     */
    public function actualizarConfiguracion(Request $request)
    {
        try {
            // Validar payload de configuración
            $data = $request->validate([
                'email_habilitado' => 'required|boolean',
                'sms_habilitado' => 'required|boolean',
                'resumen_diario_hora' => ['required','regex:/^\\d{2}:\\d{2}$/'],
                'throttling_email' => 'required|integer|min:1|max:100',
                'throttling_sms' => 'required|integer|min:1|max:100',
                'destinatarios_resumen' => 'nullable|array',
                'destinatarios_resumen.*' => 'integer|exists:users,id',
            ]);

            // Guardar en base de datos usando el modelo
            $configuracion = ConfiguracionServicio::actualizarConfiguracionServiciosExternos($data);

            // Mantener también en caché para compatibilidad con servicios existentes
            $payload = array_merge($configuracion, [
                'updated_at' => now()->toISOString(),
            ]);
            Cache::forever(self::CONFIG_CACHE_KEY, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente',
                'configuracion' => $configuracion
            ]);

        } catch (\Exception $e) {
            \Log::error('Error actualizando configuración servicios externos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno actualizando configuración'
            ], 500);
        }
    }

    /**
     * Forzar envío de resúmenes
     */
    public function forzarResumenes()
    {
        try {
            $proceso = \Artisan::call('notifications:send-daily-summary');
            
            return response()->json([
                'success' => true,
                'message' => 'Resúmenes enviados exitosamente',
                'detalles' => [
                    'comando_ejecutado' => 'notifications:send-daily-summary',
                    'timestamp' => now()->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error ejecutando resúmenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticasGenerales(): array
    {
        $hoy = Carbon::today();
        $semana = Carbon::now()->subDays(7);

        return [
            'email' => [
                'enviados_hoy' => Notificacion::whereDate('created_at', $hoy)
                    ->whereIn('prioridad', ['alta', 'critica'])
                    ->count(),
                'enviados_semana' => Notificacion::where('created_at', '>=', $semana)
                    ->whereIn('prioridad', ['alta', 'critica'])
                    ->count(),
                'usuarios_con_email' => User::whereNotNull('email')->count(),
                'ultimo_envio' => Notificacion::whereIn('prioridad', ['alta', 'critica'])
                    ->latest()
                    ->first()?->created_at?->format('d/m/Y H:i') ?? 'N/A'
            ],
            'sms' => [
                'enviados_hoy' => Notificacion::whereDate('created_at', $hoy)
                    ->where('prioridad', 'critica')
                    ->count(),
                'enviados_semana' => Notificacion::where('created_at', '>=', $semana)
                    ->where('prioridad', 'critica')
                    ->count(),
                'usuarios_con_telefono' => User::whereNotNull('telefono')->count(),
                'ultimo_envio' => Notificacion::where('prioridad', 'critica')
                    ->latest()
                    ->first()?->created_at?->format('d/m/Y H:i') ?? 'N/A'
            ],
            'notificaciones' => [
                'total_pendientes' => Notificacion::where('estado', 'pendiente')->count(),
                'criticas_pendientes' => Notificacion::where('estado', 'pendiente')
                    ->where('prioridad', 'critica')
                    ->count(),
                'automaticas_hoy' => Notificacion::whereDate('created_at', $hoy)
                    ->where('es_automatica', true)
                    ->count()
            ]
        ];
    }

    /**
     * Método privado para obtener configuración
     */
    private function obtenerConfiguracion(): array
    {
        try {
            // Obtener configuración desde base de datos
            $configuracion = ConfiguracionServicio::obtenerConfiguracionServiciosExternos();
            
            // Mantener también en caché para compatibilidad
            Cache::forever(self::CONFIG_CACHE_KEY, $configuracion);
            
            return $configuracion;
            
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo configuración desde BD, usando caché: ' . $e->getMessage());
            
            // Fallback al caché si falla la BD
            $defaults = [
                'email_habilitado' => true,
                'sms_habilitado' => config('app.env') !== 'production',
                'resumen_diario_hora' => '08:00',
                'throttling_email' => 5,
                'throttling_sms' => 3,
                'destinatarios_resumen' => [],
            ];

            $persisted = Cache::get(self::CONFIG_CACHE_KEY, []);
            $merged = array_merge($defaults, is_array($persisted) ? $persisted : []);

            // Información dinámica del entorno
            $merged['ambiente'] = config('app.env');
            $merged['mail_driver'] = config('mail.default');
            $merged['queue_connection'] = config('queue.default');

            return $merged;
        }
    }

    /**
     * Obtener logs recientes
     */
    private function obtenerLogsRecientes(): array
    {
        return Notificacion::where('es_automatica', true)
            ->latest()
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->map(function ($notificacion) {
                return [
                    'id' => $notificacion->id,
                    'tipo' => $notificacion->tipo,
                    'usuario' => $notificacion->user->name ?? 'N/A',
                    'prioridad' => $notificacion->prioridad,
                    'created_at' => $notificacion->created_at->format('d/m/Y H:i:s'),
                    'titulo' => $notificacion->titulo
                ];
            })
            ->toArray();
    }

    /**
     * Obtener datos para gráficos
     */
    private function obtenerDatosGraficos(): array
    {
        $ultimos7Dias = collect(range(0, 6))->map(function ($day) {
            $fecha = Carbon::now()->subDays($day);
            return [
                'fecha' => $fecha->format('d/m'),
                'emails' => Notificacion::whereDate('created_at', $fecha)
                    ->whereIn('prioridad', ['alta', 'critica'])
                    ->count(),
                'sms' => Notificacion::whereDate('created_at', $fecha)
                    ->where('prioridad', 'critica')
                    ->count(),
                'notificaciones' => Notificacion::whereDate('created_at', $fecha)
                    ->where('es_automatica', true)
                    ->count()
            ];
        })->reverse()->values();

        $porTipo = Notificacion::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'tipo' => ucwords(str_replace('_', ' ', $item->tipo)),
                    'total' => $item->total
                ];
            });

        return [
            'timeline' => $ultimos7Dias,
            'por_tipo' => $porTipo
        ];
    }
}
