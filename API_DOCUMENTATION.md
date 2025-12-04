# 📡 API Documentation - ArchiveyCloud SGDEA

**Version:** 1.0  
**Base URL:** `http://localhost:8000/api`  
**Authentication:** Bearer Token (Laravel Sanctum)

---

## 🔐 Authentication

All API requests require authentication using Bearer tokens.

### Headers Required:
```
Authorization: Bearer {your-token-here}
Content-Type: application/json
Accept: application/json
```

---

## 📋 Workflows API

### 1. List All Workflows
**Endpoint:** `GET /api/workflows`

**Query Parameters:**
- `activo` (boolean) - Filter by active status
- `tipo_entidad` (string) - Filter by entity type
- `buscar` (string) - Search in name and description
- `per_page` (int) - Items per page (default: 15)

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Aprobación Simple de Documentos",
      "descripcion": "Workflow básico con un solo nivel de aprobación",
      "tipo_entidad": "App\\Models\\Documento",
      "activo": true,
      "pasos": [...],
      "created_at": "2025-11-02T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5
  }
}
```

---

### 2. Create Workflow
**Endpoint:** `POST /api/workflows`

**Request Body:**
```json
{
  "nombre": "Mi Workflow Personalizado",
  "descripcion": "Descripción del workflow",
  "tipo_entidad": "App\\Models\\Documento",
  "pasos": [
    {
      "nombre": "Revisión Inicial",
      "descripcion": "Primera revisión del documento",
      "tipo_asignacion": "usuario",
      "asignado_id": 1,
      "asignado_type": "App\\Models\\User",
      "dias_vencimiento": 3
    }
  ],
  "configuracion": {
    "requiere_observaciones": true,
    "notificar_vencimiento": true
  },
  "activo": true
}
```

**Response:** `201 Created`
```json
{
  "message": "Workflow creado exitosamente",
  "data": {
    "id": 6,
    "nombre": "Mi Workflow Personalizado",
    ...
  }
}
```

---

### 3. Get Workflow Details
**Endpoint:** `GET /api/workflows/{id}`

**Response Example:**
```json
{
  "data": {
    "id": 1,
    "nombre": "Aprobación Simple",
    "pasos": [
      {
        "nombre": "Revisión",
        "tipo_asignacion": "usuario",
        "dias_vencimiento": 3
      }
    ],
    "instancias": [
      {
        "id": 1,
        "estado": "completado",
        "created_at": "2025-11-02T10:00:00Z"
      }
    ]
  }
}
```

---

### 4. Update Workflow
**Endpoint:** `PUT /api/workflows/{id}`

**Request Body:** (all fields optional)
```json
{
  "nombre": "Nombre Actualizado",
  "descripcion": "Nueva descripción",
  "activo": false
}
```

**Response:** `200 OK`

---

### 5. Delete Workflow
**Endpoint:** `DELETE /api/workflows/{id}`

**Response:** `200 OK`
```json
{
  "message": "Workflow eliminado exitosamente"
}
```

**Error:** `409 Conflict` (if has active instances)

---

### 6. Start Workflow Instance
**Endpoint:** `POST /api/workflows/{id}/iniciar`

**Request Body:**
```json
{
  "entidad_id": 42,
  "datos": {
    "prioridad": "alta",
    "observaciones": "Requiere revisión urgente"
  }
}
```

**Response:** `201 Created`
```json
{
  "message": "Workflow iniciado exitosamente",
  "data": {
    "id": 123,
    "workflow_id": 1,
    "estado": "en_progreso",
    "paso_actual": 1,
    "tareas": [
      {
        "id": 456,
        "nombre": "Revisión Inicial",
        "estado": "pendiente",
        "fecha_vencimiento": "2025-11-05T12:00:00Z"
      }
    ]
  }
}
```

---

### 7. List Workflow Instances
**Endpoint:** `GET /api/workflows/{id}/instancias`

**Query Parameters:**
- `estado` (string) - Filter by status: pendiente, en_progreso, completado, cancelado
- `per_page` (int) - Items per page

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "workflow_id": 1,
      "estado": "completado",
      "paso_actual": 3,
      "fecha_finalizacion": "2025-11-02T15:30:00Z"
    }
  ]
}
```

---

### 8. Get Instance Details
**Endpoint:** `GET /api/workflows/instancias/{instanciaId}`

**Response Example:**
```json
{
  "data": {
    "id": 1,
    "workflow": {...},
    "estado": "en_progreso",
    "paso_actual": 2,
    "tareas": [
      {
        "id": 1,
        "nombre": "Revisión Técnica",
        "estado": "completado",
        "resultado": "aprobado"
      },
      {
        "id": 2,
        "nombre": "Revisión Legal",
        "estado": "pendiente"
      }
    ]
  }
}
```

---

### 9. Approve Task
**Endpoint:** `POST /api/workflows/tareas/{tareaId}/aprobar`

**Request Body:**
```json
{
  "observaciones": "Aprobado sin observaciones"
}
```

**Response:** `200 OK`
```json
{
  "message": "Tarea aprobada exitosamente",
  "data": {
    "id": 1,
    "estado": "completado",
    "resultado": "aprobado",
    "fecha_completado": "2025-11-02T16:45:00Z"
  }
}
```

---

### 10. Reject Task
**Endpoint:** `POST /api/workflows/tareas/{tareaId}/rechazar`

**Request Body:**
```json
{
  "motivo": "Falta información requerida"
}
```

**Response:** `200 OK`
```json
{
  "message": "Tarea rechazada",
  "data": {
    "id": 1,
    "estado": "completado",
    "resultado": "rechazado",
    "observaciones": "Falta información requerida"
  }
}
```

---

### 11. My Pending Tasks
**Endpoint:** `GET /api/workflows/mis-tareas`

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Revisión de Contrato",
      "descripcion": "Revisar contrato de servicios",
      "estado": "pendiente",
      "fecha_vencimiento": "2025-11-05T12:00:00Z",
      "instancia": {
        "id": 42,
        "workflow": {
          "nombre": "Aprobación de Contratos"
        }
      }
    }
  ]
}
```

---

### 12. Workflow Statistics
**Endpoint:** `GET /api/workflows/{id}/estadisticas`

**Response Example:**
```json
{
  "data": {
    "total_instancias": 150,
    "instancias_por_estado": {
      "completado": 100,
      "en_progreso": 30,
      "pendiente": 15,
      "cancelado": 5
    },
    "tareas_pendientes": 45,
    "tiempo_promedio_completado": 48.5
  }
}
```

---

## 📊 Dashboard API

### Executive Dashboard
**Endpoint:** `GET /api/dashboard/executive`

**Query Parameters:**
- `period` (int) - Days to analyze (7, 30, 90, 365)

**Response Example:**
```json
{
  "kpis": {
    "total_documentos": 1250,
    "tendencia_documentos": 5.2,
    "expedientes_activos": 320,
    "usuarios_activos": 45,
    "cumplimiento_normativo": 89.5
  },
  "actividad_temporal": [...],
  "distribucion_series": [...],
  "metricas_cumplimiento": {...}
}
```

---

### Export Dashboard
**Endpoint:** `POST /api/dashboard/export`

**Request Body:**
```json
{
  "formato": "pdf",
  "period": 30
}
```

**Response:** File download

---

## 🔍 Error Responses

### 400 Bad Request
```json
{
  "message": "Invalid request parameters"
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "message": "Error de validación",
  "errors": {
    "nombre": ["El campo nombre es requerido"],
    "pasos": ["Debe incluir al menos un paso"]
  }
}
```

### 500 Server Error
```json
{
  "message": "Internal server error",
  "error": "Detailed error message"
}
```

---

## 📝 Best Practices

### 1. **Pagination**
Always use pagination for list endpoints:
```
GET /api/workflows?per_page=20&page=2
```

### 2. **Filtering**
Combine multiple filters:
```
GET /api/workflows?activo=true&tipo_entidad=Documento&buscar=aprobacion
```

### 3. **Error Handling**
Always check response status codes and handle errors appropriately.

### 4. **Rate Limiting**
API has rate limiting of 60 requests per minute per user.

### 5. **Versioning**
Include API version in headers:
```
Accept: application/vnd.archivey.v1+json
```

---

## 🔄 Workflow States

### Instance States:
- `pendiente` - Not started
- `en_progreso` - In progress
- `completado` - Completed successfully
- `cancelado` - Cancelled

### Task States:
- `pendiente` - Waiting for action
- `en_progreso` - Being processed
- `completado` - Finished
- `cancelado` - Cancelled

### Task Results:
- `aprobado` - Approved
- `rechazado` - Rejected
- `null` - Not completed yet

---

## 📞 Support

For API support, contact: api@archiveycloud.com

**Documentation Version:** 1.0  
**Last Updated:** November 2, 2025
