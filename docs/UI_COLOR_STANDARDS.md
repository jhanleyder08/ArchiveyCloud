# 🎨 GUÍA OFICIAL DE COLORES Y BADGES - SGDEA ArchiveyCloud

## 📋 ESTÁNDARES DE DISEÑO Y CONSISTENCIA VISUAL

Esta documentación define los colores estándar para badges, botones y elementos de estado en todas las vistas administrativas del Sistema de Gestión Documental Electrónico de Archivo (SGDEA).

---

## 🎯 OBJETIVO

Garantizar consistencia visual y semántica en toda la aplicación mediante:
- Colores estandarizados para cada contexto
- Componentes uniformes y reutilizables  
- Experiencia de usuario profesional y coherente
- Facilidad de mantenimiento y escalabilidad

---

## 🎨 PALETA DE COLORES INSTITUCIONAL

### **Color Primario Institucional**
- **Azul SGDEA:** `#2a3d83`
- **Uso:** Botones principales, enlaces, elementos destacados

### **Colores de Estado (Pastel)**
- **Verde (Activo/Aprobado):** `bg-green-100 text-green-800`
- **Rojo (Inactivo/Error):** `bg-red-100 text-red-800`
- **Amarillo (Pendiente/Advertencia):** `bg-yellow-100 text-yellow-800`
- **Azul (Información):** `bg-blue-100 text-[#2a3d83]`
- **Gris (Neutral/Borrador):** `bg-gray-100 text-gray-800`
- **Púrpura (Especial):** `bg-purple-100 text-purple-800`

---

## 📊 DEFINICIÓN POR TIPO DE DATO

### **🟢 ESTADOS ACTIVOS/OPERATIVOS**
**Color:** `bg-green-100 text-green-800`
**Contextos:**
- Estados: Activo, Vigente, Aprobada
- Disposición Final: Conservación Permanente
- Roles: Usuario activo
- Cualquier elemento funcional y operativo

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
    Activo
</span>
```

### **🔴 ESTADOS INACTIVOS/PROBLEMÁTICOS**
**Color:** `bg-red-100 text-red-800`
**Contextos:**
- Estados: Inactivo, No Vigente, Rechazado
- Disposición Final: Eliminación
- Roles: Usuario bloqueado
- Cualquier elemento no operativo o con problemas

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
    Inactivo
</span>
```

### **🟡 ESTADOS EN PROCESO/PENDIENTES**
**Color:** `bg-yellow-100 text-yellow-800`
**Contextos:**
- Estados: En Revisión, Pendiente
- Disposición Final: Selección
- Alertas y advertencias
- Cualquier elemento en proceso de validación

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
    En Revisión
</span>
```

### **🔵 INFORMACIÓN INSTITUCIONAL**
**Color:** `bg-blue-100 text-[#2a3d83]`
**Contextos:**
- Estados: Vigente (para TRD)
- Roles: Administrativos
- Niveles: Fondo (principal)
- Información institucional relevante

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
    Vigente
</span>
```

### **⚪ ESTADOS NEUTRALES/BORRADORES**
**Color:** `bg-gray-100 text-gray-800`
**Contextos:**
- Estados: Borrador, Sin definir
- Elementos en preparación
- Información secundaria o neutral

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
    Borrador
</span>
```

### **🟣 CATEGORÍAS ESPECIALES**
**Color:** `bg-purple-100 text-purple-800`
**Contextos:**
- Estados: Histórica
- Disposición Final: Microfilmación
- Niveles jerárquicos secundarios
- Procesos especializados

**Ejemplo:**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
    Histórica
</span>
```

---

## 🔘 ESTÁNDARES PARA BOTONES DE ACCIÓN

### **Botón Principal (Editar/Ver)**
```tsx
<Button variant="ghost" size="sm" className="text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50">
    <Edit className="h-4 w-4" />
</Button>
```

### **Botón Eliminar**
```tsx
<Button variant="ghost" size="sm" className="text-red-600 hover:text-red-800 hover:bg-red-50">
    <Trash2 className="h-4 w-4" />
</Button>
```

### **Botón Toggle (Activar)**
```tsx
<Button variant="ghost" size="sm" className="text-green-600 hover:text-green-800 hover:bg-green-50">
    <ToggleRight className="h-4 w-4" />
</Button>
```

### **Botón Toggle (Desactivar)**
```tsx
<Button variant="ghost" size="sm" className="text-orange-600 hover:text-orange-800 hover:bg-orange-50">
    <ToggleLeft className="h-4 w-4" />
</Button>
```

---

## 📋 INVENTARIO DE APLICACIÓN POR MÓDULO

### **✅ MÓDULOS COMPLETAMENTE ESTANDARIZADOS**

#### **Gestión de Usuarios** 
- **Badges:** Roles (azul institucional), Estados (verde/rojo pastel)
- **Botones:** Colores institucionales estándar
- **Estado:** ✅ COMPLETO

#### **CCD (Cuadros de Clasificación Documental)**
- **Badges:** Estado (verde/rojo/amarillo/gris pastel), Nivel (azul/púrpura pastel)
- **Botones:** Colores institucionales estándar
- **Modales:** Badges de activo/inactivo estandarizados
- **Estado:** ✅ COMPLETO

#### **Series Documentales**
- **Badges:** Estado (verde/rojo pastel), Disposición Final (verde/rojo/amarillo pastel)
- **Botones:** Colores institucionales estándar
- **Estado:** ✅ COMPLETO

#### **Subseries Documentales**
- **Badges:** Estado (verde/rojo pastel), Disposición Final (verde/rojo/amarillo pastel)
- **Botones:** Colores institucionales estándar
- **Estado:** ✅ COMPLETO

#### **TRD (Tablas de Retención Documental)**
- **Badges:** Estado (colores pasteles estandarizados), Vigencia (verde/rojo pastel)
- **Botones:** Colores institucionales estándar
- **Vistas:** Index, Show, Edit - todas estandarizadas
- **Modales:** Badges de vigencia estandarizados
- **Estado:** ✅ COMPLETO

---

## 🚫 REGLAS PROHIBIDAS

### **❌ NO USAR:**
- Colores sólidos intensos (bg-green-500, bg-red-500, etc.)
- Variantes de Badge inconsistentes (variant="default" con colores custom)
- Colores hardcodeados sin contexto semántico
- Íconos o estilos inconsistentes entre módulos

### **❌ EVITAR:**
- Mezclar componentes Badge con span personalizados sin justificación
- Colores que no siguen la paleta institucional
- Botones sin hover states apropiados

---

## 📏 ESTRUCTURA TÉCNICA RECOMENDADA

### **Badge Estándar (Recomendado)**
```tsx
<span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium [COLOR_CLASSES]">
    {texto}
</span>
```

### **Badge con Ícono (Para casos especiales)**
```tsx
<Badge className="[COLOR_CLASSES] font-medium flex items-center gap-1">
    <Icon className="h-3 w-3" />
    {texto}
</Badge>
```

---

## 🔧 IMPLEMENTACIÓN Y MANTENIMIENTO

### **Para Nuevos Módulos:**
1. Consultar esta guía antes de implementar badges
2. Usar los colores definidos según el contexto semántico
3. Seguir la estructura técnica recomendada
4. Mantener consistencia con módulos existentes

### **Para Modificaciones:**
1. Verificar que los cambios sigan esta guía
2. Probar en múltiples módulos para consistencia
3. Actualizar esta documentación si es necesario

### **Testing de Consistencia:**
1. Revisar visualmente todos los módulos
2. Verificar que colores similares tengan significados similares
3. Confirmar que botones de acción sigan patrones estándar

---

## 📅 HISTORIAL DE CAMBIOS

- **2024-01-XX:** Creación del documento y estandarización inicial
- **2024-01-XX:** Aplicación completa en todos los módulos principales
- **2024-01-XX:** Corrección de inconsistencias en modales y vistas secundarias

---

## 🎯 RESULTADO ESPERADO

**✅ CONSISTENCIA COMPLETA:**
- Todos los badges usan colores pasteles institucionales
- Todos los botones siguen el estándar de colores y hover states
- Experiencia de usuario unificada y profesional
- Mantenimiento simplificado y escalable

**📊 MÉTRICAS DE ÉXITO:**
- 100% de los módulos siguen la guía de colores
- 0 inconsistencias visuales entre vistas
- Tiempo de desarrollo reducido para nuevas funcionalidades
- Feedback positivo de usuarios sobre coherencia visual

---

*Documento actualizado: 2024-01-XX*  
*Versión: 1.0*  
*Responsable: Equipo de Desarrollo SGDEA*
