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
        Schema::table('trds', function (Blueprint $table) {
            // Agregar relación con CCD
            $table->foreignId('ccd_id')
                ->nullable()
                ->after('codigo')
                ->constrained('cuadros_clasificacion')
                ->onDelete('set null');
            
            $table->index('ccd_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trds', function (Blueprint $table) {
            $table->dropForeign(['ccd_id']);
            $table->dropIndex(['ccd_id']);
            $table->dropColumn('ccd_id');
        });
    }
};
