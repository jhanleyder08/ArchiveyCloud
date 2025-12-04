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
        Schema::table('disposicion_finals', function (Blueprint $table) {
            $table->json('datos_responsable_externo')->nullable()->after('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposicion_finals', function (Blueprint $table) {
            $table->dropColumn('datos_responsable_externo');
        });
    }
};
