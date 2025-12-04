<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixCCDTable extends Command
{
    protected $signature = 'fix:ccd-table';
    protected $description = 'Fix missing columns in cuadros_clasificacion_documental table';

    public function handle()
    {
        $this->info('Checking cuadros_clasificacion_documental table structure...');
        
        try {
            // Verificar si la tabla existe
            if (!Schema::hasTable('cuadros_clasificacion_documental')) {
                $this->error('Table cuadros_clasificacion_documental does not exist!');
                return 1;
            }
            
            $this->info('Table exists. Checking columns...');
            
            // Obtener todas las columnas
            $columns = Schema::getColumnListing('cuadros_clasificacion_documental');
            $this->line('Current columns: ' . implode(', ', $columns));
            
            // Verificar si existe la columna estado
            if (!in_array('estado', $columns)) {
                $this->warn('Column "estado" is missing. Adding it...');
                
                // Agregar la columna usando SQL directo
                DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN estado ENUM("borrador", "activo", "inactivo", "historico") DEFAULT "borrador" AFTER orden_jerarquico');
                DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD INDEX idx_estado (estado)');
                
                $this->info('Column "estado" added successfully!');
            } else {
                $this->info('Column "estado" already exists.');
            }
            
            // Verificar otras columnas importantes
            $requiredColumns = [
                'codigo', 'nombre', 'descripcion', 'nivel', 'padre_id', 
                'orden_jerarquico', 'estado', 'activo', 'created_at', 'updated_at', 'deleted_at'
            ];
            
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                $this->warn('Missing columns: ' . implode(', ', $missingColumns));
                
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'codigo':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN codigo VARCHAR(50) NULL');
                            break;
                        case 'nombre':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN nombre VARCHAR(255) NOT NULL');
                            break;
                        case 'descripcion':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN descripcion TEXT NULL');
                            break;
                        case 'nivel':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN nivel INT DEFAULT 1');
                            break;
                        case 'padre_id':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN padre_id BIGINT UNSIGNED NULL');
                            break;
                        case 'orden_jerarquico':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN orden_jerarquico INT DEFAULT 0');
                            break;
                        case 'activo':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN activo BOOLEAN DEFAULT 1');
                            break;
                        case 'deleted_at':
                            DB::statement('ALTER TABLE cuadros_clasificacion_documental ADD COLUMN deleted_at TIMESTAMP NULL');
                            break;
                    }
                    $this->info("Added column: $column");
                }
            }
            
            $this->info('Table structure verification completed!');
            
            // Mostrar estructura final
            $finalColumns = Schema::getColumnListing('cuadros_clasificacion_documental');
            $this->line('Final columns: ' . implode(', ', $finalColumns));
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
