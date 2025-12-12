<?php

namespace App\Observers;

use App\Models\Expediente;
use App\Models\IndiceElectronico;
use App\Services\DocumentIndexingService;
use App\Services\IndiceElectronicoService;
use Illuminate\Support\Facades\Log;

class ExpedienteObserver
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

    public function created(Expediente $expediente): void
    {
        // Indexación Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexExpediente($expediente))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexExpediente($expediente);
        }
        
        // Crear Índice Electrónico automáticamente
        $this->crearIndiceElectronico($expediente);
    }

    public function updated(Expediente $expediente): void
    {
        // Indexación Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->indexingService->indexExpediente($expediente))
                ->onQueue(config('elasticsearch.queue.queue'));
        } else {
            $this->indexingService->indexExpediente($expediente);
        }
        
        // Actualizar Índice Electrónico
        $this->actualizarIndiceElectronico($expediente);
    }

    public function deleted(Expediente $expediente): void
    {
        // Elasticsearch
        if (config('elasticsearch.queue.enabled')) {
            dispatch(fn() => $this->elasticsearchService->deleteDocument('expedientes', (string) $expediente->id))
                ->onQueue(config('elasticsearch.queue.queue'));
        }
        
        // Eliminar Índice Electrónico
        IndiceElectronico::where('tipo_entidad', 'expediente')
            ->where('entidad_id', $expediente->id)
            ->delete();
    }
    
    /**
     * Crear índice electrónico para el expediente
     */
    private function crearIndiceElectronico(Expediente $expediente): void
    {
        try {
            $usuario = auth()->user() ?? \App\Models\User::first();
            if ($usuario) {
                $this->indiceElectronicoService->indexarExpediente($expediente, $usuario);
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo crear índice electrónico para expediente {$expediente->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar índice electrónico del expediente
     */
    private function actualizarIndiceElectronico(Expediente $expediente): void
    {
        try {
            $usuario = auth()->user() ?? \App\Models\User::first();
            $indice = IndiceElectronico::where('tipo_entidad', 'expediente')
                ->where('entidad_id', $expediente->id)
                ->first();
                
            if ($indice && $usuario) {
                $this->indiceElectronicoService->actualizarIndice($indice, $expediente, $usuario);
            } elseif ($usuario) {
                $this->indiceElectronicoService->indexarExpediente($expediente, $usuario);
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo actualizar índice electrónico para expediente {$expediente->id}: " . $e->getMessage());
        }
    }
}
