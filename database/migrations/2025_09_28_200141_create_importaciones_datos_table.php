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
        Schema::create('importaciones_datos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['expedientes', 'documentos', 'series', 'subseries', 'usuarios', 'trd', 'certificados', 'mixto']);
            $table->enum('formato_origen', ['csv', 'excel', 'json', 'xml', 'sql', 'zip']);
            $table->enum('estado', ['pendiente', 'procesando', 'completada', 'fallida', 'cancelada'])->default('pendiente');
            $table->string('archivo_origen');
            $table->string('archivo_procesado')->nullable();
            $table->string('archivo_errores')->nullable();
            $table->string('archivo_log')->nullable();
            $table->json('configuracion')->nullable(); // Mapeo de campos, validaciones, etc.
            $table->json('estadisticas')->nullable(); // Total, procesados, errores, etc.
            $table->json('metadatos')->nullable(); // Información adicional
            $table->integer('total_registros')->default(0);
            $table->integer('registros_procesados')->default(0);
            $table->integer('registros_exitosos')->default(0);
            $table->integer('registros_fallidos')->default(0);
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_finalizacion')->nullable();
            $table->integer('tiempo_procesamiento')->nullable(); // En segundos
            $table->text('mensaje_error')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['tipo', 'estado']);
            $table->index(['usuario_id', 'created_at']);
            $table->index('estado');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importaciones_datos');
    }
};
