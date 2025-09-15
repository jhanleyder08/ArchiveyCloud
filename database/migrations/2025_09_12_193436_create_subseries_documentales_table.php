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
        Schema::create('subseries_documentales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50);
            $table->string('nombre');
            $table->text('descripcion');
            
            // Relación obligatoria con Serie Documental
            $table->foreignId('serie_id')->constrained('series_documentales')->onDelete('cascade');
            
            // Herencia de metadatos de la serie padre
            $table->json('metadatos_propios')->nullable();
            $table->json('metadatos_heredados')->nullable();
            
            // Tiempos de retención específicos (opcional, hereda de serie si no se define)
            $table->integer('tiempo_archivo_gestion')->nullable();
            $table->integer('tiempo_archivo_central')->nullable();
            
            // Disposición final específica (opcional, hereda de serie si no se define)
            $table->enum('disposicion_final', [
                'conservacion_total', 
                'eliminacion', 
                'seleccion', 
                'transferencia', 
                'migracion'
            ])->nullable();
            
            // Procedimiento específico de la subserie
            $table->text('procedimiento')->nullable();
            
            // Palabras clave específicas (JSON array)
            $table->json('palabras_clave')->nullable();
            
            // Responsabilidad
            $table->foreignId('usuario_responsable_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Estado
            $table->boolean('activa')->default(true);
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            // Soft deletes y timestamps
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['serie_id', 'activa']);
            $table->index(['codigo']);
            $table->unique(['serie_id', 'codigo']); // Código único dentro de cada serie
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subseries_documentales');
    }
};
