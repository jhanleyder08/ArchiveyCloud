<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'jhanleyder71@gmail.com')->first();

if ($user) {
    echo "✅ USUARIO ENCONTRADO\n";
    echo "==================\n";
    echo "Nombre: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Rol: " . ($user->role ? $user->role->name : 'Sin rol asignado') . "\n";
    echo "Nivel jerárquico: " . ($user->role ? $user->role->nivel_jerarquico : 'N/A') . "\n";
    echo "Es Super Admin: " . ($user->role && $user->role->nivel_jerarquico === 1 ? '✅ SÍ' : '❌ NO') . "\n";
    echo "Activo: " . ($user->active ? 'Sí' : 'No') . "\n";
    echo "Estado: {$user->estado_cuenta}\n";
    echo "Email verificado: " . ($user->email_verified_at ? 'Sí' : 'No') . "\n";
    
    if ($user->role) {
        echo "\nPermisos del rol ({$user->role->permisos()->count()}):\n";
        $permisos = $user->role->permisos()->get(['nombre'])->pluck('nombre')->take(5);
        foreach ($permisos as $permiso) {
            echo "  - {$permiso}\n";
        }
        if ($user->role->permisos()->count() > 5) {
            echo "  ... y " . ($user->role->permisos()->count() - 5) . " permisos más\n";
        }
    }
} else {
    echo "❌ USUARIO NO ENCONTRADO\n";
    echo "==================\n";
    echo "El email 'jhanleyder71@gmail.com' no existe en la base de datos.\n";
    echo "\nPara crear este usuario como Super Administrador, ejecuta:\n";
    echo "php artisan tinker\n";
    echo "\nY luego:\n";
    echo "\$user = new App\\Models\\User();\n";
    echo "\$user->name = 'Jhan Duarte';\n";
    echo "\$user->email = 'jhanleyder71@gmail.com';\n";
    echo "\$user->password = bcrypt('Admi1234');\n";
    echo "\$user->role_id = 1;\n";
    echo "\$user->active = true;\n";
    echo "\$user->estado_cuenta = 'activo';\n";
    echo "\$user->email_verified_at = now();\n";
    echo "\$user->save();\n";
}
