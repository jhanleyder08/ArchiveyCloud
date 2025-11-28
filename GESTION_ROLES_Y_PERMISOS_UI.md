# 🎨 Gestión de Roles y Permisos - Interfaz de Usuario

**Fecha:** 2025-11-28  
**Estado:** ✅ IMPLEMENTADO  
**Acceso:** Solo Super Administrador

---

## 🎯 FUNCIONALIDAD IMPLEMENTADA

Se ha agregado una **interfaz completa** para que el **Super Administrador** pueda gestionar los permisos de cada rol directamente desde la interfaz web.

---

## 🚀 ACCESO

### **Desde la página de Usuarios:**
```
1. Ir a: http://127.0.0.1:8000/admin/users
2. Solo el Super Administrador verá el botón "Gestionar Roles"
3. Hacer clic en "Gestionar Roles"
4. Se abrirá la interfaz de gestión
```

### **Directamente:**
```
URL: http://127.0.0.1:8000/admin/roles
Nota: Solo accesible para Super Administrador
```

---

## 📋 CARACTERÍSTICAS

### **1. Listado de Roles**
- ✅ Muestra todos los roles del sistema
- ✅ Indica el nivel jerárquico de cada rol
- ✅ Muestra la cantidad de permisos asignados
- ✅ Marca el rol "Super Administrador" como protegido
- ✅ Descripción de cada rol

### **2. Gestión de Permisos por Rol**
- ✅ Visualización de permisos por categorías
- ✅ Checkboxes para activar/desactivar permisos individuales
- ✅ Opción para seleccionar/deseleccionar toda una categoría
- ✅ Contador de permisos seleccionados
- ✅ Tabs para navegar entre categorías

### **3. Protección del Super Administrador**
- ✅ El rol "Super Administrador" NO puede ser modificado
- ✅ Mensaje de alerta al intentar modificarlo
- ✅ Siempre tiene todos los permisos del sistema

### **4. Indicador de Cambios**
- ✅ Alerta visual cuando hay cambios sin guardar
- ✅ Botones "Guardar" y "Cancelar" prominentes
- ✅ Confirmación al cambiar de rol con cambios sin guardar

### **5. Responsive Design**
- ✅ Se adapta a móviles, tablets y desktop
- ✅ Layout de 2 columnas en desktop
- ✅ Stack vertical en móviles

---

## 🔒 SEGURIDAD

### **Gate Implementado:**
```php
Gate::define('manage-roles', function ($user) {
    return $user->role && $user->role->name === 'Super Administrador';
});
```

### **Verificaciones:**
- ✅ **Backend:** Middleware `can:manage-roles` en todas las rutas
- ✅ **Frontend:** Hook `usePermissions().isSuperAdmin` para mostrar el botón
- ✅ **Controlador:** `$this->authorize('manage-roles')` en cada método
- ✅ **Base de Datos:** Validación de que los permisos existan

### **Protecciones:**
- ❌ No se puede modificar el rol "Super Administrador"
- ❌ No se puede desactivar roles del sistema
- ❌ No se pueden asignar permisos que no existen

---

## 📁 ARCHIVOS CREADOS/MODIFICADOS

### **Nuevos Archivos:**

1. **`app/Http/Controllers/Admin/RoleController.php`**
   - Controlador para gestión de roles
   - Métodos: `index`, `updatePermissions`, `toggleStatus`

2. **`resources/js/pages/admin/roles.tsx`**
   - Vista React para gestión de permisos
   - Componente completo con Tabs, Checkboxes, Alertas

3. **`GESTION_ROLES_Y_PERMISOS_UI.md`**
   - Documentación de uso

### **Archivos Modificados:**

1. **`app/Providers/AppServiceProvider.php`**
   - Agregado Gate `manage-roles`

2. **`routes/web.php`**
   - Agregadas rutas para gestión de roles

3. **`resources/js/pages/admin/users.tsx`**
   - Agregado botón "Gestionar Roles" (solo para Super Admin)
   - Import de Shield y usePermissions

---

## 🎨 INTERFAZ DE USUARIO

### **Layout Principal:**
```
┌─────────────────────────────────────────────────┐
│  Header: "Gestión de Roles y Permisos"         │
│  [Botón: Guardar Cambios] (si hay cambios)     │
├─────────────────────────────────────────────────┤
│  Alerta: Solo Super Admin puede modificar      │
├──────────────────┬──────────────────────────────┤
│  Listado Roles   │  Permisos del Rol           │
│  ┌────────────┐  │  ┌───────────────────────┐  │
│  │ Super      │  │  │ [Tabs por Categoría]  │  │
│  │ Admin      │  │  │                       │  │
│  │ (31 perm.) │  │  │ ☑ Permiso 1          │  │
│  ├────────────┤  │  │ ☑ Permiso 2          │  │
│  │ Administra │  │  │ ☐ Permiso 3          │  │
│  │ dor        │  │  │ ...                  │  │
│  │ (26 perm.) │  │  └───────────────────────┘  │
│  ├────────────┤  │                              │
│  │ ...        │  │                              │
│  └────────────┘  │                              │
└──────────────────┴──────────────────────────────┘
│  Alerta: "Tienes cambios sin guardar..."       │
│  [Cancelar] [Guardar]                           │
└─────────────────────────────────────────────────┘
```

### **Categorías de Permisos:**
```
Tabs disponibles:
- Administración
- Usuarios
- Seguridad
- Clasificación
- Expedientes
- Plantillas
- Préstamos
- Disposiciones
- Reportes
- Notificaciones
- Índices
- Firmas
- Workflow
- API
- Certificados
- Importación
- Búsqueda
- Auditoría
- Retención
```

---

## 🔄 FLUJO DE USO

### **Paso 1: Acceder a Gestión de Roles**
```
1. Login como Super Administrador
2. Ir a "Gestión de Usuarios"
3. Clic en botón "Gestionar Roles"
4. Se carga la interfaz con todos los roles
```

### **Paso 2: Seleccionar Rol**
```
1. En la columna izquierda, se muestran todos los roles
2. Hacer clic en el rol que deseas modificar
3. Se cargan los permisos actuales del rol
```

### **Paso 3: Modificar Permisos**
```
1. Navegar por las tabs de categorías
2. Seleccionar/deseleccionar permisos individuales
3. O usar el checkbox "Todos los permisos de [categoría]"
4. El contador se actualiza en tiempo real
```

### **Paso 4: Guardar Cambios**
```
1. Aparece alerta: "Tienes cambios sin guardar"
2. Hacer clic en "Guardar Cambios" (arriba o abajo)
3. Confirmación: "Permisos actualizados exitosamente"
4. Los cambios se reflejan inmediatamente
```

### **Paso 5: Verificar**
```
1. Los usuarios con ese rol deben cerrar sesión
2. Volver a iniciar sesión
3. Verán los nuevos permisos en el sidebar
```

---

## ⚠️ IMPORTANTE

### **Cambios Afectan a Todos los Usuarios:**
Cuando modificas los permisos de un rol, **TODOS los usuarios** que tengan ese rol verán los cambios después de hacer logout/login.

### **No se Puede Modificar Super Administrador:**
El rol "Super Administrador" está protegido y siempre tendrá todos los permisos. Esto es por seguridad.

### **Roles del Sistema:**
Los roles marcados como "sistema" no pueden ser desactivados. Solo se pueden modificar sus permisos.

---

## 🧪 PRUEBAS REALIZADAS

### **Test 1: Acceso Restringido ✅**
```
Usuario: Administrador (no Super Admin)
Resultado: No ve el botón "Gestionar Roles"
Acceso directo a /admin/roles: Error 403
```

### **Test 2: Acceso Super Admin ✅**
```
Usuario: Super Administrador
Resultado: Ve el botón "Gestionar Roles"
Acceso directo a /admin/roles: ✓ Funciona
```

### **Test 3: Modificar Permisos ✅**
```
1. Modificar rol "Operativo"
2. Agregar permiso "usuarios.ver"
3. Guardar cambios
4. Usuario operativo hace logout/login
Resultado: ✓ Ahora puede ver usuarios
```

### **Test 4: Protección Super Admin ✅**
```
1. Intentar modificar "Super Administrador"
2. Los checkboxes están deshabilitados
3. Mensaje: "No puede ser modificado"
Resultado: ✓ Protegido correctamente
```

### **Test 5: Cambios sin Guardar ✅**
```
1. Modificar permisos de un rol
2. Intentar cambiar a otro rol sin guardar
3. Aparece confirmación
Resultado: ✓ Previene pérdida de cambios
```

---

## 📊 ENDPOINTS API

### **GET /admin/roles**
- **Descripción:** Lista todos los roles con sus permisos
- **Autenticación:** Requerida
- **Autorización:** Solo Super Administrador
- **Respuesta:**
  ```json
  {
    "roles": [
      {
        "id": 1,
        "name": "Super Administrador",
        "description": "Control total del sistema",
        "nivel_jerarquico": 1,
        "activo": true,
        "sistema": true,
        "permisos_count": 54,
        "permisos": [1, 2, 3, ...]
      }
    ],
    "permisos": {
      "administracion": [
        {
          "id": 1,
          "nombre": "administracion.dashboard.ver",
          "descripcion": "Ver dashboard administrativo",
          "categoria": "administracion"
        }
      ]
    }
  }
  ```

### **PUT /admin/roles/{role}/permissions**
- **Descripción:** Actualiza los permisos de un rol
- **Autenticación:** Requerida
- **Autorización:** Solo Super Administrador
- **Body:**
  ```json
  {
    "permisos": [1, 2, 3, 5, 8, 13]
  }
  ```
- **Respuesta:**
  ```json
  {
    "message": "Permisos actualizados para el rol 'Administrador' exitosamente."
  }
  ```

### **PATCH /admin/roles/{role}/toggle-status**
- **Descripción:** Activa/desactiva un rol
- **Autenticación:** Requerida
- **Autorización:** Solo Super Administrador
- **Restricción:** No se pueden desactivar roles del sistema
- **Respuesta:**
  ```json
  {
    "message": "Rol 'Operativo' desactivado exitosamente."
  }
  ```

---

## 🎯 CASOS DE USO

### **Caso 1: Dar Más Permisos a Operativos**
```
Problema: Los operativos necesitan ver reportes
Solución:
1. Ir a Gestión de Roles
2. Seleccionar rol "Operativo"
3. Tab "Reportes"
4. ☑ reportes.ver
5. ☑ reportes.generar
6. Guardar
```

### **Caso 2: Restringir Acceso a Auditoría**
```
Problema: Solo Auditores y Admins deben ver auditoría
Solución:
1. Revisar cada rol
2. Desmarcar "auditoria.ver" para roles no autorizados
3. Dejar solo en: Super Admin, Administrador, Auditor
4. Guardar en cada rol modificado
```

### **Caso 3: Nuevo Tipo de Usuario**
```
Problema: Necesitamos rol "Consulta Externa"
Solución:
1. Crear rol en base de datos (seeder o SQL)
2. Ir a Gestión de Roles
3. Seleccionar el nuevo rol
4. Asignar solo permisos de lectura
5. Guardar
```

---

## 🔧 TROUBLESHOOTING

### **Problema: No veo el botón "Gestionar Roles"**
**Solución:**
```
1. Verificar que eres Super Administrador
2. Cerrar sesión y volver a entrar
3. Verificar en consola: 
   window.___inertia.page.props.auth.user.role.name
   // Debe ser "Super Administrador"
```

### **Problema: Error 403 al acceder**
**Solución:**
```
1. Solo Super Administrador tiene acceso
2. Verificar rol en base de datos:
   php artisan tinker
   App\Models\User::find(YOUR_ID)->role->name
```

### **Problema: Los cambios no se reflejan**
**Solución:**
```
1. Asegurarse de hacer clic en "Guardar"
2. Usuarios afectados deben hacer logout/login
3. O presionar F5 para recargar permisos
4. Verificar en consola:
   window.___inertia.page.props.auth.permissions
```

### **Problema: No puedo modificar Super Administrador**
**Solución:**
```
Esto es intencional y por seguridad.
El Super Administrador siempre tiene todos los permisos.
No se puede ni debe modificar.
```

---

## 📝 COMANDOS ÚTILES

### **Verificar permisos de un rol:**
```bash
php artisan tinker
$role = App\Models\Role::find(2); // Administrador
$role->permisos()->pluck('nombre');
```

### **Sincronizar permisos manualmente:**
```bash
php artisan tinker
$role = App\Models\Role::find(2);
$role->permisos()->sync([1, 2, 3, 5, 8]);
```

### **Ver todos los roles y permisos:**
```bash
php verify_admin_permissions.php
```

---

## 🚀 PRÓXIMAS MEJORAS (OPCIONAL)

### **Funcionalidades Adicionales:**
- [ ] Crear nuevos roles desde la interfaz
- [ ] Eliminar roles personalizados
- [ ] Duplicar configuración de un rol
- [ ] Exportar/importar configuración de permisos
- [ ] Historial de cambios en permisos
- [ ] Vista previa de qué módulos verá cada rol
- [ ] Búsqueda de permisos por nombre
- [ ] Comparar permisos entre dos roles

---

## ✅ RESUMEN

**Funcionalidad implementada con éxito:**
- ✅ Interfaz completa para gestión de roles y permisos
- ✅ Acceso restringido solo a Super Administrador
- ✅ Protección del rol Super Administrador
- ✅ Gestión por categorías de permisos
- ✅ Indicadores visuales de cambios
- ✅ Responsive design
- ✅ Seguridad en backend y frontend
- ✅ Documentación completa

**El Super Administrador ahora puede:**
- ✅ Ver todos los roles del sistema
- ✅ Ver todos los permisos disponibles
- ✅ Modificar permisos de cualquier rol (excepto Super Admin)
- ✅ Seleccionar/deseleccionar permisos individuales
- ✅ Seleccionar/deseleccionar categorías completas
- ✅ Guardar y ver cambios en tiempo real

---

**Implementado por:** Windsurf Cascade AI  
**Fecha:** 2025-11-28  
**Estado:** ✅ PRODUCCIÓN READY

---

## 🎊 ¡LISTO PARA USAR!

El Super Administrador ahora tiene control total sobre los permisos de cada rol desde una interfaz intuitiva y segura.

**Accede ahora:**
```
http://127.0.0.1:8000/admin/users
→ Clic en "Gestionar Roles"
```

¡Disfruta de la nueva funcionalidad! 🚀
