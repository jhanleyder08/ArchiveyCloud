<?php

namespace App\Observers;

use App\Models\Documento;
use App\Services\DocumentIndexingService;
use Illuminate\Support\Facades\Log;

class DocumentoObserver
{
    protected DocumentIndexingService $indexingService;

    public function __construct(DocumentIndexingService $indexingService)
    {
        $this->indexingService = $indexingService;
    }

    /**
     * Handle the Documento "created" event.
     */
    public function created(Documento $documento): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexDocument($documento))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexDocument($documento);
        }
    }

    /**
     * Handle the Documento "updated" event.
     */
    public function updated(Documento $documento): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->updateDocument($documento))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->updateDocument($documento);
        }
    }

    /**
     * Handle the Documento "deleted" event.
     */
    public function deleted(Documento $documento): void
    {
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->deleteDocument($documento->id))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->deleteDocument($documento->id);
        }
    }
}
