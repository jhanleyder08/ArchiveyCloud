<?php

namespace App\Console\Commands;

use App\Services\DocumentIndexingService;
use Illuminate\Console\Command;

class ElasticsearchReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:reindex 
                            {--type=all : Tipo de entidad a reindexar (all, documentos, expedientes)}
                            {--chunk=100 : Tamaño del lote para procesamiento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindexar todos los documentos y expedientes en Elasticsearch';

    protected DocumentIndexingService $indexingService;

    public function __construct(DocumentIndexingService $indexingService)
    {
        parent::__construct();
        $this->indexingService = $indexingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $chunkSize = (int) $this->option('chunk');

        $this->info('🔄 Iniciando reindexación...');
        $this->newLine();

        if ($type === 'all' || $type === 'documentos') {
            $this->reindexDocuments($chunkSize);
        }

        if ($type === 'all' || $type === 'expedientes') {
            $this->reindexExpedientes($chunkSize);
        }

        $this->newLine();
        $this->info('🎉 Reindexación completada');

        return Command::SUCCESS;
    }

    protected function reindexDocuments(int $chunkSize): void
    {
        $this->info('📄 Reindexando documentos...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();

        $result = $this->indexingService->reindexAllDocuments($chunkSize);
        
        $bar->finish();
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $result['total']],
                ['Indexados exitosamente', $result['indexed']],
                ['Errores', $result['errors']],
            ]
        );
    }

    protected function reindexExpedientes(int $chunkSize): void
    {
        $this->info('📁 Reindexando expedientes...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();

        $result = $this->indexingService->reindexAllExpedientes($chunkSize);
        
        $bar->finish();
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $result['total']],
                ['Indexados exitosamente', $result['indexed']],
                ['Errores', $result['errors']],
            ]
        );
    }
}
