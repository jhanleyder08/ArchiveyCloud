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
        // Verificar si la tabla ya existe para evitar conflictos
        if (!Schema::hasTable('api_token_logs')) {
            Schema::create('api_token_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('api_token_id')->constrained('api_tokens')->onDelete('cascade');
                $table->string('ruta', 500)->comment('Endpoint consultado');
                $table->string('metodo', 10)->comment('Método HTTP');
                $table->ipAddress('ip')->comment('IP del cliente');
                $table->text('user_agent')->nullable()->comment('User agent del cliente');
                $table->integer('status_code')->comment('Código de respuesta HTTP');
                $table->decimal('tiempo_respuesta', 8, 2)->nullable()->comment('Tiempo de respuesta en ms');
                $table->json('datos_request')->nullable()->comment('Datos adicionales del request');
                $table->timestamps();

                // Índices para optimizar consultas
                $table->index(['api_token_id', 'created_at']);
                $table->index('created_at');
                $table->index('status_code');
                $table->index('ip');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_token_logs');
    }
};
