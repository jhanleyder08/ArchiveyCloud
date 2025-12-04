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
        Schema::create('plantillas_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('categoria')->default('general'); // general, contrato, oficio, etc.
            $table->longText('contenido_html')->nullable();
            $table->json('contenido_json')->nullable();
            $table->json('campos_variables')->nullable(); // [{nombre, tipo, requerido, valor_default}]
            $table->foreignId('serie_documental_id')->nullable()->constrained('series_documentales')->nullOnDelete();
            $table->foreignId('subserie_documental_id')->nullable()->constrained('subseries_documentales')->nullOnDelete();
            $table->json('metadatos_predefinidos')->nullable();
            $table->boolean('es_publica')->default(false);
            $table->foreignId('usuario_creador_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('activa')->default(true);
            $table->integer('version')->default(1);
            $table->string('archivo_adjunto')->nullable(); // Si hay un archivo base
            $table->string('extension')->nullable(); // docx, pdf, html
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('nombre');
            $table->index('categoria');
            $table->index('tipo_documento');
            $table->index('es_publica');
            $table->index('activa');
            $table->index('usuario_creador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_documento');
    }
};
