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
        // Tabla principal de CCD (Cuadro de Clasificación Documental)
        Schema::create('cuadros_clasificacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('version', 20);
            $table->enum('estado', ['borrador', 'activo', 'inactivo', 'archivado'])->default('borrador');
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_vigencia_inicio')->nullable();
            $table->date('fecha_vigencia_fin')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->json('vocabulario_controlado')->nullable(); // Vocabulario de términos
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('estado');
            $table->index('version');
        });

        // Niveles jerárquicos del CCD (estructura de árbol)
        Schema::create('ccd_niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_id')->constrained('cuadros_clasificacion')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('ccd_niveles')->onDelete('cascade');
            $table->string('codigo', 50);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->integer('nivel'); // 1=Fondo, 2=Sección, 3=Serie, etc.
            $table->string('tipo_nivel', 50); // fondo, seccion, subseccion, serie, subserie
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->string('ruta', 500)->nullable(); // Ruta jerárquica completa
            $table->json('palabras_clave')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['ccd_id', 'parent_id']);
            $table->index('nivel');
            $table->index('activo');
            $table->index('orden');
            $table->index('ruta');
        });

        // Asignación de permisos por nivel de CCD
        Schema::create('ccd_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_nivel_id')->constrained('ccd_niveles')->onDelete('cascade');
            $table->unsignedBigInteger('role_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('tipo_permiso', ['lectura', 'escritura', 'administracion']);
            $table->boolean('heredable')->default(true); // Si se hereda a niveles inferiores
            $table->timestamps();
            
            $table->index(['ccd_nivel_id', 'role_id']);
            $table->index(['ccd_nivel_id', 'user_id']);
        });

        // Vocabulario controlado y tesauros
        Schema::create('ccd_vocabularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_id')->constrained('cuadros_clasificacion')->onDelete('cascade');
            $table->string('termino', 255);
            $table->string('termino_preferido', 255)->nullable(); // Para sinónimos
            $table->text('definicion')->nullable();
            $table->string('tipo', 50); // descriptor, termino_no_preferido, termino_relacionado
            $table->foreignId('termino_padre_id')->nullable()->constrained('ccd_vocabularios');
            $table->json('terminos_relacionados')->nullable(); // IDs de términos relacionados
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('termino');
            $table->index('termino_preferido');
            $table->index('tipo');
        });

        // Relaciones entre niveles CCD y TRD
        Schema::create('ccd_trd_relaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_nivel_id')->constrained('ccd_niveles')->onDelete('cascade');
            $table->unsignedBigInteger('serie_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->unsignedBigInteger('subserie_id')->nullable(); // Foreign key sin constraint por orden de migraciones
            $table->string('tipo_relacion', 50); // mapeo_directo, equivalencia, incluye
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index(['ccd_nivel_id', 'serie_id']);
            $table->index(['ccd_nivel_id', 'subserie_id']);
        });

        // Historial de cambios en CCD
        Schema::create('ccd_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_id')->constrained('cuadros_clasificacion')->onDelete('cascade');
            $table->string('version_anterior', 20);
            $table->string('version_nueva', 20);
            $table->text('cambios')->nullable();
            $table->json('datos_anteriores')->nullable(); // Snapshot
            $table->foreignId('modificado_por')->constrained('users');
            $table->timestamp('fecha_cambio');
            $table->timestamps();
            
            $table->index('ccd_id');
            $table->index('fecha_cambio');
        });

        // Importaciones/Exportaciones de CCD
        Schema::create('ccd_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ccd_id')->nullable()->constrained('cuadros_clasificacion');
            $table->enum('tipo', ['importacion', 'exportacion']);
            $table->enum('formato', ['xml', 'excel', 'csv', 'json']);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->enum('estado', ['procesando', 'completado', 'error'])->default('procesando');
            $table->integer('registros_procesados')->default(0);
            $table->integer('registros_error')->default(0);
            $table->json('errores')->nullable();
            $table->json('estadisticas')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
            
            $table->index('estado');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccd_importaciones');
        Schema::dropIfExists('ccd_versiones');
        Schema::dropIfExists('ccd_trd_relaciones');
        Schema::dropIfExists('ccd_vocabularios');
        Schema::dropIfExists('ccd_permisos');
        Schema::dropIfExists('ccd_niveles');
        Schema::dropIfExists('cuadros_clasificacion');
    }
};
