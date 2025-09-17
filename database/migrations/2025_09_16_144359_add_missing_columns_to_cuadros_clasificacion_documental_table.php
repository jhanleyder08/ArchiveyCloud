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
        // Enfoque simplificado: solo agregar lo que realmente falta
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            // Como ya sabemos que 'codigo' existe, solo agregamos las columnas que faltan
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'nombre')) {
                $table->string('nombre', 255)->nullable()->after('entidad');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'nivel')) {
                $table->integer('nivel')->default(1)->after('descripcion');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'padre_id')) {
                $table->unsignedBigInteger('padre_id')->nullable()->after('nivel');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'estado')) {
                $table->enum('estado', ['borrador', 'activo', 'inactivo'])->default('borrador')->after('orden_jerarquico');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'metadatos')) {
                $table->json('metadatos')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'usuario_creador_id')) {
                $table->unsignedBigInteger('usuario_creador_id')->nullable()->after('metadatos');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'usuario_modificador_id')) {
                $table->unsignedBigInteger('usuario_modificador_id')->nullable()->after('usuario_creador_id');
            }
            if (!Schema::hasColumn('cuadros_clasificacion_documental', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
        
        // Agregar relaciones e índices con manejo de errores
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            try {
                // Intentar agregar foreign keys (si ya existen, se ignorará el error)
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'padre_id')) {
                    $table->foreign('padre_id')->references('id')->on('cuadros_clasificacion_documental')->onDelete('cascade');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'usuario_creador_id')) {
                    $table->foreign('usuario_creador_id')->references('id')->on('users')->onDelete('set null');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'usuario_modificador_id')) {
                    $table->foreign('usuario_modificador_id')->references('id')->on('users')->onDelete('set null');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            // Agregar índices con manejo de errores
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'codigo')) {
                    $table->index('codigo');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'nivel')) {
                    $table->index('nivel');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'padre_id')) {
                    $table->index('padre_id');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
            
            try {
                if (Schema::hasColumn('cuadros_clasificacion_documental', 'estado')) {
                    $table->index('estado');
                }
            } catch (\Exception $e) {
                // Ignorar si ya existe
            }
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
