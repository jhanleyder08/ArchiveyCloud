<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para completar campos de Series/Subseries según formato FOR-GDI-GDO-002
 * del Hospital Universitario del Valle "Evaristo García" E.S.E.
 * 
 * Campos agregados según el documento oficial:
 * - Soporte: Documento Físico / Documento Electrónico
 * - Retención: Archivo Gestión (años) / Archivo Central (años)
 * - Disposición Final: CT (Conservación Total), E (Eliminación), D (Digitalización), S (Selección)
 * - Procedimiento: Texto descriptivo del proceso
 * - Tipos documentales: Lista de documentos que componen la serie/subserie
 */
return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos a series_documentales
        Schema::table('series_documentales', function (Blueprint $table) {
            // Tipos documentales que componen la serie (lista)
            if (!Schema::hasColumn('series_documentales', 'tipos_documentales_lista')) {
                $table->text('tipos_documentales_lista')->nullable()->after('descripcion');
            }
            
            // Retención en años
            if (!Schema::hasColumn('series_documentales', 'retencion_gestion')) {
                $table->integer('retencion_gestion')->default(0)->after('soporte_electronico');
            }
            if (!Schema::hasColumn('series_documentales', 'retencion_central')) {
                $table->integer('retencion_central')->default(0)->after('retencion_gestion');
            }
            
            // Disposición final (CT, E, D, S - pueden ser múltiples)
            if (!Schema::hasColumn('series_documentales', 'disposicion_ct')) {
                $table->boolean('disposicion_ct')->default(false)->after('retencion_central'); // Conservación Total
            }
            if (!Schema::hasColumn('series_documentales', 'disposicion_e')) {
                $table->boolean('disposicion_e')->default(false)->after('disposicion_ct'); // Eliminación
            }
            if (!Schema::hasColumn('series_documentales', 'disposicion_d')) {
                $table->boolean('disposicion_d')->default(false)->after('disposicion_e'); // Digitalización
            }
            if (!Schema::hasColumn('series_documentales', 'disposicion_s')) {
                $table->boolean('disposicion_s')->default(false)->after('disposicion_d'); // Selección
            }
            
            // Procedimiento
            if (!Schema::hasColumn('series_documentales', 'procedimiento')) {
                $table->text('procedimiento')->nullable()->after('disposicion_s');
            }
        });

        // Agregar campos a subseries_documentales
        Schema::table('subseries_documentales', function (Blueprint $table) {
            // Tipos documentales que componen la subserie (lista)
            if (!Schema::hasColumn('subseries_documentales', 'tipos_documentales_lista')) {
                $table->text('tipos_documentales_lista')->nullable()->after('descripcion');
            }
            
            // Retención en años
            if (!Schema::hasColumn('subseries_documentales', 'retencion_gestion')) {
                $table->integer('retencion_gestion')->default(0)->after('soporte_electronico');
            }
            if (!Schema::hasColumn('subseries_documentales', 'retencion_central')) {
                $table->integer('retencion_central')->default(0)->after('retencion_gestion');
            }
            
            // Disposición final
            if (!Schema::hasColumn('subseries_documentales', 'disposicion_ct')) {
                $table->boolean('disposicion_ct')->default(false)->after('retencion_central');
            }
            if (!Schema::hasColumn('subseries_documentales', 'disposicion_e')) {
                $table->boolean('disposicion_e')->default(false)->after('disposicion_ct');
            }
            if (!Schema::hasColumn('subseries_documentales', 'disposicion_d')) {
                $table->boolean('disposicion_d')->default(false)->after('disposicion_e');
            }
            if (!Schema::hasColumn('subseries_documentales', 'disposicion_s')) {
                $table->boolean('disposicion_s')->default(false)->after('disposicion_d');
            }
            
            // Procedimiento
            if (!Schema::hasColumn('subseries_documentales', 'procedimiento')) {
                $table->text('procedimiento')->nullable()->after('disposicion_s');
            }
        });
    }

    public function down(): void
    {
        Schema::table('series_documentales', function (Blueprint $table) {
            $columns = ['tipos_documentales_lista', 'retencion_gestion', 'retencion_central', 
                       'disposicion_ct', 'disposicion_e', 'disposicion_d', 'disposicion_s', 'procedimiento'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('series_documentales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('subseries_documentales', function (Blueprint $table) {
            $columns = ['tipos_documentales_lista', 'retencion_gestion', 'retencion_central',
                       'disposicion_ct', 'disposicion_e', 'disposicion_d', 'disposicion_s', 'procedimiento'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('subseries_documentales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
