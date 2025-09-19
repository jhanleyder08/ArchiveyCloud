<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeriesDocumentalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de insertar
        DB::table('series_documentales')->delete();
        
        // Insertar series documentales por defecto
        $series = [
            [
                'id' => 1,
                'codigo' => 'SD-001',
                'nombre' => 'Actas de Comité',
                'descripcion' => 'Documentos que contienen los registros de las reuniones de los comités',
                'cuadro_clasificacion_id' => 1,
                'tabla_retencion_id' => 1,
                'tiempo_archivo_gestion' => 3,
                'tiempo_archivo_central' => 5,
                'disposicion_final' => 'conservacion_permanente',
                'procedimiento' => 'Conservar permanentemente en archivo histórico',
                'activa' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'codigo' => 'SD-002',
                'nombre' => 'Correspondencia General',
                'descripcion' => 'Comunicaciones oficiales internas y externas',
                'cuadro_clasificacion_id' => 1,
                'tabla_retencion_id' => 1,
                'tiempo_archivo_gestion' => 2,
                'tiempo_archivo_central' => 3,
                'disposicion_final' => 'seleccion',
                'procedimiento' => 'Seleccionar documentos de valor histórico antes de eliminar',
                'activa' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'codigo' => 'SD-003',
                'nombre' => 'Informes de Gestión',
                'descripcion' => 'Reportes periódicos de actividades y resultados',
                'cuadro_clasificacion_id' => 2,
                'tabla_retencion_id' => 1,
                'tiempo_archivo_gestion' => 1,
                'tiempo_archivo_central' => 4,
                'disposicion_final' => 'conservacion_permanente',
                'procedimiento' => 'Conservar en archivo central por valor histórico',
                'activa' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('series_documentales')->insert($series);
    }
}
