<?php

namespace App\Console\Commands;

use App\Models\WorkflowInstancia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando para limpiar workflows antiguos completados
 */
class CleanupOldWorkflows extends Command
{
    /**
     * Signature del comando
     */
    protected $signature = 'workflows:cleanup 
                            {--days=90 : Días de antigüedad para limpiar}
                            {--dry-run : Solo mostrar qué se eliminaría sin hacerlo}
                            {--force : Forzar sin confirmación}';

    /**
     * Descripción del comando
     */
    protected $description = 'Limpiar instancias de workflows completadas antiguas';

    /**
     * Ejecutar el comando
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("🔍 Buscando workflows completados hace más de {$days} días...");

        // Obtener workflows antiguos completados
        $query = WorkflowInstancia::where('estado', 'completado')
            ->where('fecha_finalizacion', '<', now()->subDays($days))
            ->with(['workflow', 'tareas']);

        $total = $query->count();

        if ($total === 0) {
            $this->info('✅ No hay workflows antiguos para limpiar');
            return self::SUCCESS;
        }

        $this->warn("📊 Se encontraron {$total} instancias de workflows para limpiar");

        // Mostrar detalles si es dry-run
        if ($dryRun) {
            $this->info("\n🔎 Modo DRY-RUN - No se eliminará nada\n");
            
            $instancias = $query->limit(10)->get();
            
            $this->table(
                ['ID', 'Workflow', 'Completado', 'Antigüedad'],
                $instancias->map(function ($instancia) {
                    return [
                        $instancia->id,
                        $instancia->workflow->nombre ?? 'N/A',
                        $instancia->fecha_finalizacion?->format('Y-m-d'),
                        $instancia->fecha_finalizacion?->diffForHumans() ?? 'N/A',
                    ];
                })
            );

            if ($total > 10) {
                $this->info("... y " . ($total - 10) . " más");
            }

            return self::SUCCESS;
        }

        // Confirmar eliminación
        if (!$force && !$this->confirm("¿Deseas eliminar {$total} instancias de workflows?")) {
            $this->info('❌ Operación cancelada');
            return self::FAILURE;
        }

        // Procesar eliminación
        $this->info("\n🗑️  Eliminando instancias antiguas...");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;
        $errors = 0;

        DB::transaction(function () use ($query, $bar, &$deleted, &$errors) {
            $query->chunk(100, function ($instancias) use ($bar, &$deleted, &$errors) {
                foreach ($instancias as $instancia) {
                    try {
                        // Eliminar tareas primero
                        $instancia->tareas()->delete();
                        
                        // Eliminar instancia
                        $instancia->delete();
                        
                        $deleted++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("\nError al eliminar instancia {$instancia->id}: " . $e->getMessage());
                    }
                    
                    $bar->advance();
                }
            });
        });

        $bar->finish();

        // Resumen
        $this->newLine(2);
        $this->info("✅ Limpieza completada:");
        $this->info("   - Eliminadas: {$deleted}");
        
        if ($errors > 0) {
            $this->warn("   - Errores: {$errors}");
        }

        // Optimizar tablas
        $this->info("\n🔧 Optimizando tablas...");
        DB::statement('OPTIMIZE TABLE workflow_instancias, workflow_tareas');
        
        $this->info('✅ Tablas optimizadas');

        return self::SUCCESS;
    }
}
