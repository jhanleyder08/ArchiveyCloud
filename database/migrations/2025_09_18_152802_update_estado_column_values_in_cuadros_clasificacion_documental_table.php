<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar la columna estado para tener los valores correctos
        DB::statement("ALTER TABLE cuadros_clasificacion_documental MODIFY COLUMN estado ENUM('borrador', 'activo', 'inactivo', 'historico') NOT NULL DEFAULT 'borrador'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            //
        });
    }
};
