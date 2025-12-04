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
        // Detectar el tipo de base de datos
        $driver = DB::connection()->getDriverName();
        
        // Solo intentar eliminar el índice único en código
        try {
            Schema::table('tablas_retencion_documental', function (Blueprint $table) {
                $table->dropUnique(['codigo']);
            });
            echo "Índice eliminado: tablas_retencion_documental_codigo_unique\n";
        } catch (Exception $e) {
            // Si no existe, no pasa nada
            echo "Índice único no encontrado o ya eliminado: " . $e->getMessage() . "\n";
        }

        // Crear nuevo índice único que funcione con soft deletes
        try {
            if ($driver === 'sqlite') {
                // Para SQLite, crear un índice único parcial
                DB::statement("
                    CREATE UNIQUE INDEX tablas_retencion_documental_codigo_active_unique 
                    ON tablas_retencion_documental (codigo) 
                    WHERE deleted_at IS NULL
                ");
            } else {
                // Para MySQL, crear índice único compuesto con deleted_at
                DB::statement("
                    CREATE UNIQUE INDEX tablas_retencion_documental_codigo_active_unique 
                    ON tablas_retencion_documental (codigo, deleted_at)
                ");
            }
            echo "Nueva restricción única creada exitosamente\n";
        } catch (Exception $e) {
            echo "Error creando nueva restricción: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el índice único activo
        try {
            DB::statement("DROP INDEX tablas_retencion_documental_codigo_active_unique");
        } catch (Exception $e) {
            // Ignora errores si no existe
        }
        
        // Restaurar la restricción única original
        try {
            Schema::table('tablas_retencion_documental', function (Blueprint $table) {
                $table->unique('codigo');
            });
        } catch (Exception $e) {
            // Ignora si ya existe
        }
    }
};
