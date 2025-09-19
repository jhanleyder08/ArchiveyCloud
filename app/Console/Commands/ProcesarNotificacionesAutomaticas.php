<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificacionService;

class ProcesarNotificacionesAutomaticas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notificaciones:procesar {--dry-run : Ejecutar sin crear notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar notificaciones automáticas del sistema';

    protected NotificacionService $notificacionService;

    public function __construct(NotificacionService $notificacionService)
    {
        parent::__construct();
        $this->notificacionService = $notificacionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Iniciando procesamiento de notificaciones automáticas...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Modo dry-run activado - No se crearán notificaciones reales');
            $this->newLine();
        }

        $resultados = $this->notificacionService->ejecutarTodasLasVerificaciones();

        // Mostrar resultados
        $this->info('📊 Resultados del procesamiento:');
        $this->table(
            ['Tipo de Verificación', 'Notificaciones Creadas'],
            [
                ['Expedientes próximos a vencer', $resultados['expedientes_proximos']],
                ['Expedientes vencidos', $resultados['expedientes_vencidos']],
                ['Préstamos próximos a vencer', $resultados['prestamos_proximos']],
                ['Préstamos vencidos', $resultados['prestamos_vencidos']],
                ['Disposiciones pendientes', $resultados['disposiciones_pendientes']],
                ['Notificaciones antiguas eliminadas', $resultados['limpieza_antiguas']],
            ]
        );

        $total = array_sum(array_slice($resultados, 0, -1)); // Excluir limpieza del total
        $this->newLine();
        
        if ($total > 0) {
            $this->info("✅ Se crearon {$total} notificaciones nuevas");
        } else {
            $this->comment('ℹ️  No se crearon notificaciones nuevas');
        }

        if ($resultados['limpieza_antiguas'] > 0) {
            $this->info("🧹 Se eliminaron {$resultados['limpieza_antiguas']} notificaciones antiguas");
        }

        $this->newLine();
        $this->info('🏁 Procesamiento completado');

        return Command::SUCCESS;
    }
}
