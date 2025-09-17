<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Corrige las discrepancias entre la tabla documentos y el modelo Documento.php
     * para garantizar el correcto funcionamiento del módulo de Captura e Ingreso.
     */
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // Renombrar campos para coincidir con el modelo
            $table->renameColumn('codigo_documento', 'codigo');
            $table->renameColumn('titulo', 'nombre');
            $table->renameColumn('tipologia_documental_id', 'tipologia_id');
            $table->renameColumn('productor_id', 'usuario_creador_id');
            $table->renameColumn('tamano_bytes', 'tamaño');
            $table->renameColumn('created_by', 'created_by_temp'); // Temporal para evitar conflictos
            $table->renameColumn('updated_by', 'updated_by_temp');
        });

        Schema::table('documentos', function (Blueprint $table) {
            // Primero eliminar las claves foráneas existentes
            try {
                $table->dropForeign(['created_by']);
            } catch (\Exception $e) {
                // Ignorar si no existe
            }
            
            try {
                $table->dropForeign(['updated_by']);
            } catch (\Exception $e) {
                // Ignorar si no existe
            }
            
            // Eliminar campos que no usa el modelo
            $table->dropColumn(['version_mayor', 'version_menor', 'fecha_captura', 'activo']);
            
            // Renombrar campos de auditoría finales (sin usar temp ya que puede causar conflictos)
            if (Schema::hasColumn('documentos', 'created_by')) {
                $table->renameColumn('created_by', 'created_by_old');
            }
            if (Schema::hasColumn('documentos', 'updated_by')) {
                $table->renameColumn('updated_by', 'updated_by_old');
            }
            
            // Agregar campos faltantes del modelo
            $table->string('tipo_documental')->nullable()->after('tipologia_id');
            $table->enum('tipo_soporte', ['electronico', 'fisico', 'hibrido'])->default('electronico')->after('tipo_documental');
            $table->integer('numero_folios')->nullable()->after('tamaño');
            $table->string('ruta_archivo')->nullable()->after('numero_folios');
            $table->string('ruta_miniatura')->nullable()->after('ruta_archivo');
            $table->string('hash_integridad')->nullable()->after('ruta_miniatura');
            $table->text('firma_digital')->nullable()->after('hash_integridad');
            $table->timestamp('fecha_creacion')->nullable()->after('fecha_documento');
            $table->timestamp('fecha_modificacion')->nullable()->after('fecha_creacion');
            $table->timestamp('fecha_digitalizacion')->nullable()->after('fecha_modificacion');
            $table->string('version')->default('1.0')->after('fecha_digitalizacion');
            $table->boolean('es_version_principal')->default(true)->after('version');
            $table->foreignId('documento_padre_id')->nullable()->constrained('documentos')->onDelete('set null')->after('es_version_principal');
            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'activo', 'archivado', 'obsoleto', 'eliminado'])->default('borrador')->after('documento_padre_id');
            $table->enum('confidencialidad', ['publica', 'interna', 'confidencial', 'reservada', 'clasificada'])->default('publica')->after('estado');
            $table->json('palabras_clave')->nullable()->after('confidencialidad');
            $table->json('metadatos_documento')->nullable()->after('palabras_clave');
            $table->string('ubicacion_fisica')->nullable()->after('metadatos_documento');
            $table->text('observaciones')->nullable()->after('ubicacion_fisica');
        });

        Schema::table('documentos', function (Blueprint $table) {
            // Renombrar el campo temporal al nombre correcto
            $table->dropColumn('usuario_modificador_id_temp');
            
            // Renombrar fecha_documento a nombre más descriptivo
            $table->renameColumn('fecha_documento', 'fecha_documento_original');
            $table->renameColumn('nombre_archivo', 'nombre_archivo_original');
            
            // Actualizar índices
            $table->dropIndex(['expediente_id', 'activo']); // Este índice ya no existe
            $table->index(['expediente_id', 'estado']);
            $table->index(['estado', 'es_version_principal']);
            $table->index(['tipologia_id']);
            $table->index(['usuario_creador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // Revertir todos los cambios en orden inverso
            $table->dropIndex(['expediente_id', 'estado']);
            $table->dropIndex(['estado', 'es_version_principal']);
            $table->dropIndex(['tipologia_id']);
            $table->dropIndex(['usuario_creador_id']);
            
            $table->renameColumn('fecha_documento_original', 'fecha_documento');
            $table->renameColumn('nombre_archivo_original', 'nombre_archivo');
            
            $table->dropColumn([
                'tipo_documental', 'tipo_soporte', 'numero_folios', 'ruta_archivo', 
                'ruta_miniatura', 'hash_integridad', 'firma_digital', 'fecha_creacion',
                'fecha_modificacion', 'fecha_digitalizacion', 'version', 'es_version_principal',
                'documento_padre_id', 'estado', 'confidencialidad', 'palabras_clave',
                'metadatos_documento', 'ubicacion_fisica', 'observaciones'
            ]);
            
            $table->integer('version_mayor')->default(1);
            $table->integer('version_menor')->default(0);
            $table->timestamp('fecha_captura')->nullable();
            $table->boolean('activo')->default(true);
            
            $table->renameColumn('codigo', 'codigo_documento');
            $table->renameColumn('nombre', 'titulo');
            $table->renameColumn('tipologia_id', 'tipologia_documental_id');
            $table->renameColumn('usuario_creador_id', 'productor_id');
            $table->renameColumn('tamaño', 'tamano_bytes');
            
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            
            $table->index(['expediente_id', 'activo']);
        });
    }
};
