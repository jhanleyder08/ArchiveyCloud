<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RolesSeeder::class);

        // Create default admin user
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@archiveycloud.com',
            'role_id' => 1, // Administrador role
            'is_active' => true,
        ]);

        // Create default user
        User::factory()->create([
            'name' => 'Usuario Demo',
            'email' => 'usuario@archiveycloud.com',
            'role_id' => 2, // Usuario role
            'is_active' => true,
        ]);
    }
}
