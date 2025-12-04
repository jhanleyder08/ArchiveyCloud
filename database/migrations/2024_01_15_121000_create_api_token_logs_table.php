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
        Schema::create('api_token_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_token_id')->constrained('api_tokens')->onDelete('cascade');
            $table->string('ruta')->comment('Ruta de la API accedida');
            $table->string('metodo', 10)->comment('Método HTTP usado');
            $table->string('ip', 45)->comment('IP del cliente');
            $table->text('user_agent')->nullable()->comment('User Agent del cliente');
            $table->json('parametros')->nullable()->comment('Parámetros de la petición');
            $table->integer('codigo_respuesta')->nullable()->comment('Código de respuesta HTTP');
            $table->decimal('tiempo_respuesta', 8, 3)->nullable()->comment('Tiempo de respuesta en segundos');
            $table->timestamp('created_at')->useCurrent();

            // Índices para optimizar consultas
            $table->index(['api_token_id', 'created_at']);
            $table->index(['ruta', 'metodo']);
            $table->index(['ip', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_token_logs');
    }
};
