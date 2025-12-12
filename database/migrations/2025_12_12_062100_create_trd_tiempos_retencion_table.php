<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta tabla almacena los tiempos de retención documental para cada nivel
     * de la estructura jerárquica del CCD dentro de una TRD específica.
     */
    public function up(): void
    {
        Schema::create('trd_tiempos_retencion', function (Blueprint $table) {
            $table->id();
            
            // Relación con la TRD
            $table->foreignId('trd_id')
                ->constrained('tablas_retencion_documental')
                ->onDelete('cascade')
                ->comment('TRD a la que pertenece este tiempo de retención');
            
            // Relación con el nivel del CCD
            $table->foreignId('ccd_nivel_id')
                ->constrained('ccd_niveles')
                ->onDelete('cascade')
                ->comment('Nivel del CCD (sección, subsección, serie, subserie)');
            
            // Tiempos de retención (en años)
            $table->integer('retencion_archivo_gestion')
                ->default(0)
                ->comment('Años en archivo de gestión (AG)');
            
            $table->integer('retencion_archivo_central')
                ->default(0)
                ->comment('Años en archivo central (AC)');
            
            // Disposición final
            $table->enum('disposicion_final', ['CT', 'E', 'D', 'S', 'M'])
                ->nullable()
                ->comment('CT=Conservación Total, E=Eliminación, D=Digitalización, S=Selección, M=Microfilmación');
            
            // Soportes
            $table->boolean('soporte_fisico')->default(false);
            $table->boolean('soporte_electronico')->default(false);
            $table->boolean('soporte_hibrido')->default(false);
            
            // Procedimiento
            $table->text('procedimiento')->nullable()->comment('Procedimiento asociado');
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            // Metadatos adicionales
            $table->json('metadatos_adicionales')->nullable();
            
            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['trd_id', 'ccd_nivel_id']);
            $table->unique(['trd_id', 'ccd_nivel_id'], 'trd_nivel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trd_tiempos_retencion');
    }
};
