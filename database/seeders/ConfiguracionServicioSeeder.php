<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfiguracionServicio;
use Carbon\Carbon;

class ConfiguracionServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear configuración por defecto para servicios externos
        ConfiguracionServicio::updateOrCreate(
            [
                'clave' => ConfiguracionServicio::CLAVE_SERVICIOS_EXTERNOS,
                'activa' => true
            ],
            [
                'email_habilitado' => true,
                'sms_habilitado' => config('app.env') !== 'production',
                'resumen_diario_hora' => '08:00',
                'throttling_email' => 5,
                'throttling_sms' => 3,
                'destinatarios_resumen' => [],
                'ambiente' => config('app.env'),
                'mail_driver' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'metadata' => [
                    'created_at' => now()->toISOString(),
                    'created_by' => 'system_seeder',
                    'version' => '1.0.0'
                ]
            ]
        );

        // Log de información
        $this->command->info('✅ Configuración de Servicios Externos creada exitosamente');
    }
}
