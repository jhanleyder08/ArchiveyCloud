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
            $table->boolean('firmado_digitalmente')->default(false)->after('activo');
            $table->timestamp('fecha_ultima_firma')->nullable()->after('firmado_digitalmente');
            $table->enum('estado_firma', ['sin_firmar', 'firmado', 'firma_invalida'])->default('sin_firmar')->after('fecha_ultima_firma');
            $table->integer('total_firmas')->default(0)->after('estado_firma');
            
            // Índices para optimización
            $table->index('firmado_digitalmente');
            $table->index('estado_firma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropIndex(['firmado_digitalmente']);
            $table->dropIndex(['estado_firma']);
            $table->dropColumn([
                'firmado_digitalmente',
                'fecha_ultima_firma',
                'estado_firma',
                'total_firmas'
            ]);
        });
    }
};
