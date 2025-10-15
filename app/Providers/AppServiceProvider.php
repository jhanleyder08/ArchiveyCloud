<?php

namespace App\Providers;

use App\Models\Documento;
use App\Models\Expediente;
use App\Observers\DocumentoObserver;
use App\Observers\ExpedienteObserver;
use App\Events\ValidationFailedEvent;
use App\Events\ValidationPassedEvent;
use App\Events\DocumentSignedEvent;
use App\Events\SignatureValidationFailedEvent;
use App\Events\CertificateExpiringEvent;
use App\Listeners\LogValidationResults;
use App\Listeners\NotifyValidationIssues;
use App\Listeners\SignatureEventListener;
use Illuminate\Support\Facades\Event;
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
        // Registrar eventos de validación
        $this->registerValidationEvents();

        // Registrar observers de Elasticsearch
        if (config('elasticsearch.queue.enabled', false)) {
            Documento::observe(DocumentoObserver::class);
            Expediente::observe(ExpedienteObserver::class);
        }
    }

    /**
     * Registrar listeners para eventos de validación
     */
    private function registerValidationEvents(): void
    {
        Event::listen([
            ValidationFailedEvent::class,
        ], LogValidationResults::class . '@handleValidationFailed');

        Event::listen([
            ValidationPassedEvent::class,
        ], LogValidationResults::class . '@handleValidationPassed');

        Event::listen([
            ValidationFailedEvent::class,
        ], NotifyValidationIssues::class);

        // Eventos de firmas digitales
        Event::listen([
            DocumentSignedEvent::class,
        ], SignatureEventListener::class . '@handleDocumentSigned');

        Event::listen([
            SignatureValidationFailedEvent::class,
        ], SignatureEventListener::class . '@handleSignatureValidationFailed');

        Event::listen([
            CertificateExpiringEvent::class,
        ], SignatureEventListener::class . '@handleCertificateExpiring');
    }
}
