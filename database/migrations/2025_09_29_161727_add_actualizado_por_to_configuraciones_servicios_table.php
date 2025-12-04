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
        Schema::table('configuraciones_servicios', function (Blueprint $table) {
            $table->unsignedBigInteger('actualizado_por')->nullable()->after('activo')
                  ->comment('Usuario que actualizó la configuración');
            
            $table->foreign('actualizado_por')->references('id')->on('users')->onDelete('set null');
            $table->index('actualizado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuraciones_servicios', function (Blueprint $table) {
            $table->dropForeign(['actualizado_por']);
            $table->dropIndex(['actualizado_por']);
            $table->dropColumn('actualizado_por');
        });
    }
};
