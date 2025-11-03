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
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            
            // Relación polimórfica con cualquier entidad (Documento, Expediente, etc.)
            $table->morphs('comentable');
            
            // Usuario que crea el comentario
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            
            // Comentario padre para hilos de respuestas
            $table->foreignId('padre_id')->nullable()->constrained('comentarios')->onDelete('cascade');
            
            // Contenido del comentario
            $table->text('contenido');
            
            // Metadatos adicionales
            $table->boolean('es_privado')->default(false);
            $table->boolean('es_resuelto')->default(false);
            $table->timestamp('fecha_resolucion')->nullable();
            
            // Posición en la página (para anotaciones en PDFs)
            $table->integer('pagina')->nullable();
            $table->json('coordenadas')->nullable(); // {x, y, width, height}
            
            // Tracking de ediciones
            $table->timestamp('editado_at')->nullable();
            $table->foreignId('editado_por_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices (morphs() ya crea el índice de comentable_type, comentable_id)
            $table->index('usuario_id');
            $table->index('padre_id');
            $table->index('es_privado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
