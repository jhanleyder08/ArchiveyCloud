# 🔐 GUÍA: ASIGNAR ROL DE SUPER ADMINISTRADOR

## 📋 COMANDO RÁPIDO

### **Crear y Asignar Super Administrador a un Usuario**

```bash
php artisan tinker --execute="
\$role = DB::table('roles')->insertGetId([
    'name' => 'Super Administrador',
    'description' => 'Acceso total al sistema',
    'nivel_jerarquico' => 1,
    'activo' => true,
    'sistema' => true,
    'created_at' => now(),
    'updated_at' => now()
]);
echo 'Rol Super Administrador creado con ID: ' . \$role . PHP_EOL;
\$user = DB::table('users')->where('email', 'TU_EMAIL@gmail.com')->first();
if (\$user) {
    DB::table('user_roles')->insert([
        'user_id' => \$user->id,
        'role_id' => \$role,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo 'Rol asignado al usuario ' . \$user->email . ' ✓' . PHP_EOL;
} else {
    echo 'Usuario no encontrado. Verifica el email.' . PHP_EOL;
}
"
```

**⚠️ IMPORTANTE:** Reemplaza `TU_EMAIL@gmail.com` con el email del usuario.

---

## ✅ EJEMPLO EJECUTADO

```bash
# Usuario: jhanleyder71@gmail.com (ID: 16)
php artisan tinker --execute="
\$role = DB::table('roles')->insertGetId([
    'name' => 'Super Administrador',
    'description' => 'Acceso total al sistema',
    'nivel_jerarquico' => 1,
    'activo' => true,
    'sistema' => true,
    'created_at' => now(),
    'updated_at' => now()
]);
echo 'Rol Super Administrador creado con ID: ' . \$role . PHP_EOL;
DB::table('user_roles')->insert([
    'user_id' => 16,
    'role_id' => \$role,
    'created_at' => now(),
    'updated_at' => now()
]);
echo 'Rol asignado al usuario jhanleyder71@gmail.com ✓' . PHP_EOL;
"
```

**Resultado:**
```
Rol Super Administrador creado con ID: 1
Rol asignado al usuario jhanleyder71@gmail.com ✓
```

---

## 🔍 COMANDOS ÚTILES

### **1. Ver todos los roles existentes**
```bash
php artisan tinker --execute="
echo 'Roles disponibles:' . PHP_EOL;
DB::table('roles')->select('id', 'name')->whereNull('deleted_at')->get()->each(function(\$r) { 
    echo 'ID: ' . \$r->id . ' - ' . \$r->name . PHP_EOL; 
});
"
```

---

### **2. Ver todos los usuarios y sus roles**
```bash
php artisan tinker --execute="
\$users = DB::table('users')
    ->leftJoin('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
    ->select('users.id', 'users.email', 'users.name', 'roles.name as role_name')
    ->whereNull('users.deleted_at')
    ->get();
echo 'Usuarios registrados:' . PHP_EOL;
foreach (\$users as \$u) {
    echo 'ID: ' . \$u->id . ' | ' . \$u->email . ' | Rol: ' . (\$u->role_name ?? 'Sin rol') . PHP_EOL;
}
"
```

---

### **3. Asignar rol existente a un usuario**
```bash
php artisan tinker --execute="
\$user = DB::table('users')->where('email', 'EMAIL_USUARIO@gmail.com')->first();
\$role = DB::table('roles')->where('name', 'Super Administrador')->first();
if (\$user && \$role) {
    DB::table('user_roles')->updateOrInsert(
        ['user_id' => \$user->id],
        ['role_id' => \$role->id, 'updated_at' => now(), 'created_at' => now()]
    );
    echo 'Rol asignado correctamente ✓' . PHP_EOL;
} else {
    echo 'Usuario o rol no encontrado' . PHP_EOL;
}
"
```

---

### **4. Ver columnas de la tabla roles**
```bash
php artisan tinker --execute="
\$columns = DB::select('SHOW COLUMNS FROM roles');
echo 'Columnas de la tabla roles:' . PHP_EOL;
foreach (\$columns as \$col) {
    echo '- ' . \$col->Field . PHP_EOL;
}
"
```

**Resultado:**
```
Columnas de la tabla roles:
- id
- name
- description
- nivel_jerarquico
- padre_id
- activo
- sistema
- configuracion
- observaciones
- created_at
- updated_at
- deleted_at
```

---

### **5. Buscar un usuario por email**
```bash
php artisan tinker --execute="
echo 'Buscando usuario...' . PHP_EOL;
\$user = DB::table('users')->where('email', 'jhanleyder71@gmail.com')->first();
if (\$user) {
    echo 'Usuario encontrado:' . PHP_EOL;
    echo 'ID: ' . \$user->id . PHP_EOL;
    echo 'Email: ' . \$user->email . PHP_EOL;
    echo 'Nombre: ' . \$user->name . PHP_EOL;
} else {
    echo 'Usuario no encontrado' . PHP_EOL;
}
"
```

---

### **6. Verificar el rol de un usuario**
```bash
php artisan tinker --execute="
\$user = DB::table('users')->where('email', 'jhanleyder71@gmail.com')->first();
if (\$user) {
    \$userRole = DB::table('user_roles')
        ->join('roles', 'user_roles.role_id', '=', 'roles.id')
        ->where('user_roles.user_id', \$user->id)
        ->select('roles.name', 'roles.id')
        ->first();
    if (\$userRole) {
        echo 'Usuario: ' . \$user->email . PHP_EOL;
        echo 'Rol: ' . \$userRole->name . ' (ID: ' . \$userRole->id . ')' . PHP_EOL;
    } else {
        echo 'El usuario no tiene rol asignado' . PHP_EOL;
    }
}
"
```

---

## 📊 ESTRUCTURA DE LA TABLA ROLES

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único del rol |
| `name` | varchar(255) | Nombre del rol |
| `description` | text | Descripción del rol |
| `nivel_jerarquico` | int | Nivel en la jerarquía (1=más alto) |
| `padre_id` | bigint | ID del rol padre |
| `activo` | boolean | Si el rol está activo |
| `sistema` | boolean | Si es un rol del sistema |
| `configuracion` | json | Configuración adicional |
| `observaciones` | text | Observaciones |
| `created_at` | timestamp | Fecha de creación |
| `updated_at` | timestamp | Fecha de actualización |
| `deleted_at` | timestamp | Fecha de eliminación (soft delete) |

---

## 🎯 ROLES ESTÁNDAR DEL SISTEMA

| ID | Nombre | Nivel Jerárquico | Descripción |
|----|--------|------------------|-------------|
| 1 | Super Administrador | 1 | Acceso total al sistema |
| 2 | Administrador SGDEA | 2 | Administrador del archivo |
| 3 | Administrador de Seguridad | 2 | Gestión de seguridad |
| 4 | Supervisor | 3 | Supervisión de procesos |
| 5 | Auditor | 3 | Auditoría del sistema |
| 6 | Coordinador | 4 | Coordinación de actividades |
| 7 | Operativo | 5 | Operaciones diarias |
| 8 | Consulta | 6 | Solo consulta |
| 9 | Sin Acceso | 7 | Sin permisos (usuarios nuevos) |

---

## ⚡ PASOS DETALLADOS

### **Paso 1: Verificar que no existe el rol**
```bash
php artisan tinker --execute="
\$role = DB::table('roles')->where('name', 'Super Administrador')->first();
if (\$role) {
    echo 'El rol Super Administrador ya existe con ID: ' . \$role->id . PHP_EOL;
} else {
    echo 'El rol Super Administrador NO existe, procede a crearlo' . PHP_EOL;
}
"
```

### **Paso 2: Verificar que existe el usuario**
```bash
php artisan tinker --execute="
\$user = DB::table('users')->where('email', 'jhanleyder71@gmail.com')->first();
if (\$user) {
    echo 'Usuario encontrado - ID: ' . \$user->id . ' - Email: ' . \$user->email . PHP_EOL;
} else {
    echo 'Usuario NO encontrado, verifica el email' . PHP_EOL;
}
"
```

### **Paso 3: Crear el rol (si no existe)**
```bash
php artisan tinker --execute="
\$role = DB::table('roles')->insertGetId([
    'name' => 'Super Administrador',
    'description' => 'Acceso total al sistema',
    'nivel_jerarquico' => 1,
    'activo' => true,
    'sistema' => true,
    'created_at' => now(),
    'updated_at' => now()
]);
echo 'Rol creado con ID: ' . \$role . PHP_EOL;
"
```

### **Paso 4: Asignar el rol al usuario**
```bash
php artisan tinker --execute="
DB::table('user_roles')->insert([
    'user_id' => 16,
    'role_id' => 1,
    'created_at' => now(),
    'updated_at' => now()
]);
echo 'Rol asignado correctamente ✓' . PHP_EOL;
"
```

---

## 🔒 VERIFICACIÓN FINAL

```bash
php artisan tinker --execute="
\$user = DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
    ->where('users.email', 'jhanleyder71@gmail.com')
    ->select('users.email', 'users.name', 'roles.name as role_name', 'roles.id as role_id')
    ->first();
if (\$user) {
    echo '✅ VERIFICACIÓN EXITOSA' . PHP_EOL;
    echo 'Usuario: ' . \$user->name . ' (' . \$user->email . ')' . PHP_EOL;
    echo 'Rol asignado: ' . \$user->role_name . ' (ID: ' . \$user->role_id . ')' . PHP_EOL;
} else {
    echo '❌ No se encontró el usuario con rol asignado' . PHP_EOL;
}
"
```

---

## 📝 NOTAS IMPORTANTES

1. **El rol "Super Administrador" debe tener `nivel_jerarquico = 1`** (el más alto)
2. **Marca `sistema = true`** para indicar que es un rol del sistema
3. **Marca `activo = true`** para que el rol esté activo
4. **Si el usuario ya tiene un rol asignado**, usa `updateOrInsert` para reemplazarlo
5. **Para verificar permisos**, asegúrate de que la tabla `role_permisos` tenga los permisos asignados al rol

---

## 🚀 COMANDO TODO EN UNO (Recomendado)

```bash
php artisan tinker --execute="
echo '🔍 Verificando sistema...' . PHP_EOL . PHP_EOL;

// Verificar usuario
\$email = 'jhanleyder71@gmail.com';
\$user = DB::table('users')->where('email', \$email)->first();
if (!\$user) {
    echo '❌ Usuario no encontrado: ' . \$email . PHP_EOL;
    exit;
}
echo '✓ Usuario encontrado - ID: ' . \$user->id . PHP_EOL;

// Verificar o crear rol
\$role = DB::table('roles')->where('name', 'Super Administrador')->first();
if (!\$role) {
    \$roleId = DB::table('roles')->insertGetId([
        'name' => 'Super Administrador',
        'description' => 'Acceso total al sistema',
        'nivel_jerarquico' => 1,
        'activo' => true,
        'sistema' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo '✓ Rol Super Administrador creado - ID: ' . \$roleId . PHP_EOL;
} else {
    \$roleId = \$role->id;
    echo '✓ Rol Super Administrador existente - ID: ' . \$roleId . PHP_EOL;
}

// Asignar rol
DB::table('user_roles')->updateOrInsert(
    ['user_id' => \$user->id],
    ['role_id' => \$roleId, 'updated_at' => now(), 'created_at' => now()]
);
echo '✓ Rol asignado correctamente' . PHP_EOL . PHP_EOL;

// Verificación final
\$verification = DB::table('users')
    ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
    ->where('users.id', \$user->id)
    ->select('users.email', 'roles.name as role_name')
    ->first();

echo '✅ RESULTADO FINAL:' . PHP_EOL;
echo 'Usuario: ' . \$verification->email . PHP_EOL;
echo 'Rol: ' . \$verification->role_name . PHP_EOL;
"
```

---

## 📅 Fecha de Creación
**9 de diciembre de 2025**

---

## ✅ Estado
**IMPLEMENTADO Y VERIFICADO**
- Usuario: jhanleyder71@gmail.com (ID: 16)
- Rol: Super Administrador (ID: 1)
- Fecha: 2025-12-10 02:25:20
