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
        Schema::create('cuadros_clasificacion_documental', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 255);
            $table->text('descripcion');
            $table->integer('nivel')->default(1);
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->integer('orden_jerarquico')->default(0);
            $table->enum('estado', ['borrador', 'activo', 'inactivo'])->default('borrador');
            $table->json('metadatos')->nullable();
            $table->unsignedBigInteger('usuario_creador_id')->nullable();
            $table->unsignedBigInteger('usuario_modificador_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices y relaciones
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
        Schema::dropIfExists('cuadros_clasificacion_documental');
    }
};
