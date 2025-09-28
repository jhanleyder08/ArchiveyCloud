<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ConfiguracionServicio;

class ConfiguracionServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configuración inicial de servicios externos
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
                    'created_by_seeder' => true,
                    'version' => '1.0',
                    'description' => 'Configuración inicial de servicios externos'
                ]
            ]
        );

        $this->command->info('✅ Configuración inicial de servicios externos creada exitosamente');
    }
}
