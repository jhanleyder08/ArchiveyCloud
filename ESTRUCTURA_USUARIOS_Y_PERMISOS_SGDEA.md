# 📊 ESTRUCTURA DE USUARIOS Y PERMISOS - SGDEA

## 📋 TABLA DE CONTENIDOS

1. [CRUD de Usuarios](#crud-de-usuarios)
2. [Tipos de Usuarios (Roles)](#tipos-de-usuarios-roles)
3. [Jerarquía de Roles](#jerarquía-de-roles)
4. [Matriz de Permisos por Rol](#matriz-de-permisos-por-rol)
5. [Categorías de Permisos](#categorías-de-permisos)
6. [Permisos Detallados](#permisos-detallados)

---

## 🔐 CRUD DE USUARIOS

### **Estructura del Modelo User**

#### **Campos Principales:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | Integer | Identificador único |
| `name` | String | Nombre completo |
| `email` | String (único) | Correo electrónico |
| `password` | String (hash) | Contraseña encriptada |
| `role_id` | Integer | Rol principal del usuario |
| `active` | Boolean | Usuario activo/inactivo |
| `email_verified_at` | Timestamp | Fecha de verificación de email |

#### **Campos de Identificación:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `documento_identidad` | String | Número de documento |
| `tipo_documento` | Enum | CC, CE, Pasaporte, TI |
| `telefono` | String | Teléfono de contacto |
| `cargo` | String | Cargo en la organización |
| `dependencia` | String | Área o departamento |

#### **Campos de Control:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fecha_ingreso` | Date | Fecha de ingreso al sistema |
| `fecha_vencimiento_cuenta` | Date | Fecha de vencimiento de cuenta |
| `ultimo_acceso` | Timestamp | Último inicio de sesión |
| `intentos_fallidos` | Integer | Intentos de login fallidos |
| `bloqueado_hasta` | Timestamp | Fecha hasta la que está bloqueado |
| `estado_cuenta` | Enum | activo, inactivo, bloqueado, suspendido, vencido |

#### **Campos de Seguridad:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cambio_password_requerido` | Boolean | Requiere cambio de contraseña |
| `fecha_ultimo_cambio_password` | Timestamp | Última vez que cambió password |
| `historial_passwords` | JSON | Últimas 5 contraseñas (hash) |
| `configuracion_notificaciones` | JSON | Preferencias de notificaciones |
| `preferencias_usuario` | JSON | Configuración personalizada |

### **Operaciones CRUD**

#### **CREATE - Crear Usuario**

**Endpoint:** `POST /api/users`

**Permisos requeridos:** `usuarios.crear` (Nivel: Administrador)

**Campos obligatorios:**
- name
- email
- password
- role_id
- tipo_documento
- documento_identidad

**Validaciones:**
- Email único en el sistema
- Password mínimo 8 caracteres
- Role_id debe existir y estar activo
- Documento único por tipo

**Proceso:**
1. Validar datos de entrada
2. Encriptar contraseña
3. Asignar rol por defecto si no se especifica
4. Enviar email de verificación
5. Registrar en pista de auditoría
6. Retornar usuario creado

#### **READ - Leer Usuarios**

**Endpoints:**
- `GET /api/users` - Listar todos (paginado)
- `GET /api/users/{id}` - Ver uno específico

**Permisos requeridos:** `usuarios.ver` (Nivel: Intermedio)

**Filtros disponibles:**
- Por rol
- Por estado de cuenta
- Por dependencia
- Por rango de fechas

**Respuesta incluye:**
- Datos del usuario
- Rol principal y roles adicionales
- Permisos directos
- Estado de verificación de email
- Última actividad

#### **UPDATE - Actualizar Usuario**

**Endpoint:** `PUT /api/users/{id}`

**Permisos requeridos:** `usuarios.editar` (Nivel: Administrador)

**Campos editables:**
- Datos personales (name, email, teléfono, cargo, dependencia)
- Rol principal
- Estado de cuenta (activo/inactivo)
- Configuración de notificaciones
- Preferencias de usuario

**Campos NO editables directamente:**
- Password (usar endpoint específico)
- Intentos fallidos
- Bloqueado hasta
- Historial de passwords

**Validaciones:**
- Solo administradores pueden cambiar roles
- Email debe ser único si se cambia
- No puede desactivar su propia cuenta

#### **DELETE - Eliminar Usuario**

**Endpoint:** `DELETE /api/users/{id}`

**Permisos requeridos:** `usuarios.eliminar` (Nivel: Super Administrador)

**Tipo:** Soft Delete (eliminación lógica)

**Restricciones:**
- No puede eliminarse a sí mismo
- No puede eliminar usuarios con rol Super Administrador (excepto otro Super Admin)
- Se valida que no tenga documentos asignados pendientes

**Proceso:**
1. Validar permisos
2. Verificar restricciones
3. Marcar como deleted_at
4. Desactivar sesiones activas
5. Registrar en auditoría
6. Notificar al usuario

---

## 👥 TIPOS DE USUARIOS (ROLES)

### **Roles del Sistema (8 roles predefinidos)**

#### **1. Super Administrador** 🔴

- **Nivel Jerárquico:** 1 (Máxima autoridad)
- **Descripción:** Control total del sistema, puede realizar cualquier acción
- **Características:**
  - Acceso completo a todas las funcionalidades
  - Puede gestionar otros Super Administradores
  - Configura políticas del sistema
  - Gestiona configuración de seguridad
  - No puede ser eliminado del sistema mientras sea el único

**Casos de uso:**
- Director de TI
- Administrador del sistema
- Gerente general

---

#### **2. Administrador** 🟠

- **Nivel Jerárquico:** 2
- **Descripción:** Administración general del sistema sin acceso a configuración crítica
- **Características:**
  - Gestiona usuarios y roles (excepto Super Admin)
  - Aprueba TRD y CCD
  - Genera reportes avanzados
  - Supervisa auditoría
  - Gestiona dependencias

**Casos de uso:**
- Jefe de archivo
- Coordinador administrativo
- Responsable de gestión documental

---

#### **3. Administrador de Seguridad** 🟠

- **Nivel Jerárquico:** 2
- **Descripción:** Especializado en gestión de seguridad y control de acceso
- **Características:**
  - Gestiona permisos y roles
  - Configura políticas de seguridad
  - Audita accesos
  - Gestiona certificados digitales
  - Monitorea sesiones activas

**Casos de uso:**
- Oficial de seguridad informática
- Responsable de cumplimiento
- Auditor de sistemas

---

#### **4. Supervisor** 🟡

- **Nivel Jerárquico:** 3
- **Padre:** Administrador
- **Descripción:** Supervisión de procesos documentales
- **Características:**
  - Aprueba series documentales
  - Supervisa clasificación
  - Genera reportes operativos
  - Asigna tareas a coordinadores
  - Revisa disposición documental

**Casos de uso:**
- Supervisor de archivo
- Jefe de sección documental
- Coordinador de gestión

---

#### **5. Coordinador** 🟢

- **Nivel Jerárquico:** 4
- **Padre:** Supervisor
- **Descripción:** Coordinación de actividades documentales
- **Características:**
  - Crea y edita TRD
  - Gestiona series documentales
  - Coordina clasificación
  - Asigna documentos
  - Gestiona expedientes

**Casos de uso:**
- Coordinador de archivo
- Profesional de gestión documental
- Archivista senior

---

#### **6. Operativo** 🔵

- **Nivel Jerárquico:** 5
- **Padre:** Coordinador
- **Descripción:** Operaciones básicas del sistema
- **Características:**
  - Carga documentos
  - Edita metadatos básicos
  - Clasifica documentos
  - Consulta información
  - Genera reportes simples

**Casos de uso:**
- Auxiliar de archivo
- Técnico documental
- Asistente administrativo

---

#### **7. Consulta** ⚪

- **Nivel Jerárquico:** 6
- **Padre:** Operativo
- **Descripción:** Solo consulta de información
- **Características:**
  - Búsqueda básica de documentos
  - Visualización de información
  - Exportación limitada
  - Sin permisos de edición
  - Consulta de reportes públicos

**Casos de uso:**
- Usuario final
- Personal de consulta
- Visitante autorizado

---

#### **8. Auditor** 🟣

- **Nivel Jerárquico:** 3
- **Padre:** Ninguno (independiente)
- **Descripción:** Auditoría y revisión del sistema
- **Características:**
  - Acceso completo a pistas de auditoría
  - Genera reportes de cumplimiento
  - Revisa logs del sistema
  - Exporta información de auditoría
  - Sin permisos de modificación

**Casos de uso:**
- Auditor interno
- Control de calidad
- Revisor de procesos

---

## 🏛️ JERARQUÍA DE ROLES

```
┌─────────────────────────────────────┐
│   Super Administrador (Nivel 1)    │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       │                │
┌──────▼──────┐  ┌─────▼──────────────┐
│Administrador│  │Admin. de Seguridad │
│  (Nivel 2)  │  │     (Nivel 2)      │
└──────┬──────┘  └────────────────────┘
       │
       │         ┌────────────┐
       │         │  Auditor   │
       └────────►│ (Nivel 3)  │
                 └────────────┘
       │
┌──────▼──────┐
│ Supervisor  │
│  (Nivel 3)  │
└──────┬──────┘
       │
┌──────▼──────┐
│Coordinador  │
│  (Nivel 4)  │
└──────┬──────┘
       │
┌──────▼──────┐
│  Operativo  │
│  (Nivel 5)  │
└──────┬──────┘
       │
┌──────▼──────┐
│  Consulta   │
│  (Nivel 6)  │
└─────────────┘
```

**Herencia de Permisos:**
- Los roles hijo heredan permisos de sus padres
- Un rol puede tener permisos adicionales específicos
- La herencia es recursiva hacia arriba

---

## 📊 MATRIZ DE PERMISOS POR ROL

### **Leyenda:**
- ✅ = Permiso completo
- 🟡 = Permiso limitado
- ❌ = Sin permiso

| Permiso | Super Admin | Admin | Admin Seg. | Supervisor | Coordinador | Operativo | Consulta | Auditor |
|---------|:-----------:|:-----:|:----------:|:----------:|:-----------:|:---------:|:--------:|:-------:|
| **ADMINISTRACIÓN** |
| Dashboard admin | ✅ | ✅ | ✅ | ✅ | 🟡 | ❌ | ❌ | ✅ |
| Configuración sistema | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **USUARIOS** |
| Crear usuarios | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ver usuarios | ✅ | ✅ | ✅ | ✅ | 🟡 | ❌ | ❌ | ✅ |
| Editar usuarios | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Eliminar usuarios | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **ROLES Y PERMISOS** |
| Gestionar roles | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Asignar permisos | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **TRD** |
| Crear TRD | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ver TRD | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar TRD | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Aprobar TRD | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Exportar TRD | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 🟡 | ✅ |
| **CCD** |
| Crear CCD | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ver CCD | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar CCD | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **SERIES DOCUMENTALES** |
| Crear series | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ver series | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar series | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **DOCUMENTOS** |
| Crear documentos | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Ver documentos | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar documentos | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Eliminar documentos | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **BÚSQUEDA** |
| Búsqueda básica | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Búsqueda avanzada | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **REPORTES** |
| Generar reportes | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 🟡 | ✅ |
| Exportar reportes | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **AUDITORÍA** |
| Ver auditoría | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Exportar auditoría | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **RETENCIÓN** |
| Gestionar retención | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ejecutar disposición | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **SEGURIDAD** |
| Configurar seguridad | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📁 CATEGORÍAS DE PERMISOS

### **1. Administración** (`administracion`)

**Permisos incluidos:**
- `administracion.dashboard.ver` - Ver dashboard administrativo
- `administracion.configuracion.gestionar` - Gestionar configuración del sistema

**Nivel requerido:** Administrador (4) / Super Administrador (5)

---

### **2. Usuarios** (`usuarios`)

**Permisos incluidos:**
- `usuarios.crear` - Crear nuevos usuarios
- `usuarios.ver` - Ver información de usuarios
- `usuarios.editar` - Editar información de usuarios
- `usuarios.eliminar` - Eliminar usuarios

**Nivel requerido:** Intermedio (2) a Super Administrador (5)

---

### **3. Seguridad** (`seguridad`)

**Permisos incluidos:**
- `roles.gestionar` - Gestionar roles del sistema
- `seguridad.configurar` - Configurar políticas de seguridad

**Nivel requerido:** Administrador (4) / Super Administrador (5)

---

### **4. Clasificación** (`clasificacion`)

**Subcategorías:**
- **TRD** (Tabla de Retención Documental)
- **CCD** (Cuadro de Clasificación Documental)
- **Series** (Series Documentales)

**Permisos incluidos:**
- `trd.crear` / `trd.ver` / `trd.editar` / `trd.aprobar` / `trd.exportar`
- `ccd.crear` / `ccd.ver` / `ccd.editar`
- `series.crear` / `series.ver` / `series.editar`

**Nivel requerido:** Básico (1) para ver, Avanzado (3) para crear/editar

---

### **5. Documentos** (`documentos`)

**Permisos incluidos:**
- `documentos.crear` - Crear y cargar documentos
- `documentos.ver` - Ver documentos
- `documentos.editar` - Editar metadatos de documentos
- `documentos.eliminar` - Eliminar documentos

**Nivel requerido:** Básico (1) a Avanzado (3)

---

### **6. Búsqueda** (`busqueda`)

**Subcategorías:**
- **Básica** - Búsqueda simple
- **Avanzada** - Búsqueda con filtros múltiples

**Permisos incluidos:**
- `busqueda.basica` - Realizar búsquedas básicas
- `busqueda.avanzada` - Realizar búsquedas avanzadas

**Nivel requerido:** Básico (1) a Intermedio (2)

---

### **7. Reportes** (`reportes`)

**Permisos incluidos:**
- `reportes.generar` - Generar reportes del sistema
- `reportes.exportar` - Exportar reportes

**Nivel requerido:** Intermedio (2)

---

### **8. Auditoría** (`auditoria`)

**Permisos incluidos:**
- `auditoria.ver` - Ver pistas de auditoría
- `auditoria.exportar` - Exportar pistas de auditoría

**Nivel requerido:** Administrador (4)

---

### **9. Retención** (`retencion`)

**Subcategorías:**
- **Políticas** - Gestión de retención
- **Disposición** - Disposición final

**Permisos incluidos:**
- `retencion.gestionar` - Gestionar políticas de retención
- `disposicion.ejecutar` - Ejecutar disposiciones finales

**Nivel requerido:** Avanzado (3) a Administrador (4)

---

### **10. Configuración** (`configuracion`)

**Permisos incluidos:**
- `configuracion.sistema` - Configurar parámetros del sistema
- `configuracion.plantillas` - Gestionar plantillas
- `configuracion.integraciones` - Configurar integraciones

**Nivel requerido:** Super Administrador (5)

---

## 🔑 PERMISOS DETALLADOS

### **Formato de Nombres de Permisos**

Los permisos siguen el patrón:
```
{categoria}.{subcategoria}.{recurso}.{accion}
```

**Ejemplo:**
```
clasificacion.trd.aprobar
  │          │    │
  │          │    └─ Acción
  │          └────── Subcategoría
  └───────────────── Categoría
```

### **Niveles de Permisos**

| Nivel | Nombre | Valor | Descripción |
|-------|--------|-------|-------------|
| 1 | Básico | `NIVEL_BASICO` | Operaciones de consulta básica |
| 2 | Intermedio | `NIVEL_INTERMEDIO` | Operaciones de edición limitada |
| 3 | Avanzado | `NIVEL_AVANZADO` | Operaciones complejas y especializadas |
| 4 | Administrador | `NIVEL_ADMINISTRADOR` | Gestión y aprobaciones |
| 5 | Super Administrador | `NIVEL_SUPER_ADMINISTRADOR` | Control total |

### **Acciones Estándar**

| Acción | Constante | Descripción |
|--------|-----------|-------------|
| Crear | `ACCION_CREAR` | Crear nuevos registros |
| Leer | `ACCION_LEER` | Consultar información |
| Actualizar | `ACCION_ACTUALIZAR` | Modificar registros existentes |
| Eliminar | `ACCION_ELIMINAR` | Eliminar registros |
| Exportar | `ACCION_EXPORTAR` | Exportar datos |
| Importar | `ACCION_IMPORTAR` | Importar datos |
| Aprobar | `ACCION_APROBAR` | Aprobar solicitudes/registros |
| Rechazar | `ACCION_RECHAZAR` | Rechazar solicitudes/registros |
| Archivar | `ACCION_ARCHIVAR` | Archivar documentos |

---

## 🔐 CONFIGURACIONES DE SEGURIDAD

### **Políticas de Contraseña**

```php
const MAX_INTENTOS_FALLIDOS = 3;
const TIEMPO_BLOQUEO_MINUTOS = 30;
const DIAS_VIGENCIA_PASSWORD = 90;
const HISTORIAL_PASSWORDS = 5;
```

**Requisitos:**
- Mínimo 8 caracteres
- Al menos 1 mayúscula
- Al menos 1 minúscula
- Al menos 1 número
- Al menos 1 carácter especial
- No puede repetir últimas 5 contraseñas

### **Control de Sesiones**

- **Sesiones simultáneas:** Configurables por usuario
- **Timeout de inactividad:** 30 minutos
- **Cierre automático:** Configurable
- **Registro de sesiones:** Sí, en tabla `sesion_usuario`

### **Auditoría**

**Se audita:**
- Creación, edición y eliminación de usuarios
- Cambios de rol
- Asignación de permisos
- Intentos de acceso fallidos
- Modificación de datos sensibles
- Exportación de información

---

## 📈 ESTADÍSTICAS Y REPORTES

### **Reportes Disponibles**

1. **Usuarios por Rol**
   - Total de usuarios por cada rol
   - Usuarios activos vs inactivos
   - Gráfico de distribución

2. **Actividad de Usuarios**
   - Último acceso
   - Usuarios inactivos > 30 días
   - Frecuencia de uso

3. **Permisos y Accesos**
   - Permisos más utilizados
   - Usuarios con accesos especiales
   - Auditoría de cambios de permisos

4. **Seguridad**
   - Intentos de acceso fallidos
   - Usuarios bloqueados
   - Contraseñas próximas a vencer

---

## 🛠️ CONFIGURACIÓN INICIAL

### **Seeders Disponibles**

Para poblar la base de datos con roles y permisos iniciales:

```bash
# Crear roles del sistema
php artisan db:seed --class=RoleSeeder

# Crear permisos del sistema
php artisan db:seed --class=PermisoSeeder

# Crear usuario Super Administrador inicial
php artisan db:seed --class=AdminSeeder
```

### **Comandos Útiles**

```bash
# Listar todos los roles
php artisan roles:list

# Listar todos los permisos
php artisan permisos:list

# Asignar rol a usuario
php artisan user:assign-role {user_id} {role_id}

# Ver permisos de un usuario
php artisan user:permissions {user_id}

# Exportar configuración de roles
php artisan roles:export
```

---

## 📝 NOTAS IMPORTANTES

### **Buenas Prácticas**

1. ✅ **Nunca** asignar Super Administrador a usuarios de producción innecesariamente
2. ✅ **Usar** roles específicos para cada función
3. ✅ **Aplicar** el principio de mínimo privilegio
4. ✅ **Revisar** permisos periódicamente
5. ✅ **Auditar** cambios en roles y permisos

### **Restricciones del Sistema**

1. 🚫 No se pueden eliminar roles del sistema
2. 🚫 No se pueden modificar permisos del sistema
3. 🚫 Un usuario no puede eliminar su propia cuenta
4. 🚫 Un usuario no puede cambiar su propio rol
5. 🚫 Roles con usuarios asignados no pueden eliminarse

### **Mantenimiento**

- **Revisar usuarios inactivos:** Mensual
- **Auditar permisos:** Trimestral
- **Actualizar políticas:** Anual
- **Capacitación de usuarios:** Semestral

---

## 📞 SOPORTE Y CONTACTO

Para consultas sobre usuarios y permisos:
- **Documentación técnica:** `/docs/api/usuarios`
- **Logs de auditoría:** `storage/logs/auditoria/`
- **Configuración:** `config/auth.php` y `config/permissions.php`

---

**Última actualización:** 2025-11-04
**Versión del documento:** 1.0
**Autor:** Sistema SGDEA - Archivey Cloud
