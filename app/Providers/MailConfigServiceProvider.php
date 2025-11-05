<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $mailManager) {
            // Interceptar la creación del transporte SMTP
            $mailManager->extend('smtp', function ($config) use ($mailManager) {
                // Crear el transporte usando el método protegido de Laravel mediante reflection
                $reflection = new \ReflectionClass($mailManager);
                $method = $reflection->getMethod('createSmtpTransport');
                $method->setAccessible(true);
                
                /** @var EsmtpTransport $transport */
                $transport = $method->invoke($mailManager, $config);
                
                // Obtener el stream y configurar las opciones SSL
                $stream = $transport->getStream();
                
                if ($stream instanceof SocketStream) {
                    // Obtener opciones SSL de la configuración o usar valores por defecto
                    $sslOptions = $config['stream']['ssl'] ?? [];
                    
                    // Obtener las opciones actuales del stream
                    $streamOptions = $stream->getStreamOptions();
                    
                    // Asegurar que las opciones SSL estén configuradas correctamente
                    // Merge: primero defaults, luego actuales, luego config
                    $streamOptions['ssl'] = array_merge([
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ], $streamOptions['ssl'] ?? [], $sslOptions);
                    
                    // Aplicar las opciones al stream
                    $stream->setStreamOptions($streamOptions);
                }
                
                return $transport;
            });
        });
    }
}
