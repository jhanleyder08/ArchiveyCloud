<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subseries_documentales', function (Blueprint $table) {
            // Renombrar serie_id a serie_documental_id
            $table->renameColumn('serie_id', 'serie_documental_id');
            
            // Agregar columnas faltantes
            $table->integer('tiempo_archivo_gestion')->nullable()->after('serie_documental_id');
            $table->integer('tiempo_archivo_central')->nullable()->after('tiempo_archivo_gestion');
            $table->enum('disposicion_final', ['conservacion_permanente', 'eliminacion', 'seleccion', 'microfilmacion'])
                  ->after('tiempo_archivo_central');
            $table->text('procedimiento')->nullable()->after('disposicion_final');
            $table->json('metadatos_especificos')->nullable()->after('procedimiento');
            $table->json('tipologias_documentales')->nullable()->after('metadatos_especificos');
            $table->text('observaciones')->nullable()->after('tipologias_documentales');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('restrict')->after('activa');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict')->after('created_by');
            
            // Eliminar columna orden si existe
            if (Schema::hasColumn('subseries_documentales', 'orden')) {
                $table->dropColumn('orden');
            }
            
            // Añadir índices
            $table->index(['serie_documental_id', 'activa']);
            $table->index('disposicion_final');
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subseries_documentales', function (Blueprint $table) {
            // Revertir cambios
            $table->renameColumn('serie_documental_id', 'serie_id');
            $table->dropColumn([
                'tiempo_archivo_gestion',
                'tiempo_archivo_central',
                'disposicion_final',
                'procedimiento',
                'metadatos_especificos',
                'tipologias_documentales',
                'observaciones',
                'created_by',
                'updated_by'
            ]);
            $table->integer('orden')->default(0)->after('descripcion');
        });
    }
};
