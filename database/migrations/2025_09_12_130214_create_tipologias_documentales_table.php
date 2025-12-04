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
        Schema::create('tipologias_documentales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->string('categoria')->nullable();
            $table->string('grupo_tipologico')->nullable();
            $table->json('formatos_aceptados')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('activa')->default(true);
            $table->boolean('requiere_firma_digital')->default(false);
            $table->json('metadatos_obligatorios')->nullable();
            $table->json('metadatos_opcionales')->nullable();
            $table->json('reglas_validacion')->nullable();
            $table->json('plantilla_metadatos')->nullable();
            $table->text('notas_captura')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['categoria', 'activa']);
            $table->index(['grupo_tipologico', 'activa']);
            $table->index('codigo');
            $table->index('activa');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipologias_documentales');
    }
};
