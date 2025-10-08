<?php

namespace App\Providers;

use App\Models\Documento;
use App\Models\Expediente;
use App\Observers\DocumentoObserver;
use App\Observers\ExpedienteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers de Elasticsearch
        if (config('elasticsearch.queue.enabled', false)) {
            Documento::observe(DocumentoObserver::class);
            Expediente::observe(ExpedienteObserver::class);
        }
    }
}
