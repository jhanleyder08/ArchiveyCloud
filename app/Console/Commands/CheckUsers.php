<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class CheckUsers extends Command
{
    protected $signature = 'app:check-users';
    protected $description = 'Check users and their roles in the system';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE USUARIOS ===');
        
        $users = User::with('role')->get();
        
        foreach ($users as $user) {
            $roleName = $user->role ? $user->role->name : 'Sin rol asignado';
            $this->line("ID: {$user->id} | Email: {$user->email} | Rol: {$roleName}");
        }
        
        $this->info("\n=== ROLES DISPONIBLES ===");
        $roles = Role::where('activo', true)->get();
        
        foreach ($roles as $role) {
            $this->line("ID: {$role->id} | Nombre: {$role->name}");
        }
        
        // Verificar usuario admin específicamente
        $admin = User::where('email', 'admin@archiveycloud.com')->first();
        if ($admin) {
            $this->info("\n=== USUARIO ADMIN ===");
            $this->line("Email: {$admin->email}");
            $this->line("Estado: {$admin->estado_cuenta}");
            $this->line("Role ID: " . ($admin->role_id ?? 'null'));
            $this->line("Rol: " . ($admin->role ? $admin->role->name : 'Sin rol'));
        } else {
            $this->error('Usuario admin@archiveycloud.com NO encontrado');
        }
    }
}
