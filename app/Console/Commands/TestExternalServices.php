<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificacionEmailService;
use App\Services\NotificacionSmsService;
use App\Models\User;
use App\Models\Notificacion;

class TestExternalServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:test-external 
                          {--email : Probar servicio de email}
                          {--sms : Probar servicio de SMS}
                          {--phone= : Número de teléfono para prueba SMS}
                          {--user= : ID del usuario para pruebas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar servicios externos (Email y SMS) del sistema de notificaciones';

    private $emailService;
    private $smsService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new NotificacionEmailService();
        $this->smsService = new NotificacionSmsService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando servicios externos de ArchiveyCloud...');
        $this->newLine();

        $testEmail = $this->option('email');
        $testSms = $this->option('sms');
        $phone = $this->option('phone');
        $userId = $this->option('user');

        // Si no se especifica ninguna opción, probar todo
        if (!$testEmail && !$testSms) {
            $testEmail = true;
            $testSms = true;
        }

        // Obtener usuario para pruebas
        $usuario = $this->obtenerUsuarioPrueba($userId);
        if (!$usuario) {
            $this->error('❌ No se encontró usuario válido para pruebas');
            return Command::FAILURE;
        }

        $this->info("👤 Usuario de prueba: {$usuario->name} ({$usuario->email})");
        $this->newLine();

        // Resultados
        $resultados = [
            'email' => false,
            'sms' => false,
            'detalles' => []
        ];

        // Probar servicio de email
        if ($testEmail) {
            $resultados['email'] = $this->probarServicioEmail($usuario);
        }

        // Probar servicio de SMS
        if ($testSms) {
            $resultados['sms'] = $this->probarServicioSms($usuario, $phone);
        }

        // Mostrar estadísticas
        $this->mostrarEstadisticas();

        // Resumen final
        $this->mostrarResumen($resultados);

        return Command::SUCCESS;
    }

    /**
     * Obtener usuario para pruebas
     */
    private function obtenerUsuarioPrueba($userId = null): ?User
    {
        if ($userId) {
            return User::find($userId);
        }

        // Buscar admin@archiveycloud.com
        $admin = User::where('email', 'admin@archiveycloud.com')->first();
        if ($admin) {
            return $admin;
        }

        // Si no, obtener el primer usuario disponible
        return User::first();
    }

    /**
     * Probar servicio de email
     */
    private function probarServicioEmail(User $usuario): bool
    {
        $this->info('📧 Probando servicio de EMAIL...');

        try {
            // Crear notificación de prueba
            $notificacion = new Notificacion([
                'user_id' => $usuario->id,
                'tipo' => 'prueba_email',
                'titulo' => 'Prueba de Email - ArchiveyCloud',
                'mensaje' => 'Este es un email de prueba del sistema de notificaciones automáticas de ArchiveyCloud. Si recibes este mensaje, el servicio de email está funcionando correctamente.',
                'prioridad' => 'media',
                'estado' => 'pendiente',
                'es_automatica' => true,
                'accion_url' => '/admin/notificaciones',
                'datos' => [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                    'ambiente' => config('app.env')
                ]
            ]);

            $notificacion->user = $usuario;

            // Intentar enviar email
            $enviado = $this->emailService->enviarNotificacion($notificacion);

            if ($enviado) {
                $this->line('   ✅ Email de prueba enviado exitosamente');
                $this->line("   📨 Destinatario: {$usuario->email}");
                $this->line('   📋 Asunto: 📋 Prueba de Email - ArchiveyCloud');
                return true;
            } else {
                $this->line('   ❌ Error enviando email de prueba');
                return false;
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: {$e->getMessage()}");
            return false;
        } finally {
            $this->newLine();
        }
    }

    /**
     * Probar servicio de SMS
     */
    private function probarServicioSms(User $usuario, $phone = null): bool
    {
        $this->info('📱 Probando servicio de SMS...');

        // Si se proporciona teléfono, usarlo; si no, usar el del usuario
        $telefono = $phone ?? $usuario->telefono ?? null;

        if (!$telefono) {
            $this->line('   ⚠️  No se proporcionó número de teléfono');
            $this->line('   💡 Usa --phone=+573001234567 para probar SMS');
            $this->newLine();
            return false;
        }

        try {
            // Probar configuración primero
            $this->line('   🔧 Verificando configuración SMS...');
            $config = $this->smsService->probarConfiguracion($telefono);

            foreach ($config['detalles'] as $detalle) {
                if (str_contains($detalle, 'Error') || str_contains($detalle, 'no configurada') || str_contains($detalle, 'no disponible')) {
                    $this->line("   ⚠️  {$detalle}");
                } else {
                    $this->line("   ✅ {$detalle}");
                }
            }

            if (!$config['configuracion_ok']) {
                $this->line('   ❌ Configuración SMS no válida');
                return false;
            }

            // Si estamos en local, simular envío
            if (config('app.env') === 'local') {
                $this->line('   📱 SMS simulado (ambiente local)');
                $this->line("   📞 Teléfono: {$telefono}");
                $this->line('   💬 Mensaje: 🚨 ArchiveyCloud: Prueba SMS - Ver: ' . config('app.url') . '/admin');
                return true;
            }

            return $config['mensaje_prueba_enviado'];

        } catch (\Exception $e) {
            $this->error("   ❌ Exception: {$e->getMessage()}");
            return false;
        } finally {
            $this->newLine();
        }
    }

    /**
     * Mostrar estadísticas de servicios
     */
    private function mostrarEstadisticas(): void
    {
        $this->info('📊 ESTADÍSTICAS DE SERVICIOS:');
        
        // Estadísticas de email
        try {
            $statsEmail = $this->emailService->obtenerEstadisticas();
            $this->line('   📧 Email:');
            $this->line("      - Emails hoy: {$statsEmail['emails_hoy']}");
            $this->line("      - Emails esta semana: {$statsEmail['emails_semana']}");
            $this->line("      - Usuarios activos: {$statsEmail['usuarios_activos_email']}");
        } catch (\Exception $e) {
            $this->line('   📧 Email: Error obteniendo estadísticas');
        }

        // Estadísticas de SMS
        try {
            $statsSms = $this->smsService->obtenerEstadisticas();
            $this->line('   📱 SMS:');
            $this->line("      - SMS hoy: {$statsSms['sms_hoy']}");
            $this->line("      - SMS esta semana: {$statsSms['sms_semana']}");
            $this->line("      - Usuarios con teléfono: {$statsSms['usuarios_con_telefono']}");
        } catch (\Exception $e) {
            $this->line('   📱 SMS: Error obteniendo estadísticas');
        }

        $this->newLine();
    }

    /**
     * Mostrar resumen final
     */
    private function mostrarResumen(array $resultados): void
    {
        $this->info('📋 RESUMEN DE PRUEBAS:');

        $emailStatus = $resultados['email'] ? '✅ FUNCIONANDO' : '❌ FALLÓ';
        $smsStatus = $resultados['sms'] ? '✅ FUNCIONANDO' : '❌ FALLÓ';

        $this->line("   📧 Servicio Email: {$emailStatus}");
        $this->line("   📱 Servicio SMS: {$smsStatus}");

        $this->newLine();

        // Recomendaciones
        $this->info('💡 RECOMENDACIONES:');

        if (!$resultados['email']) {
            $this->line('   📧 Para configurar email:');
            $this->line('      - Configurar MAIL_* en .env');
            $this->line('      - Verificar credenciales SMTP');
            $this->line('      - Verificar connectivity de red');
        }

        if (!$resultados['sms']) {
            $this->line('   📱 Para configurar SMS:');
            $this->line('      - Configurar API key en config/services.php');
            $this->line('      - Verificar conectividad con proveedor SMS');
            $this->line('      - Agregar teléfonos válidos a usuarios');
        }

        $this->newLine();

        // Estado general
        if ($resultados['email'] && $resultados['sms']) {
            $this->info('🎉 ¡Todos los servicios externos funcionando correctamente!');
        } elseif ($resultados['email'] || $resultados['sms']) {
            $this->warn('⚠️  Algunos servicios necesitan configuración');
        } else {
            $this->error('❌ Los servicios externos requieren configuración');
        }
    }
}
