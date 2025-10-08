<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ElasticsearchSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:setup 
                            {--force : Eliminar índices existentes antes de crearlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar índices de Elasticsearch para el sistema';

    protected ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        parent::__construct();
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Configurando Elasticsearch...');
        
        // Verificar conexión
        if (!$this->elasticsearchService->ping()) {
            $this->error('❌ No se puede conectar a Elasticsearch. Verifica la configuración.');
            return Command::FAILURE;
        }
        
        $this->info('✅ Conexión a Elasticsearch exitosa');

        $indices = ['documentos', 'expedientes'];
        $force = $this->option('force');

        foreach ($indices as $index) {
            $this->info("📝 Procesando índice: {$index}");
            
            if ($force) {
                $this->warn("⚠️  Eliminando índice existente: {$index}");
                $this->elasticsearchService->deleteIndex($index);
            }

            $this->info("➕ Creando índice: {$index}");
            if ($this->elasticsearchService->createIndexIfNotExists($index)) {
                $this->info("✅ Índice {$index} configurado correctamente");
            } else {
                $this->error("❌ Error al configurar índice {$index}");
            }
        }

        $this->newLine();
        $this->info('🎉 Configuración de Elasticsearch completada');
        $this->info('💡 Ejecuta "php artisan elasticsearch:reindex" para indexar documentos existentes');

        return Command::SUCCESS;
    }
}
