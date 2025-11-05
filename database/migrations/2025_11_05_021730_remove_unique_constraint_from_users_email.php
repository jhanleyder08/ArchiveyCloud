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
        Schema::table('users', function (Blueprint $table) {
            // Eliminar la restricción unique existente en email
            $table->dropUnique(['email']);
            
            // Agregar un índice regular para performance
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir: eliminar índice y restaurar unique
            $table->dropIndex(['email']);
            $table->unique('email');
        });
    }
};
