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
        Schema::create('solicitudes_firma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');
            $table->foreignId('solicitante_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('tipo_flujo', ['secuencial', 'paralelo', 'mixto'])->default('secuencial');
            $table->enum('prioridad', ['baja', 'normal', 'alta', 'urgente'])->default('normal');
            $table->timestamp('fecha_limite')->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada', 'vencida'])->default('pendiente');
            $table->json('configuracion_flujo')->nullable();
            $table->json('metadata_solicitud')->nullable();
            $table->timestamp('completada_en')->nullable();
            $table->timestamp('cancelada_en')->nullable();
            $table->text('razon_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['documento_id', 'estado']);
            $table->index(['solicitante_id', 'estado']);
            $table->index(['estado', 'fecha_limite']);
            $table->index(['prioridad', 'estado']);
            $table->index('fecha_limite');
            $table->index('tipo_flujo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_firma');
    }
};
