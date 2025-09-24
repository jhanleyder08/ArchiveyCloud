<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;

class ListRoles extends Command
{
    protected $signature = 'list:roles';
    protected $description = 'List all roles in the system';

    public function handle()
    {
        $this->info('📋 Available Roles:');
        
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $this->line("  ID: {$role->id} - Name: {$role->name}");
        }
        
        return 0;
    }
}
