<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notificacion;
use Carbon\Carbon;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia notificaciones antiguas y archivadas del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de notificaciones...');

        // Eliminar notificaciones archivadas de más de 3 meses
        $notificacionesArchivadas = Notificacion::where('estado', 'archivada')
            ->where('updated_at', '<=', Carbon::now()->subMonths(3))
            ->delete();

        // Eliminar notificaciones leídas de más de 6 meses  
        $notificacionesLeidas = Notificacion::where('estado', 'leida')
            ->where('updated_at', '<=', Carbon::now()->subMonths(6))
            ->delete();

        // Eliminar notificaciones no críticas de más de 1 año
        $notificacionesAntiguas = Notificacion::where('prioridad', '!=', 'critica')
            ->where('created_at', '<=', Carbon::now()->subYear())
            ->delete();

        $totalEliminadas = $notificacionesArchivadas + $notificacionesLeidas + $notificacionesAntiguas;

        $this->info("✅ Limpieza completada:");
        $this->line("   → Notificaciones archivadas eliminadas: {$notificacionesArchivadas}");
        $this->line("   → Notificaciones leídas antiguas eliminadas: {$notificacionesLeidas}");
        $this->line("   → Notificaciones antiguas eliminadas: {$notificacionesAntiguas}");
        $this->info("   📊 Total eliminadas: {$totalEliminadas}");

        return Command::SUCCESS;
    }
}
