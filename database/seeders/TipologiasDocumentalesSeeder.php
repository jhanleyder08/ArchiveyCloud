<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipologiasDocumentalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar tipologías documentales solo si no existen
        $tipologias = [
            [
                'id' => 1,
                'codigo' => 'TIP-001',
                'nombre' => 'Acta de Reunión',
                'descripcion' => 'Documentos que registran formalmente las reuniones y decisiones tomadas',
                'categoria' => 'administrativo',
                'grupo_tipologico' => 'actas',
                'formatos_aceptados' => json_encode(['pdf', 'doc', 'docx']),
                'version' => 1,
                'activa' => true,
                'requiere_firma_digital' => true,
                'metadatos_obligatorios' => json_encode(['fecha_reunion', 'participantes', 'temas_tratados']),
                'metadatos_opcionales' => json_encode(['observaciones', 'anexos']),
                'reglas_validacion' => json_encode(['min_participantes' => 3]),
                'plantilla_metadatos' => json_encode([]),
                'notas_captura' => 'Capturar inmediatamente después de la reunión',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'codigo' => 'TIP-002',
                'nombre' => 'Correspondencia',
                'descripcion' => 'Comunicaciones oficiales internas y externas',
                'categoria' => 'comunicaciones',
                'grupo_tipologico' => 'correspondencia',
                'formatos_aceptados' => json_encode(['pdf', 'doc', 'docx', 'jpg', 'png']),
                'version' => 1,
                'activa' => true,
                'requiere_firma_digital' => false,
                'metadatos_obligatorios' => json_encode(['remitente', 'destinatario', 'asunto']),
                'metadatos_opcionales' => json_encode(['urgencia', 'clasificacion']),
                'reglas_validacion' => json_encode([]),
                'plantilla_metadatos' => json_encode([]),
                'notas_captura' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'codigo' => 'TIP-003',
                'nombre' => 'Informe Técnico',
                'descripcion' => 'Reportes técnicos especializados y análisis',
                'categoria' => 'tecnico',
                'grupo_tipologico' => 'informes',
                'formatos_aceptados' => json_encode(['pdf', 'doc', 'docx', 'xls', 'xlsx']),
                'version' => 1,
                'activa' => true,
                'requiere_firma_digital' => true,
                'metadatos_obligatorios' => json_encode(['autor_tecnico', 'area_especialidad', 'fecha_elaboracion']),
                'metadatos_opcionales' => json_encode(['revisado_por', 'anexos_tecnicos']),
                'reglas_validacion' => json_encode(['min_paginas' => 5]),
                'plantilla_metadatos' => json_encode([]),
                'notas_captura' => 'Validar firma del responsable técnico',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'codigo' => 'TIP-004',
                'nombre' => 'Contrato',
                'descripcion' => 'Documentos contractuales y convenios',
                'categoria' => 'legal',
                'grupo_tipologico' => 'contratos',
                'formatos_aceptados' => json_encode(['pdf']),
                'version' => 1,
                'activa' => true,
                'requiere_firma_digital' => true,
                'metadatos_obligatorios' => json_encode(['partes_contrato', 'objeto_contrato', 'vigencia', 'valor']),
                'metadatos_opcionales' => json_encode(['clausulas_especiales', 'garantias']),
                'reglas_validacion' => json_encode(['requiere_valor' => true, 'min_vigencia_dias' => 30]),
                'plantilla_metadatos' => json_encode([]),
                'notas_captura' => 'Verificar todas las firmas antes de capturar',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'codigo' => 'TIP-005',
                'nombre' => 'Documento General',
                'descripcion' => 'Documentos diversos sin clasificación específica',
                'categoria' => 'general',
                'grupo_tipologico' => 'varios',
                'formatos_aceptados' => json_encode(['pdf', 'doc', 'docx', 'jpg', 'png', 'xls', 'xlsx', 'mp4', 'mp3']),
                'version' => 1,
                'activa' => true,
                'requiere_firma_digital' => false,
                'metadatos_obligatorios' => json_encode(['titulo_documento', 'descripcion']),
                'metadatos_opcionales' => json_encode(['palabras_clave', 'observaciones']),
                'reglas_validacion' => json_encode([]),
                'plantilla_metadatos' => json_encode([]),
                'notas_captura' => null,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($tipologias as $tipologia) {
            $exists = DB::table('tipologias_documentales')->where('id', $tipologia['id'])->exists();
            if (!$exists) {
                DB::table('tipologias_documentales')->insert($tipologia);
            }
        }
        
        echo "✅ Tipologías documentales creadas exitosamente.\n";
    }
}
