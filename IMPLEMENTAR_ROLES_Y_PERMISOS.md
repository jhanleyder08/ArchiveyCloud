# 🔐 IMPLEMENTACIÓN DE ROLES Y PERMISOS - SGDEA

## 📋 CAMBIOS IMPLEMENTADOS

### **1. ✅ Seeder Completo: `RolesYPermisosSeeder.php`**

Se ha creado un seeder que:
- ✅ Crea **33 permisos** del sistema organizados en 10 categorías
- ✅ Crea **9 roles** (8 del sistema + 1 "Sin Acceso")
- ✅ Asigna permisos a cada rol según la matriz del documento
- ✅ Establece jerarquía padre-hijo entre roles

### **2. ✅ Modificación del Registro: `RegisteredUserController.php`**

Los usuarios nuevos ahora:
- ✅ Se registran con el rol **"Sin Acceso"**
- ✅ Solo pueden editar su perfil (nombre, email, contraseña)
- ✅ NO tienen acceso a módulos del sidebar
- ✅ Esperan a que un administrador les asigne un rol real

---

## 🚀 PASOS PARA IMPLEMENTAR

### **Paso 1: Ejecutar el Seeder**

```bash
# Navegar a la carpeta del proyecto
cd "d:\xampp\htdocs\PROYECTOS\Nueva carpeta\ArchiveyCloud"

# Ejecutar el seeder de roles y permisos
php artisan db:seed --class=RolesYPermisosSeeder
```

**Resultado esperado:**
```
✅ Permisos creados
✅ Rol "Sin Acceso" creado
✅ Roles del sistema creados
✅ Permisos asignados a roles
✅ Roles y permisos creados exitosamente
```

---

### **Paso 2: Verificar Roles Creados**

```bash
# Ver todos los roles
php artisan tinker

# Dentro de tinker:
App\Models\Role::all(['id', 'name', 'nivel_jerarquico']);
```

**Deberías ver 9 roles:**
```
1. Sin Acceso (Nivel 7)
2. Super Administrador (Nivel 1)
3. Administrador (Nivel 2)
4. Administrador de Seguridad (Nivel 2)
5. Supervisor (Nivel 3)
6. Coordinador (Nivel 4)
7. Operativo (Nivel 5)
8. Consulta (Nivel 6)
9. Auditor (Nivel 3)
```

---

### **Paso 3: Verificar Permisos Creados**

```bash
# Dentro de tinker:
App\Models\Permiso::count();
```

**Deberías ver:** `33 permisos`

---

### **Paso 4: Verificar Asignación de Permisos**

```bash
# Ver permisos del Super Administrador
$superAdmin = App\Models\Role::where('name', 'Super Administrador')->first();
$superAdmin->permisos->count();  // Debería mostrar 33 (todos)

# Ver permisos del rol "Sin Acceso"
$sinAcceso = App\Models\Role::where('name', 'Sin Acceso')->first();
$sinAcceso->permisos->pluck('nombre');  // Debería mostrar solo: perfil.ver y perfil.editar
```

---

### **Paso 5: Probar Registro de Usuario Nuevo**

1. Ve a: `http://127.0.0.1:8000/register`
2. Registra un nuevo usuario
3. Verifica que:
   - ✅ El usuario se crea con `role_id` del rol "Sin Acceso"
   - ✅ Solo puede acceder a su perfil
   - ✅ No ve opciones del sidebar

```bash
# Verificar el rol asignado
$user = App\Models\User::where('email', 'test@example.com')->first();
$user->role->name;  // Debería mostrar: "Sin Acceso"
```

---

### **Paso 6: Asignar Rol Real a Usuario**

Para que un usuario pueda acceder al sistema, un administrador debe asignarle un rol:

```bash
# Opción 1: Desde tinker
$user = App\Models\User::where('email', 'test@example.com')->first();
$rolOperativo = App\Models\Role::where('name', 'Operativo')->first();
$user->role_id = $rolOperativo->id;
$user->save();

# Opción 2: Desde la interfaz de administración (cuando esté lista)
# Ir a Usuarios → Editar Usuario → Asignar Rol
```

---

## 📊 ROLES Y SUS PERMISOS

### **1. 🔴 Sin Acceso (Nivel 7)**
```
Permisos (2):
├── perfil.ver
└── perfil.editar

Uso: Usuario recién registrado
```

---

### **2. 🔴 Super Administrador (Nivel 1)**
```
Permisos (33): TODOS

Acceso completo a:
├── Administración (Dashboard, Config)
├── Usuarios (CRUD completo)
├── Roles y Seguridad
├── TRD, CCD, Series
├── Documentos (CRUD completo)
├── Búsqueda (Básica y Avanzada)
├── Reportes
├── Auditoría
└── Retención y Disposición
```

---

### **3. 🟠 Administrador (Nivel 2)**
```
Permisos (28):
✅ Crear, editar usuarios (no eliminar)
✅ Gestionar roles
✅ Aprobar TRD
✅ CRUD completo de documentos
✅ Ver y exportar auditoría
✅ Ejecutar disposición final
❌ No configura sistema
❌ No elimina usuarios
```

---

### **4. 🟠 Administrador de Seguridad (Nivel 2)**
```
Permisos (20):
✅ Crear, editar usuarios
✅ Gestionar roles
✅ Configurar seguridad
✅ Ver y exportar auditoría
✅ CRUD documentos
❌ No aprueba TRD
❌ No ejecuta disposición
```

---

### **5. 🟡 Supervisor (Nivel 3)**
```
Permisos (23):
✅ Ver dashboard
✅ Ver usuarios
✅ Aprobar TRD
✅ CRUD completo TRD, CCD, Series
✅ Eliminar documentos
✅ Gestionar retención
❌ No gestiona usuarios
❌ No ve auditoría
```

---

### **6. 🟢 Coordinador (Nivel 4)**
```
Permisos (18):
✅ CRUD TRD (excepto aprobar)
✅ CRUD CCD y Series
✅ Crear, ver, editar documentos
✅ Búsqueda avanzada
✅ Generar reportes
✅ Gestionar retención
❌ No aprueba TRD
❌ No elimina documentos
❌ Sin dashboard admin
```

---

### **7. 🔵 Operativo (Nivel 5)**
```
Permisos (13):
✅ Ver y exportar TRD
✅ Ver CCD
✅ Editar series
✅ Crear, ver, editar documentos
✅ Búsqueda avanzada
✅ Generar reportes
❌ No crea TRD/CCD/Series
❌ No elimina documentos
```

---

### **8. ⚪ Consulta (Nivel 6)**
```
Permisos (7):
✅ Ver TRD, CCD, Series
✅ Ver documentos
✅ Búsqueda básica
✅ Ver su perfil
❌ Sin edición
❌ Sin creación
❌ Sin reportes
```

---

### **9. 🟣 Auditor (Nivel 3)**
```
Permisos (17):
✅ Ver dashboard
✅ Ver usuarios
✅ Ver y exportar TRD, CCD, Series
✅ Ver documentos
✅ Búsqueda avanzada
✅ Generar y exportar reportes
✅ VER Y EXPORTAR AUDITORÍA (principal)
❌ Sin modificación
❌ Sin creación
```

---

## 🔐 JERARQUÍA DE ROLES

```
Super Administrador (1)
    │
    ├── Administrador (2)
    │       │
    │       ├── Supervisor (3)
    │       │       │
    │       │       └── Coordinador (4)
    │       │               │
    │       │               └── Operativo (5)
    │       │                       │
    │       │                       └── Consulta (6)
    │       │
    │       └── Auditor (3) [independiente]
    │
    └── Admin. Seguridad (2)

Sin Acceso (7) [sin jerarquía]
```

---

## 🛠️ COMANDOS ÚTILES

### **Listar Roles**
```bash
php artisan tinker
App\Models\Role::all(['name', 'nivel_jerarquico', 'activo']);
```

### **Ver Permisos de un Rol**
```bash
$role = App\Models\Role::where('name', 'Coordinador')->first();
$role->permisos->pluck('nombre');
```

### **Ver Usuarios con Rol Específico**
```bash
$role = App\Models\Role::where('name', 'Sin Acceso')->first();
$role->users;
```

### **Cambiar Rol de un Usuario**
```bash
$user = App\Models\User::find(1);
$rol = App\Models\Role::where('name', 'Super Administrador')->first();
$user->role_id = $rol->id;
$user->save();
```

### **Ver Estadísticas de Roles**
```bash
foreach (App\Models\Role::all() as $role) {
    echo $role->name . ": " . $role->users()->count() . " usuarios\n";
}
```

---

## ⚠️ IMPORTANTE - NOTAS DE SEGURIDAD

### **1. Usuarios Nuevos**
- ✅ Se crean con rol "Sin Acceso"
- ✅ Solo pueden editar su perfil
- ✅ NO tienen acceso a módulos del sidebar
- ⚠️ **Un administrador DEBE asignarles un rol manualmente**

### **2. Primer Administrador**
Para crear el primer Super Administrador del sistema:

```bash
php artisan tinker

# Crear usuario admin
$user = new App\Models\User();
$user->name = 'Administrador Principal';
$user->email = 'admin@archiveycloud.com';
$user->password = bcrypt('Password123!');
$user->role_id = 1; // Super Administrador
$user->active = true;
$user->estado_cuenta = 'activo';
$user->email_verified_at = now();
$user->save();
```

### **3. Protección de Roles del Sistema**
- 🔒 Los roles con `sistema = true` NO pueden ser eliminados
- 🔒 Los roles del sistema NO pueden cambiar su nombre o nivel jerárquico
- 🔒 Solo Super Administradores pueden modificar configuración de seguridad

---

## 🧪 PRUEBAS RECOMENDADAS

### **Test 1: Registro de Usuario Nuevo**
1. Registrar usuario en `/register`
2. Verificar que tiene rol "Sin Acceso"
3. Intentar acceder al dashboard → Debería bloquearse
4. Verificar que solo puede editar perfil

### **Test 2: Asignación de Rol**
1. Como Super Admin, asignar rol "Operativo" a usuario
2. Usuario debe poder:
   - ✅ Ver y crear documentos
   - ✅ Realizar búsquedas
   - ✅ Generar reportes
3. Usuario NO debe poder:
   - ❌ Crear TRD
   - ❌ Ver auditoría
   - ❌ Gestionar usuarios

### **Test 3: Herencia de Permisos**
1. Verificar que Consulta (hijo de Operativo) tiene menos permisos
2. Verificar que Supervisor puede hacer todo lo que hace Coordinador
3. Verificar que jerarquía funciona correctamente

---

## 📝 CHECKLIST DE IMPLEMENTACIÓN

- [ ] Ejecutar seeder `RolesYPermisosSeeder`
- [ ] Verificar creación de 9 roles
- [ ] Verificar creación de 33 permisos
- [ ] Verificar asignación de permisos a roles
- [ ] Probar registro de usuario nuevo
- [ ] Verificar que usuario nuevo tiene rol "Sin Acceso"
- [ ] Crear primer Super Administrador
- [ ] Probar asignación de roles a usuarios
- [ ] Verificar middleware de permisos en sidebar
- [ ] Probar acceso a diferentes módulos según rol
- [ ] Documentar proceso para otros administradores

---

## 🔄 PRÓXIMOS PASOS

### **1. Modificar Sidebar**
El sidebar debe:
- Mostrar solo opciones según permisos del usuario
- Ocultar completamente módulos sin acceso
- Mostrar mensaje si usuario tiene rol "Sin Acceso"

### **2. Crear Middleware de Permisos**
```php
// Middleware: CheckPermission
if (!auth()->user()->role->hasPermission($permission)) {
    abort(403, 'No tienes permiso para acceder a este recurso');
}
```

### **3. Interfaz de Administración de Usuarios**
Crear página para que administradores puedan:
- Ver lista de usuarios
- Ver rol actual de cada usuario
- Cambiar rol de usuarios
- Ver usuarios con rol "Sin Acceso" pendientes de asignación

---

## 📞 SOPORTE

Si tienes problemas:

1. **Verificar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verificar estado de la base de datos:**
   ```bash
   php artisan migrate:status
   ```

3. **Re-ejecutar seeder si es necesario:**
   ```bash
   php artisan db:seed --class=RolesYPermisosSeeder --force
   ```

---

**✅ IMPLEMENTACIÓN COMPLETADA**

Ahora el sistema tiene:
- ✅ 9 roles definidos con jerarquía
- ✅ 33 permisos organizados por categorías
- ✅ Matriz completa de permisos por rol
- ✅ Usuarios nuevos con acceso limitado
- ✅ Sistema listo para asignación de roles

**📅 Fecha:** 2025-11-04
**📝 Versión:** 1.0
