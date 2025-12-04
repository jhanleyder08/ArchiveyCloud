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
        Schema::create('certificados_digitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre_certificado');
            $table->string('numero_serie')->unique();
            $table->text('emisor');
            $table->text('sujeto');
            $table->string('algoritmo_firma');
            $table->integer('longitud_clave');
            $table->string('huella_digital');
            $table->longText('certificado_x509');
            $table->longText('clave_publica');
            $table->timestamp('fecha_emision')->nullable();
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->enum('estado', ['activo', 'vencido', 'revocado', 'suspendido'])->default('activo');
            $table->enum('tipo_certificado', ['usuario', 'servidor', 'autoridad_certificadora', 'sello_tiempo'])->default('usuario');
            $table->json('uso_permitido')->nullable();
            $table->timestamp('revocado_en')->nullable();
            $table->text('razon_revocacion')->nullable();
            $table->json('metadata_pki')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['usuario_id', 'estado']);
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index(['tipo_certificado', 'estado']);
            $table->index('numero_serie');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados_digitales');
    }
};
