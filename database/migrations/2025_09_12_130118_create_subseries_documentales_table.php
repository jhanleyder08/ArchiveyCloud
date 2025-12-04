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
        if (!Schema::hasTable('subseries_documentales')) {
            Schema::create('subseries_documentales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->foreignId('serie_documental_id')->constrained('series_documentales')->onDelete('cascade');
            $table->integer('tiempo_archivo_gestion')->nullable();
            $table->integer('tiempo_archivo_central')->nullable();
            $table->enum('disposicion_final', ['conservacion_permanente', 'eliminacion', 'seleccion', 'microfilmacion']);
            $table->text('procedimiento')->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadatos_especificos')->nullable();
            $table->json('tipologias_documentales')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['serie_documental_id', 'activa']);
            $table->index('disposicion_final');
            $table->index('codigo');
            $table->index('activa');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subseries_documentales');
    }
};
