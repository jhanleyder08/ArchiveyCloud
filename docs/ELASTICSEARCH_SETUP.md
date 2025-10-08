# Instalación y Configuración de Elasticsearch

## 📋 Requisitos

- Elasticsearch 8.x o superior
- PHP 8.1 o superior
- Composer

## 🚀 Instalación de Elasticsearch

### Windows

1. Descargar Elasticsearch desde: https://www.elastic.co/downloads/elasticsearch
2. Extraer el archivo ZIP
3. Ejecutar `bin\elasticsearch.bat`

### Linux/Mac

```bash
# Descargar e instalar
wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-8.11.0-linux-x86_64.tar.gz
tar -xzf elasticsearch-8.11.0-linux-x86_64.tar.gz
cd elasticsearch-8.11.0/

# Iniciar Elasticsearch
./bin/elasticsearch
```

### Docker (Recomendado para desarrollo)

```bash
docker run -d \
  --name elasticsearch \
  -p 9200:9200 \
  -p 9300:9300 \
  -e "discovery.type=single-node" \
  -e "xpack.security.enabled=false" \
  docker.elastic.co/elasticsearch/elasticsearch:8.11.0
```

## 📦 Instalación de Dependencias PHP

```bash
# Instalar cliente de Elasticsearch para PHP
composer require elasticsearch/elasticsearch

# Instalar librería para extracción de texto de PDFs (opcional pero recomendado)
composer require smalot/pdfparser
```

## ⚙️ Configuración del Proyecto

### 1. Configurar variables de entorno

Copiar el archivo `.env.example` a `.env` y configurar:

```env
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_SCHEME=http
ELASTICSEARCH_INDEX_PREFIX=sgdea
ELASTICSEARCH_QUEUE_ENABLED=true
```

### 2. Crear índices de Elasticsearch

```bash
# Crear los índices con sus mappings
php artisan elasticsearch:setup

# Si necesitas recrear los índices (elimina los existentes)
php artisan elasticsearch:setup --force
```

### 3. Indexar documentos existentes

```bash
# Indexar todos los documentos y expedientes
php artisan elasticsearch:reindex

# Indexar solo documentos
php artisan elasticsearch:reindex --type=documentos

# Indexar solo expedientes
php artisan elasticsearch:reindex --type=expedientes

# Cambiar tamaño de lote (por defecto 100)
php artisan elasticsearch:reindex --chunk=200
```

## 🔍 Uso del Sistema de Búsqueda

### Búsqueda Simple

```javascript
// Frontend (usando axios o fetch)
const response = await axios.post('/search/simple', {
    q: 'término de búsqueda',
    type: 'documentos',
    size: 20,
    from: 0,
    sort: { fecha_creacion: 'desc' }
});
```

### Búsqueda Avanzada con Operadores

```javascript
const response = await axios.post('/search/advanced', {
    // Términos que DEBEN estar (AND)
    must: ['contrato', 'servicios'],
    
    // Términos que DEBERÍAN estar (OR)
    should: ['2024', '2025'],
    
    // Términos que NO deben estar (NOT)
    must_not: ['borrador', 'cancelado'],
    
    // Búsqueda por campos específicos
    fields: {
        codigo: 'DOC-2024*',  // con wildcard
        nombre: '=Contrato Exacto',  // búsqueda exacta
    },
    
    // Rango de fechas
    date_range: {
        fecha_creacion: {
            from: '2024-01-01',
            to: '2024-12-31'
        }
    },
    
    // Filtros
    filters: {
        estado: 'activo',
        serie_documental_id: 123
    },
    
    // Palabras clave
    keywords: ['contrato', 'servicios profesionales'],
    
    type: 'documentos',
    size: 20,
    
    // Solicitar aggregations/facetas
    aggregations: [
        'tipo_documento',
        'serie_documental_nombre',
        'estado'
    ]
});
```

### Autocompletado

```javascript
const response = await axios.get('/search/autocomplete', {
    params: {
        q: 'contr',
        field: 'nombre',
        type: 'documentos'
    }
});

// Respuesta: ['Contrato 2024', 'Contrato de Servicios', 'Control de Calidad', ...]
```

## 🎯 Operadores de Búsqueda Soportados

### Operadores Booleanos
- `AND` (y): Intersección de resultados
- `OR` (o): Unión de resultados
- `NOT` (no): Exclusión de términos

### Wildcards
- `*`: Múltiples caracteres (ej: `doc*` encuentra `documento`, `docs`, etc.)
- `?`: Un solo carácter (ej: `do?` encuentra `doc`, `dos`, etc.)
- `$`: Fin de palabra
- `=`: Coincidencia exacta (ej: `=Contrato` solo encuentra "Contrato" exacto)

### Búsqueda Aproximada (Fuzzy)
Automáticamente habilitada con `fuzziness: AUTO` - tolera errores ortográficos

## 📊 Ordenamiento de Resultados (REQ-BP-013)

```javascript
{
    sort: {
        '_score': 'desc',              // Relevancia/pertinencia
        'fecha_creacion': 'desc',      // Fecha de creación
        'nombre.keyword': 'asc',       // Alfabético
        'tamanio': 'desc',             // Tamaño
        'usuario_creador': 'asc'       // Usuario
    }
}
```

## 🔧 Mantenimiento

### Verificar estado de Elasticsearch

```bash
curl -X GET "localhost:9200/_cluster/health?pretty"
```

### Ver estadísticas de índices

```bash
curl -X GET "localhost:9200/_cat/indices?v"
```

### Eliminar un índice

```bash
curl -X DELETE "localhost:9200/sgdea_documentos"
```

## ⚠️ Troubleshooting

### Elasticsearch no se conecta

1. Verificar que Elasticsearch está corriendo: `curl localhost:9200`
2. Verificar firewall/puertos
3. Revisar logs: `tail -f logs/laravel.log`

### Indexación lenta

1. Aumentar `ELASTICSEARCH_BULK_SIZE` en `.env`
2. Habilitar queue: `ELASTICSEARCH_QUEUE_ENABLED=true`
3. Configurar workers: `php artisan queue:work --queue=elasticsearch`

### Búsquedas lentas

1. Verificar que los índices están optimizados
2. Reducir `ELASTICSEARCH_SEARCH_TIMEOUT`
3. Revisar mappings y analyzers

## 📚 Requerimientos Implementados

- ✅ **REQ-BP-001**: Búsqueda de texto completo en documentos
- ✅ **REQ-BP-002**: Búsqueda avanzada con operadores booleanos
- ✅ **REQ-BP-002**: Autocompletado inteligente
- ✅ **REQ-BP-002**: Coincidencias aproximadas (fuzzy)
- ✅ **REQ-BP-002**: Wildcards y búsqueda con comodines
- ✅ **REQ-BP-007**: Búsqueda jerárquica por CCD
- ✅ **REQ-BP-011**: Búsqueda integrada de texto y metadatos
- ✅ **REQ-BP-013**: Ordenamiento por múltiples criterios
- ✅ **REQ-BP-012**: Filtrado por permisos de acceso

## 🔗 Referencias

- [Documentación oficial de Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Cliente PHP de Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
- [Query DSL](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html)
