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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre descriptivo del token');
            $table->string('token', 64)->unique()->comment('Token hasheado (SHA256)');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->json('permisos')->nullable()->comment('Array de permisos/scopes del token');
            $table->boolean('activo')->default(true)->comment('Estado del token');
            $table->timestamp('fecha_expiracion')->nullable()->comment('Fecha de expiración del token');
            $table->integer('limite_usos')->nullable()->comment('Límite máximo de usos');
            $table->integer('usos_realizados')->default(0)->comment('Número de usos realizados');
            $table->json('ips_permitidas')->nullable()->comment('IPs permitidas para usar el token');
            $table->text('descripcion')->nullable()->comment('Descripción del propósito del token');
            $table->timestamp('ultimo_uso')->nullable()->comment('Última vez que se usó el token');
            $table->string('ultima_ip', 45)->nullable()->comment('Última IP que usó el token');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['activo', 'fecha_expiracion']);
            $table->index(['usuario_id', 'activo']);
            $table->index('ultimo_uso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
