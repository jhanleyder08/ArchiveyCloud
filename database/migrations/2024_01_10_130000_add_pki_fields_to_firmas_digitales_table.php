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
        Schema::table('firmas_digitales', function (Blueprint $table) {
            // Agregar campos PKI después de usuario_id
            $table->foreignId('certificado_id')->nullable()->after('usuario_id')
                  ->constrained('certificados_digitales')->onDelete('set null');
            
            $table->foreignId('solicitud_firma_id')->nullable()->after('certificado_id')
                  ->constrained('solicitudes_firma')->onDelete('set null');
            
            // Agregar campos de sello de tiempo y cadena de certificación
            $table->json('sello_tiempo')->nullable()->after('metadata');
            $table->json('cadena_certificacion')->nullable()->after('sello_tiempo');

            // Agregar índices para optimizar consultas
            $table->index('certificado_id');
            $table->index('solicitud_firma_id');
            $table->index(['documento_id', 'certificado_id']);
            $table->index(['usuario_id', 'valida']);
            $table->index(['tipo_firma', 'valida']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmas_digitales', function (Blueprint $table) {
            $table->dropForeign(['certificado_id']);
            $table->dropForeign(['solicitud_firma_id']);
            
            $table->dropIndex(['certificado_id']);
            $table->dropIndex(['solicitud_firma_id']);
            $table->dropIndex(['documento_id', 'certificado_id']);
            $table->dropIndex(['usuario_id', 'valida']);
            $table->dropIndex(['tipo_firma', 'valida']);
            
            $table->dropColumn([
                'certificado_id',
                'solicitud_firma_id', 
                'sello_tiempo',
                'cadena_certificacion'
            ]);
        });
    }
};
