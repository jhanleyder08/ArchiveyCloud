<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $mailManager) {
            $mailManager->extend('smtp', function ($config) {
                $transport = new EsmtpTransport(
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 25,
                    $config['encryption'] ?? null
                );

                if (isset($config['username']) && isset($config['password'])) {
                    $transport->setUsername($config['username']);
                    $transport->setPassword($config['password']);
                }

                // Configurar opciones SSL para desactivar verificación
                $streamOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $transport->setStreamOptions($streamOptions);

                return $transport;
            });
        });
    }
}
