<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubseriesDocumentalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de insertar
        DB::table('subseries_documentales')->delete();
        
        // Insertar subseries documentales por defecto
        $subseries = [
            [
                'id' => 1,
                'codigo' => 'SSD-001-01',
                'nombre' => 'Actas de Comité Directivo',
                'descripcion' => 'Actas específicas del comité directivo de la organización',
                'serie_documental_id' => 1,
                'activa' => true,
                'created_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'codigo' => 'SSD-002-01',
                'nombre' => 'Correspondencia Interna',
                'descripcion' => 'Comunicaciones internas entre dependencias',
                'serie_documental_id' => 2,
                'activa' => true,
                'created_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'codigo' => 'SSD-002-02',
                'nombre' => 'Correspondencia Externa',
                'descripcion' => 'Comunicaciones con entidades externas',
                'serie_documental_id' => 2,
                'activa' => true,
                'created_by' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        DB::table('subseries_documentales')->insert($subseries);
    }
}
