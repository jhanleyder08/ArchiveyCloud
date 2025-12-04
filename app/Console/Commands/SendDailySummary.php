<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificacionEmailService;
use App\Models\User;
use App\Models\Notificacion;
use App\Models\ConfiguracionServicio;
use Carbon\Carbon;

class SendDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-daily-summary 
                          {--user= : ID específico de usuario}
                          {--dry-run : Solo mostrar estadísticas sin enviar emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar resumen diario de notificaciones a usuarios administrativos';

    private $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new NotificacionEmailService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📅 Enviando resúmenes diarios de notificaciones...');
        $this->newLine();

        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        // Obtener usuarios para resumen
        $usuarios = $this->obtenerUsuariosParaResumen($userId);

        if ($usuarios->isEmpty()) {
            $this->warn('⚠️  No se encontraron usuarios para enviar resumen');
            return Command::SUCCESS;
        }

        $this->info("👥 Usuarios para resumen: {$usuarios->count()}");
        $this->newLine();

        $resumenesEnviados = 0;
        $errores = 0;

        foreach ($usuarios as $usuario) {
            try {
                $estadisticas = $this->obtenerEstadisticasUsuario($usuario);
                
                if ($dryRun) {
                    $this->mostrarEstadisticasUsuario($usuario, $estadisticas);
                } else {
                    $enviado = $this->enviarResumenUsuario($usuario, $estadisticas);
                    
                    if ($enviado) {
                        $resumenesEnviados++;
                        $this->line("✅ {$usuario->name} - Resumen enviado");
                    } else {
                        $errores++;
                        $this->error("❌ {$usuario->name} - Error enviando resumen");
                    }
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("❌ {$usuario->name} - Exception: {$e->getMessage()}");
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info('🧪 Modo dry-run completado - No se enviaron emails');
        } else {
            $this->info("✅ Resúmenes procesados:");
            $this->line("   📧 Enviados exitosamente: {$resumenesEnviados}");
            $this->line("   ❌ Errores: {$errores}");
        }

        return Command::SUCCESS;
    }

    /**
     * Obtener usuarios para enviar resumen
     */
    private function obtenerUsuariosParaResumen($userId = null)
    {
        if ($userId) {
            return User::where('id', $userId)->get();
        }

        try {
            // Obtener configuración de destinatarios específicos
            $config = ConfiguracionServicio::obtenerConfiguracionServiciosExternos();
            $destinatariosEspecificos = $config['destinatarios_resumen'] ?? [];

            if (!empty($destinatariosEspecificos)) {
                $this->line("📋 Usando destinatarios configurados: " . count($destinatariosEspecificos) . " usuarios");
                
                return User::whereIn('id', $destinatariosEspecificos)
                    ->where('estado_cuenta', 'activo')
                    ->whereNotNull('email')
                    ->get();
            }

        } catch (\Exception $e) {
            $this->warn("⚠️  Error obteniendo configuración de destinatarios: " . $e->getMessage());
        }

        // Fallback: usuarios con roles administrativos
        $this->line("📋 Usando destinatarios por roles administrativos");
        
        return User::whereHas('role', function ($query) {
            $query->whereIn('name', [
                'Super Administrador',
                'Administrador SGDEA',
                'Gestor Documental'
            ]);
        })
        ->where('estado_cuenta', 'activo')
        ->whereNotNull('email')
        ->get();
    }

    /**
     * Obtener estadísticas del usuario
     */
    private function obtenerEstadisticasUsuario(User $usuario): array
    {
        $ayer = Carbon::yesterday();
        $hace7Dias = Carbon::now()->subDays(7);

        return [
            // Notificaciones del usuario
            'notificaciones_pendientes' => Notificacion::where('user_id', $usuario->id)
                ->where('estado', 'pendiente')
                ->count(),
            
            'notificaciones_ayer' => Notificacion::where('user_id', $usuario->id)
                ->whereDate('created_at', $ayer)
                ->count(),
            
            'notificaciones_criticas' => Notificacion::where('user_id', $usuario->id)
                ->where('prioridad', 'critica')
                ->where('estado', 'pendiente')
                ->count(),

            'notificaciones_semana' => Notificacion::where('user_id', $usuario->id)
                ->where('created_at', '>=', $hace7Dias)
                ->count(),

            // Estadísticas globales del sistema
            'total_notificaciones_sistema' => Notificacion::whereDate('created_at', $ayer)->count(),
            
            'usuarios_activos_ayer' => User::where('ultimo_acceso', '>=', $ayer)->count(),
            
            // Top tipos de notificaciones
            'tipos_mas_frecuentes' => Notificacion::where('user_id', $usuario->id)
                ->where('created_at', '>=', $hace7Dias)
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
                })
                ->toArray(),
        ];
    }

    /**
     * Mostrar estadísticas del usuario (modo dry-run)
     */
    private function mostrarEstadisticasUsuario(User $usuario, array $stats): void
    {
        $this->line("👤 {$usuario->name} ({$usuario->email}):");
        $this->line("   📋 Notificaciones pendientes: {$stats['notificaciones_pendientes']}");
        $this->line("   📅 Notificaciones ayer: {$stats['notificaciones_ayer']}");
        $this->line("   🚨 Críticas pendientes: {$stats['notificaciones_criticas']}");
        $this->line("   📊 Notificaciones esta semana: {$stats['notificaciones_semana']}");
        
        if (!empty($stats['tipos_mas_frecuentes'])) {
            $this->line("   🏆 Tipos más frecuentes:");
            foreach ($stats['tipos_mas_frecuentes'] as $tipo) {
                $this->line("      - {$tipo['tipo']}: {$tipo['total']}");
            }
        }
        
        $this->newLine();
    }

    /**
     * Enviar resumen al usuario
     */
    private function enviarResumenUsuario(User $usuario, array $stats): bool
    {
        // Solo enviar si hay notificaciones pendientes o actividad reciente
        if ($stats['notificaciones_pendientes'] == 0 && $stats['notificaciones_ayer'] == 0) {
            return true; // No hay nada que reportar, pero no es un error
        }

        // Crear título dinámico
        $titulo = $this->generarTituloResumen($stats);
        
        // Crear mensaje del resumen
        $mensaje = $this->generarMensajeResumen($usuario, $stats);

        // Crear notificación de resumen
        $notificacionResumen = new Notificacion([
            'user_id' => $usuario->id,
            'tipo' => 'resumen_diario',
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'prioridad' => $stats['notificaciones_criticas'] > 0 ? 'alta' : 'media',
            'estado' => 'pendiente',
            'es_automatica' => true,
            'accion_url' => '/admin/notificaciones',
            'datos' => $stats
        ]);

        $notificacionResumen->user = $usuario;

        return $this->emailService->enviarNotificacion($notificacionResumen);
    }

    /**
     * Generar título dinámico para el resumen
     */
    private function generarTituloResumen(array $stats): string
    {
        $pendientes = $stats['notificaciones_pendientes'];
        $criticas = $stats['notificaciones_criticas'];

        if ($criticas > 0) {
            return "🚨 Resumen Diario: {$criticas} notificaciones críticas pendientes";
        }

        if ($pendientes > 5) {
            return "📋 Resumen Diario: {$pendientes} notificaciones requieren atención";
        }

        if ($pendientes > 0) {
            return "📅 Resumen Diario: {$pendientes} notificaciones pendientes";
        }

        return "✅ Resumen Diario: Sistema actualizado";
    }

    /**
     * Generar mensaje detallado del resumen
     */
    private function generarMensajeResumen(User $usuario, array $stats): string
    {
        $mensaje = "Hola {$usuario->name},\n\n";
        $mensaje .= "Aquí está tu resumen diario de ArchiveyCloud:\n\n";

        // Sección de notificaciones personales
        $mensaje .= "📋 TUS NOTIFICACIONES:\n";
        $mensaje .= "• Pendientes: {$stats['notificaciones_pendientes']}\n";
        $mensaje .= "• Recibidas ayer: {$stats['notificaciones_ayer']}\n";
        
        if ($stats['notificaciones_criticas'] > 0) {
            $mensaje .= "• 🚨 CRÍTICAS pendientes: {$stats['notificaciones_criticas']}\n";
        }
        
        $mensaje .= "\n";

        // Top tipos
        if (!empty($stats['tipos_mas_frecuentes'])) {
            $mensaje .= "📊 PRINCIPALES TIPOS (últimos 7 días):\n";
            foreach (array_slice($stats['tipos_mas_frecuentes'], 0, 3) as $tipo) {
                $mensaje .= "• {$tipo['tipo']}: {$tipo['total']}\n";
            }
            $mensaje .= "\n";
        }

        // Estadísticas del sistema
        $mensaje .= "🌐 ACTIVIDAD DEL SISTEMA:\n";
        $mensaje .= "• Notificaciones generadas ayer: {$stats['total_notificaciones_sistema']}\n";
        $mensaje .= "• Usuarios activos ayer: {$stats['usuarios_activos_ayer']}\n\n";

        // Llamada a la acción
        if ($stats['notificaciones_pendientes'] > 0) {
            $mensaje .= "👆 Ingresa al sistema para revisar tus notificaciones pendientes.\n\n";
        }

        $mensaje .= "Este resumen se envía diariamente a las " . now()->format('H:i') . " horas.\n";
        $mensaje .= "ArchiveyCloud - Sistema de Gestión Documental";

        return $mensaje;
    }
}
