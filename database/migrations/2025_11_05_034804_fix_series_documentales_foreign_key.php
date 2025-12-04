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
        // Eliminar la foreign key incorrecta que apunta a 'trds'
        try {
            DB::statement('ALTER TABLE `series_documentales` DROP FOREIGN KEY `series_documentales_trd_id_foreign`');
        } catch (\Exception $e) {
            // Si no existe, continuar
        }

        // Crear la foreign key correcta que apunta a 'tablas_retencion_documental'
        Schema::table('series_documentales', function (Blueprint $table) {
            $table->foreign('trd_id')
                  ->references('id')
                  ->on('tablas_retencion_documental')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la foreign key correcta
        try {
            DB::statement('ALTER TABLE `series_documentales` DROP FOREIGN KEY `series_documentales_trd_id_foreign`');
        } catch (\Exception $e) {
            // Si no existe, continuar
        }

        // Restaurar la foreign key incorrecta (por si acaso)
        Schema::table('series_documentales', function (Blueprint $table) {
            $table->foreign('trd_id')
                  ->references('id')
                  ->on('trds')
                  ->onDelete('cascade');
        });
    }
};
