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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_documento')->unique();
            $table->string('titulo');
            $table->text('descripcion');
            $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('restrict');
            $table->foreignId('tipologia_documental_id')->constrained('tipologias_documentales')->onDelete('restrict');
            $table->foreignId('productor_id')->constrained('users')->onDelete('restrict');
            
            // Control de versiones básico
            $table->integer('version_mayor')->default(1);
            $table->integer('version_menor')->default(0);
            
            // Información básica del archivo
            $table->string('nombre_archivo');
            $table->string('formato');
            $table->bigInteger('tamano_bytes');
            
            // Fechas importantes
            $table->timestamp('fecha_documento');
            $table->timestamp('fecha_captura')->nullable();
            
            // Estado básico
            $table->boolean('activo')->default(true);
            
            // Auditoría
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices básicos
            $table->index(['expediente_id', 'activo']);
            $table->index('codigo_documento');
            $table->index('fecha_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
