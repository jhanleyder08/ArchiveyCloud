<?php

/**
 * Script de verificación del sistema de permisos
 * 
 * Ejecutar con: php verificar-permisos.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permiso;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   VERIFICACIÓN DEL SISTEMA DE PERMISOS Y ROLES\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// 1. Verificar que existen roles
echo "1️⃣  Verificando roles en el sistema...\n";
echo "───────────────────────────────────────────────────────────────\n";

$roles = Role::all();
if ($roles->isEmpty()) {
    echo "❌ ERROR: No hay roles en el sistema\n";
    echo "   Ejecuta los seeders primero: php artisan db:seed\n";
    exit(1);
}

echo "✅ Roles encontrados: " . $roles->count() . "\n\n";
foreach ($roles as $role) {
    $permisosCount = $role->permisos()->count();
    echo "   • {$role->name} (Nivel {$role->nivel_jerarquico}) - {$permisosCount} permisos\n";
}
echo "\n";

// 2. Verificar que existen permisos
echo "2️⃣  Verificando permisos en el sistema...\n";
echo "───────────────────────────────────────────────────────────────\n";

$permisos = Permiso::all();
if ($permisos->isEmpty()) {
    echo "❌ ERROR: No hay permisos en el sistema\n";
    echo "   Ejecuta los seeders primero: php artisan db:seed\n";
    exit(1);
}

echo "✅ Permisos encontrados: " . $permisos->count() . "\n";
echo "   Permisos esperados: 33\n\n";

// 3. Verificar rol "Sin Acceso"
echo "3️⃣  Verificando rol 'Sin Acceso'...\n";
echo "───────────────────────────────────────────────────────────────\n";

$rolSinAcceso = Role::where('name', 'Sin Acceso')->first();
if (!$rolSinAcceso) {
    echo "❌ ERROR: No existe el rol 'Sin Acceso'\n";
    exit(1);
}

$permisosSinAcceso = $rolSinAcceso->permisos;
echo "✅ Rol 'Sin Acceso' encontrado\n";
echo "   ID: {$rolSinAcceso->id}\n";
echo "   Nivel: {$rolSinAcceso->nivel_jerarquico}\n";
echo "   Permisos: {$permisosSinAcceso->count()}\n";

if ($permisosSinAcceso->count() > 0) {
    echo "   Permisos asignados:\n";
    foreach ($permisosSinAcceso as $permiso) {
        echo "      - {$permiso->nombre}\n";
    }
}
echo "\n";

// 4. Verificar usuarios con rol "Sin Acceso"
echo "4️⃣  Verificando usuarios con rol 'Sin Acceso'...\n";
echo "───────────────────────────────────────────────────────────────\n";

$usuariosSinAcceso = User::where('role_id', $rolSinAcceso->id)->get();
echo "✅ Usuarios con rol 'Sin Acceso': {$usuariosSinAcceso->count()}\n";

if ($usuariosSinAcceso->count() > 0) {
    foreach ($usuariosSinAcceso as $user) {
        echo "   • {$user->name} ({$user->email})\n";
    }
} else {
    echo "   ℹ️  No hay usuarios con este rol actualmente\n";
}
echo "\n";

// 5. Verificar usuario Super Administrador
echo "5️⃣  Verificando Super Administrador...\n";
echo "───────────────────────────────────────────────────────────────\n";

$rolSuperAdmin = Role::where('name', 'Super Administrador')->first();
if ($rolSuperAdmin) {
    $superAdmins = User::where('role_id', $rolSuperAdmin->id)->get();
    echo "✅ Super Administradores: {$superAdmins->count()}\n";
    
    if ($superAdmins->count() > 0) {
        foreach ($superAdmins as $admin) {
            $permisos = $admin->role->permisos;
            echo "   • {$admin->name} ({$admin->email})\n";
            echo "     Permisos: {$permisos->count()}\n";
            
            // Verificar método hasPermission
            if (method_exists($admin, 'hasPermission')) {
                $testPermiso = $admin->hasPermission('usuarios.ver');
                echo "     hasPermission('usuarios.ver'): " . ($testPermiso ? '✅' : '❌') . "\n";
            }
        }
    }
} else {
    echo "❌ ERROR: No existe el rol 'Super Administrador'\n";
}
echo "\n";

// 6. Verificar middleware registrado
echo "6️⃣  Verificando middleware de permisos...\n";
echo "───────────────────────────────────────────────────────────────\n";

$middlewarePath = __DIR__ . '/app/Http/Middleware/PermissionMiddleware.php';
if (file_exists($middlewarePath)) {
    echo "✅ PermissionMiddleware existe\n";
    echo "   Ubicación: app/Http/Middleware/PermissionMiddleware.php\n";
} else {
    echo "❌ ERROR: PermissionMiddleware no encontrado\n";
}
echo "\n";

// 7. Verificar hook de React
echo "7️⃣  Verificando hook de permisos en frontend...\n";
echo "───────────────────────────────────────────────────────────────\n";

$hookPath = __DIR__ . '/resources/js/hooks/usePermissions.ts';
if (file_exists($hookPath)) {
    echo "✅ usePermissions hook existe\n";
    echo "   Ubicación: resources/js/hooks/usePermissions.ts\n";
} else {
    echo "❌ ERROR: usePermissions hook no encontrado\n";
}
echo "\n";

// 8. Resumen final
echo "═══════════════════════════════════════════════════════════════\n";
echo "   RESUMEN DE VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$checks = [
    'Roles en sistema' => $roles->count() > 0,
    'Permisos en sistema' => $permisos->count() >= 33,
    'Rol "Sin Acceso"' => $rolSinAcceso !== null,
    'Rol "Super Administrador"' => $rolSuperAdmin !== null,
    'PermissionMiddleware' => file_exists($middlewarePath),
    'usePermissions hook' => file_exists($hookPath),
];

$todosOk = true;
foreach ($checks as $check => $passed) {
    $icon = $passed ? '✅' : '❌';
    echo "{$icon} {$check}\n";
    if (!$passed) {
        $todosOk = false;
    }
}

echo "\n";

if ($todosOk) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ✅ SISTEMA DE PERMISOS CORRECTAMENTE CONFIGURADO\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Próximos pasos:\n";
    echo "1. Probar login con usuario 'Sin Acceso'\n";
    echo "2. Verificar que solo ve el Dashboard\n";
    echo "3. Intentar acceder a /admin/users (debe ser bloqueado)\n";
    echo "4. Probar login con Super Administrador\n";
    echo "5. Verificar que ve todas las opciones\n";
    echo "\n";
} else {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ⚠️  HAY PROBLEMAS EN LA CONFIGURACIÓN\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Revisa los errores arriba y corrígelos antes de continuar.\n";
    echo "\n";
    exit(1);
}
