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
        Schema::create('notificacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipo')->index(); // expediente_vencido, prestamo_vencido, disposicion_pendiente, etc.
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('datos')->nullable(); // datos adicionales del contexto
            $table->string('accion_url')->nullable(); // URL para accionar la notificación
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->enum('estado', ['pendiente', 'leida', 'archivada'])->default('pendiente')->index();
            $table->timestamp('leida_en')->nullable();
            $table->timestamp('programada_para')->nullable(); // para notificaciones programadas
            $table->foreignId('relacionado_id')->nullable(); // ID del objeto relacionado (expediente, préstamo, etc.)
            $table->string('relacionado_tipo')->nullable(); // tipo del objeto relacionado
            $table->boolean('es_automatica')->default(true); // si fue generada automáticamente
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['user_id', 'estado', 'created_at']);
            $table->index(['tipo', 'estado']);
            $table->index(['prioridad', 'estado']);
            $table->index(['relacionado_id', 'relacionado_tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacions');
    }
};
