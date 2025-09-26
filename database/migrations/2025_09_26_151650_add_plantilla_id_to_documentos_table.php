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
        Schema::table('documentos', function (Blueprint $table) {
            // Agregar columna para relacionar documentos con plantillas
            $table->foreignId('plantilla_id')->nullable()->after('tipologia_documental_id')->constrained('plantillas_documentales');
            
            // Agregar índice para optimizar consultas
            $table->index('plantilla_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['plantilla_id']);
            $table->dropIndex(['plantilla_id']);
            $table->dropColumn('plantilla_id');
        });
    }
};
