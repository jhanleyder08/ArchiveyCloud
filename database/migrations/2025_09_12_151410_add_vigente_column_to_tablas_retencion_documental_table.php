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
            $table->boolean('vigente')->default(false)->after('estado');
            $table->string('justificacion')->nullable()->after('descripcion');
            $table->string('identificador_unico')->nullable()->unique()->after('codigo');
            $table->string('formato_archivo')->default('XML')->after('metadatos_adicionales');
            $table->json('metadatos_asociados')->nullable()->after('formato_archivo');
            $table->foreignId('usuario_creador_id')->nullable()->constrained('users')->onDelete('set null')->after('metadatos_asociados');
            $table->foreignId('usuario_modificador_id')->nullable()->constrained('users')->onDelete('set null')->after('usuario_creador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tablas_retencion_documental', function (Blueprint $table) {
            $table->dropColumn(['vigente', 'justificacion', 'identificador_unico', 'formato_archivo', 'metadatos_asociados']);
            $table->dropForeign(['usuario_creador_id']);
            $table->dropForeign(['usuario_modificador_id']);
            $table->dropColumn(['usuario_creador_id', 'usuario_modificador_id']);
        });
    }
};
