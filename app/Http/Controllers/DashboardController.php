<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\User;
use App\Models\SerieDocumental;
use App\Models\PistaAuditoria;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Métricas generales
        $metricas = [
            'total_documentos' => Documento::count(),
            'total_expedientes' => Expediente::count(),
            'total_usuarios' => User::where('active', true)->count(),
            'total_series' => SerieDocumental::where('activa', true)->count(),
            'documentos_hoy' => Documento::whereDate('created_at', today())->count(),
            'expedientes_hoy' => Expediente::whereDate('created_at', today())->count(),
            'documentos_semana' => Documento::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'expedientes_semana' => Expediente::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
        ];

        // Calcular almacenamiento total
        $almacenamiento_bytes = Documento::sum('tamano_bytes') ?? 0;
        $almacenamiento_mb = round($almacenamiento_bytes / (1024 * 1024), 2);
        $almacenamiento_gb = round($almacenamiento_mb / 1024, 2);
        $metricas['almacenamiento_mb'] = $almacenamiento_mb;
        $metricas['almacenamiento_gb'] = $almacenamiento_gb;

        // Actividad reciente del usuario
        $actividad_reciente = PistaAuditoria::where('usuario_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'accion' => $item->accion ?? $item->operacion,
                    'descripcion' => $item->descripcion ?? $item->accion_detalle ?? 'Sin descripción',
                    'fecha' => $item->created_at->format('d/m/Y H:i'),
                    'modulo' => $item->modulo ?? 'SGDEA',
                ];
            });

        // Notificaciones pendientes del usuario
        $notificaciones_pendientes = Notificacion::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'titulo' => $item->titulo,
                    'mensaje' => $item->mensaje,
                    'prioridad' => $item->prioridad,
                    'fecha' => $item->created_at->format('d/m/Y H:i'),
                ];
            });

        // Documentos recientes del usuario
        $documentos_recientes = Documento::where(function($query) use ($user) {
                $query->where('created_by', $user->id)
                      ->orWhere('productor_id', $user->id);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'codigo' => $item->codigo ?? $item->codigo_documento,
                    'titulo' => $item->titulo ?? 'Sin título',
                    'fecha' => $item->created_at->format('d/m/Y'),
                    'estado' => $item->estado_ciclo_vida ?? $item->estado ?? 'activo',
                ];
            });

        // Expedientes recientes del usuario
        $expedientes_recientes = Expediente::where('created_by', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'numero_expediente' => $item->numero_expediente ?? $item->codigo,
                    'titulo' => $item->titulo ?? 'Sin título',
                    'fecha' => $item->created_at->format('d/m/Y'),
                    'estado' => $item->estado_ciclo_vida ?? $item->estado ?? 'abierto',
                ];
            });

        return Inertia::render('dashboard', [
            'metricas' => $metricas,
            'actividad_reciente' => $actividad_reciente,
            'notificaciones_pendientes' => $notificaciones_pendientes,
            'documentos_recientes' => $documentos_recientes,
            'expedientes_recientes' => $expedientes_recientes,
            'usuario' => [
                'nombre' => $user->name,
                'email' => $user->email,
                'rol' => $user->role->name ?? 'Usuario',
            ],
        ]);
    }
}

