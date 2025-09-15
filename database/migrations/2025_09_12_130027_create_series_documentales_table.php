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
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->foreignId('cuadro_clasificacion_id')->constrained('cuadros_clasificacion_documental')->onDelete('cascade');
            $table->foreignId('tabla_retencion_id')->constrained('tablas_retencion_documental')->onDelete('cascade');
            $table->integer('tiempo_archivo_gestion')->nullable();
            $table->integer('tiempo_archivo_central')->nullable();
            $table->enum('disposicion_final', ['conservacion_permanente', 'eliminacion', 'seleccion', 'microfilmacion']);
            $table->text('procedimiento')->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadatos_heredables')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['cuadro_clasificacion_id', 'activa']);
            $table->index(['tabla_retencion_id', 'activa']);
            $table->index('disposicion_final');
            $table->index('codigo');
            $table->index('activa');
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
