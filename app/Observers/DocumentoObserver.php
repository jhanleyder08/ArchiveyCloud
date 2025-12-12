<?php

namespace App\Observers;

use App\Models\Documento;
use App\Models\IndiceElectronico;
use App\Services\DocumentIndexingService;
use App\Services\IndiceElectronicoService;
use Illuminate\Support\Facades\Log;

class DocumentoObserver
{
    protected DocumentIndexingService $indexingService;
    protected IndiceElectronicoService $indiceElectronicoService;

    public function __construct(
        DocumentIndexingService $indexingService,
        IndiceElectronicoService $indiceElectronicoService
    ) {
        $this->indexingService = $indexingService;
        $this->indiceElectronicoService = $indiceElectronicoService;
    }

    /**
     * Handle the Documento "created" event.
     */
    public function created(Documento $documento): void
    {
        // Indexación Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexDocument($documento))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexDocument($documento);
        }
        
        // Crear Índice Electrónico automáticamente
        $this->crearIndiceElectronico($documento);
    }

    /**
     * Handle the Documento "updated" event.
     */
    public function updated(Documento $documento): void
    {
        // Indexación Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->updateDocument($documento))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->updateDocument($documento);
        }
        
        // Actualizar Índice Electrónico
        $this->actualizarIndiceElectronico($documento);
    }

    /**
     * Handle the Documento "deleted" event.
     */
    public function deleted(Documento $documento): void
    {
        // Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->deleteDocument($documento->id))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->deleteDocument($documento->id);
        }
        
        // Eliminar Índice Electrónico
        IndiceElectronico::where('tipo_entidad', 'documento')
            ->where('entidad_id', $documento->id)
            ->delete();
    }
    
    /**
     * Crear índice electrónico para el documento
     */
    private function crearIndiceElectronico(Documento $documento): void
    {
        try {
            $usuario = auth()->user() ?? \App\Models\User::first();
            if ($usuario) {
                $this->indiceElectronicoService->indexarDocumento($documento, $usuario);
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo crear índice electrónico para documento {$documento->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar índice electrónico del documento
     */
    private function actualizarIndiceElectronico(Documento $documento): void
    {
        try {
            $usuario = auth()->user() ?? \App\Models\User::first();
            $indice = IndiceElectronico::where('tipo_entidad', 'documento')
                ->where('entidad_id', $documento->id)
                ->first();
                
            if ($indice && $usuario) {
                $this->indiceElectronicoService->actualizarIndice($indice, $documento, $usuario);
            } elseif ($usuario) {
                $this->indiceElectronicoService->indexarDocumento($documento, $usuario);
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo actualizar índice electrónico para documento {$documento->id}: " . $e->getMessage());
        }
    }
}
