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
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('nivel')->default(1); // Nivel en la jerarquía (1=raíz, 2=hijos, etc.)
            $table->unsignedBigInteger('padre_id')->nullable(); // Self-referencing FK added separately
            $table->integer('orden')->default(0); // Orden dentro del mismo nivel
            $table->enum('estado', ['borrador', 'activo', 'inactivo', 'historico'])->default('borrador');
            $table->boolean('activo')->default(true);
            $table->json('vocabulario_controlado')->nullable(); // Términos controlados
            $table->json('metadatos')->nullable(); // Metadatos adicionales
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_creador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('usuario_modificador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['padre_id', 'nivel', 'orden']);
            $table->index(['estado', 'activo']);
            $table->index(['nivel']);
        });

        // Add self-referencing foreign key after table creation
        Schema::table('cuadros_clasificacion_documental', function (Blueprint $table) {
            $table->foreign('padre_id')->references('id')->on('cuadros_clasificacion_documental')->onDelete('cascade');
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
