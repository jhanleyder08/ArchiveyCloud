<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\User;

class ShowRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all roles and user counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 ROLES Y USUARIOS ACTUALES');
        $this->line('================================');
        
        $roles = Role::withCount('users')->get();
        
        if ($roles->isEmpty()) {
            $this->warn('No hay roles configurados en el sistema');
            return;
        }
        
        $tableData = [];
        foreach ($roles as $role) {
            $tableData[] = [
                'ID' => $role->id,
                'Nombre' => $role->name,
                'Descripción' => $role->description ?? 'Sin descripción',
                'Usuarios' => $role->users_count,
                'Activo' => $role->activo ? 'Sí' : 'No'
            ];
        }
        
        $this->table(
            ['ID', 'Nombre', 'Descripción', 'Usuarios', 'Activo'],
            $tableData
        );
        
        $this->line('');
        $this->info('👥 USUARIOS POR ROL:');
        
        foreach ($roles as $role) {
            if ($role->users_count > 0) {
                $this->line("📋 {$role->name} ({$role->users_count} usuarios):");
                $users = User::where('role_id', $role->id)->get(['id', 'name', 'email', 'active']);
                foreach ($users as $user) {
                    $status = $user->active ? '✅' : '❌';
                    $this->line("   {$status} {$user->name} ({$user->email})");
                }
                $this->line('');
            }
        }
        
        return 0;
    }
}
