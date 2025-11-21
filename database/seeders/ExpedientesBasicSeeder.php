<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpedientesBasicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que tengamos series disponibles
        $series = DB::table('series_documentales')->first();
        if (!$series) {
            $this->command->warn('No hay series documentales disponibles. Ejecuta SeriesDocumentalesSeeder primero.');
            return;
        }

        // Crear expedientes de ejemplo
        $expedientes = [
            [
                'numero_expediente' => 'EXP-2025-001',
                'titulo' => 'Expediente de Contratación General',
                'descripcion' => 'Expediente para procesos de contratación administrativa',
                'serie_documental_id' => $series->id,
                'tipo_expediente' => 'electronico',
                'estado' => 'en_tramite',
                'fecha_apertura' => now()->format('Y-m-d'),
                'volumen_actual' => 1,
                'volumen_maximo' => 10,
                'tamaño_mb' => 0.0,
                'ubicacion_fisica' => 'Archivo Central - Estante A1',
                'ubicacion_digital' => '/documentos/expedientes/2025/001',
                'responsable_id' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_expediente' => 'EXP-2025-002',
                'titulo' => 'Expediente de Correspondencia Oficial',
                'descripcion' => 'Gestión de correspondencia institucional',
                'serie_documental_id' => $series->id,
                'tipo_expediente' => 'hibrido',
                'estado' => 'activo',
                'fecha_apertura' => now()->subDays(15)->format('Y-m-d'),
                'volumen_actual' => 1,
                'volumen_maximo' => 5,
                'tamaño_mb' => 25.5,
                'ubicacion_fisica' => 'Secretaría General - Archivo A',
                'ubicacion_digital' => '/documentos/expedientes/2025/002',
                'responsable_id' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_expediente' => 'EXP-2025-003',
                'titulo' => 'Expediente de Informes de Gestión',
                'descripcion' => 'Reportes mensuales y anuales de actividades',
                'serie_documental_id' => $series->id,
                'tipo_expediente' => 'electronico',
                'estado' => 'semiactivo',
                'fecha_apertura' => now()->subDays(60)->format('Y-m-d'),
                'fecha_cierre' => now()->subDays(5)->format('Y-m-d'),
                'volumen_actual' => 1,
                'volumen_maximo' => 3,
                'tamaño_mb' => 150.75,
                'ubicacion_digital' => '/documentos/expedientes/2025/003',
                'observaciones' => 'Expediente cerrado y transferido al archivo central',
                'responsable_id' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        // Insertar expedientes uno por uno para mejor control
        foreach ($expedientes as $expediente) {
            DB::table('expedientes')->insert($expediente);
        }
        
        $this->command->info('✅ Expedientes de ejemplo creados exitosamente');
    }
}
