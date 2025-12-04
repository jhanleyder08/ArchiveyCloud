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
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion');
            $table->string('categoria');
            $table->string('subcategoria')->nullable();
            $table->string('recurso');
            $table->string('accion');
            $table->integer('nivel_requerido')->default(1);
            $table->boolean('activo')->default(true);
            $table->boolean('sistema')->default(false);
            $table->json('configuracion_adicional')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['categoria', 'accion']);
            $table->index(['recurso', 'accion']);
            $table->index(['activo', 'sistema']);
            $table->index('nivel_requerido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
