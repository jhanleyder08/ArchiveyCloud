<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CuadrosClasificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de insertar
        DB::table('cuadros_clasificacion_documental')->delete();
        
        // Insertar cuadros de clasificación por defecto
        $cuadros = [
            [
                'id' => 1,
                'codigo' => 'CCD-001',
                'nombre' => 'Cuadro de Clasificación Documental General',
                'descripcion' => 'Cuadro de clasificación documental general para la organización',
                'activo' => true,
                'fecha_aprobacion' => now(),
                'version' => '1.0',
                'created_by' => 1, // Usuario admin
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'codigo' => 'CCD-ADM',
                'nombre' => 'Cuadro Administrativo',
                'descripcion' => 'Cuadro de clasificación para documentos administrativos',
                'activo' => true,
                'fecha_aprobacion' => now(),
                'version' => '1.0',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        DB::table('cuadros_clasificacion_documental')->insert($cuadros);
    }
}
