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
        // Tabla principal de TRD (Tabla de Retención Documental)
        if (!Schema::hasTable('trds')) {
            Schema::create('trds', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('version', 20);
            $table->enum('estado', ['borrador', 'activa', 'inactiva', 'archivada'])->default('borrador');
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_vigencia_inicio')->nullable();
            $table->date('fecha_vigencia_fin')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('estado');
            $table->index('version');
            $table->index(['fecha_vigencia_inicio', 'fecha_vigencia_fin']);
            });
        }

        // Series documentales - Solo crear si no existe
        if (!Schema::hasTable('series_documentales')) {
            Schema::create('series_documentales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_id')->constrained('trds')->onDelete('cascade');
            $table->string('codigo', 50);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('dependencia', 255)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique(['trd_id', 'codigo']);
            $table->index('activa');
            $table->index('orden');
            });
        }

        // Subseries documentales - Solo crear si no existe
        if (!Schema::hasTable('subseries_documentales')) {
            Schema::create('subseries_documentales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->constrained('series_documentales')->onDelete('cascade');
            $table->string('codigo', 50);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique(['serie_id', 'codigo']);
            $table->index('activa');
            $table->index('orden');
            });
        }

        // Tipos documentales
        if (!Schema::hasTable('tipos_documentales')) {
            Schema::create('tipos_documentales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subserie_id')->nullable()->constrained('subseries_documentales')->onDelete('cascade');
            $table->foreignId('serie_id')->nullable()->constrained('series_documentales')->onDelete('cascade');
            $table->string('codigo', 50);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->json('formatos_permitidos')->nullable(); // ['pdf', 'docx', 'xlsx']
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('activo');
            $table->index('orden');
            });
        }

        // Retención y disposición
        if (!Schema::hasTable('retenciones')) {
            Schema::create('retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->nullable()->constrained('series_documentales')->onDelete('cascade');
            $table->foreignId('subserie_id')->nullable()->constrained('subseries_documentales')->onDelete('cascade');
            $table->foreignId('tipo_documental_id')->nullable()->constrained('tipos_documentales')->onDelete('cascade');
            
            // Tiempos de retención
            $table->integer('retencion_archivo_gestion')->default(0); // En años
            $table->integer('retencion_archivo_central')->default(0); // En años
            
            // Disposición final
            $table->enum('disposicion_final', [
                'conservacion_total',
                'eliminacion',
                'seleccion',
                'transferencia_historica',
                'digitalizacion_eliminacion_fisica'
            ]);
            
            $table->text('procedimiento_disposicion')->nullable();
            $table->text('justificacion')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('disposicion_final');
            });
        }

        // Historial de versiones TRD
        if (!Schema::hasTable('trd_versiones')) {
            Schema::create('trd_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_id')->constrained('trds')->onDelete('cascade');
            $table->string('version_anterior', 20);
            $table->string('version_nueva', 20);
            $table->text('cambios')->nullable(); // Descripción de cambios
            $table->json('datos_anteriores')->nullable(); // Snapshot de datos anteriores
            $table->foreignId('modificado_por')->constrained('users');
            $table->timestamp('fecha_cambio');
            $table->timestamps();
            
            $table->index('trd_id');
            $table->index('fecha_cambio');
            });
        }

        // Importaciones/Exportaciones TRD
        if (!Schema::hasTable('trd_importaciones')) {
            Schema::create('trd_importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_id')->nullable()->constrained('trds');
            $table->enum('tipo', ['importacion', 'exportacion']);
            $table->enum('formato', ['xml', 'excel', 'csv', 'json']);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->enum('estado', ['procesando', 'completado', 'error'])->default('procesando');
            $table->integer('registros_procesados')->default(0);
            $table->integer('registros_error')->default(0);
            $table->json('errores')->nullable(); // Detalles de errores
            $table->json('estadisticas')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
            
            $table->index('estado');
            $table->index('tipo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trd_importaciones');
        Schema::dropIfExists('trd_versiones');
        Schema::dropIfExists('retenciones');
        Schema::dropIfExists('tipos_documentales');
        Schema::dropIfExists('subseries_documentales');
        Schema::dropIfExists('series_documentales');
        Schema::dropIfExists('trds');
    }
};
