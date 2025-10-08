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
        if (!Schema::hasTable('expedientes')) {
            Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_expediente')->unique();
            $table->string('titulo');
            $table->text('descripcion');
            $table->foreignId('serie_documental_id')->constrained('series_documentales')->onDelete('restrict');
            $table->foreignId('subserie_documental_id')->nullable()->constrained('subseries_documentales')->onDelete('restrict');
            $table->foreignId('productor_id')->constrained('users')->onDelete('restrict');
            $table->enum('tipo_expediente', ['fisico', 'electronico', 'hibrido'])->default('electronico');
            $table->enum('estado_ciclo_vida', ['tramite', 'gestion', 'central', 'historico', 'eliminado'])
                  ->default('tramite');
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->integer('volumen_actual')->default(1);
            $table->integer('volumen_maximo')->default(1);
            $table->decimal('tamaño_mb', 10, 2)->default(0);
            $table->json('metadatos_serie')->nullable();
            $table->json('metadatos_subserie')->nullable();
            $table->json('metadatos_propios')->nullable();
            $table->string('ubicacion_fisica')->nullable();
            $table->string('ubicacion_digital')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['serie_documental_id', 'estado_ciclo_vida']);
            $table->index(['subserie_documental_id', 'estado_ciclo_vida']);
            $table->index(['productor_id', 'estado_ciclo_vida']);
            $table->index('numero_expediente');
            $table->index('fecha_apertura');
            $table->index('tipo_expediente');
            $table->index('estado_ciclo_vida');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
