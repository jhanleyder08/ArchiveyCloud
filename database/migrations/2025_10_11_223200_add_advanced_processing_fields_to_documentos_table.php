<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para agregar campos de procesamiento avanzado a documentos
 * 
 * Implementa requerimientos:
 * REQ-CP-007: Validación avanzada de formatos
 * REQ-CP-014: OCR básico  
 * REQ-CP-028: Conversión automática
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // REQ-CP-014: Contenido OCR extraído
            $table->longText('contenido_ocr')->nullable()->after('observaciones');
            
            // REQ-CP-007: Hash de integridad SHA-256
            $table->string('hash_sha256', 64)->nullable()->after('tamaño');
            
            // Rutas de archivos procesados
            $table->string('ruta_miniatura')->nullable()->after('ruta_archivo');
            $table->json('rutas_conversiones')->nullable()->after('ruta_miniatura');
            
            // Estado del procesamiento en background
            $table->enum('estado_procesamiento', [
                'pendiente', 
                'procesando', 
                'completado', 
                'error', 
                'fallido'
            ])->default('pendiente')->after('estado');
            
            $table->text('error_procesamiento')->nullable()->after('estado_procesamiento');
            $table->timestamp('fecha_procesamiento')->nullable()->after('error_procesamiento');
            
            // Metadatos del archivo (JSON)
            $table->json('metadatos_archivo')->nullable()->after('fecha_procesamiento');
            
            // Configuración de procesamiento aplicada
            $table->json('configuracion_procesamiento')->nullable()->after('metadatos_archivo');
            
            // Índices para búsqueda y rendimiento
            $table->index('hash_sha256');
            $table->index('estado_procesamiento');
            $table->index('fecha_procesamiento');
            
            // Índice de texto completo para OCR
            $table->fullText(['contenido_ocr'], 'documentos_ocr_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['hash_sha256']);
            $table->dropIndex(['estado_procesamiento']);
            $table->dropIndex(['fecha_procesamiento']);
            $table->dropFullText('documentos_ocr_fulltext');
            
            // Eliminar columnas
            $table->dropColumn([
                'contenido_ocr',
                'hash_sha256',
                'ruta_miniatura',
                'rutas_conversiones',
                'estado_procesamiento',
                'error_procesamiento',
                'fecha_procesamiento',
                'metadatos_archivo',
                'configuracion_procesamiento'
            ]);
        });
    }
};
