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
        Schema::create('aprobacion_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_documento_id')->constrained('workflow_documentos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->integer('nivel_aprobacion'); // Nivel en el workflow (0, 1, 2, etc.)
            $table->enum('accion', ['aprobado', 'rechazado', 'enviado_revision', 'delegado'])->default('aprobado');
            $table->text('comentarios')->nullable();
            $table->timestamp('fecha_accion');
            $table->integer('tiempo_respuesta_horas')->nullable(); // Tiempo que tardó en responder
            $table->json('archivos_adjuntos')->nullable(); // Archivos adjuntos del revisor
            $table->timestamps();
            
            // Índices con nombres cortos
            $table->index(['workflow_documento_id', 'nivel_aprobacion'], 'idx_workflow_nivel');
            $table->index(['usuario_id', 'fecha_accion'], 'idx_usuario_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprobacion_workflows');
    }
};
