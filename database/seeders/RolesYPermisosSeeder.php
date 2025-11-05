<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para crear los 8 roles del sistema SGDEA con sus permisos correspondientes
 * Basado en: ESTRUCTURA_USUARIOS_Y_PERMISOS_SGDEA.md
 */
class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // 1. Crear permisos del sistema
            $this->crearPermisos();
            
            // 2. Crear rol "Sin Acceso" para usuarios nuevos
            $this->crearRolSinAcceso();
            
            // 3. Crear los 8 roles del sistema
            $this->crearRolesSistema();
            
            // 4. Asignar permisos a cada rol
            $this->asignarPermisosARoles();
            
            DB::commit();
            
            $this->command->info('✅ Roles y permisos creados exitosamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear todos los permisos del sistema
     */
    private function crearPermisos(): void
    {
        $permisos = [
            // ADMINISTRACIÓN
            ['nombre' => 'administracion.dashboard.ver', 'descripcion' => 'Ver dashboard administrativo', 'categoria' => 'administracion', 'subcategoria' => 'dashboard', 'recurso' => 'dashboard', 'accion' => 'leer', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'administracion.configuracion.gestionar', 'descripcion' => 'Gestionar configuración del sistema', 'categoria' => 'administracion', 'subcategoria' => 'configuracion', 'recurso' => 'configuracion', 'accion' => 'actualizar', 'nivel_requerido' => 5, 'sistema' => true],

            // USUARIOS
            ['nombre' => 'usuarios.crear', 'descripcion' => 'Crear nuevos usuarios', 'categoria' => 'usuarios', 'recurso' => 'users', 'accion' => 'crear', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'usuarios.ver', 'descripcion' => 'Ver información de usuarios', 'categoria' => 'usuarios', 'recurso' => 'users', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],
            ['nombre' => 'usuarios.editar', 'descripcion' => 'Editar información de usuarios', 'categoria' => 'usuarios', 'recurso' => 'users', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'usuarios.eliminar', 'descripcion' => 'Eliminar usuarios', 'categoria' => 'usuarios', 'recurso' => 'users', 'accion' => 'eliminar', 'nivel_requerido' => 5, 'sistema' => true],

            // ROLES Y SEGURIDAD
            ['nombre' => 'roles.gestionar', 'descripcion' => 'Gestionar roles del sistema', 'categoria' => 'seguridad', 'subcategoria' => 'roles', 'recurso' => 'roles', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'seguridad.configurar', 'descripcion' => 'Configurar políticas de seguridad', 'categoria' => 'seguridad', 'recurso' => 'seguridad', 'accion' => 'actualizar', 'nivel_requerido' => 5, 'sistema' => true],

            // TRD
            ['nombre' => 'trd.crear', 'descripcion' => 'Crear Tablas de Retención Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'trd', 'recurso' => 'trd', 'accion' => 'crear', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'trd.ver', 'descripcion' => 'Ver Tablas de Retención Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'trd', 'recurso' => 'trd', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'trd.editar', 'descripcion' => 'Editar Tablas de Retención Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'trd', 'recurso' => 'trd', 'accion' => 'actualizar', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'trd.aprobar', 'descripcion' => 'Aprobar Tablas de Retención Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'trd', 'recurso' => 'trd', 'accion' => 'aprobar', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'trd.exportar', 'descripcion' => 'Exportar Tablas de Retención Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'trd', 'recurso' => 'trd', 'accion' => 'exportar', 'nivel_requerido' => 2, 'sistema' => true],

            // CCD
            ['nombre' => 'ccd.crear', 'descripcion' => 'Crear elementos del Cuadro de Clasificación', 'categoria' => 'clasificacion', 'subcategoria' => 'ccd', 'recurso' => 'ccd', 'accion' => 'crear', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'ccd.ver', 'descripcion' => 'Ver Cuadro de Clasificación Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'ccd', 'recurso' => 'ccd', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'ccd.editar', 'descripcion' => 'Editar Cuadro de Clasificación Documental', 'categoria' => 'clasificacion', 'subcategoria' => 'ccd', 'recurso' => 'ccd', 'accion' => 'actualizar', 'nivel_requerido' => 3, 'sistema' => true],

            // SERIES DOCUMENTALES
            ['nombre' => 'series.crear', 'descripcion' => 'Crear Series Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'series', 'recurso' => 'series', 'accion' => 'crear', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'series.ver', 'descripcion' => 'Ver Series Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'series', 'recurso' => 'series', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'series.editar', 'descripcion' => 'Editar Series Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'series', 'recurso' => 'series', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // SUBSERIES DOCUMENTALES
            ['nombre' => 'subseries.crear', 'descripcion' => 'Crear Subseries Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'subseries', 'recurso' => 'subseries', 'accion' => 'crear', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'subseries.ver', 'descripcion' => 'Ver Subseries Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'subseries', 'recurso' => 'subseries', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'subseries.editar', 'descripcion' => 'Editar Subseries Documentales', 'categoria' => 'clasificacion', 'subcategoria' => 'subseries', 'recurso' => 'subseries', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // EXPEDIENTES
            ['nombre' => 'expedientes.crear', 'descripcion' => 'Crear Expedientes Electrónicos', 'categoria' => 'expedientes', 'recurso' => 'expedientes', 'accion' => 'crear', 'nivel_requerido' => 2, 'sistema' => true],
            ['nombre' => 'expedientes.ver', 'descripcion' => 'Ver Expedientes Electrónicos', 'categoria' => 'expedientes', 'recurso' => 'expedientes', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'expedientes.editar', 'descripcion' => 'Editar Expedientes Electrónicos', 'categoria' => 'expedientes', 'recurso' => 'expedientes', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // PLANTILLAS
            ['nombre' => 'plantillas.crear', 'descripcion' => 'Crear Plantillas Documentales', 'categoria' => 'plantillas', 'recurso' => 'plantillas', 'accion' => 'crear', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'plantillas.ver', 'descripcion' => 'Ver Plantillas Documentales', 'categoria' => 'plantillas', 'recurso' => 'plantillas', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'plantillas.editar', 'descripcion' => 'Editar Plantillas Documentales', 'categoria' => 'plantillas', 'recurso' => 'plantillas', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // PRÉSTAMOS
            ['nombre' => 'prestamos.ver', 'descripcion' => 'Ver Préstamos y Consultas', 'categoria' => 'prestamos', 'recurso' => 'prestamos', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],
            ['nombre' => 'prestamos.gestionar', 'descripcion' => 'Gestionar Préstamos y Consultas', 'categoria' => 'prestamos', 'recurso' => 'prestamos', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // DISPOSICIONES
            ['nombre' => 'disposiciones.ver', 'descripcion' => 'Ver Disposiciones Finales', 'categoria' => 'disposiciones', 'recurso' => 'disposiciones', 'accion' => 'leer', 'nivel_requerido' => 3, 'sistema' => true],

            // REPORTES (agregar ver)
            ['nombre' => 'reportes.ver', 'descripcion' => 'Ver Reportes y Estadísticas', 'categoria' => 'reportes', 'recurso' => 'reportes', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],

            // NOTIFICACIONES
            ['nombre' => 'notificaciones.gestionar', 'descripcion' => 'Gestionar Notificaciones', 'categoria' => 'notificaciones', 'recurso' => 'notificaciones', 'accion' => 'actualizar', 'nivel_requerido' => 2, 'sistema' => true],

            // ÍNDICES
            ['nombre' => 'indices.ver', 'descripcion' => 'Ver Índices Electrónicos', 'categoria' => 'indices', 'recurso' => 'indices', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],

            // FIRMAS DIGITALES
            ['nombre' => 'firmas.gestionar', 'descripcion' => 'Gestionar Firmas Digitales', 'categoria' => 'firmas', 'recurso' => 'firmas', 'accion' => 'actualizar', 'nivel_requerido' => 3, 'sistema' => true],

            // WORKFLOW
            ['nombre' => 'workflow.gestionar', 'descripcion' => 'Gestionar Workflow de Aprobaciones', 'categoria' => 'workflow', 'recurso' => 'workflow', 'accion' => 'actualizar', 'nivel_requerido' => 3, 'sistema' => true],

            // API
            ['nombre' => 'api.gestionar', 'descripcion' => 'Gestionar API Tokens', 'categoria' => 'api', 'recurso' => 'api', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],

            // CERTIFICADOS
            ['nombre' => 'certificados.gestionar', 'descripcion' => 'Gestionar Certificados Digitales', 'categoria' => 'certificados', 'recurso' => 'certificados', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],

            // IMPORTACIÓN
            ['nombre' => 'importacion.gestionar', 'descripcion' => 'Gestionar Importaciones de Datos', 'categoria' => 'importacion', 'recurso' => 'importacion', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],

            // USUARIOS (agregar activar)
            ['nombre' => 'usuarios.activar', 'descripcion' => 'Activar/Desactivar usuarios', 'categoria' => 'usuarios', 'recurso' => 'users', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],

            // BÚSQUEDA
            ['nombre' => 'busqueda.basica', 'descripcion' => 'Realizar búsquedas básicas', 'categoria' => 'busqueda', 'subcategoria' => 'basica', 'recurso' => 'busqueda', 'accion' => 'leer', 'nivel_requerido' => 1, 'sistema' => true],
            ['nombre' => 'busqueda.avanzada', 'descripcion' => 'Realizar búsquedas avanzadas', 'categoria' => 'busqueda', 'subcategoria' => 'avanzada', 'recurso' => 'busqueda', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],

            // REPORTES
            ['nombre' => 'reportes.generar', 'descripcion' => 'Generar reportes del sistema', 'categoria' => 'reportes', 'recurso' => 'reportes', 'accion' => 'leer', 'nivel_requerido' => 2, 'sistema' => true],
            ['nombre' => 'reportes.exportar', 'descripcion' => 'Exportar reportes', 'categoria' => 'reportes', 'recurso' => 'reportes', 'accion' => 'exportar', 'nivel_requerido' => 2, 'sistema' => true],

            // AUDITORÍA
            ['nombre' => 'auditoria.ver', 'descripcion' => 'Ver pistas de auditoría', 'categoria' => 'auditoria', 'recurso' => 'auditoria', 'accion' => 'leer', 'nivel_requerido' => 4, 'sistema' => true],
            ['nombre' => 'auditoria.exportar', 'descripcion' => 'Exportar pistas de auditoría', 'categoria' => 'auditoria', 'recurso' => 'auditoria', 'accion' => 'exportar', 'nivel_requerido' => 4, 'sistema' => true],

            // RETENCIÓN Y DISPOSICIÓN
            ['nombre' => 'retencion.gestionar', 'descripcion' => 'Gestionar políticas de retención', 'categoria' => 'retencion', 'recurso' => 'retencion', 'accion' => 'actualizar', 'nivel_requerido' => 3, 'sistema' => true],
            ['nombre' => 'disposicion.ejecutar', 'descripcion' => 'Ejecutar disposiciones finales', 'categoria' => 'retencion', 'subcategoria' => 'disposicion', 'recurso' => 'disposicion', 'accion' => 'actualizar', 'nivel_requerido' => 4, 'sistema' => true],

            // PERFIL (para usuarios sin rol asignado)
            ['nombre' => 'perfil.ver', 'descripcion' => 'Ver su propio perfil', 'categoria' => 'usuarios', 'recurso' => 'perfil', 'accion' => 'leer', 'nivel_requerido' => 0, 'sistema' => true],
            ['nombre' => 'perfil.editar', 'descripcion' => 'Editar nombre, email y contraseña propia', 'categoria' => 'usuarios', 'recurso' => 'perfil', 'accion' => 'actualizar', 'nivel_requerido' => 0, 'sistema' => true],
        ];

        foreach ($permisos as $permiso) {
            Permiso::firstOrCreate(
                ['nombre' => $permiso['nombre']],
                $permiso
            );
        }

        $this->command->info('✅ Permisos creados');
    }

    /**
     * Crear rol "Sin Acceso" para usuarios recién registrados
     */
    private function crearRolSinAcceso(): void
    {
        Role::firstOrCreate(
            ['name' => 'Sin Acceso'],
            [
                'description' => 'Usuario nuevo sin acceso al sistema. Solo puede editar su perfil hasta que un administrador le asigne un rol.',
                'nivel_jerarquico' => 7,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'acceso_limitado' => true,
                    'solo_perfil' => true,
                    'requiere_asignacion_rol' => true
                ]),
                'observaciones' => 'Rol temporal para usuarios nuevos'
            ]
        );

        $this->command->info('✅ Rol "Sin Acceso" creado');
    }

    /**
     * Crear los 8 roles del sistema según el documento
     */
    private function crearRolesSistema(): void
    {
        $roles = [
            // 1. Super Administrador (Nivel 1)
            [
                'name' => 'Super Administrador',
                'description' => 'Control total del sistema',
                'nivel_jerarquico' => 1,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'acceso_total' => true,
                    'puede_modificar_sistema' => true,
                    'puede_eliminar_usuarios' => true
                ])
            ],
            // 2. Administrador (Nivel 2)
            [
                'name' => 'Administrador',
                'description' => 'Administración general del sistema',
                'nivel_jerarquico' => 2,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'gestiona_usuarios' => true,
                    'aprueba_trd' => true,
                    'genera_reportes_avanzados' => true
                ])
            ],
            // 3. Administrador de Seguridad (Nivel 2)
            [
                'name' => 'Administrador de Seguridad',
                'description' => 'Gestión de seguridad y control de acceso',
                'nivel_jerarquico' => 2,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'gestiona_roles' => true,
                    'configura_seguridad' => true,
                    'audita_accesos' => true
                ])
            ],
            // 4. Supervisor (Nivel 3)
            [
                'name' => 'Supervisor',
                'description' => 'Supervisión de procesos documentales',
                'nivel_jerarquico' => 3,
                'padre_id' => null, // Se asignará después
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'aprueba_series' => true,
                    'supervisa_clasificacion' => true,
                    'genera_reportes_operativos' => true
                ])
            ],
            // 5. Coordinador (Nivel 4)
            [
                'name' => 'Coordinador',
                'description' => 'Coordinación de actividades documentales',
                'nivel_jerarquico' => 4,
                'padre_id' => null, // Se asignará después
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'crea_trd' => true,
                    'gestiona_series' => true,
                    'coordina_clasificacion' => true
                ])
            ],
            // 6. Operativo (Nivel 5)
            [
                'name' => 'Operativo',
                'description' => 'Operaciones básicas del sistema',
                'nivel_jerarquico' => 5,
                'padre_id' => null, // Se asignará después
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'carga_documentos' => true,
                    'edita_metadatos_basicos' => true,
                    'clasifica_documentos' => true
                ])
            ],
            // 7. Consulta (Nivel 6)
            [
                'name' => 'Consulta',
                'description' => 'Solo consulta de información',
                'nivel_jerarquico' => 6,
                'padre_id' => null, // Se asignará después
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'solo_lectura' => true,
                    'busqueda_basica' => true,
                    'sin_edicion' => true
                ])
            ],
            // 8. Auditor (Nivel 3 - Independiente)
            [
                'name' => 'Auditor',
                'description' => 'Auditoría y revisión del sistema',
                'nivel_jerarquico' => 3,
                'padre_id' => null,
                'activo' => true,
                'sistema' => true,
                'configuracion' => json_encode([
                    'acceso_auditoria' => true,
                    'genera_reportes_cumplimiento' => true,
                    'sin_modificacion' => true
                ])
            ],
        ];

        foreach ($roles as $rolData) {
            Role::firstOrCreate(
                ['name' => $rolData['name']],
                $rolData
            );
        }

        // Asignar relaciones padre-hijo después de crear todos los roles
        $this->asignarJerarquiaRoles();

        $this->command->info('✅ Roles del sistema creados');
    }

    /**
     * Asignar jerarquía padre-hijo a los roles
     */
    private function asignarJerarquiaRoles(): void
    {
        $administrador = Role::where('name', 'Administrador')->first();
        $supervisor = Role::where('name', 'Supervisor')->first();
        $coordinador = Role::where('name', 'Coordinador')->first();
        $operativo = Role::where('name', 'Operativo')->first();

        if ($administrador && $supervisor) {
            $supervisor->update(['padre_id' => $administrador->id]);
        }

        if ($supervisor && $coordinador) {
            $coordinador->update(['padre_id' => $supervisor->id]);
        }

        if ($coordinador && $operativo) {
            $operativo->update(['padre_id' => $coordinador->id]);
        }

        if ($operativo) {
            $consulta = Role::where('name', 'Consulta')->first();
            if ($consulta) {
                $consulta->update(['padre_id' => $operativo->id]);
            }
        }
    }

    /**
     * Asignar permisos a cada rol según la matriz del documento
     */
    private function asignarPermisosARoles(): void
    {
        // Rol: Sin Acceso - Solo perfil
        $this->asignarPermisosARol('Sin Acceso', [
            'perfil.ver',
            'perfil.editar',
        ]);

        // Rol: Super Administrador - TODOS los permisos
        $superAdmin = Role::where('name', 'Super Administrador')->first();
        if ($superAdmin) {
            $todosLosPermisos = Permiso::where('sistema', true)->pluck('id');
            $superAdmin->permisos()->sync($todosLosPermisos);
        }

        // Rol: Administrador
        $this->asignarPermisosARol('Administrador', [
            'administracion.dashboard.ver',
            'usuarios.crear', 'usuarios.ver', 'usuarios.editar', 'usuarios.activar',
            'roles.gestionar',
            'trd.crear', 'trd.ver', 'trd.editar', 'trd.aprobar', 'trd.exportar',
            'ccd.crear', 'ccd.ver', 'ccd.editar',
            'series.crear', 'series.ver', 'series.editar',
            'subseries.crear', 'subseries.ver', 'subseries.editar',
            'expedientes.crear', 'expedientes.ver', 'expedientes.editar',
            'plantillas.crear', 'plantillas.ver', 'plantillas.editar',
            'documentos.crear', 'documentos.ver', 'documentos.editar', 'documentos.eliminar',
            'prestamos.ver', 'prestamos.gestionar',
            'disposiciones.ver',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'notificaciones.gestionar',
            'indices.ver',
            'auditoria.ver', 'auditoria.exportar',
            'retencion.gestionar', 'disposicion.ejecutar',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Administrador de Seguridad
        $this->asignarPermisosARol('Administrador de Seguridad', [
            'administracion.dashboard.ver',
            'usuarios.crear', 'usuarios.ver', 'usuarios.editar', 'usuarios.activar',
            'roles.gestionar',
            'seguridad.configurar',
            'trd.ver', 'trd.exportar',
            'ccd.ver',
            'series.ver',
            'subseries.ver',
            'expedientes.ver',
            'plantillas.ver',
            'documentos.crear', 'documentos.ver', 'documentos.editar',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'notificaciones.gestionar',
            'indices.ver',
            'firmas.gestionar',
            'certificados.gestionar',
            'auditoria.ver', 'auditoria.exportar',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Supervisor
        $this->asignarPermisosARol('Supervisor', [
            'administracion.dashboard.ver',
            'usuarios.ver',
            'trd.crear', 'trd.ver', 'trd.editar', 'trd.aprobar', 'trd.exportar',
            'ccd.crear', 'ccd.ver', 'ccd.editar',
            'series.crear', 'series.ver', 'series.editar',
            'subseries.crear', 'subseries.ver', 'subseries.editar',
            'expedientes.crear', 'expedientes.ver', 'expedientes.editar',
            'plantillas.crear', 'plantillas.ver', 'plantillas.editar',
            'documentos.crear', 'documentos.ver', 'documentos.editar', 'documentos.eliminar',
            'prestamos.ver', 'prestamos.gestionar',
            'disposiciones.ver',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'notificaciones.gestionar',
            'indices.ver',
            'firmas.gestionar',
            'workflow.gestionar',
            'retencion.gestionar',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Coordinador
        $this->asignarPermisosARol('Coordinador', [
            'trd.crear', 'trd.ver', 'trd.editar', 'trd.exportar',
            'ccd.crear', 'ccd.ver', 'ccd.editar',
            'series.crear', 'series.ver', 'series.editar',
            'subseries.crear', 'subseries.ver', 'subseries.editar',
            'expedientes.crear', 'expedientes.ver', 'expedientes.editar',
            'plantillas.crear', 'plantillas.ver', 'plantillas.editar',
            'documentos.crear', 'documentos.ver', 'documentos.editar',
            'prestamos.ver',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'retencion.gestionar',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Operativo
        $this->asignarPermisosARol('Operativo', [
            'trd.ver', 'trd.exportar',
            'ccd.ver',
            'series.ver', 'series.editar',
            'subseries.ver',
            'expedientes.ver',
            'plantillas.ver',
            'documentos.crear', 'documentos.ver', 'documentos.editar',
            'prestamos.ver',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Consulta
        $this->asignarPermisosARol('Consulta', [
            'trd.ver',
            'ccd.ver',
            'series.ver',
            'subseries.ver',
            'expedientes.ver',
            'plantillas.ver',
            'documentos.ver',
            'busqueda.basica',
            'reportes.ver',
            'perfil.ver', 'perfil.editar',
        ]);

        // Rol: Auditor
        $this->asignarPermisosARol('Auditor', [
            'administracion.dashboard.ver',
            'usuarios.ver',
            'trd.ver', 'trd.exportar',
            'ccd.ver',
            'series.ver',
            'subseries.ver',
            'expedientes.ver',
            'plantillas.ver',
            'documentos.ver',
            'busqueda.basica', 'busqueda.avanzada',
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'indices.ver',
            'auditoria.ver', 'auditoria.exportar',
            'perfil.ver', 'perfil.editar',
        ]);

        $this->command->info('✅ Permisos asignados a roles');
    }

    /**
     * Asignar permisos a un rol específico
     */
    private function asignarPermisosARol(string $nombreRol, array $nombresPermisos): void
    {
        $role = Role::where('name', $nombreRol)->first();
        
        if (!$role) {
            $this->command->warn("⚠️  Rol '$nombreRol' no encontrado");
            return;
        }

        $permisosIds = Permiso::whereIn('nombre', $nombresPermisos)->pluck('id')->toArray();
        
        if (count($permisosIds) !== count($nombresPermisos)) {
            $this->command->warn("⚠️  Algunos permisos no fueron encontrados para rol '$nombreRol'");
        }

        $role->permisos()->sync($permisosIds);
    }
}
