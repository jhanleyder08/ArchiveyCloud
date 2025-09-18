<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class DiagnoseEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:diagnose {--send-test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose email configuration and send test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnóstico de Configuración de Email');
        $this->line('==========================================');
        
        // 1. Verificar configuración de correo
        $this->info('📧 Configuración actual de correo:');
        $this->table(['Variable', 'Valor'], [
            ['MAIL_MAILER', config('mail.default')],
            ['MAIL_HOST', config('mail.mailers.smtp.host')],
            ['MAIL_PORT', config('mail.mailers.smtp.port')],
            ['MAIL_USERNAME', config('mail.mailers.smtp.username') ? '***configurado***' : 'NO CONFIGURADO'],
            ['MAIL_PASSWORD', config('mail.mailers.smtp.password') ? '***configurado***' : 'NO CONFIGURADO'],
            ['MAIL_FROM_ADDRESS', config('mail.from.address')],
            ['MAIL_FROM_NAME', config('mail.from.name')],
            ['QUEUE_CONNECTION', config('queue.default')],
        ]);
        
        // 2. Verificar si los usuarios tienen email_verified_at null
        $usersUnverified = User::whereNull('email_verified_at')->count();
        $this->line('');
        $this->info("👥 Usuarios sin verificar email: {$usersUnverified}");
        
        // 3. Verificar conexión de queue si es necesaria
        if (config('mail.default') !== 'log') {
            $this->info('⚙️ Verificando conexión de queue...');
            try {
                \Illuminate\Support\Facades\Queue::size();
                $this->info('✅ Queue funcionando correctamente');
            } catch (\Exception $e) {
                $this->error('❌ Error en queue: ' . $e->getMessage());
            }
        }
        
        // 4. Comprobar el driver de correo
        $mailDriver = config('mail.default');
        $this->info("📮 Driver de correo actual: {$mailDriver}");
        
        if ($mailDriver === 'log') {
            $this->warn('⚠️  PROBLEMA ENCONTRADO: El driver de correo está configurado como "log"');
            $this->warn('   Esto significa que los correos se escriben en logs en lugar de enviarse');
            $this->warn('   Los correos de verificación aparecerán en: storage/logs/laravel.log');
        }
        
        // 5. Enviar email de prueba si se solicita
        if ($this->option('send-test')) {
            $this->info('📤 Enviando email de prueba...');
            
            try {
                Mail::raw('Este es un correo de prueba desde ArchiveyCloud', function ($message) {
                    $message->to('test@example.com')
                           ->subject('Email de Prueba - ArchiveyCloud');
                });
                
                if ($mailDriver === 'log') {
                    $this->info('✅ Email de prueba "enviado" (revisa storage/logs/laravel.log)');
                } else {
                    $this->info('✅ Email de prueba enviado exitosamente');
                }
            } catch (\Exception $e) {
                $this->error('❌ Error enviando email: ' . $e->getMessage());
            }
        }
        
        // 6. Soluciones recomendadas
        $this->line('');
        $this->info('🔧 SOLUCIONES RECOMENDADAS:');
        $this->line('');
        
        if ($mailDriver === 'log') {
            $this->line('1. Para ambiente de desarrollo, configura Mailtrap o similar:');
            $this->line('   MAIL_MAILER=smtp');
            $this->line('   MAIL_HOST=sandbox.smtp.mailtrap.io');
            $this->line('   MAIL_PORT=2525');
            $this->line('   MAIL_USERNAME=tu_username');
            $this->line('   MAIL_PASSWORD=tu_password');
            $this->line('');
            $this->line('2. O para Gmail (menos recomendado para desarrollo):');
            $this->line('   MAIL_MAILER=smtp');
            $this->line('   MAIL_HOST=smtp.gmail.com');
            $this->line('   MAIL_PORT=587');
            $this->line('   MAIL_USERNAME=tu_email@gmail.com');
            $this->line('   MAIL_PASSWORD=tu_app_password');
            $this->line('   MAIL_ENCRYPTION=tls');
        }
        
        $this->line('');
        $this->info('3. Después de configurar, ejecuta:');
        $this->line('   php artisan config:cache');
        $this->line('   php artisan queue:work (si usas queues)');
        
        return 0;
    }
}
