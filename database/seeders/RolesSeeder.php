<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de insertar para evitar duplicados
        DB::table('roles')->delete();
        
        // Insertar roles base sin referencias padre primero
        $rolesBase = [
            [
                'id' => 1,
                'name' => 'Super Administrador',
                'description' => 'Acceso total al sistema, configuración y administración completa del SGDEA',
                'nivel_jerarquico' => 1,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'acceso_total' => true,
                    'puede_modificar_estructura' => true,
                    'puede_gestionar_usuarios' => true,
                    'puede_acceder_auditoria' => true
                ]),
                'observaciones' => 'Rol del sistema con privilegios máximos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Consultor',
                'description' => 'Usuario con acceso de solo lectura para consulta de documentos',
                'nivel_jerarquico' => 5,
                'padre_id' => null,
                'activo' => true,
                'sistema' => false,
                'configuracion' => json_encode([
                    'consultar_documentos' => true,
                    'exportar_documentos' => false,
                    'imprimir_documentos' => true,
                    'descargar_documentos' => false,
                    'ver_metadatos' => true
                ]),
                'observaciones' => 'Acceso de consulta limitada según permisos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insertar roles base primero
        DB::table('roles')->insert($rolesBase);

        // Insertar roles con referencias padre después
        $rolesConPadre = [
            [
                'id' => 2,
                'name' => 'Administrador SGDEA',
                'description' => 'Administrador del sistema documental con capacidades de gestión avanzada',
                'nivel_jerarquico' => 2,
                'padre_id' => 1,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'gestionar_trd' => true,
                    'gestionar_ccd' => true,
                    'gestionar_usuarios' => true,
                    'acceder_reportes' => true,
                    'configurar_flujos' => true
                ]),
                'observaciones' => 'Administrador especializado en gestión documental',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Gestor Documental',
                'description' => 'Encargado de la gestión y clasificación de documentos y expedientes',
                'nivel_jerarquico' => 3,
                'padre_id' => 2,
                'activo' => true,
                'sistema' => false,
                'configuracion' => json_encode([
                    'crear_expedientes' => true,
                    'gestionar_documentos' => true,
                    'aplicar_disposicion' => true,
                    'generar_reportes_basicos' => true,
                    'validar_clasificacion' => true
                ]),
                'observaciones' => 'Responsable de la gestión operativa documental',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Auditor',
                'description' => 'Revisor con acceso a pistas de auditoría y controles de cumplimiento',
                'nivel_jerarquico' => 3,
                'padre_id' => 2,
                'activo' => true,
                'sistema' => false,
                'configuracion' => json_encode([
                    'acceder_auditoria' => true,
                    'generar_reportes_auditoria' => true,
                    'revisar_compliance' => true,
                    'monitorear_accesos' => true,
                    'validar_integridad' => true
                ]),
                'observaciones' => 'Especialista en auditoría y cumplimiento normativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Productor Documental',
                'description' => 'Usuario que crea y captura documentos en el sistema',
                'nivel_jerarquico' => 4,
                'padre_id' => 3,
                'activo' => true,
                'sistema' => false,
                'configuracion' => json_encode([
                    'crear_documentos' => true,
                    'capturar_documentos' => true,
                    'consultar_expedientes_propios' => true,
                    'actualizar_metadatos' => true,
                    'firmar_documentos' => false
                ]),
                'observaciones' => 'Usuario operativo de captura documental',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insertar roles con padre después
        DB::table('roles')->insert($rolesConPadre);
    }
}
