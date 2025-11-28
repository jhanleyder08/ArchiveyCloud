<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SetupSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:superadmin {email? : Email del usuario a convertir en Super Administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea los roles del sistema y convierte un usuario en Super Administrador';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando configuración de Super Administrador...');

        try {
            DB::beginTransaction();

            // 1. Crear roles del sistema si no existen
            $this->info('📋 Verificando roles del sistema...');
            
            $rolesExistentes = Role::count();
            
            if ($rolesExistentes === 0) {
                $this->warn('⚠️  La tabla de roles está vacía. Creando roles del sistema...');
                Role::crearRolesSistema();
                $this->info('✅ Roles del sistema creados exitosamente.');
            } else {
                $this->info('ℹ️  Ya existen roles en el sistema (' . $rolesExistentes . ' roles).');
            }

            // 2. Obtener el rol de Super Administrador
            $superAdminRole = Role::where('name', 'Super Administrador')->first();
            
            if (!$superAdminRole) {
                $this->error('❌ No se pudo encontrar el rol de Super Administrador.');
                DB::rollBack();
                return 1;
            }

            $this->info("✅ Rol 'Super Administrador' encontrado (ID: {$superAdminRole->id})");

            // 3. Obtener el usuario
            $email = $this->argument('email');
            
            if (!$email) {
                // Intentar encontrar al usuario por nombre
                $user = User::where('name', 'Jhan Duarte')->first();
                
                if (!$user) {
                    // Si no existe, buscar por email predeterminado
                    $user = User::where('email', 'jhanleyder71@gmail.com')->first();
                }
                
                if (!$user) {
                    // Listar todos los usuarios disponibles
                    $this->warn('⚠️  No se encontró el usuario "Jhan Duarte".');
                    $this->info('Usuarios disponibles:');
                    
                    $usuarios = User::select('id', 'name', 'email')->get();
                    
                    foreach ($usuarios as $u) {
                        $rolActual = $u->role ? $u->role->name : 'Sin rol';
                        $this->line("  - ID: {$u->id} | {$u->name} ({$u->email}) | Rol: {$rolActual}");
                    }
                    
                    $userId = $this->ask('Ingresa el ID del usuario que deseas convertir en Super Administrador');
                    $user = User::find($userId);
                    
                    if (!$user) {
                        $this->error('❌ Usuario no encontrado.');
                        DB::rollBack();
                        return 1;
                    }
                }
            } else {
                $user = User::where('email', $email)->first();
                
                if (!$user) {
                    $this->error("❌ No se encontró ningún usuario con el email: {$email}");
                    DB::rollBack();
                    return 1;
                }
            }

            $this->info("👤 Usuario encontrado: {$user->name} ({$user->email})");

            // 4. Asignar el rol de Super Administrador
            $rolAnterior = $user->role ? $user->role->name : 'Sin rol';
            
            $user->role_id = $superAdminRole->id;
            $user->save();

            $this->info("✅ Rol actualizado de '{$rolAnterior}' a 'Super Administrador'");

            DB::commit();

            $this->newLine();
            $this->info('🎉 ¡Configuración completada exitosamente!');
            $this->info("El usuario {$user->name} ahora es Super Administrador.");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Ubicación: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }
}
