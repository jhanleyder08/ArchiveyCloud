<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed SGDEA data in proper order
        $this->call([
            RolesSeeder::class,
            PermisosSeeder::class,
        ]);

        // Create default Super Administrador user usando DB directo para evitar problemas
        DB::table('users')->insert([
            'name' => 'Super Administrador SGDEA',
            'email' => 'admin@archiveycloud.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role_id' => 1, // Super Administrador role
            'active' => true,
            'documento_identidad' => '12345678',
            'tipo_documento' => 'cedula_ciudadania',
            'cargo' => 'Administrador del Sistema',
            'dependencia' => 'Tecnología e Información',
            'fecha_ingreso' => now(),
            'estado_cuenta' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('SGDEA seeders executed successfully!');
        $this->command->info('- Roles: 6 roles created with hierarchy');
        $this->command->info('- Permissions: 20 permissions created by categories');
        $this->command->info('- Admin user: admin@archiveycloud.com (password: password)');
    }
}
