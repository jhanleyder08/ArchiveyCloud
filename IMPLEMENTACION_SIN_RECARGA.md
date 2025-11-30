# Implementación de Actualizaciones Sin Recarga de Página

## 📋 Objetivo
Hacer que todas las acciones CRUD (Crear, Leer, Actualizar, Eliminar) en el proyecto se ejecuten **sin recargar la página completa**, mejorando significativamente la experiencia del usuario.

## 🎯 Hook Personalizado: `useInertiaActions`

Se ha creado un hook reutilizable en `resources/js/hooks/useInertiaActions.ts` que proporciona métodos optimizados para todas las operaciones.

### Características:
- ✅ **No recarga la página completa**
- ✅ **Preserva el estado del componente**
- ✅ **Mantiene la posición del scroll**
- ✅ **Feedback visual automático** (toast notifications)
- ✅ **Manejo de errores**
- ✅ **Confirmaciones opcionales**
- ✅ **Recarga selectiva de datos**

## 🔧 Uso del Hook

### Importar el Hook

```typescript
import { useInertiaActions } from '@/hooks/useInertiaActions';
```

### Inicializar en el Componente

```typescript
export default function MiComponente({ data }: Props) {
    const actions = useInertiaActions({
        preserveState: true,   // Mantiene estado del componente
        preserveScroll: true,  // Mantiene posición scroll
        only: ['data', 'estadisticas'] // Solo recarga estos datos (opcional)
    });
    
    // ... resto del componente
}
```

## 📝 Ejemplos de Implementación

### 1. CREAR (POST)

```typescript
// ANTES - Recarga completa
post('/admin/ccd', formData, {
    onSuccess: () => {
        toast.success('CCD creado');
    }
});

// DESPUÉS - Sin recarga
actions.create('/admin/ccd', formData, {
    successMessage: 'CCD creado exitosamente',
    errorMessage: 'Error al crear CCD',
    only: ['ccds', 'estadisticas'], // Solo recarga estos datos
    onSuccess: () => {
        setShowModal(false);
        reset();
    }
});
```

### 2. ACTUALIZAR (PUT/PATCH)

```typescript
// ANTES
router.put(`/admin/ccd/${id}`, formData);

// DESPUÉS
actions.update(`/admin/ccd/${id}`, formData, {
    successMessage: 'CCD actualizado exitosamente',
    only: ['ccd'], // Solo recarga el CCD actual
    onSuccess: () => {
        setEditMode(false);
    }
});
```

### 3. ELIMINAR (DELETE)

```typescript
// ANTES
if (confirm('¿Eliminar?')) {
    router.delete(`/admin/ccd/${id}`);
}

// DESPUÉS
actions.destroy(`/admin/ccd/${id}`, {
    confirmMessage: '¿Está seguro de eliminar este CCD?',
    successMessage: 'CCD eliminado exitosamente',
    only: ['ccds', 'estadisticas'], // Recarga lista y stats
});
```

### 4. NAVEGACIÓN SIN RECARGA

```typescript
// ANTES
router.visit('/admin/ccd');

// DESPUÉS
actions.visit('/admin/ccd', {
    only: ['ccds'] // Solo carga los CCDs
});
```

### 5. RECARGAR DATOS SIN NAVEGAR

```typescript
// Útil después de una acción en segundo plano
actions.reload({
    only: ['estadisticas'] // Solo recarga estadísticas
});
```

## 🎨 Patrón Completo para un Módulo

```typescript
import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useInertiaActions } from '@/hooks/useInertiaActions';
import { toast } from 'sonner';

interface Props {
    items: {
        data: Item[];
        // ... paginación
    };
    estadisticas: any;
}

export default function Index({ items, estadisticas }: Props) {
    // Hook de acciones sin recarga
    const actions = useInertiaActions({
        only: ['items', 'estadisticas']
    });
    
    const [showCreateModal, setShowCreateModal] = useState(false);
    
    const { data, setData, reset } = useForm({
        nombre: '',
        descripcion: '',
    });
    
    // CREAR
    const handleCreate = () => {
        actions.create('/admin/items', data, {
            successMessage: 'Item creado exitosamente',
            onSuccess: () => {
                setShowCreateModal(false);
                reset();
            },
            onError: (errors) => {
                // Manejo personalizado de errores si es necesario
                console.error(errors);
            }
        });
    };
    
    // ACTUALIZAR
    const handleUpdate = (id: number) => {
        actions.update(`/admin/items/${id}`, data, {
            successMessage: 'Item actualizado exitosamente',
            onSuccess: () => {
                setShowEditModal(false);
            }
        });
    };
    
    // ELIMINAR
    const handleDelete = (item: Item) => {
        const confirmMsg = `¿Está seguro de eliminar "${item.nombre}"?\n\nEsta acción NO se puede deshacer.`;
        
        actions.destroy(`/admin/items/${item.id}`, {
            confirmMessage: confirmMsg,
            successMessage: 'Item eliminado exitosamente',
            errorMessage: 'Error al eliminar el item'
        });
    };
    
    return (
        <AppLayout>
            <Head title="Items" />
            
            {/* ... Tu JSX aquí ... */}
            
            <button onClick={() => handleDelete(item)}>
                Eliminar
            </button>
        </AppLayout>
    );
}
```

## 📊 Módulos a Actualizar (Prioridad)

### ✅ Fase 1 - Completado
- [x] CCD (Cuadros de Clasificación Documental)
  - [x] index.tsx
  - [x] show.tsx
  - [x] create.tsx
  - [x] edit.tsx

### 🔄 Fase 2 - En Proceso
- [ ] Documentos
  - [ ] index.tsx
  - [ ] create.tsx
  - [ ] show.tsx
  - [ ] firmar.tsx

### ⏳ Fase 3 - Pendiente
- [ ] Series Documentales
- [ ] Subseries
- [ ] Expedientes
- [ ] Disposiciones

### ⏳ Fase 4 - Pendiente
- [ ] Usuarios/Roles
- [ ] API Tokens
- [ ] Certificados
- [ ] Auditoría

### ⏳ Fase 5 - Pendiente
- [ ] Configuración
- [ ] OCR
- [ ] Email Accounts
- [ ] Dashboard Ejecutivo

## ⚙️ Opciones Avanzadas

### Recarga Selectiva

```typescript
// Solo recarga campos específicos de la respuesta del servidor
actions.create('/admin/items', data, {
    only: ['items', 'estadisticas', 'total'],
    // No recarga: filters, opciones, etc.
});
```

### Confirmaciones Personalizadas

```typescript
actions.destroy(`/admin/items/${id}`, {
    confirmMessage: `⚠️ ADVERTENCIA\n\nEstá a punto de eliminar "${item.nombre}"\n\nEsta acción NO se puede deshacer.\n\n¿Continuar?`,
    successMessage: '✅ Item eliminado correctamente'
});
```

### Callbacks Personalizados

```typescript
actions.update(`/admin/items/${id}`, data, {
    onSuccess: (page) => {
        // Acceso a la respuesta completa del servidor
        console.log('Datos actualizados:', page.props);
        
        // Lógica personalizada
        if (page.props.needsApproval) {
            toast.info('Cambios pendientes de aprobación');
        }
    },
    onError: (errors) => {
        // Manejo de errores personalizado
        Object.keys(errors).forEach(field => {
            console.error(`Error en ${field}:`, errors[field]);
        });
    }
});
```

## 🐛 Solución de Problemas

### La página se recarga completamente

**Causa:** No estás usando el hook o no pasas las opciones correctas.

**Solución:**
```typescript
// Asegúrate de usar el hook
const actions = useInertiaActions({
    preserveState: true,
    preserveScroll: true,
});

// Y usarlo en lugar de router directo
actions.delete(...) // ✅ Correcto
router.delete(...)  // ❌ Recarga completa
```

### Los datos no se actualizan

**Causa:** El controlador Laravel no está devolviendo los datos actualizados.

**Solución:** Asegúrate que el controlador retorna la vista con datos frescos:
```php
// En tu controlador
public function destroy(CCD $ccd)
{
    $ccd->delete();
    
    // Retorna a la vista con datos actualizados
    return redirect()->route('admin.ccd.index')
        ->with('success', 'CCD eliminado');
}
```

### Los mensajes toast no aparecen

**Causa:** El parámetro `successMessage` no está definido.

**Solución:**
```typescript
actions.create('/admin/items', data, {
    successMessage: 'Item creado exitosamente', // ✅ Agrega esto
});
```

## 📚 Beneficios de Esta Implementación

1. ✅ **Mejor UX**: Usuario no ve flash de recarga
2. ✅ **Más Rápido**: Solo recarga datos necesarios
3. ✅ **Menos Consumo**: Reduce tráfico de red
4. ✅ **Estado Preservado**: Mantiene filtros, scroll, estados
5. ✅ **Consistente**: Mismo patrón en todo el proyecto
6. ✅ **Mantenible**: Cambios centralizados en el hook
7. ✅ **Escalable**: Fácil agregar nuevas funcionalidades

## 🚀 Próximos Pasos

1. Revisar el hook `useInertiaActions.ts`
2. Ver ejemplos en `admin/ccd/index.tsx`
3. Aplicar patrón en tu módulo
4. Probar todas las acciones CRUD
5. Verificar que no haya recargas de página
6. Documentar cualquier caso especial

---

**Última actualización:** 29 de Noviembre, 2025
**Versión:** 1.0
**Autor:** Sistema SGDEA - ArchiveyCloud
