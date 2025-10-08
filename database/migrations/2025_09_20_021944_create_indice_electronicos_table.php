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
        Schema::create('indice_electronicos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_entidad'); // 'expediente', 'documento', 'serie', 'subserie'
            $table->unsignedBigInteger('entidad_id'); // ID de la entidad indexada
            $table->string('codigo_clasificacion')->nullable(); // Código archivístico
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('metadatos'); // Metadatos específicos de cada tipo
            $table->json('palabras_clave')->nullable(); // Keywords para búsqueda
            $table->string('serie_documental')->nullable();
            $table->string('subserie_documental')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('responsable')->nullable();
            $table->string('ubicacion_fisica')->nullable();
            $table->string('ubicacion_digital')->nullable();
            $table->enum('nivel_acceso', ['publico', 'restringido', 'confidencial', 'secreto'])->default('publico');
            $table->enum('estado_conservacion', ['excelente', 'bueno', 'regular', 'malo', 'critico'])->default('bueno');
            $table->integer('cantidad_folios')->nullable();
            $table->string('formato_archivo')->nullable(); // PDF, DOC, etc.
            $table->bigInteger('tamaño_bytes')->nullable();
            $table->string('hash_integridad')->nullable();
            $table->boolean('es_vital')->default(false); // Información vital
            $table->boolean('es_historico')->default(false); // Valor histórico
            $table->date('fecha_indexacion');
            $table->foreignId('usuario_indexacion_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_ultima_actualizacion')->nullable();
            $table->foreignId('usuario_actualizacion_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices para optimización
            $table->index(['tipo_entidad', 'entidad_id'], 'idx_entidad');
            $table->index(['codigo_clasificacion']);
            $table->index(['serie_documental', 'subserie_documental'], 'idx_clasificacion');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_fechas');
            $table->index(['nivel_acceso']);
            $table->index(['es_vital', 'es_historico'], 'idx_importancia');
            
            // Fulltext index solo en MySQL (SQLite no lo soporta)
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['titulo', 'descripcion'], 'idx_busqueda_texto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indice_electronicos');
    }
};
