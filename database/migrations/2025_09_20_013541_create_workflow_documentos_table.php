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
        Schema::create('workflow_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');
            $table->enum('estado', ['borrador', 'pendiente', 'en_revision', 'aprobado', 'rechazado'])->default('borrador');
            $table->foreignId('solicitante_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('revisor_actual_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('aprobador_final_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Configuración del workflow
            $table->json('niveles_aprobacion'); // Array de user IDs que deben aprobar en orden
            $table->integer('nivel_actual')->default(0); // Nivel actual de aprobación (0-based)
            $table->boolean('requiere_aprobacion_unanime')->default(false);
            
            // Fechas importantes
            $table->timestamp('fecha_solicitud');
            $table->timestamp('fecha_asignacion')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamp('fecha_rechazo')->nullable();
            $table->timestamp('fecha_vencimiento')->nullable();
            
            // Metadatos
            $table->text('descripcion_solicitud')->nullable();
            $table->text('comentarios_finales')->nullable();
            $table->integer('prioridad')->default(3); // 1=alta, 2=media, 3=baja, 4=critica
            $table->json('metadata')->nullable(); // Información adicional del workflow
            
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['documento_id', 'estado']);
            $table->index(['revisor_actual_id', 'estado']);
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index('prioridad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_documentos');
    }
};
