<?php

namespace App\Providers;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = config('elasticsearch');
            
            $clientBuilder = ClientBuilder::create()
                ->setHosts($config['hosts']);
            
            // Autenticación si está configurada
            if (!empty($config['username']) && !empty($config['password'])) {
                $clientBuilder->setBasicAuthentication(
                    $config['username'],
                    $config['password']
                );
            }
            
            // Logging si está habilitado
            if (isset($config['logging']['enabled']) && $config['logging']['enabled']) {
                $logger = \Illuminate\Support\Facades\Log::getLogger();
                $clientBuilder->setLogger($logger);
            }
            
            // SSL/TLS
            if (isset($config['scheme']) && $config['scheme'] === 'https') {
                $clientBuilder->setSSLVerification(true);
            }
            
            return $clientBuilder->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
