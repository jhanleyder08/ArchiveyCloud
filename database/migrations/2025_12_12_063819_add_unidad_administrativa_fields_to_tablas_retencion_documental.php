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
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            // Agregar campos solo si no existen
            if (!Schema::hasColumn('tablas_retencion_documental', 'codigo_unidad_administrativa')) {
                $table->string('codigo_unidad_administrativa', 20)->nullable()->after('codigo');
            }
            if (!Schema::hasColumn('tablas_retencion_documental', 'nombre_unidad_administrativa')) {
                $table->string('nombre_unidad_administrativa', 255)->nullable()->after('codigo_unidad_administrativa');
            }
            if (!Schema::hasColumn('tablas_retencion_documental', 'codigo_dependencia')) {
                $table->string('codigo_dependencia', 20)->nullable()->after('nombre_unidad_administrativa');
            }
            if (!Schema::hasColumn('tablas_retencion_documental', 'nombre_dependencia')) {
                $table->string('nombre_dependencia', 255)->nullable()->after('codigo_dependencia');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            //
        });
    }
};
