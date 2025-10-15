<?php

namespace App\Console\Commands;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Services\WorkflowEngineService;
use App\Services\ApprovalWorkflowService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Comando para procesamiento automático de workflows
 */
class ProcessWorkflowsCommand extends Command
{
    protected $signature = 'workflows:process 
                           {--escalate : Escalar tareas vencidas}
                           {--cleanup : Limpiar workflows antiguos}
                           {--notify : Enviar notificaciones de recordatorio}
                           {--all : Ejecutar todas las acciones}';

    protected $description = 'Procesar workflows automáticamente: escalamiento, limpieza y notificaciones';

    protected WorkflowEngineService $workflowEngine;
    protected ApprovalWorkflowService $approvalService;
    protected array $estadisticas = [
        'escalados' => 0,
        'notificados' => 0,
        'limpiados' => 0,
        'errores' => 0
    ];

    public function __construct(
        WorkflowEngineService $workflowEngine,
        ApprovalWorkflowService $approvalService
    ) {
        parent::__construct();
        $this->workflowEngine = $workflowEngine;
        $this->approvalService = $approvalService;
    }

    public function handle(): int
    {
        $this->info('🔄 Iniciando procesamiento automático de workflows...');
        $this->line('');

        $inicioEjecucion = now();

        try {
            if ($this->option('all') || $this->option('escalate')) {
                $this->escalarTareasVencidas();
            }

            if ($this->option('all') || $this->option('notify')) {
                $this->enviarNotificacionesRecordatorio();
            }

            if ($this->option('all') || $this->option('cleanup')) {
                $this->limpiarWorkflowsAntiguos();
            }

            $duracion = $inicioEjecucion->diffInSeconds(now());
            $this->mostrarResumen($duracion);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante el procesamiento: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Escalar tareas vencidas automáticamente
     */
    private function escalarTareasVencidas(): void
    {
        $this->info('⏰ Escalando tareas vencidas...');
        
        try {
            $resultados = $this->approvalService->escalarAprobacionesVencidas();
            
            $this->estadisticas['escalados'] = count($resultados);
            
            if (count($resultados) > 0) {
                $this->warn("⚠️  Se escalaron " . count($resultados) . " tareas vencidas:");
                
                $table = [];
                foreach ($resultados as $resultado) {
                    $table[] = [
                        $resultado['tarea_id'],
                        $resultado['accion'],
                        $resultado['supervisor_nombre'] ?? $resultado['motivo'] ?? 'N/A'
                    ];
                }
                
                $this->table(['Tarea ID', 'Acción', 'Supervisor/Motivo'], $table);
            } else {
                $this->info('✅ No hay tareas vencidas para escalar');
            }
            
        } catch (\Exception $e) {
            $this->error("Error escalando tareas: {$e->getMessage()}");
            $this->estadisticas['errores']++;
        }
    }

    /**
     * Enviar notificaciones de recordatorio
     */
    private function enviarNotificacionesRecordatorio(): void
    {
        $this->info('📧 Enviando notificaciones de recordatorio...');
        
        try {
            // Tareas próximas a vencer (24 horas)
            $tareasProximasVencer = WorkflowTask::where('estado', 'pendiente')
                ->where('fecha_limite', '>', now())
                ->where('fecha_limite', '<=', now()->addDay())
                ->with(['instancia', 'asignaciones.usuario'])
                ->get();
            
            $notificacionesEnviadas = 0;
            
            foreach ($tareasProximasVencer as $tarea) {
                foreach ($tarea->asignaciones as $asignacion) {
                    if ($asignacion->activo && $asignacion->usuario) {
                        // Enviar notificación de recordatorio
                        $asignacion->usuario->notify(
                            new \App\Notifications\WorkflowTaskReminderNotification($tarea)
                        );
                        $notificacionesEnviadas++;
                    }
                }
            }
            
            $this->estadisticas['notificados'] = $notificacionesEnviadas;
            
            if ($notificacionesEnviadas > 0) {
                $this->info("📬 Se enviaron {$notificacionesEnviadas} notificaciones de recordatorio");
            } else {
                $this->info('✅ No hay tareas próximas a vencer');
            }
            
        } catch (\Exception $e) {
            $this->error("Error enviando notificaciones: {$e->getMessage()}");
            $this->estadisticas['errores']++;
        }
    }

    /**
     * Limpiar workflows antiguos completados
     */
    private function limpiarWorkflowsAntiguos(): void
    {
        $this->info('🧹 Limpiando workflows antiguos...');
        
        try {
            $fechaLimite = now()->subMonths(6); // 6 meses de antigüedad
            
            $workflowsAntiguos = WorkflowInstance::where('estado', 'completado')
                ->where('fecha_completado', '<', $fechaLimite)
                ->count();
            
            if ($workflowsAntiguos > 0) {
                $confirmacion = $this->confirm(
                    "¿Confirma la limpieza de {$workflowsAntiguos} workflows completados hace más de 6 meses?"
                );
                
                if ($confirmacion) {
                    // Archivar en lugar de eliminar
                    $archivados = WorkflowInstance::where('estado', 'completado')
                        ->where('fecha_completado', '<', $fechaLimite)
                        ->update([
                            'archivado' => true,
                            'fecha_archivado' => now()
                        ]);
                    
                    $this->estadisticas['limpiados'] = $archivados;
                    $this->info("📦 Se archivaron {$archivados} workflows antiguos");
                } else {
                    $this->info('❌ Limpieza cancelada por el usuario');
                }
            } else {
                $this->info('✅ No hay workflows antiguos para limpiar');
            }
            
        } catch (\Exception $e) {
            $this->error("Error en limpieza: {$e->getMessage()}");
            $this->estadisticas['errores']++;
        }
    }

    /**
     * Mostrar resumen de la ejecución
     */
    private function mostrarResumen(int $duracion): void
    {
        $this->line('');
        $this->info('📊 Resumen de Procesamiento:');
        $this->line('');

        $this->table(
            ['Acción', 'Cantidad'],
            [
                ['Tareas escaladas', $this->estadisticas['escalados']],
                ['Notificaciones enviadas', $this->estadisticas['notificados']],
                ['Workflows archivados', $this->estadisticas['limpiados']],
                ['Errores encontrados', $this->estadisticas['errores']]
            ]
        );

        $this->line('');
        $this->info("⏱️  Tiempo total: {$duracion} segundos");

        // Mostrar recomendaciones
        $this->mostrarRecomendaciones();
    }

    /**
     * Mostrar recomendaciones basadas en los resultados
     */
    private function mostrarRecomendaciones(): void
    {
        $recomendaciones = [];

        if ($this->estadisticas['escalados'] > 5) {
            $recomendaciones[] = "⚠️  Alto número de escalamientos - revisar tiempos límite de tareas";
        }

        if ($this->estadisticas['notificados'] > 20) {
            $recomendaciones[] = "📬 Muchas tareas próximas a vencer - considere redistribuir carga de trabajo";
        }

        if ($this->estadisticas['errores'] > 0) {
            $recomendaciones[] = "❌ Se encontraron errores - revisar logs del sistema";
        }

        if ($this->estadisticas['escalados'] === 0 && $this->estadisticas['notificados'] === 0) {
            $recomendaciones[] = "🎉 Sistema de workflows funcionando correctamente";
        }

        if (!empty($recomendaciones)) {
            $this->line('');
            $this->warn('📋 Recomendaciones:');
            foreach ($recomendaciones as $recomendacion) {
                $this->line("  • {$recomendacion}");
            }
        }
    }
}
