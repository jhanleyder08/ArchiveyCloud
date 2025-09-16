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
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            // Agregar todas las columnas faltantes
            $table->string('codigo', 50)->nullable()->after('id');
            $table->string('nombre', 255)->after('codigo');
            $table->text('descripcion')->after('nombre');
            $table->integer('nivel')->default(1)->after('descripcion');
            $table->unsignedBigInteger('padre_id')->nullable()->after('nivel');
            $table->integer('orden_jerarquico')->default(0)->after('padre_id');
            $table->enum('estado', ['borrador', 'activo', 'inactivo'])->default('borrador')->after('orden_jerarquico');
            $table->json('metadatos')->nullable()->after('estado');
            $table->unsignedBigInteger('usuario_creador_id')->nullable()->after('metadatos');
            $table->unsignedBigInteger('usuario_modificador_id')->nullable()->after('usuario_creador_id');
            $table->boolean('activo')->default(true)->after('usuario_modificador_id');
            $table->softDeletes()->after('updated_at');
            
            // Agregar índices y relaciones
            $table->foreign('padre_id')->references('id')->on('cuadros_clasificacion_documental')->onDelete('cascade');
            $table->foreign('usuario_creador_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('usuario_modificador_id')->references('id')->on('users')->onDelete('set null');
            
            // Índices para búsquedas
            $table->index('codigo');
            $table->index('nivel');
            $table->index('padre_id');
            $table->index('estado');
            $table->unique(['codigo'], 'unique_codigo_ccd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            // Eliminar índices y foreign keys primero
            $table->dropForeign(['padre_id']);
            $table->dropForeign(['usuario_creador_id']);
            $table->dropForeign(['usuario_modificador_id']);
            $table->dropUnique('unique_codigo_ccd');
            $table->dropIndex(['codigo']);
            $table->dropIndex(['nivel']);
            $table->dropIndex(['padre_id']);
            $table->dropIndex(['estado']);
            
            // Eliminar las columnas
            $table->dropSoftDeletes();
            $table->dropColumn([
                'codigo',
                'nombre',
                'descripcion',
                'nivel',
                'padre_id',
                'orden_jerarquico',
                'estado',
                'metadatos',
                'usuario_creador_id',
                'usuario_modificador_id',
                'activo'
            ]);
        });
    }
};
