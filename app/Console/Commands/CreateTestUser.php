<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for testing the role functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Crear usuario de prueba
        $user = User::create([
            'name' => 'Usuario de Prueba',
            'email' => 'test@archiveycloud.com',
            'password' => Hash::make('password'),
            'active' => true,
            'estado_cuenta' => User::ESTADO_ACTIVO,
            'email_verified_at' => now(),
        ]);

        $this->info("Usuario de prueba creado:");
        $this->info("ID: {$user->id}");
        $this->info("Email: {$user->email}");
        $this->info("Nombre: {$user->name}");
        
        // Mostrar roles disponibles
        $roles = Role::where('activo', true)->orderBy('nivel_jerarquico')->get(['id', 'name']);
        $this->info("\nRoles disponibles:");
        foreach ($roles as $role) {
            $this->info("- ID: {$role->id}, Nombre: {$role->name}");
        }
        
        $this->info("\nAhora puedes editar este usuario en: http://127.0.0.1:8000/admin/users/{$user->id}/edit");
    }
}
