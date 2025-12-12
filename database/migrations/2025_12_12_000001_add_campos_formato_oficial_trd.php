<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para agregar campos del formato oficial de TRD
 * Basado en el formato FOR-GDI-GDO-002 del Hospital Universitario del Valle
 * 
 * Campos agregados:
 * - codigo_unidad_administrativa: Código de la unidad (ej: 110)
 * - nombre_unidad_administrativa: Nombre completo de la unidad
 * - codigo_dependencia: Código de la dependencia productora (ej: 111)
 * - nombre_dependencia: Nombre de la dependencia productora
 * - soporte_fisico: Si el documento tiene soporte físico
 * - soporte_electronico: Si el documento tiene soporte electrónico
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar campos a la tabla TRDs
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            // Unidad Administrativa (según formato oficial)
            $table->string('codigo_unidad_administrativa', 20)->nullable()->after('codigo');
            $table->string('nombre_unidad_administrativa', 255)->nullable()->after('codigo_unidad_administrativa');
            
            // Dependencia Productora (según formato oficial)
            $table->string('codigo_dependencia', 20)->nullable()->after('nombre_unidad_administrativa');
            $table->string('nombre_dependencia', 255)->nullable()->after('codigo_dependencia');
            
            // Índices para búsqueda
            $table->index('codigo_unidad_administrativa');
            $table->index('codigo_dependencia');
        });

        // 2. Agregar campos de soporte a Series Documentales
        Schema::table('series_documentales', function (Blueprint $table) {
            $table->boolean('soporte_fisico')->default(false)->after('dependencia');
            $table->boolean('soporte_electronico')->default(true)->after('soporte_fisico');
        });

        // 3. Agregar campos de soporte a Subseries Documentales
        Schema::table('subseries_documentales', function (Blueprint $table) {
            $table->boolean('soporte_fisico')->default(false)->after('descripcion');
            $table->boolean('soporte_electronico')->default(true)->after('soporte_fisico');
        });

        // 4. Agregar campos de soporte a Tipos Documentales
        Schema::table('tipos_documentales', function (Blueprint $table) {
            $table->boolean('soporte_fisico')->default(false)->after('descripcion');
            $table->boolean('soporte_electronico')->default(true)->after('soporte_fisico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            $table->dropIndex(['codigo_unidad_administrativa']);
            $table->dropIndex(['codigo_dependencia']);
            $table->dropColumn([
                'codigo_unidad_administrativa',
                'nombre_unidad_administrativa',
                'codigo_dependencia',
                'nombre_dependencia',
            ]);
        });

        Schema::table('series_documentales', function (Blueprint $table) {
            $table->dropColumn(['soporte_fisico', 'soporte_electronico']);
        });

        Schema::table('subseries_documentales', function (Blueprint $table) {
            $table->dropColumn(['soporte_fisico', 'soporte_electronico']);
        });

        Schema::table('tipos_documentales', function (Blueprint $table) {
            $table->dropColumn(['soporte_fisico', 'soporte_electronico']);
        });
    }
};
