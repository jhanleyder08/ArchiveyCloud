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
        Schema::table('prestamos', function (Blueprint $table) {
            $table->enum('tipo_solicitante', ['usuario', 'externo'])->default('usuario')->after('documento_id');
            $table->json('datos_solicitante_externo')->nullable()->after('solicitante_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn(['tipo_solicitante', 'datos_solicitante_externo']);
        });
    }
};
