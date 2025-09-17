<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración para el módulo de Retención y Disposición del SGDEA
     * 
     * Implementa requerimientos:
     * - REQ-RD-001: Gestión de tiempos de retención
     * - REQ-RD-002: Auditoría y trazabilidad
     * - REQ-RD-005: Acciones de disposición
     * - REQ-RD-007: Sistema de alertas
     * - REQ-RD-008: Gestión de aplazamientos
     */
    public function up(): void
    {
        // Tabla principal de procesos de retención y disposición
        Schema::create('procesos_retencion_disposicion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_proceso')->unique()->comment('Identificador único del proceso');
            
            // Relación con el documento/expediente objetivo
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->onDelete('cascade');
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->onDelete('cascade');
            $table->string('tipo_entidad')->comment('documento o expediente'); // Polimórfica
            
            // Información de la TRD aplicable
            $table->foreignId('trd_id')->constrained('tablas_retencion_documental')->onDelete('cascade');
            $table->foreignId('serie_documental_id')->nullable()->constrained('series_documentales')->onDelete('set null');
            $table->foreignId('subserie_documental_id')->nullable()->constrained('subseries_documentales')->onDelete('set null');
            
            // Fechas de retención calculadas (REQ-RD-001)
            $table->date('fecha_creacion_documento')->comment('Fecha de creación del documento');
            $table->integer('periodo_retencion_archivo_gestion')->comment('Años en archivo de gestión');
            $table->integer('periodo_retencion_archivo_central')->comment('Años en archivo central');
            $table->date('fecha_vencimiento_gestion')->comment('Fecha de transferencia a archivo central');
            $table->date('fecha_vencimiento_central')->comment('Fecha de disposición final');
            $table->date('fecha_alerta_previa')->comment('Fecha de alerta previa al vencimiento');
            
            // Estado del proceso
            $table->enum('estado', [
                'activo',           // En período de retención
                'alerta_previa',    // Se aproxima vencimiento
                'vencido',         // Vencido, requiere acción
                'en_disposicion',   // Proceso de disposición iniciado
                'transferido',      // Transferido a archivo central
                'eliminado',        // Eliminado según disposición
                'conservado',       // Conservación permanente
                'aplazado',         // Disposición aplazada
                'suspendido'        // Proceso suspendido
            ])->default('activo');
            
            // Acción de disposición final (REQ-RD-005)
            $table->enum('accion_disposicion', [
                'conservacion_permanente',
                'eliminacion',
                'transferencia_historico',
                'seleccion_documental',
                'microfilmacion',
                'digitalizacion_permanente'
            ])->nullable();
            
            // Gestión de aplazamientos (REQ-RD-008)
            $table->boolean('aplazado')->default(false);
            $table->date('fecha_aplazamiento')->nullable();
            $table->text('razon_aplazamiento')->nullable();
            $table->date('fecha_fin_aplazamiento')->nullable();
            $table->foreignId('usuario_aplazamiento')->nullable()->constrained('users')->onDelete('set null');
            
            // Sistema de alertas (REQ-RD-007)
            $table->boolean('alertas_activas')->default(true);
            $table->integer('dias_alerta_previa')->default(30)->comment('Días antes del vencimiento para alertar');
            $table->json('canales_notificacion')->nullable()->comment('email, sms, sistema');
            $table->timestamp('ultima_alerta_enviada')->nullable();
            
            // Integridad referencial y seguridad (REQ-RD-009)
            $table->string('hash_integridad')->nullable()->comment('Hash para verificar integridad');
            $table->boolean('bloqueado_eliminacion')->default(false);
            $table->text('razon_bloqueo')->nullable();
            
            // Metadatos adicionales
            $table->json('metadatos_adicionales')->nullable();
            $table->text('observaciones')->nullable();
            
            // Auditoría (REQ-RD-002)
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['estado', 'fecha_vencimiento_gestion'], 'idx_estado_fecha_venc');
            $table->index(['fecha_alerta_previa', 'alertas_activas'], 'idx_alerta_fecha_activa');
            $table->index(['trd_id', 'estado'], 'idx_trd_estado');
        });

        // Tabla de historial de acciones de disposición (REQ-RD-002)
        Schema::create('historial_acciones_disposicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proceso_retencion_id')->constrained('procesos_retencion_disposicion')->onDelete('cascade');
            
            $table->enum('tipo_accion', [
                'creacion_proceso',
                'calculo_fechas',
                'envio_alerta',
                'cambio_estado',
                'aplazamiento',
                'transferencia',
                'eliminacion',
                'conservacion',
                'suspension',
                'reactivacion',
                'modificacion_fechas',
                'bloqueo',
                'desbloqueo'
            ]);
            
            $table->enum('estado_anterior', [
                'activo', 'alerta_previa', 'vencido', 'en_disposicion',
                'transferido', 'eliminado', 'conservado', 'aplazado', 'suspendido'
            ])->nullable();
            
            $table->enum('estado_nuevo', [
                'activo', 'alerta_previa', 'vencido', 'en_disposicion',
                'transferido', 'eliminado', 'conservado', 'aplazado', 'suspendido'
            ])->nullable();
            
            $table->text('descripcion_accion');
            $table->json('datos_adicionales')->nullable()->comment('Detalles específicos de la acción');
            $table->timestamp('fecha_accion');
            $table->foreignId('usuario_accion')->constrained('users');
            $table->string('ip_origen')->nullable();
            $table->string('user_agent')->nullable();
            
            // Firma digital de la acción para inmutabilidad (REQ-RD-002)
            $table->string('hash_accion')->comment('Hash SHA-256 para garantizar inmutabilidad');
            
            $table->timestamps();
            
            // Índices
            $table->index(['proceso_retencion_id', 'fecha_accion'], 'idx_proceso_fecha');
            $table->index(['tipo_accion', 'fecha_accion'], 'idx_tipo_fecha');
        });

        // Tabla de alertas y notificaciones (REQ-RD-007)
        Schema::create('alertas_retencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proceso_retencion_id')->constrained('procesos_retencion_disposicion')->onDelete('cascade');
            
            $table->enum('tipo_alerta', [
                'vencimiento_proximo',
                'vencimiento_actual',
                'accion_requerida',
                'error_proceso',
                'confirmacion_disposicion'
            ]);
            
            $table->enum('nivel_prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->string('titulo_alerta');
            $table->text('mensaje_alerta');
            $table->date('fecha_vencimiento_relacionada');
            
            // Destinatarios y canales
            $table->json('destinatarios_usuarios')->nullable()->comment('IDs de usuarios destinatarios');
            $table->json('destinatarios_roles')->nullable()->comment('Roles destinatarios');
            $table->json('canales_envio')->comment('email, sms, sistema, push');
            
            // Estado de la alerta
            $table->enum('estado_alerta', ['pendiente', 'enviada', 'leida', 'atendida', 'descartada'])->default('pendiente');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_lectura')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            
            // Configuración de repetición
            $table->boolean('repetir_hasta_atencion')->default(false);
            $table->integer('intervalo_repeticion_horas')->nullable();
            $table->integer('max_repeticiones')->nullable();
            $table->integer('repeticiones_enviadas')->default(0);
            
            $table->timestamps();
            
            // Índices
            $table->index(['estado_alerta', 'fecha_vencimiento_relacionada'], 'idx_estado_fecha_venc');
            $table->index(['tipo_alerta', 'nivel_prioridad'], 'idx_tipo_prioridad');
        });

        // Tabla de configuración del motor de reglas (REQ-RD-003)
        Schema::create('reglas_retencion_disposicion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_regla')->unique();
            $table->text('descripcion_regla');
            
            // Aplicabilidad de la regla
            $table->json('criterios_aplicacion')->comment('Criterios para aplicar la regla');
            $table->enum('tipo_entidad', ['documento', 'expediente', 'serie', 'subserie']);
            $table->json('tipos_documentales')->nullable()->comment('Tipos documentales específicos');
            
            // Lógica de la regla
            $table->enum('tipo_regla', [
                'calculo_fechas',
                'accion_automatica',
                'validacion_integridad',
                'notificacion_personalizada',
                'escalamiento_alertas'
            ]);
            
            $table->json('parametros_regla')->comment('Parámetros específicos de la regla');
            $table->text('condiciones_ejecucion')->nullable();
            $table->integer('prioridad')->default(1)->comment('Orden de ejecución');
            
            // Estado y control
            $table->boolean('activa')->default(true);
            $table->date('fecha_inicio_vigencia')->nullable();
            $table->date('fecha_fin_vigencia')->nullable();
            
            // Auditoría
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['activa', 'tipo_entidad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglas_retencion_disposicion');
        Schema::dropIfExists('alertas_retencion');
        Schema::dropIfExists('historial_acciones_disposicion');
        Schema::dropIfExists('procesos_retencion_disposicion');
    }
};
