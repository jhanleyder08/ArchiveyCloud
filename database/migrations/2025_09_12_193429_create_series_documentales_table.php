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
        Schema::create('series_documentales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('descripcion');
            
            // Relación obligatoria con TRD
            $table->foreignId('trd_id')->constrained('tablas_retencion_documental')->onDelete('restrict');
            
            // Relación opcional con CCD
            $table->foreignId('ccd_id')->nullable()->constrained('cuadros_clasificacion_documental')->onDelete('set null');
            
            // Tiempos de retención (en años)
            $table->integer('tiempo_archivo_gestion')->default(0);
            $table->integer('tiempo_archivo_central')->default(0);
            
            // Disposición final
            $table->enum('disposicion_final', [
                'conservacion_total', 
                'eliminacion', 
                'seleccion', 
                'transferencia', 
                'migracion'
            ])->default('conservacion_total');
            
            // Procedimiento de la serie
            $table->text('procedimiento')->nullable();
            
            // Metadatos heredables (JSON)
            $table->json('metadatos_heredables')->nullable();
            
            // Palabras clave (JSON array)
            $table->json('palabras_clave')->nullable();
            
            // Responsabilidad
            $table->foreignId('usuario_responsable_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('area_responsable')->nullable();
            
            // Estado
            $table->boolean('activa')->default(true);
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            // Soft deletes y timestamps
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['trd_id', 'activa']);
            $table->index(['area_responsable']);
            $table->index(['disposicion_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series_documentales');
    }
};
