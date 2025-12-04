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
        Schema::table('users', function (Blueprint $table) {
            // Relación con roles
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->boolean('active')->default(true);
            
            // Información personal y laboral
            $table->string('documento_identidad')->nullable()->unique();
            $table->enum('tipo_documento', ['cedula_ciudadania', 'cedula_extranjeria', 'pasaporte', 'tarjeta_identidad'])
                  ->default('cedula_ciudadania');
            $table->string('telefono')->nullable();
            $table->string('cargo')->nullable();
            $table->string('dependencia')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_vencimiento_cuenta')->nullable();
            
            // Control de acceso y seguridad
            $table->timestamp('ultimo_acceso')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->boolean('cambio_password_requerido')->default(false);
            $table->timestamp('fecha_ultimo_cambio_password')->nullable();
            $table->json('historial_passwords')->nullable();
            
            // Configuración y preferencias
            $table->json('configuracion_notificaciones')->nullable();
            $table->json('preferencias_usuario')->nullable();
            
            // Estado y observaciones
            $table->enum('estado_cuenta', ['activo', 'inactivo', 'bloqueado', 'suspendido', 'vencido'])
                  ->default('activo');
            $table->text('observaciones')->nullable();
            
            // Soft deletes
            $table->softDeletes();
            
            // Índices para mejorar rendimiento
            $table->index(['active', 'estado_cuenta']);
            $table->index('role_id');
            $table->index('dependencia');
            $table->index('documento_identidad');
            $table->index('ultimo_acceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['active', 'estado_cuenta']);
            $table->dropIndex(['role_id']);
            $table->dropIndex(['dependencia']);
            $table->dropIndex(['documento_identidad']);
            $table->dropIndex(['ultimo_acceso']);
            
            // Eliminar foreign key
            $table->dropForeign(['role_id']);
            
            // Eliminar columnas
            $table->dropColumn([
                'role_id',
                'active',
                'documento_identidad',
                'tipo_documento',
                'telefono',
                'cargo',
                'dependencia',
                'fecha_ingreso',
                'fecha_vencimiento_cuenta',
                'ultimo_acceso',
                'intentos_fallidos',
                'bloqueado_hasta',
                'cambio_password_requerido',
                'fecha_ultimo_cambio_password',
                'historial_passwords',
                'configuracion_notificaciones',
                'preferencias_usuario',
                'estado_cuenta',
                'observaciones',
                'deleted_at'
            ]);
        });
    }
};
