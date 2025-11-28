<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SetupPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:permissions {--assign-to-superadmin : Asignar todos los permisos al Super Administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea los permisos del sistema y opcionalmente los asigna al Super Administrador';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando configuración de permisos del sistema...');

        try {
            DB::beginTransaction();

            // 1. Verificar permisos existentes
            $this->info('📋 Verificando permisos existentes...');
            $permisosExistentes = Permiso::count();
            
            if ($permisosExistentes === 0) {
                $this->warn('⚠️  La tabla de permisos está vacía. Creando permisos del sistema...');
                Permiso::crearPermisosSistema();
                $permisosCreados = Permiso::count();
                $this->info("✅ {$permisosCreados} permisos del sistema creados exitosamente.");
            } else {
                $this->info("ℹ️  Ya existen {$permisosExistentes} permisos en el sistema.");
                
                if ($this->confirm('¿Deseas crear los permisos faltantes?')) {
                    Permiso::crearPermisosSistema();
                    $nuevosPermisos = Permiso::count() - $permisosExistentes;
                    if ($nuevosPermisos > 0) {
                        $this->info("✅ {$nuevosPermisos} nuevos permisos creados.");
                    } else {
                        $this->info("ℹ️  No hay permisos nuevos para crear.");
                    }
                }
            }

            // 2. Asignar permisos al Super Administrador si se solicita
            if ($this->option('assign-to-superadmin') || $this->confirm('¿Deseas asignar TODOS los permisos al rol de Super Administrador?', true)) {
                $this->info('🔐 Asignando permisos al Super Administrador...');
                
                $superAdminRole = Role::where('name', 'Super Administrador')->first();
                
                if (!$superAdminRole) {
                    $this->error('❌ No se encontró el rol de Super Administrador.');
                    $this->warn('💡 Ejecuta primero: php artisan setup:superadmin');
                    DB::rollBack();
                    return 1;
                }

                // Obtener todos los permisos
                $todosLosPermisos = Permiso::activos()->pluck('id')->toArray();
                
                // Obtener permisos actuales del Super Administrador
                $permisosActuales = $superAdminRole->permisos()->pluck('permiso_id')->toArray();
                
                // Sincronizar permisos (sin eliminar los existentes)
                $superAdminRole->permisos()->sync($todosLosPermisos);
                
                $permisosAsignados = count($todosLosPermisos);
                $permisosNuevos = count(array_diff($todosLosPermisos, $permisosActuales));
                
                $this->info("✅ {$permisosAsignados} permisos totales asignados al Super Administrador.");
                if ($permisosNuevos > 0) {
                    $this->info("   ({$permisosNuevos} permisos nuevos asignados)");
                }
            }

            DB::commit();

            // 3. Mostrar resumen
            $this->newLine();
            $this->info('📊 Resumen de configuración:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total de permisos en el sistema', Permiso::count()],
                    ['Permisos activos', Permiso::activos()->count()],
                    ['Permisos del sistema', Permiso::sistema()->count()],
                ]
            );

            $this->newLine();
            $this->info('🎉 ¡Configuración completada exitosamente!');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Ubicación: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }
}
