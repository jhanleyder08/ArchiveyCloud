<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Corrige la restricción única para considerar soft deletes
     */
    public function up(): void
    {
        // Buscar todos los índices únicos en la columna codigo
        $indexes = DB::select("
            SELECT DISTINCT index_name 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'tablas_retencion_documental' 
            AND column_name = 'codigo' 
            AND non_unique = 0
        ");

        // Eliminar todos los índices únicos encontrados en la columna codigo
        foreach ($indexes as $index) {
            try {
                DB::statement("ALTER TABLE tablas_retencion_documental DROP INDEX `{$index->index_name}`");
                echo "Índice eliminado: {$index->index_name}\n";
            } catch (Exception $e) {
                echo "No se pudo eliminar el índice {$index->index_name}: " . $e->getMessage() . "\n";
            }
        }

        // Método alternativo usando Laravel Schema si no hay índices encontrados
        if (empty($indexes)) {
            try {
                Schema::table('tablas_retencion_documental', function (Blueprint $table) {
                    $table->dropUnique(['codigo']);
                });
                echo "Índice eliminado usando Laravel Schema\n";
            } catch (Exception $e) {
                echo "No se pudo eliminar usando Laravel Schema: " . $e->getMessage() . "\n";
            }
        }

        // Crear nuevo índice único que considere solo registros no eliminados
        // Para MySQL, usamos una aproximación que funciona con soft deletes
        try {
            DB::statement("
                ALTER TABLE tablas_retencion_documental 
                ADD CONSTRAINT tablas_retencion_documental_codigo_unique_not_deleted 
                UNIQUE (codigo, deleted_at)
            ");
            echo "Nueva restricción única creada exitosamente\n";
        } catch (Exception $e) {
            echo "Error creando nueva restricción: " . $e->getMessage() . "\n";
            
            // Fallback: Crear índice único compuesto que permita duplicados cuando deleted_at no es null
            try {
                DB::statement("
                    CREATE UNIQUE INDEX tablas_retencion_documental_codigo_active_unique 
                    ON tablas_retencion_documental (codigo, IFNULL(deleted_at, ''))
                ");
                echo "Índice único compuesto creado como alternativa\n";
            } catch (Exception $e2) {
                echo "Error en fallback: " . $e2->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la nueva restricción única
        try {
            DB::statement("ALTER TABLE tablas_retencion_documental DROP CONSTRAINT tablas_retencion_documental_codigo_unique_not_deleted");
        } catch (Exception $e) {
            try {
                DB::statement("DROP INDEX tablas_retencion_documental_codigo_active_unique ON tablas_retencion_documental");
            } catch (Exception $e2) {
                // Ignora errores si no existe
            }
        }
        
        // Restaurar la restricción única original
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }
};
