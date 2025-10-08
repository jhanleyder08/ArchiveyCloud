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
        // Tabla principal de Expedientes
        if (!Schema::hasTable('expedientes')) {
            Schema::create('expedientes', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 100)->unique();
                $table->string('titulo', 500);
                $table->text('descripcion')->nullable();
                
                // Clasificacion archivistica
                $table->foreignId('serie_id')->constrained('series_documentales');
                $table->foreignId('subserie_id')->nullable()->constrained('subseries_documentales');
                $table->foreignId('ccd_nivel_id')->nullable()->constrained('ccd_niveles');
            
            // Ciclo de vida y estado
            $table->enum('estado', [
                'en_tramite',
                'activo',
                'semiactivo',
                'inactivo',
                'historico',
                'en_transferencia',
                'transferido',
                'en_valoracion',
                'seleccionado_eliminacion',
                'eliminado',
                'conservacion_permanente'
            ])->default('en_tramite');
            
            $table->enum('tipo_expediente', [
                'administrativo',
                'contable',
                'juridico',
                'tecnico',
                'historico',
                'personal'
            ]);
            
            $table->enum('nivel_acceso', ['publico', 'restringido', 'confidencial', 'reservado'])->default('publico');
            
            // Fechas del ciclo de vida
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->date('fecha_transferencia_archivo_gestion')->nullable();
            $table->date('fecha_transferencia_archivo_central')->nullable();
            $table->date('fecha_transferencia_archivo_historico')->nullable();
            $table->date('fecha_eliminacion')->nullable();
            
            // Retencion (calculada desde TRD)
            $table->integer('anos_archivo_gestion')->nullable();
            $table->integer('anos_archivo_central')->nullable();
            $table->enum('disposicion_final', [
                'eliminacion',
                'conservacion_total',
                'seleccion',
                'microfilmacion',
                'digitalizacion'
            ])->nullable();
            
            // Ubicacion fisica y digital
            $table->string('ubicacion_fisica', 500)->nullable();
            $table->string('estante', 50)->nullable();
            $table->string('caja', 50)->nullable();
            $table->string('carpeta', 50)->nullable();
            $table->string('ubicacion_digital', 500)->nullable();
            
            // Metadatos de gestion
            $table->foreignId('responsable_id')->constrained('users');
            $table->unsignedBigInteger('dependencia_id')->nullable();
            $table->unsignedBigInteger('unidad_administrativa_id')->nullable();
            
            // Seguridad y trazabilidad
            $table->string('hash_integridad', 128)->nullable(); // SHA-512 del expediente completo
            $table->timestamp('fecha_ultima_verificacion')->nullable();
            $table->boolean('verificado')->default(false);
            
            // Informacion adicional
            $table->integer('numero_documentos')->default(0);
            $table->bigInteger('tamano_total_bytes')->default(0);
            $table->json('palabras_clave')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notas')->nullable();
            
            // Control de versiones
            $table->string('version', 20)->default('1.0');
            $table->boolean('cerrado')->default(false);
            $table->boolean('bloqueado')->default(false);
            $table->timestamp('fecha_bloqueo')->nullable();
            $table->foreignId('bloqueado_por')->nullable()->constrained('users');
            
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('codigo');
            $table->index('estado');
            $table->index('tipo_expediente');
            $table->index('fecha_apertura');
            $table->index('fecha_cierre');
            $table->index('responsable_id');
            $table->index('serie_id');
            });
        }

        // Relacion Expediente-Documentos (muchos a muchos)
        if (!Schema::hasTable('expediente_documento')) {
            Schema::create('expediente_documento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->unsignedBigInteger('documento_id'); // Foreign key sin constraint por orden de migraciones
                $table->integer('orden')->default(0);
                $table->date('fecha_incorporacion');
                $table->text('motivo_incorporacion')->nullable();
                $table->boolean('documento_principal')->default(false);
                $table->foreignId('incorporado_por')->constrained('users');
                $table->timestamps();
                
                $table->index(['expediente_id', 'documento_id']);
                $table->index('orden');
            });
        }

        // Historial de estados del expediente
        if (!Schema::hasTable('expediente_historial')) {
            Schema::create('expediente_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->string('estado_anterior', 50);
                $table->string('estado_nuevo', 50);
                $table->text('observaciones')->nullable();
                $table->json('metadata_cambio')->nullable();
                $table->foreignId('realizado_por')->constrained('users');
                $table->timestamp('fecha_cambio');
                $table->timestamps();
                
                $table->index('expediente_id');
                $table->index('fecha_cambio');
            });
        }

        // Transferencias de expedientes
        if (!Schema::hasTable('expediente_transferencias')) {
            Schema::create('expediente_transferencias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->enum('tipo_transferencia', [
                    'archivo_gestion_a_central',
                    'archivo_central_a_historico',
                    'transferencia_entre_dependencias',
                    'devolucion'
                ]);
                $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'rechazada'])->default('pendiente');
                $table->unsignedBigInteger('origen_dependencia_id')->nullable();
                $table->unsignedBigInteger('destino_dependencia_id')->nullable();
                $table->string('ubicacion_origen', 500)->nullable();
                $table->string('ubicacion_destino', 500)->nullable();
                $table->date('fecha_solicitud');
                $table->date('fecha_transferencia')->nullable();
                $table->date('fecha_recepcion')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('acta_transferencia', 500)->nullable(); // Ruta al documento
                $table->foreignId('solicitado_por')->constrained('users');
                $table->foreignId('aprobado_por')->nullable()->constrained('users');
                $table->foreignId('recibido_por')->nullable()->constrained('users');
                $table->timestamps();
                
                $table->index('expediente_id');
                $table->index('estado');
                $table->index('tipo_transferencia');
            });
        }

        // Valoracion y disposicion final
        if (!Schema::hasTable('expediente_valoraciones')) {
            Schema::create('expediente_valoraciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->enum('tipo_valoracion', ['primaria', 'secundaria', 'revision']);
                $table->date('fecha_valoracion');
                $table->enum('decision', [
                    'conservacion_permanente',
                    'eliminacion',
                    'seleccion',
                    'revision_futura',
                    'microfilmacion',
                    'digitalizacion_conservacion'
                ]);
                $table->text('justificacion');
                $table->text('criterios_aplicados')->nullable();
                $table->json('documentos_adjuntos')->nullable(); // Actas, informes
                $table->foreignId('responsable_valoracion')->constrained('users');
                $table->foreignId('aprobado_por')->nullable()->constrained('users');
                $table->date('fecha_aprobacion')->nullable();
                $table->boolean('ejecutada')->default(false);
                $table->date('fecha_ejecucion')->nullable();
                $table->timestamps();
                
                $table->index('expediente_id');
                $table->index('decision');
                $table->index('fecha_valoracion');
            });
        }

        // Prestamos y consultas del expediente
        if (!Schema::hasTable('expediente_prestamos')) {
            Schema::create('expediente_prestamos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->enum('tipo', ['prestamo', 'consulta', 'reproduccion']);
                $table->enum('estado', ['activo', 'devuelto', 'vencido'])->default('activo');
                $table->foreignId('solicitante_id')->constrained('users');
                $table->foreignId('autorizado_por')->constrained('users');
                $table->date('fecha_prestamo');
                $table->date('fecha_devolucion_programada');
                $table->date('fecha_devolucion_real')->nullable();
                $table->text('motivo_prestamo');
                $table->text('observaciones')->nullable();
                $table->string('ubicacion_temporal', 500)->nullable();
                $table->timestamps();
                
                $table->index('expediente_id');
                $table->index('estado');
                $table->index('solicitante_id');
            });
        }

        // Auditoria de accesos al expediente
        if (!Schema::hasTable('expediente_accesos')) {
            Schema::create('expediente_accesos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->foreignId('usuario_id')->constrained('users');
                $table->enum('tipo_acceso', ['lectura', 'modificacion', 'descarga', 'impresion', 'eliminacion']);
                $table->string('ip_address', 45)->nullable();
                $table->text('detalles_acceso')->nullable();
                $table->timestamp('fecha_acceso');
                $table->timestamps();
                
                $table->index('expediente_id');
                $table->index('usuario_id');
                $table->index('fecha_acceso');
            });
        }

        // Indices de documentos del expediente (inventario)
        if (!Schema::hasTable('expediente_indices')) {
            Schema::create('expediente_indices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
                $table->string('tipo_indice', 50); // cronologico, alfabetico, tematico, documental
                $table->json('estructura_indice'); // Estructura del indice
                $table->date('fecha_elaboracion');
                $table->foreignId('elaborado_por')->constrained('users');
                $table->string('archivo_indice', 500)->nullable(); // PDF del indice
                $table->timestamps();
                
                $table->index('expediente_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expediente_indices');
        Schema::dropIfExists('expediente_accesos');
        Schema::dropIfExists('expediente_prestamos');
        Schema::dropIfExists('expediente_valoraciones');
        Schema::dropIfExists('expediente_transferencias');
        Schema::dropIfExists('expediente_historial');
        Schema::dropIfExists('expediente_documento');
        Schema::dropIfExists('expedientes');
    }
};
