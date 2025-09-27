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
        Schema::create('firmantes_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_firma_id')->constrained('solicitudes_firma')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->integer('orden')->default(1);
            $table->boolean('es_obligatorio')->default(true);
            $table->enum('rol_firmante', ['aprobador', 'revisor', 'testigo', 'autoridad', 'validador'])->default('aprobador');
            $table->enum('estado', ['pendiente', 'notificado', 'firmado', 'rechazado', 'delegado'])->default('pendiente');
            $table->timestamp('notificado_en')->nullable();
            $table->timestamp('firmado_en')->nullable();
            $table->timestamp('rechazado_en')->nullable();
            $table->text('comentario')->nullable();
            $table->string('ip_firma')->nullable();
            $table->json('metadata_firmante')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['solicitud_firma_id', 'estado']);
            $table->index(['usuario_id', 'estado']);
            $table->index(['solicitud_firma_id', 'orden']);
            $table->index(['es_obligatorio', 'estado']);
            $table->index('rol_firmante');
            
            // Índice compuesto para workflow secuencial
            $table->index(['solicitud_firma_id', 'orden', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmantes_solicitud');
    }
};
