<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrdBasicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe una TRD
        $exists = DB::table('tablas_retencion_documental')->where('id', 1)->exists();
        
        if (!$exists) {
            // Crear una TRD básica
            DB::table('tablas_retencion_documental')->insert([
                'id' => 1,
                'codigo' => 'TRD-001',
                'nombre' => 'Tabla de Retención Documental General',
                'descripcion' => 'Tabla de retención documental general para la organización',
                'entidad' => 'ArchiveyCloud Organización',
                'dependencia' => 'Gestión Documental',
                'version' => 1,
                'fecha_aprobacion' => now(),
                'fecha_vigencia_inicio' => now(),
                'estado' => 'vigente',
                'vigente' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
