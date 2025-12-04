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
        // Verificar que la tabla firmas_digitales existe antes de modificarla
        if (Schema::hasTable('firmas_digitales')) {
            Schema::table('firmas_digitales', function (Blueprint $table) {
                // Verificar que las columnas no existan ya
                if (!Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                    // Agregar campos PKI después de usuario_id
                    $table->foreignId('certificado_id')->nullable()->after('usuario_id')
                          ->constrained('certificados_digitales')->onDelete('set null');
                }
                
                if (!Schema::hasColumn('firmas_digitales', 'solicitud_firma_id')) {
                    $table->foreignId('solicitud_firma_id')->nullable()->after('certificado_id')
                          ->constrained('solicitudes_firma')->onDelete('set null');
                }
                
                // Agregar campos de sello de tiempo y cadena de certificación
                if (!Schema::hasColumn('firmas_digitales', 'sello_tiempo')) {
                    $table->json('sello_tiempo')->nullable()->after('metadata');
                }
                
                if (!Schema::hasColumn('firmas_digitales', 'cadena_certificacion')) {
                    $table->json('cadena_certificacion')->nullable()->after('sello_tiempo');
                }

                // Agregar índices para optimizar consultas (solo si las columnas existen)
                if (Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                    $table->index('certificado_id');
                    $table->index(['documento_id', 'certificado_id']);
                }
                
                if (Schema::hasColumn('firmas_digitales', 'solicitud_firma_id')) {
                    $table->index('solicitud_firma_id');
                }
                
                $table->index(['usuario_id', 'valida']);
                $table->index(['tipo_firma', 'valida']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar que la tabla existe antes de modificarla
        if (Schema::hasTable('firmas_digitales')) {
            Schema::table('firmas_digitales', function (Blueprint $table) {
                // Eliminar claves foráneas solo si existen
                if (Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                    $table->dropForeign(['certificado_id']);
                }
                if (Schema::hasColumn('firmas_digitales', 'solicitud_firma_id')) {
                    $table->dropForeign(['solicitud_firma_id']);
                }
                
                // Eliminar índices solo si existen las columnas
                if (Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                    $table->dropIndex(['certificado_id']);
                    $table->dropIndex(['documento_id', 'certificado_id']);
                }
                if (Schema::hasColumn('firmas_digitales', 'solicitud_firma_id')) {
                    $table->dropIndex(['solicitud_firma_id']);
                }
                $table->dropIndex(['usuario_id', 'valida']);
                $table->dropIndex(['tipo_firma', 'valida']);
                
                // Eliminar columnas solo si existen
                $columnsToDelete = [];
                if (Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                    $columnsToDelete[] = 'certificado_id';
                }
                if (Schema::hasColumn('firmas_digitales', 'solicitud_firma_id')) {
                    $columnsToDelete[] = 'solicitud_firma_id';
                }
                if (Schema::hasColumn('firmas_digitales', 'sello_tiempo')) {
                    $columnsToDelete[] = 'sello_tiempo';
                }
                if (Schema::hasColumn('firmas_digitales', 'cadena_certificacion')) {
                    $columnsToDelete[] = 'cadena_certificacion';
                }
                
                if (!empty($columnsToDelete)) {
                    $table->dropColumn($columnsToDelete);
                }
            });
        }
    }
};
