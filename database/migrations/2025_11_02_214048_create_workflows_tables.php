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
        // Tabla de workflows (definiciones)
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo_entidad'); // Documento, Expediente, etc.
            $table->foreignId('serie_documental_id')->nullable()->constrained('series_documentales')->nullOnDelete();
            $table->json('pasos'); // Array de pasos del workflow
            $table->json('configuracion')->nullable(); // Configuración adicional
            $table->boolean('activo')->default(true);
            $table->foreignId('usuario_creador_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo_entidad');
            $table->index('activo');
        });

        // Tabla de instancias de workflows (ejecuciones)
        Schema::create('workflow_instancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->unsignedBigInteger('entidad_id'); // ID del documento, expediente, etc.
            $table->string('entidad_type'); // Morph type
            $table->foreignId('usuario_iniciador_id')->constrained('users')->cascadeOnDelete();
            $table->integer('paso_actual')->default(0);
            $table->enum('estado', ['en_proceso', 'pausado', 'finalizado', 'cancelado'])->default('en_proceso');
            $table->json('datos')->nullable(); // Datos adicionales del proceso
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_finalizacion')->nullable();
            $table->string('resultado')->nullable(); // aprobado, rechazado, cancelado
            $table->timestamps();

            $table->index(['entidad_id', 'entidad_type']);
            $table->index('estado');
            $table->index('usuario_iniciador_id');
        });

        // Tabla de tareas de workflows
        Schema::create('workflow_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instancia_id')->constrained('workflow_instancias')->cascadeOnDelete();
            $table->integer('paso_numero');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo_asignacion')->default('usuario'); // usuario, rol, grupo
            $table->unsignedBigInteger('asignado_id')->nullable();
            $table->string('asignado_type')->nullable(); // Morph type
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'cancelada'])->default('pendiente');
            $table->string('resultado')->nullable(); // aprobado, rechazado
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_completado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_completado')->nullable();
            $table->timestamps();

            $table->index(['asignado_id', 'asignado_type']);
            $table->index('estado');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_tareas');
        Schema::dropIfExists('workflow_instancias');
        Schema::dropIfExists('workflows');
    }
};
