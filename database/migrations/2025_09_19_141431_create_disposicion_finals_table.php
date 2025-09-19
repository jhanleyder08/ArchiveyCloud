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
        Schema::create('disposicion_finals', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->onDelete('cascade');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->onDelete('cascade');
            $table->foreignId('responsable_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->onDelete('restrict');
            
            // Información básica
            $table->enum('tipo_disposicion', [
                'conservacion_permanente', 
                'eliminacion_controlada', 
                'transferencia_historica', 
                'digitalizacion', 
                'microfilmacion'
            ]);
            $table->enum('estado', [
                'pendiente', 
                'en_revision', 
                'aprobado', 
                'rechazado', 
                'ejecutado', 
                'cancelado'
            ])->default('pendiente');
            
            // Fechas importantes
            $table->date('fecha_vencimiento_retencion');
            $table->date('fecha_propuesta');
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            
            // Justificación y documentación
            $table->text('justificacion');
            $table->text('observaciones')->nullable();
            $table->text('observaciones_rechazo')->nullable();
            
            // Documentación del proceso
            $table->json('documentos_soporte')->nullable(); // PDFs, actas, etc.
            $table->json('metadata_proceso')->nullable(); // Información adicional del proceso
            
            // Control de calidad
            $table->boolean('cumple_normativa')->default(false);
            $table->text('validacion_legal')->nullable();
            $table->string('acta_comite')->nullable(); // Referencia al acta del comité
            
            // Información de ejecución
            $table->string('metodo_eliminacion')->nullable(); // triturado, incineración, etc.
            $table->string('empresa_ejecutora')->nullable();
            $table->string('certificado_destruccion')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['estado', 'fecha_vencimiento_retencion']);
            $table->index(['tipo_disposicion', 'estado']);
            $table->index('fecha_propuesta');
            $table->index('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposicion_finals');
    }
};
