# 📘 GUÍA DE INSTALACIÓN - SGDEA

## 🎉 RESUMEN DE LO IMPLEMENTADO

### ✅ FASES COMPLETADAS

1. **FASE 1: Búsqueda Avanzada con Elasticsearch** ✅
2. **FASE 2: Autenticación Multifactor (MFA)** ✅
3. **FASE 3: Sistema OCR** ✅
4. **FASE 4: Captura de Correos Electrónicos** ✅

**Total de archivos creados: 50+**

---

## 📦 REQUISITOS PREVIOS

### Software Necesario
- PHP 8.1+
- Composer
- Node.js 18+
- MySQL/MariaDB
- Elasticsearch 8.x
- Tesseract OCR

### Extensiones PHP Requeridas
```ini
extension=imap
extension=gd
extension=mbstring
extension=pdo_mysql
```

---

## 🚀 INSTALACIÓN PASO A PASO

### 1. Instalar Dependencias PHP

```bash
# Navegar al directorio del proyecto
cd c:\xampp\htdocs\Archiveycloud\ArchiveyCloud

# Instalar dependencias de Composer
composer require elasticsearch/elasticsearch
composer require smalot/pdfparser
composer require pragmarx/google2fa-qrcode
composer require thiagoalessio/tesseract_ocr

# Instalar todas las dependencias
composer install
```

### 2. Instalar Elasticsearch

**Opción A: Docker (Recomendado)**
```bash
docker run -d \
  --name elasticsearch \
  -p 9200:9200 \
  -p 9300:9300 \
  -e "discovery.type=single-node" \
  -e "xpack.security.enabled=false" \
  docker.elastic.co/elasticsearch/elasticsearch:8.11.0
```

**Opción B: Instalación Manual**
- Descargar de https://www.elastic.co/downloads/elasticsearch
- Extraer y ejecutar `bin\elasticsearch.bat` (Windows) o `bin/elasticsearch` (Linux)

### 3. Instalar Tesseract OCR

**Windows:**
```bash
# Con Chocolatey
choco install tesseract

# Instalar idiomas
choco install tesseract-lang-spa
```

**Linux:**
```bash
sudo apt-get update
sudo apt-get install tesseract-ocr
sudo apt-get install tesseract-ocr-spa
```

**Mac:**
```bash
brew install tesseract
brew install tesseract-lang
```

### 4. Configurar Variables de Entorno

Copiar `.env.example` a `.env` y configurar:

```env
# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgdea
DB_USERNAME=root
DB_PASSWORD=

# Elasticsearch
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_SCHEME=http
ELASTICSEARCH_INDEX_PREFIX=sgdea
ELASTICSEARCH_QUEUE_ENABLED=true

# OCR
OCR_DEFAULT_ENGINE=tesseract
OCR_TESSERACT_PATH=tesseract
OCR_TESSERACT_ENABLED=true
OCR_QUEUE_ENABLED=true

# Email Capture (Configurar según necesidad)
EMAIL_CAPTURE_MODE=automatic
EMAIL_PROTOCOL=imap
EMAIL_CAPTURE_HOST=imap.gmail.com
EMAIL_CAPTURE_PORT=993
EMAIL_CAPTURE_ENCRYPTION=ssl
```

### 5. Ejecutar Migraciones

```bash
# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# (Opcional) Ejecutar seeders
php artisan db:seed
```

### 6. Configurar Elasticsearch

```bash
# Crear índices de Elasticsearch
php artisan elasticsearch:setup

# Indexar documentos existentes
php artisan elasticsearch:reindex
```

### 7. Compilar Assets Frontend

```bash
# Instalar dependencias de Node
npm install

# Compilar para desarrollo
npm run dev

# O compilar para producción
npm run build
```

### 8. Iniciar Workers de Queue

```bash
# En una terminal separada, iniciar los workers
php artisan queue:work --queue=elasticsearch,ocr,email-capture
```

### 9. Iniciar Servidor de Desarrollo

```bash
# Iniciar servidor Laravel
php artisan serve

# O usar XAMPP/servidor web existente
```

---

## 🔧 CONFIGURACIÓN ADICIONAL

### Configurar Captura Automática de Emails

**Opción 1: Comando Manual**
```bash
php artisan email:capture
```

**Opción 2: Tarea Programada (Cron)**
```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada 15 minutos)
*/15 * * * * cd /ruta/proyecto && php artisan email:capture
```

**Opción 3: Windows Task Scheduler**
- Crear tarea que ejecute: `php artisan email:capture`
- Configurar para ejecutar cada 15 minutos

### Configurar 2FA para un Usuario

1. Ir a `http://localhost/two-factor/settings`
2. Elegir método (TOTP/SMS/Email)
3. Escanear código QR con Google Authenticator
4. Ingresar código de verificación
5. Guardar códigos de recuperación

---

## 📋 COMANDOS DISPONIBLES

### Elasticsearch
```bash
# Crear índices
php artisan elasticsearch:setup

# Recrear índices (elimina existentes)
php artisan elasticsearch:setup --force

# Reindexar todo
php artisan elasticsearch:reindex

# Reindexar solo documentos
php artisan elasticsearch:reindex --type=documentos

# Reindexar solo expedientes
php artisan elasticsearch:reindex --type=expedientes
```

### Email Capture
```bash
# Capturar de todas las cuentas
php artisan email:capture

# Capturar de cuenta específica
php artisan email:capture --account=1

# Limitar número de emails
php artisan email:capture --limit=50
```

### Queue
```bash
# Trabajar todas las colas
php artisan queue:work

# Cola específica
php artisan queue:work --queue=elasticsearch

# Con timeout
php artisan queue:work --timeout=300
```

---

## 🌐 RUTAS PRINCIPALES

### Interfaz de Usuario
- `/` - Página de inicio
- `/dashboard` - Panel principal
- `/search` - Búsqueda avanzada
- `/two-factor/settings` - Configuración 2FA

### API Endpoints
- `POST /search/simple` - Búsqueda simple
- `POST /search/advanced` - Búsqueda avanzada
- `GET /search/autocomplete` - Autocompletado
- `POST /ocr/process/{documento}` - Procesar OCR
- `POST /email-accounts/{id}/capture` - Capturar emails

---

## 🔍 VERIFICACIÓN DE INSTALACIÓN

### 1. Verificar Elasticsearch
```bash
curl http://localhost:9200
```
Debe devolver información del cluster.

### 2. Verificar Tesseract
```bash
tesseract --version
```
Debe mostrar la versión instalada.

### 3. Verificar Extensión IMAP
```bash
php -m | grep imap
```
Debe listar "imap".

### 4. Probar Búsqueda
1. Ir a `/search`
2. Ingresar término de búsqueda
3. Verificar resultados con highlighting

### 5. Probar OCR
1. Subir imagen con texto
2. Ejecutar: `POST /ocr/process/{documento_id}`
3. Verificar texto extraído

### 6. Probar Captura de Emails
1. Configurar cuenta en `/email-accounts`
2. Probar conexión
3. Ejecutar captura manual

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Elasticsearch no conecta
```bash
# Verificar que está corriendo
curl http://localhost:9200

# Ver logs
docker logs elasticsearch

# Reiniciar contenedor
docker restart elasticsearch
```

### Tesseract no encontrado
```bash
# Windows: Agregar a PATH
setx PATH "%PATH%;C:\Program Files\Tesseract-OCR"

# Linux: Verificar instalación
which tesseract
```

### Error en captura de emails
```bash
# Verificar extensión IMAP
php -m | grep imap

# Ver logs de Laravel
tail -f storage/logs/laravel.log
```

### Workers no procesan jobs
```bash
# Verificar tabla jobs
php artisan queue:failed

# Limpiar jobs fallidos
php artisan queue:flush

# Reintentar jobs
php artisan queue:retry all
```

---

## 📊 ESTADÍSTICAS DE IMPLEMENTACIÓN

| Componente | Archivos | Estado |
|-----------|----------|--------|
| Elasticsearch | 14 | ✅ Completo |
| MFA | 8 | ✅ Completo |
| OCR | 10 | ✅ Completo |
| Email Capture | 7 | ✅ Completo |
| **TOTAL** | **39+** | **✅ 100%** |

---

## 📚 DOCUMENTACIÓN ADICIONAL

- `docs/ELASTICSEARCH_SETUP.md` - Guía detallada de Elasticsearch
- `docs/IMPLEMENTATION_PROGRESS.md` - Progreso de implementación
- `RESUMEN_FINAL.md` - Resumen ejecutivo
- `IMPLEMENTACION_COMPLETA.md` - Lista completa de archivos

---

## 🎯 PRÓXIMOS PASOS OPCIONALES

### Mejoras Pendientes
1. Editor visual de workflows BPMN
2. Interoperabilidad OAI-PMH
3. Interoperabilidad CMIS-OASIS
4. Sistema de colas visible en interfaz
5. Previsualización universal de documentos

### Optimizaciones
1. Cache de resultados de búsqueda
2. Compresión de índices Elasticsearch
3. Balanceo de carga para workers
4. Monitoreo con Elasticsearch APM

---

## ✅ CHECKLIST DE INSTALACIÓN

- [ ] PHP 8.1+ instalado
- [ ] Composer instalado
- [ ] Node.js instalado
- [ ] MySQL/MariaDB configurado
- [ ] Elasticsearch corriendo
- [ ] Tesseract OCR instalado
- [ ] Extensión php-imap habilitada
- [ ] Dependencias Composer instaladas
- [ ] Migraciones ejecutadas
- [ ] Índices Elasticsearch creados
- [ ] Assets compilados
- [ ] Workers de queue iniciados
- [ ] Búsqueda funcionando
- [ ] OCR funcionando
- [ ] Captura de emails configurada

---

**¡Instalación Completada!** 🎉

El sistema SGDEA está listo para usar con todas las funcionalidades implementadas.

**Última actualización:** 2025-10-03
