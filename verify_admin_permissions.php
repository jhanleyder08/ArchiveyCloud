<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE ROLES Y PERMISOS ===\n\n";

// 1. Contar roles
$rolesCount = App\Models\Role::count();
echo "✓ Total de roles en BD: $rolesCount\n";

// 2. Contar permisos
$permisosCount = App\Models\Permiso::count();
echo "✓ Total de permisos en BD: $permisosCount\n\n";

// 3. Verificar rol Administrador
$admin = App\Models\Role::where('name', 'Administrador')->first();
if ($admin) {
    echo "✓ Rol 'Administrador' encontrado (ID: {$admin->id})\n";
    $permisosAdmin = $admin->permisos()->count();
    echo "  - Permisos asignados: $permisosAdmin\n";
    
    if ($permisosAdmin == 0) {
        echo "  ⚠️  ERROR: El rol Administrador NO tiene permisos asignados!\n";
    } else {
        echo "  ✓ Permisos: " . $admin->permisos()->pluck('nombre')->take(5)->implode(', ') . "...\n";
    }
} else {
    echo "✗ El rol 'Administrador' NO existe en la BD\n";
}

echo "\n";

// 4. Verificar Super Administrador
$superAdmin = App\Models\Role::where('name', 'Super Administrador')->first();
if ($superAdmin) {
    echo "✓ Rol 'Super Administrador' encontrado (ID: {$superAdmin->id})\n";
    $permisosSuperAdmin = $superAdmin->permisos()->count();
    echo "  - Permisos asignados: $permisosSuperAdmin\n";
    
    if ($permisosSuperAdmin == 0) {
        echo "  ⚠️  ERROR: El rol Super Administrador NO tiene permisos asignados!\n";
    }
} else {
    echo "✗ El rol 'Super Administrador' NO existe en la BD\n";
}

echo "\n";

// 5. Listar todos los roles
echo "=== TODOS LOS ROLES ===\n";
$roles = App\Models\Role::orderBy('nivel_jerarquico')->get();
foreach ($roles as $role) {
    $permisos = $role->permisos()->count();
    echo "  - {$role->name} (Nivel {$role->nivel_jerarquico}): {$permisos} permisos\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
