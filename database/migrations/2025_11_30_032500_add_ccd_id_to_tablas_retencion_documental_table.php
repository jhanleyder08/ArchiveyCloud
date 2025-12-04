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
            $table->foreignId('ccd_id')
                ->nullable()
                ->after('vigente')
                ->constrained('cuadros_clasificacion')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            $table->dropForeign(['ccd_id']);
            $table->dropColumn('ccd_id');
        });
    }
};
