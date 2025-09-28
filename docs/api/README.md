# API de ArchiveyCloud - Documentación Completa

## 📋 Índice
1. [Introducción](#introducción)
2. [Autenticación](#autenticación) 
3. [Endpoints Disponibles](#endpoints-disponibles)
4. [Ejemplos de Uso](#ejemplos-de-uso)
5. [Códigos de Error](#códigos-de-error)
6. [Límites y Restricciones](#límites-y-restricciones)
7. [SDKs y Librerías](#sdks-y-librerías)

---

## 🚀 Introducción

La API de ArchiveyCloud permite la integración con sistemas externos para gestionar documentos, expedientes y usuarios de forma programática. La API utiliza REST sobre HTTP/HTTPS con autenticación basada en tokens.

### Características Principales:
- ✅ **Autenticación segura** con tokens API
- ✅ **Permisos granulares** por recurso
- ✅ **Rate limiting** configurable
- ✅ **Restricciones por IP**
- ✅ **Auditoría completa** de requests
- ✅ **Respuestas JSON** estructuradas

### URL Base:
```
https://tu-servidor.com/api/v1/
```

---

## 🔐 Autenticación

### Obtener un Token API

Los tokens API se gestionan desde el panel de administración:

1. **Acceder**: `/admin/api-tokens`
2. **Crear token**: Clic en "Crear Token API" 
3. **Configurar permisos**: Seleccionar los permisos necesarios
4. **Obtener token**: Copiar el token generado (solo se muestra una vez)

### Métodos de Autenticación

#### 1. Bearer Token (Recomendado)
```http
Authorization: Bearer at_1234567890abcdef1234567890abcdef
```

#### 2. Header Personalizado
```http
X-API-Token: at_1234567890abcdef1234567890abcdef
```

#### 3. Query Parameter (No recomendado)
```http
GET /api/v1/info?api_token=at_1234567890abcdef1234567890abcdef
```

### Ejemplo de Request
```bash
curl -H "Authorization: Bearer at_tu_token_aqui" \
     -H "Accept: application/json" \
     https://tu-servidor.com/api/v1/info
```

---

## 📡 Endpoints Disponibles

### 🔍 Información General

#### GET /api/v1/info
Obtiene información básica del sistema y permisos del token.

**Permisos requeridos**: Ninguno (público)

**Respuesta exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "sistema": "ArchiveyCloud",
    "version": "1.0.0",
    "token_info": {
      "nombre": "Sistema CRM",
      "permisos": ["documentos:read", "expedientes:read"],
      "expira": "2024-12-31T23:59:59Z",
      "usos_restantes": 9850
    }
  }
}
```

### 📄 Gestión de Documentos

#### GET /api/v1/documentos
Lista documentos con paginación y filtros.

**Permisos requeridos**: `documentos:read` o `admin`

**Parámetros de consulta**:
- `page` (int): Página (default: 1)
- `per_page` (int): Items por página (max: 100, default: 15)
- `search` (string): Buscar por nombre o contenido
- `tipo` (string): Filtrar por tipo de documento
- `expediente_id` (int): Filtrar por expediente

**Ejemplo de request**:
```bash
curl -H "Authorization: Bearer at_tu_token" \
     "https://tu-servidor.com/api/v1/documentos?page=1&per_page=20&search=contrato"
```

**Respuesta exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "nombre": "Contrato de Servicios.pdf",
        "descripcion": "Contrato de prestación de servicios",
        "tipo_documento": "Contrato",
        "tamaño": 2048576,
        "hash_integridad": "sha256:abc123...",
        "expediente_id": 5,
        "usuario_id": 2,
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
      }
    ],
    "first_page_url": "https://tu-servidor.com/api/v1/documentos?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "https://tu-servidor.com/api/v1/documentos?page=5",
    "next_page_url": "https://tu-servidor.com/api/v1/documentos?page=2",
    "path": "https://tu-servidor.com/api/v1/documentos",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 67
  }
}
```

#### POST /api/v1/documentos
Crea un nuevo documento.

**Permisos requeridos**: `documentos:write` o `admin`

**Parámetros del body (multipart/form-data)**:
- `nombre` (string, requerido): Nombre del documento
- `descripcion` (string, opcional): Descripción del documento
- `tipo_documento` (string, opcional): Tipo de documento
- `expediente_id` (int, requerido): ID del expediente padre
- `archivo` (file, requerido): Archivo a subir (max 50MB)
- `palabras_clave` (array, opcional): Tags del documento

**Ejemplo de request**:
```bash
curl -H "Authorization: Bearer at_tu_token" \
     -F "nombre=Nuevo Contrato" \
     -F "descripcion=Contrato de servicios 2024" \
     -F "expediente_id=5" \
     -F "archivo=@/ruta/al/archivo.pdf" \
     https://tu-servidor.com/api/v1/documentos
```

**Respuesta exitosa (201)**:
```json
{
  "success": true,
  "message": "Documento creado exitosamente",
  "data": {
    "id": 15,
    "nombre": "Nuevo Contrato.pdf",
    "descripcion": "Contrato de servicios 2024",
    "tipo_documento": "Contrato",
    "tamaño": 1024000,
    "hash_integridad": "sha256:def456...",
    "expediente_id": 5,
    "usuario_id": 2,
    "created_at": "2024-01-15T14:30:00Z",
    "updated_at": "2024-01-15T14:30:00Z"
  }
}
```

#### GET /api/v1/documentos/{id}
Obtiene un documento específico.

**Permisos requeridos**: `documentos:read` o `admin`

#### PUT /api/v1/documentos/{id}
Actualiza un documento existente.

**Permisos requeridos**: `documentos:write` o `admin`

#### DELETE /api/v1/documentos/{id}
Elimina un documento.

**Permisos requeridos**: `documentos:delete` o `admin`

### 📁 Gestión de Expedientes

#### GET /api/v1/expedientes
Lista expedientes con paginación y filtros.

**Permisos requeridos**: `expedientes:read` o `admin`

**Parámetros de consulta**:
- `page` (int): Página (default: 1)
- `per_page` (int): Items por página (max: 100, default: 15)
- `search` (string): Buscar por código o asunto
- `estado` (string): Filtrar por estado (abierto, tramite, revision, cerrado, archivado)
- `serie_id` (int): Filtrar por serie documental

**Respuesta exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "codigo": "EXP-2024-001",
        "asunto": "Solicitud de Servicios Técnicos",
        "descripcion": "Expediente para gestión de servicios",
        "estado": "abierto",
        "serie_id": 3,
        "subserie_id": 8,
        "usuario_responsable_id": 2,
        "fecha_apertura": "2024-01-15",
        "fecha_cierre": null,
        "documentos_count": 12,
        "created_at": "2024-01-15T09:00:00Z",
        "updated_at": "2024-01-15T16:45:00Z"
      }
    ],
    "total": 25
  }
}
```

#### POST /api/v1/expedientes
Crea un nuevo expediente.

**Permisos requeridos**: `expedientes:write` o `admin`

#### GET /api/v1/expedientes/{id}
Obtiene un expediente específico con sus documentos.

#### PUT /api/v1/expedientes/{id}
Actualiza un expediente existente.

#### DELETE /api/v1/expedientes/{id}
Elimina un expediente.

**Permisos requeridos**: `expedientes:delete` o `admin`

---

## 🛠️ Ejemplos de Uso

### Ejemplo Completo: Crear Expediente con Documentos

```bash
#!/bin/bash

API_TOKEN="at_tu_token_aqui"
BASE_URL="https://tu-servidor.com/api/v1"

# 1. Crear expediente
EXPEDIENTE=$(curl -s -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "asunto": "Proyecto de Modernización",
    "descripcion": "Expediente para el proyecto de modernización tecnológica",
    "serie_id": 3,
    "subserie_id": 8
  }' \
  "$BASE_URL/expedientes")

EXPEDIENTE_ID=$(echo $EXPEDIENTE | jq -r '.data.id')
echo "Expediente creado con ID: $EXPEDIENTE_ID"

# 2. Subir documentos al expediente
curl -H "Authorization: Bearer $API_TOKEN" \
  -F "nombre=Documento Técnico" \
  -F "descripcion=Especificaciones técnicas del proyecto" \
  -F "expediente_id=$EXPEDIENTE_ID" \
  -F "archivo=@especificaciones.pdf" \
  "$BASE_URL/documentos"

# 3. Listar documentos del expediente
curl -H "Authorization: Bearer $API_TOKEN" \
  "$BASE_URL/documentos?expediente_id=$EXPEDIENTE_ID"
```

### Ejemplo JavaScript/Node.js

```javascript
const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');

class ArchiveyCloudAPI {
  constructor(apiToken, baseUrl) {
    this.apiToken = apiToken;
    this.baseUrl = baseUrl;
    this.client = axios.create({
      baseURL: baseUrl,
      headers: {
        'Authorization': `Bearer ${apiToken}`,
        'Accept': 'application/json'
      }
    });
  }

  // Obtener información del sistema
  async getInfo() {
    try {
      const response = await this.client.get('/info');
      return response.data;
    } catch (error) {
      throw new Error(`Error: ${error.response.data.message}`);
    }
  }

  // Listar documentos
  async listDocuments(page = 1, perPage = 15, filters = {}) {
    const params = { page, per_page: perPage, ...filters };
    const response = await this.client.get('/documentos', { params });
    return response.data;
  }

  // Crear documento
  async createDocument(expedienteId, file, metadata = {}) {
    const formData = new FormData();
    formData.append('expediente_id', expedienteId);
    formData.append('archivo', fs.createReadStream(file.path));
    
    if (metadata.nombre) formData.append('nombre', metadata.nombre);
    if (metadata.descripcion) formData.append('descripcion', metadata.descripcion);
    if (metadata.tipo_documento) formData.append('tipo_documento', metadata.tipo_documento);

    const response = await this.client.post('/documentos', formData, {
      headers: formData.getHeaders()
    });
    return response.data;
  }

  // Crear expediente
  async createExpediente(data) {
    const response = await this.client.post('/expedientes', data);
    return response.data;
  }
}

// Uso
const api = new ArchiveyCloudAPI('at_tu_token_aqui', 'https://tu-servidor.com/api/v1');

async function example() {
  try {
    // Crear expediente
    const expediente = await api.createExpediente({
      asunto: 'Proyecto API',
      descripcion: 'Expediente para integración vía API',
      serie_id: 3
    });
    
    console.log('Expediente creado:', expediente.data.id);
    
    // Subir documento
    const documento = await api.createDocument(expediente.data.id, {
      path: './documento.pdf'
    }, {
      nombre: 'Documento API',
      descripcion: 'Documento subido vía API'
    });
    
    console.log('Documento creado:', documento.data.id);
    
  } catch (error) {
    console.error('Error:', error.message);
  }
}

example();
```

### Ejemplo Python

```python
import requests
import json

class ArchiveyCloudAPI:
    def __init__(self, api_token, base_url):
        self.api_token = api_token
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {api_token}',
            'Accept': 'application/json'
        })
    
    def get_info(self):
        """Obtener información del sistema"""
        response = self.session.get(f'{self.base_url}/info')
        response.raise_for_status()
        return response.json()
    
    def list_documents(self, page=1, per_page=15, **filters):
        """Listar documentos con filtros"""
        params = {'page': page, 'per_page': per_page, **filters}
        response = self.session.get(f'{self.base_url}/documentos', params=params)
        response.raise_for_status()
        return response.json()
    
    def create_document(self, expediente_id, file_path, **metadata):
        """Crear documento"""
        with open(file_path, 'rb') as f:
            files = {'archivo': f}
            data = {'expediente_id': expediente_id, **metadata}
            response = self.session.post(f'{self.base_url}/documentos', files=files, data=data)
            response.raise_for_status()
            return response.json()
    
    def create_expediente(self, **data):
        """Crear expediente"""
        response = self.session.post(f'{self.base_url}/expedientes', json=data)
        response.raise_for_status()
        return response.json()

# Uso
api = ArchiveyCloudAPI('at_tu_token_aqui', 'https://tu-servidor.com/api/v1')

try:
    # Crear expediente
    expediente = api.create_expediente(
        asunto='Proyecto Python',
        descripcion='Expediente creado desde Python',
        serie_id=3
    )
    print(f"Expediente creado: {expediente['data']['id']}")
    
    # Subir documento
    documento = api.create_document(
        expediente['data']['id'],
        './documento.pdf',
        nombre='Documento Python',
        descripcion='Documento subido desde Python'
    )
    print(f"Documento creado: {documento['data']['id']}")
    
except requests.exceptions.RequestException as e:
    print(f"Error: {e}")
```

---

## ⚠️ Códigos de Error

### Errores de Autenticación

| Código | Error | Descripción |
|--------|-------|-------------|
| 401 | `TOKEN_MISSING` | Token API no proporcionado |
| 401 | `TOKEN_INVALID` | Token API inválido o malformado |
| 401 | `TOKEN_EXPIRED` | Token API ha expirado |
| 401 | `TOKEN_INACTIVE` | Token API desactivado |
| 401 | `TOKEN_LIMIT_EXCEEDED` | Límite de usos alcanzado |
| 403 | `IP_RESTRICTED` | IP no autorizada para este token |

### Errores de Autorización

| Código | Error | Descripción |
|--------|-------|-------------|
| 403 | `INSUFFICIENT_PERMISSIONS` | Permisos insuficientes |
| 403 | `RESOURCE_ACCESS_DENIED` | Acceso al recurso denegado |

### Errores de Validación

| Código | Error | Descripción |
|--------|-------|-------------|
| 422 | `VALIDATION_FAILED` | Datos de entrada inválidos |
| 413 | `FILE_TOO_LARGE` | Archivo excede tamaño máximo |
| 415 | `UNSUPPORTED_MEDIA_TYPE` | Tipo de archivo no soportado |

### Errores del Sistema

| Código | Error | Descripción |
|--------|-------|-------------|
| 404 | `RESOURCE_NOT_FOUND` | Recurso no encontrado |
| 429 | `RATE_LIMIT_EXCEEDED` | Límite de requests excedido |
| 500 | `INTERNAL_SERVER_ERROR` | Error interno del servidor |

### Formato de Respuesta de Error

```json
{
  "success": false,
  "error": {
    "code": "TOKEN_EXPIRED",
    "message": "El token API ha expirado",
    "details": {
      "token_name": "Sistema CRM",
      "expired_at": "2024-01-15T23:59:59Z"
    }
  }
}
```

---

## 📊 Límites y Restricciones

### Rate Limiting

| Tipo de Token | Requests por Hora | Requests por Día |
|---------------|-------------------|------------------|
| Desarrollo    | 100               | 1,000            |
| Producción    | 1,000             | 10,000           |
| Enterprise    | 5,000             | 50,000           |

### Tamaños de Archivo

| Tipo de Archivo | Tamaño Máximo |
|-----------------|---------------|
| Documentos PDF  | 50 MB         |
| Imágenes        | 20 MB         |
| Videos          | 100 MB        |
| Audio           | 30 MB         |
| Otros           | 25 MB         |

### Paginación

- **Máximo por página**: 100 items
- **Default por página**: 15 items
- **Máximo páginas por request**: Ilimitado

---

## 📚 SDKs y Librerías

### Oficiales

| Lenguaje | SDK | Instalación |
|----------|-----|-------------|
| PHP      | `archiveycloud/php-sdk` | `composer require archiveycloud/php-sdk` |
| JavaScript/Node.js | `@archiveycloud/js-sdk` | `npm install @archiveycloud/js-sdk` |
| Python   | `archiveycloud-python` | `pip install archiveycloud-python` |
| .NET     | `ArchiveyCloud.SDK` | `dotnet add package ArchiveyCloud.SDK` |

### Ejemplos de Instalación

#### PHP SDK
```bash
composer require archiveycloud/php-sdk
```

```php
<?php
require_once 'vendor/autoload.php';

use ArchiveyCloud\SDK\Client;

$client = new Client('at_tu_token_aqui', 'https://tu-servidor.com/api/v1');

// Crear expediente
$expediente = $client->expedientes()->create([
    'asunto' => 'Expediente desde PHP',
    'descripcion' => 'Creado con el SDK de PHP',
    'serie_id' => 3
]);

echo "Expediente creado: " . $expediente['id'];
```

#### JavaScript SDK
```bash
npm install @archiveycloud/js-sdk
```

```javascript
import ArchiveyCloud from '@archiveycloud/js-sdk';

const client = new ArchiveyCloud('at_tu_token_aqui', 'https://tu-servidor.com/api/v1');

// Crear expediente
const expediente = await client.expedientes.create({
  asunto: 'Expediente desde JavaScript',
  descripcion: 'Creado con el SDK de JavaScript',
  serie_id: 3
});

console.log('Expediente creado:', expediente.id);
```

---

## 🔧 Herramientas de Desarrollo

### Postman Collection

Importa nuestra colección de Postman para probar fácilmente todos los endpoints:

[⬇️ Descargar ArchiveyCloud.postman_collection.json](./postman/ArchiveyCloud.postman_collection.json)

### OpenAPI/Swagger

Especificación completa de la API en formato OpenAPI 3.0:

[📖 Ver Documentación Swagger](https://tu-servidor.com/api/docs)

### Webhook Testing

Para probar webhooks en desarrollo, recomendamos:

- **ngrok**: `ngrok http 8000`
- **localtunnel**: `lt --port 8000`

---

## 📞 Soporte

### Recursos de Ayuda

- 📧 **Email**: api-support@archiveycloud.com
- 🌐 **Portal**: [https://support.archiveycloud.com](https://support.archiveycloud.com)
- 💬 **Chat**: Disponible en el panel de administración
- 📖 **Knowledge Base**: [https://docs.archiveycloud.com](https://docs.archiveycloud.com)

### Reportar Problemas

Para reportar bugs o solicitar nuevas características:

1. **GitHub Issues**: [https://github.com/archiveycloud/api/issues](https://github.com/archiveycloud/api/issues)
2. **Email**: development@archiveycloud.com
3. **Portal de Desarrollo**: [https://dev.archiveycloud.com](https://dev.archiveycloud.com)

---

## 📄 Changelog

### v1.0.0 (2024-01-15)
- ✅ Lanzamiento inicial de la API
- ✅ Endpoints de documentos y expedientes
- ✅ Sistema de autenticación por tokens
- ✅ Permisos granulares
- ✅ Rate limiting
- ✅ Auditoría completa

---

**© 2024 ArchiveyCloud. Todos los derechos reservados.**

> Esta documentación está en constante evolución. Para la versión más actualizada, visita [https://docs.archiveycloud.com/api](https://docs.archiveycloud.com/api)
