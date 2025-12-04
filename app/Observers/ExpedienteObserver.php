<?php

namespace App\Observers;

use App\Models\Expediente;
use App\Services\DocumentIndexingService;

class ExpedienteObserver
{
    protected DocumentIndexingService $indexingService;

    public function __construct(DocumentIndexingService $indexingService)
    {
        $this->indexingService = $indexingService;
    }

    public function created(Expediente $expediente): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexExpediente($expediente))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexExpediente($expediente);
        }
    }

    public function updated(Expediente $expediente): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexExpediente($expediente))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexExpediente($expediente);
        }
    }

    public function deleted(Expediente $expediente): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->elasticsearchService->deleteDocument('expedientes', (string) $expediente->id))
                ->onQueue(config('elasticsearch.queue.queue'));
        }
    }
}
