<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class TestEmailSend extends Command
{
    protected $signature = 'test:email {email}';
    protected $description = 'Prueba el envío de correo electrónico';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Intentando enviar correo a: ' . $email);
        $this->info('Configuración actual:');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->info('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->info('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
        
        try {
            Mail::raw('Este es un correo de prueba desde Archivey Cloud', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Prueba de correo - Archivey Cloud');
            });
            
            $this->info('✅ Correo enviado exitosamente!');
            $this->info('Revisa tu bandeja de entrada (y también la carpeta de spam)');
            
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correo:');
            $this->error($e->getMessage());
        }
        
        return 0;
    }
}
