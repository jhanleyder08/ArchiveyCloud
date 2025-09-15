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
        Schema::create('pistas_auditoria', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_hora');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('evento');
            $table->string('tabla_afectada');
            $table->bigInteger('registro_id')->nullable();
            $table->enum('operacion', ['create', 'read', 'update', 'delete', 'login', 'logout', 'failed_login', 'export', 'import']);
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('sesion_id')->nullable();
            $table->string('modulo')->nullable();
            $table->string('accion_detalle')->nullable();
            $table->enum('resultado', ['exitoso', 'fallido', 'bloqueado'])->default('exitoso');
            $table->text('observaciones')->nullable();
            $table->string('hash_integridad', 64);
            $table->timestamps();
            
            // NO soft deletes - los registros de auditoría son inmutables
            
            // Índices para mejorar el rendimiento
            $table->index(['usuario_id', 'fecha_hora']);
            $table->index(['tabla_afectada', 'operacion']);
            $table->index(['evento', 'fecha_hora']);
            $table->index('fecha_hora');
            $table->index('operacion');
            $table->index('ip_address');
            $table->index('resultado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pistas_auditoria');
    }
};
