<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:email {email : El email de destino}';

    /**
     * The console command description.
     */
    protected $description = 'Prueba el envío de correos SMTP de Archivey Cloud';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('🚀 Enviando correo de prueba...');
        $this->info("📧 Destinatario: {$email}");
        $this->info("📤 Remitente: " . config('mail.from.address'));

        try {
            Mail::to($email)->send(new TestMail());
            
            $this->info('✅ ¡Correo enviado exitosamente!');
            $this->info('📨 Revisa la bandeja de entrada (y spam) del destinatario.');
            
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correo:');
            $this->error($e->getMessage());
            
            $this->newLine();
            $this->warn('🔧 Verifica:');
            $this->warn('1. Que MAIL_PASSWORD tenga la contraseña de aplicación de Gmail');
            $this->warn('2. Que la verificación en 2 pasos esté activada en Gmail');
            $this->warn('3. Que la configuración SMTP sea correcta');
        }
    }
}
