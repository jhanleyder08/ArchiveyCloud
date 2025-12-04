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
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            // Agregar la columna estado solo si no existe
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'estado')) {
                $table->enum('estado', ['borrador', 'activo', 'inactivo', 'historico'])->default('borrador')->after('orden_jerarquico');
                $table->index('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            if (Schema::hasColumn('cuadros_clasificacion_documental', 'estado')) {
                $table->dropIndex(['estado']);
                $table->dropColumn('estado');
            }
        });
    }
};
