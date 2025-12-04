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
            // Agregar columnas faltantes para el sistema de firma digital
            if (!Schema::hasColumn('firmas_digitales', 'certificado_id')) {
                $table->foreignId('certificado_id')->nullable()->after('usuario_id')
                    ->constrained('certificados_digitales')->nullOnDelete();
            }
            if (!Schema::hasColumn('firmas_digitales', 'nivel_firma')) {
                $table->string('nivel_firma', 10)->nullable()->after('tipo_firma');
            }
            if (!Schema::hasColumn('firmas_digitales', 'ruta_archivo_firmado')) {
                $table->string('ruta_archivo_firmado')->nullable()->after('nivel_firma');
            }
            if (!Schema::hasColumn('firmas_digitales', 'metadatos_firma')) {
                $table->json('metadatos_firma')->nullable()->after('ruta_archivo_firmado');
            }
            if (!Schema::hasColumn('firmas_digitales', 'estado')) {
                $table->string('estado', 20)->default('valida')->after('metadatos_firma');
            }
            if (!Schema::hasColumn('firmas_digitales', 'datos_certificado')) {
                $table->json('datos_certificado')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('firmas_digitales', 'politica_firma')) {
                $table->json('politica_firma')->nullable()->after('datos_certificado');
            }
            if (!Schema::hasColumn('firmas_digitales', 'resultado_validacion')) {
                $table->json('resultado_validacion')->nullable()->after('politica_firma');
            }
            if (!Schema::hasColumn('firmas_digitales', 'fecha_validacion')) {
                $table->timestamp('fecha_validacion')->nullable()->after('resultado_validacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmas_digitales', function (Blueprint $table) {
            $columns = [
                'certificado_id', 'nivel_firma', 'ruta_archivo_firmado', 
                'metadatos_firma', 'estado', 'datos_certificado', 
                'politica_firma', 'resultado_validacion', 'fecha_validacion'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('firmas_digitales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
