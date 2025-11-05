<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AsignarRolUsuario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar un rol a un usuario por su email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Usuario con email '{$email}' no encontrado");
            return 1;
        }

        // Buscar rol
        $role = Role::where('name', $roleName)
            ->orWhere('id', $roleName)
            ->first();

        if (!$role) {
            $this->error("❌ Rol '{$roleName}' no encontrado");
            $this->line("\n📋 Roles disponibles:");
            
            $roles = Role::all(['id', 'name', 'nivel_jerarquico']);
            $this->table(
                ['ID', 'Nombre', 'Nivel'],
                $roles->map(fn($r) => [$r->id, $r->name, $r->nivel_jerarquico])
            );
            
            return 1;
        }

        // Guardar rol anterior
        $rolAnterior = $user->role ? $user->role->name : 'Sin rol';

        // Asignar nuevo rol
        $user->role_id = $role->id;
        $user->save();

        $this->info("✅ Rol asignado exitosamente");
        $this->line("\n📊 Resumen:");
        $this->line("Usuario: {$user->name} ({$user->email})");
        $this->line("Rol anterior: {$rolAnterior}");
        $this->line("Nuevo rol: {$role->name} (Nivel {$role->nivel_jerarquico})");
        $this->line("\n🔑 Permisos del rol:");
        
        $permisos = $role->permisos()->get(['nombre', 'descripcion']);
        $this->table(
            ['Permiso', 'Descripción'],
            $permisos->map(fn($p) => [$p->nombre, $p->descripcion])->take(10)
        );
        
        if ($permisos->count() > 10) {
            $this->line("... y " . ($permisos->count() - 10) . " permisos más");
        }

        return 0;
    }
}
