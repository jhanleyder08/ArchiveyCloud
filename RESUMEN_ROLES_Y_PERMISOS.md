# 📊 RESUMEN EJECUTIVO - ROLES Y PERMISOS SGDEA

## 🎯 CRUD DE USUARIOS

### **Operaciones Principales**

| Operación | Endpoint | Permiso | Nivel Mínimo |
|-----------|----------|---------|--------------|
| **Crear** | POST /api/users | `usuarios.crear` | Administrador |
| **Leer** | GET /api/users | `usuarios.ver` | Intermedio |
| **Actualizar** | PUT /api/users/{id} | `usuarios.editar` | Administrador |
| **Eliminar** | DELETE /api/users/{id} | `usuarios.eliminar` | Super Admin |

### **Datos del Usuario**

```
📝 Información Básica
├── name (nombre completo)
├── email (único)
├── password (encriptada)
├── role_id (rol principal)
└── active (activo/inactivo)

🆔 Identificación
├── tipo_documento (CC/CE/Pasaporte/TI)
├── documento_identidad
├── telefono
├── cargo
└── dependencia

🔒 Control y Seguridad
├── intentos_fallidos (máx 3)
├── bloqueado_hasta
├── fecha_vencimiento_cuenta
├── cambio_password_requerido
└── historial_passwords (últimas 5)
```

---

## 👥 TIPOS DE USUARIOS (8 ROLES)

### **1. 🔴 Super Administrador** (Nivel 1)
```
✅ Control total del sistema
✅ Gestiona configuración crítica
✅ Elimina cualquier usuario
✅ Configura políticas de seguridad
```
**Uso:** Director TI, Admin del sistema

---

### **2. 🟠 Administrador** (Nivel 2)
```
✅ Gestiona usuarios (excepto Super Admin)
✅ Aprueba TRD y CCD
✅ Genera reportes avanzados
✅ Supervisa auditoría
❌ No configura sistema
```
**Uso:** Jefe de archivo, Coordinador administrativo

---

### **3. 🟠 Administrador de Seguridad** (Nivel 2)
```
✅ Gestiona roles y permisos
✅ Configura políticas de seguridad
✅ Audita accesos
✅ Gestiona certificados digitales
❌ No gestiona usuarios directamente
```
**Uso:** Oficial de seguridad, Auditor de sistemas

---

### **4. 🟡 Supervisor** (Nivel 3)
```
✅ Aprueba series documentales
✅ Supervisa clasificación
✅ Genera reportes operativos
✅ Asigna tareas
❌ No crea usuarios
```
**Uso:** Supervisor de archivo, Jefe de sección

---

### **5. 🟢 Coordinador** (Nivel 4)
```
✅ Crea y edita TRD
✅ Gestiona series documentales
✅ Coordina clasificación
✅ Gestiona expedientes
❌ No aprueba TRD
```
**Uso:** Coordinador de archivo, Archivista senior

---

### **6. 🔵 Operativo** (Nivel 5)
```
✅ Carga documentos
✅ Edita metadatos básicos
✅ Clasifica documentos
✅ Consulta información
❌ No edita series
```
**Uso:** Auxiliar de archivo, Técnico documental

---

### **7. ⚪ Consulta** (Nivel 6)
```
✅ Búsqueda básica
✅ Visualiza información
🟡 Exportación limitada
❌ Sin edición
❌ Sin creación
```
**Uso:** Usuario final, Personal de consulta

---

### **8. 🟣 Auditor** (Nivel 3 - Independiente)
```
✅ Acceso completo a auditoría
✅ Genera reportes de cumplimiento
✅ Exporta logs
✅ Revisa procesos
❌ Sin modificación
```
**Uso:** Auditor interno, Control de calidad

---

## 📊 MATRIZ RÁPIDA DE PERMISOS

| Funcionalidad | Super | Admin | Seg | Sup | Coord | Oper | Cons | Aud |
|---------------|:-----:|:-----:|:---:|:---:|:-----:|:----:|:----:|:---:|
| Config sistema | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Crear usuarios | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Gestionar roles | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Aprobar TRD | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Crear TRD | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Crear docs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Ver docs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Búsqueda avanz | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Ver auditoría | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Disposición | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📁 CATEGORÍAS DE PERMISOS

### **10 Categorías Principales**

```
1. 🔧 Administración
   └── Dashboard, Configuración

2. 👥 Usuarios
   └── CRUD de usuarios

3. 🔐 Seguridad
   └── Roles, Permisos, Políticas

4. 📋 Clasificación
   ├── TRD (Tabla Retención Documental)
   ├── CCD (Cuadro Clasificación)
   └── Series Documentales

5. 📄 Documentos
   └── CRUD de documentos

6. 🔍 Búsqueda
   ├── Básica
   └── Avanzada

7. 📊 Reportes
   └── Generación y exportación

8. 🕵️ Auditoría
   └── Pistas y logs

9. ⏱️ Retención
   ├── Políticas
   └── Disposición final

10. ⚙️ Configuración
    └── Sistema, Plantillas, Integraciones
```

---

## 🔑 FORMATO DE PERMISOS

### **Nomenclatura**
```
{categoria}.{subcategoria}.{accion}

Ejemplos:
├── usuarios.crear
├── trd.aprobar
├── documentos.ver
├── auditoria.exportar
└── seguridad.configurar
```

### **Niveles de Acceso**

| Nivel | Nombre | Valor | Ejemplos de Uso |
|:-----:|--------|-------|-----------------|
| 1 | 🟢 Básico | `NIVEL_BASICO` | Ver documentos, Búsqueda simple |
| 2 | 🟡 Intermedio | `NIVEL_INTERMEDIO` | Editar metadatos, Reportes |
| 3 | 🟠 Avanzado | `NIVEL_AVANZADO` | Crear TRD, Eliminar docs |
| 4 | 🔴 Administrador | `NIVEL_ADMINISTRADOR` | Aprobar TRD, Gestionar users |
| 5 | ⚫ Super Admin | `NIVEL_SUPER_ADMINISTRADOR` | Config sistema |

---

## 🔒 POLÍTICAS DE SEGURIDAD

### **Contraseñas**
```
✅ Mínimo 8 caracteres
✅ 1 mayúscula + 1 minúscula
✅ 1 número + 1 especial
✅ Historial de 5 contraseñas
⏱️ Vigencia: 90 días
```

### **Bloqueos**
```
🚫 Max 3 intentos fallidos
⏳ Bloqueo: 30 minutos
📊 Registro en auditoría
```

### **Sesiones**
```
⏰ Timeout: 30 minutos inactividad
📱 Sesiones simultáneas: Configurables
🔐 Cierre automático: Opcional
```

---

## 🏛️ JERARQUÍA VISUAL

```
        Super Admin (1)
             │
        ┌────┴────┐
        │         │
     Admin (2)  Admin Seg (2)
        │
        │         Auditor (3)
        │
   Supervisor (3)
        │
   Coordinador (4)
        │
    Operativo (5)
        │
    Consulta (6)
```

**Herencia:** Los hijos heredan permisos de padres

---

## 📈 RESUMEN DE PERMISOS POR ROL

| Rol | Total Permisos | Puede Crear | Puede Aprobar | Puede Eliminar | Acceso Auditoría |
|-----|:--------------:|:-----------:|:-------------:|:--------------:|:----------------:|
| Super Admin | **Todos** | ✅ | ✅ | ✅ | ✅ |
| Admin | ~90% | ✅ | ✅ | 🟡 | ✅ |
| Admin Seguridad | ~60% | 🟡 | ❌ | ❌ | ✅ |
| Supervisor | ~50% | 🟡 | ✅ | 🟡 | ❌ |
| Coordinador | ~40% | ✅ | ❌ | ❌ | ❌ |
| Operativo | ~30% | 🟡 | ❌ | ❌ | ❌ |
| Consulta | ~10% | ❌ | ❌ | ❌ | ❌ |
| Auditor | ~20% | ❌ | ❌ | ❌ | ✅ |

---

## 🛠️ COMANDOS RÁPIDOS

```bash
# Crear roles iniciales
php artisan db:seed --class=RoleSeeder

# Crear permisos iniciales
php artisan db:seed --class=PermisoSeeder

# Listar roles
php artisan roles:list

# Listar permisos
php artisan permisos:list

# Asignar rol a usuario
php artisan user:assign-role {user_id} {role_id}

# Ver permisos de usuario
php artisan user:permissions {user_id}
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### **Configuración Inicial**

- [ ] Ejecutar migrations de usuarios, roles y permisos
- [ ] Ejecutar seeders de roles del sistema
- [ ] Ejecutar seeders de permisos del sistema
- [ ] Crear usuario Super Administrador inicial
- [ ] Configurar políticas de contraseñas
- [ ] Configurar tiempos de sesión
- [ ] Habilitar auditoría

### **Gestión de Usuarios**

- [ ] Definir roles personalizados (si aplica)
- [ ] Asignar permisos a roles personalizados
- [ ] Crear usuarios por departamento
- [ ] Asignar roles a usuarios
- [ ] Configurar notificaciones de usuarios
- [ ] Probar flujo de registro y verificación

### **Seguridad**

- [ ] Revisar permisos asignados
- [ ] Configurar 2FA (si aplica)
- [ ] Configurar backup de datos de usuarios
- [ ] Establecer política de revisión de accesos
- [ ] Documentar procedimientos de emergencia

---

**💡 TIP:** Para más detalles, consulta `ESTRUCTURA_USUARIOS_Y_PERMISOS_SGDEA.md`

**📅 Última actualización:** 2025-11-04
