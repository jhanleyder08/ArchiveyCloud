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
            
            // Relación con el usuario propietario
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            
            // Información básica del certificado
            $table->string('nombre_certificado');
            $table->string('numero_serie', 64)->unique();
            $table->text('emisor');
            $table->text('sujeto');
            
            // Configuración criptográfica
            $table->string('algoritmo_firma', 20);
            $table->integer('longitud_clave');
            $table->string('huella_digital', 64)->nullable();
            
            // Datos del certificado
            $table->longText('certificado_x509')->nullable();
            $table->longText('clave_publica')->nullable();
            
            // Fechas de validez
            $table->datetime('fecha_emision');
            $table->datetime('fecha_vencimiento');
            
            // Estado del certificado
            $table->enum('estado', ['activo', 'vencido', 'revocado', 'suspendido'])->default('activo');
            $table->enum('tipo_certificado', ['usuario', 'servidor', 'autoridad_certificadora', 'sello_tiempo']);
            
            // Usos permitidos (JSON array)
            $table->json('uso_permitido');
            
            // Información de revocación
            $table->datetime('revocado_en')->nullable();
            $table->text('razon_revocacion')->nullable();
            
            // Metadatos PKI adicionales
            $table->json('metadata_pki')->nullable();
            
            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['usuario_id', 'estado']);
            $table->index(['tipo_certificado', 'estado']);
            $table->index(['fecha_vencimiento', 'estado']);
            $table->index(['estado', 'created_at']);
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
