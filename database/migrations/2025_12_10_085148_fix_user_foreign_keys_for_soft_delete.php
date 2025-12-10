<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cambia todas las foreign keys de users de ON DELETE RESTRICT a ON DELETE SET NULL
     * para soportar soft delete. Esto permite eliminar usuarios sin romper relaciones.
     */
    public function up(): void
    {
        // Obtener todas las foreign keys que apuntan a users con DELETE RESTRICT
        $foreignKeys = DB::select("
            SELECT 
                kcu.TABLE_NAME,
                kcu.COLUMN_NAME,
                kcu.CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = DATABASE()
            AND kcu.REFERENCED_TABLE_NAME = 'users'
            AND rc.DELETE_RULE = 'RESTRICT'
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ");

        foreach ($foreignKeys as $fk) {
            try {
                echo "Procesando {$fk->TABLE_NAME}.{$fk->COLUMN_NAME}...\n";
                
                Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                    // 1. Drop foreign key existente
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
                
                Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                    // 2. Hacer columna nullable
                    $table->unsignedBigInteger($fk->COLUMN_NAME)->nullable()->change();
                });
                
                Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                    // 3. Recrear foreign key con SET NULL
                    $table->foreign($fk->COLUMN_NAME)
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                });
                
                echo "✓ {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} actualizado\n";
                
            } catch (\Exception $e) {
                echo "✗ Error en {$fk->TABLE_NAME}.{$fk->COLUMN_NAME}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nTotal procesado: " . count($foreignKeys) . " foreign keys\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Para revertir, cambiar de SET NULL a RESTRICT
        $foreignKeys = [
            'aprobacion_workflows' => ['usuario_id'],
            'ccd_importaciones' => ['usuario_id'],
            'ccd_versiones' => ['modificado_por'],
            'cuadros_clasificacion' => ['aprobado_por', 'created_by', 'updated_by'],
            'cuadros_clasificacion_documental' => ['created_by', 'updated_by'],
            'disposicion_finals' => ['aprobado_por', 'responsable_id'],
            'documentos' => ['created_by', 'productor_id', 'updated_by'],
            'expedientes' => ['bloqueado_por', 'created_by', 'responsable_id', 'updated_by'],
            'expediente_accesos' => ['usuario_id'],
            'expediente_documento' => ['incorporado_por'],
            'expediente_historial' => ['realizado_por'],
            'expediente_indices' => ['elaborado_por'],
            'expediente_prestamos' => ['autorizado_por', 'solicitante_id'],
            'expediente_transferencias' => ['aprobado_por', 'recibido_por', 'solicitado_por'],
            'expediente_valoraciones' => ['aprobado_por', 'responsable_valoracion'],
            'firmas_digitales' => ['usuario_id'],
            'indice_electronicos' => ['usuario_indexacion_id'],
            'plantillas_documentales' => ['usuario_creador_id'],
            'subseries_documentales' => ['created_by', 'updated_by'],
            'tablas_retencion_documental' => ['created_by', 'updated_by'],
            'tipologias_documentales' => ['created_by', 'updated_by'],
            'trds' => ['aprobado_por', 'created_by', 'updated_by'],
            'trd_importaciones' => ['usuario_id'],
            'trd_versiones' => ['modificado_por'],
            'workflow_documentos' => ['solicitante_id'],
        ];

        foreach ($foreignKeys as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    try {
                        $constraintName = "{$table}_{$column}_foreign";
                        $blueprint->dropForeign($constraintName);
                        
                        $blueprint->foreign($column)
                            ->references('id')
                            ->on('users')
                            ->onDelete('restrict');
                    } catch (\Exception $e) {
                        echo "Warning: Could not revert {$table}.{$column}: " . $e->getMessage() . "\n";
                    }
                }
            });
        }
    }
};
