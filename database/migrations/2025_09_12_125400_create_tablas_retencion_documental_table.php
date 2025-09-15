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
        Schema::create('tablas_retencion_documental', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->string('entidad');
            $table->string('dependencia')->nullable();
            $table->integer('version')->default(1);
            $table->date('fecha_aprobacion');
            $table->date('fecha_vigencia_inicio');
            $table->date('fecha_vigencia_fin')->nullable();
            $table->enum('estado', ['borrador', 'revision', 'aprobada', 'vigente', 'obsoleta'])->default('borrador');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observaciones_generales')->nullable();
            $table->json('metadatos_adicionales')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['entidad', 'estado']);
            $table->index(['codigo', 'version']);
            $table->index('fecha_vigencia_inicio');
            $table->index('fecha_vigencia_fin');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tablas_retencion_documental');
    }
};
