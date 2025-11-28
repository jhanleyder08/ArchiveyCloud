<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class FixUsersWithoutRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-without-role {--force : Forzar la asignación sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna el rol "Sin Acceso" a todos los usuarios que no tienen rol asignado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando usuarios sin rol asignado...');
        
        // Obtener rol "Sin Acceso"
        $rolSinAcceso = Role::where('name', 'Sin Acceso')->first();
        
        if (!$rolSinAcceso) {
            $this->error('❌ Error: No se encontró el rol "Sin Acceso" en la base de datos.');
            $this->warn('⚠️  Por favor ejecute primero: php artisan db:seed --class=RolesYPermisosSeeder');
            return 1;
        }
        
        $this->info("✅ Rol 'Sin Acceso' encontrado (ID: {$rolSinAcceso->id})");
        
        // Buscar usuarios sin rol
        $usersWithoutRole = User::whereNull('role_id')->get();
        
        if ($usersWithoutRole->isEmpty()) {
            $this->info('✅ No se encontraron usuarios sin rol asignado. Todo está correcto.');
            return 0;
        }
        
        $count = $usersWithoutRole->count();
        $this->warn("⚠️  Se encontraron {$count} usuario(s) sin rol asignado:");
        
        // Mostrar lista de usuarios
        $this->table(
            ['ID', 'Nombre', 'Email', 'Fecha de registro'],
            $usersWithoutRole->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d H:i:s')
                ];
            })
        );
        
        // Confirmar acción si no se usa --force
        if (!$this->option('force')) {
            if (!$this->confirm("¿Desea asignar el rol 'Sin Acceso' a estos {$count} usuario(s)?")) {
                $this->info('❌ Operación cancelada por el usuario.');
                return 0;
            }
        }
        
        // Asignar rol "Sin Acceso" a cada usuario
        $this->info('🔄 Asignando rol "Sin Acceso"...');
        
        $updated = 0;
        foreach ($usersWithoutRole as $user) {
            $user->role_id = $rolSinAcceso->id;
            $user->save();
            $updated++;
            
            $this->line("  ✓ {$user->name} ({$user->email})");
        }
        
        $this->info("✅ Se asignó el rol 'Sin Acceso' a {$updated} usuario(s) exitosamente.");
        $this->info('');
        $this->info('💡 Ahora puede ir a la sección de Usuarios en el admin para asignarles roles específicos.');
        
        return 0;
    }
}
