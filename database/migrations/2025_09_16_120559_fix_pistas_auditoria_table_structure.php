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
        Schema::table('pistas_auditoria', function (Blueprint $table) {
            // Agregar columnas que faltan para el modelo PistaAuditoria
            $table->string('entidad_type')->after('id')->nullable();
            $table->unsignedBigInteger('entidad_id')->after('entidad_type')->nullable();
            $table->string('accion')->after('usuario_id')->nullable();
            $table->text('descripcion')->after('accion')->nullable();
            $table->json('metadatos_cambios')->after('valores_nuevos')->nullable();
            $table->json('permisos_anteriores')->after('metadatos_cambios')->nullable();
            $table->json('permisos_nuevos')->after('permisos_anteriores')->nullable();
            $table->json('contexto_adicional')->after('permisos_nuevos')->nullable();
            $table->string('pais')->after('sesion_id')->nullable();
            $table->string('navegador')->after('pais')->nullable();
            $table->string('dispositivo')->after('navegador')->nullable();
            $table->string('sistema_operativo')->after('dispositivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pistas_auditoria', function (Blueprint $table) {
            $table->dropColumn([
                'entidad_type',
                'entidad_id',
                'accion',
                'descripcion',
                'metadatos_cambios',
                'permisos_anteriores',
                'permisos_nuevos',
                'contexto_adicional',
                'pais',
                'navegador',
                'dispositivo',
                'sistema_operativo'
            ]);
        });
    }
};
