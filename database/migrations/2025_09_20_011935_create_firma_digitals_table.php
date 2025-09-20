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
        Schema::create('firmas_digitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->string('hash_documento', 64); // SHA-256 del documento firmado
            $table->string('hash_firma', 64); // Hash único de la firma
            $table->json('certificado_info'); // Información del certificado del usuario
            $table->text('motivo_firma')->nullable(); // Motivo de la firma
            $table->timestamp('fecha_firma');
            $table->string('algoritmo_hash', 20)->default('SHA-256');
            $table->enum('tipo_firma', ['electronica', 'digital', 'avanzada'])->default('electronica');
            $table->boolean('valida')->default(true);
            $table->json('metadata')->nullable(); // IP, user agent, etc.
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['documento_id', 'fecha_firma']);
            $table->index(['usuario_id', 'fecha_firma']);
            $table->index('valida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmas_digitales');
    }
};
