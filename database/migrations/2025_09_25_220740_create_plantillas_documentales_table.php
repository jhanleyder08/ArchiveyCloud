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
        Schema::create('plantillas_documentales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('categoria', [
                'memorando', 'oficio', 'resolucion', 'acta', 
                'informe', 'circular', 'comunicacion', 'otro'
            ])->default('otro');
            $table->string('tipo_documento', 100)->nullable();
            
            // Relaciones con TRD
            $table->foreignId('serie_documental_id')->nullable()->constrained('series_documentales');
            $table->foreignId('subserie_documental_id')->nullable()->constrained('subseries_documentales');
            
            // Contenido de la plantilla
            $table->longText('contenido_html')->nullable();
            $table->json('contenido_json')->nullable();
            $table->json('campos_variables')->nullable();
            $table->json('metadatos_predefinidos')->nullable();
            $table->json('configuracion_formato')->nullable();
            
            // Control y versionado
            $table->foreignId('usuario_creador_id')->constrained('users');
            $table->enum('estado', ['borrador', 'revision', 'activa', 'archivada', 'obsoleta'])->default('borrador');
            $table->boolean('es_publica')->default(false);
            $table->decimal('version', 8, 2)->default(1.0);
            $table->foreignId('plantilla_padre_id')->nullable()->constrained('plantillas_documentales');
            
            // Metadatos adicionales
            $table->json('tags')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['categoria', 'estado']);
            $table->index(['serie_documental_id', 'estado']);
            $table->index(['usuario_creador_id', 'es_publica']);
            $table->index(['plantilla_padre_id', 'version']);
            $table->index('estado');
            $table->index('es_publica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_documentales');
    }
};
