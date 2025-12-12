# 📘 Manual Técnico de Implementación - ArchiveyCloud SGDEA

**Sistema de Gestión Documental Electrónico de Archivo**  
**Versión:** 1.0.1  
**Fecha:** Diciembre 2025  
**Clasificación:** Documento Técnico - Desarrolladores

---

## 📑 Índice General

1. [Información del Proyecto](#1-información-del-proyecto)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Stack Tecnológico](#3-stack-tecnológico)
4. [Requisitos de Instalación](#4-requisitos-de-instalación)
5. [Guía de Instalación](#5-guía-de-instalación)
6. [Estructura de Base de Datos](#6-estructura-de-base-de-datos)
7. [Módulos del Sistema](#7-módulos-del-sistema)
8. [API REST](#8-api-rest)
9. [Seguridad y Autenticación](#9-seguridad-y-autenticación)
10. [Configuración Avanzada](#10-configuración-avanzada)
11. [Comandos Artisan](#11-comandos-artisan)
12. [Troubleshooting](#12-troubleshooting)
13. [Mantenimiento](#13-mantenimiento)

---

## 1. Información del Proyecto

### 1.1 Descripción General

**ArchiveyCloud SGDEA** es un Sistema de Gestión Documental Electrónico de Archivo diseñado para cumplir con la normativa archivística colombiana. Proporciona gestión integral del ciclo de vida documental desde la captura hasta la disposición final.

### 1.2 Objetivos del Sistema

| Objetivo | Descripción |
|----------|-------------|
| **Gestión Documental** | Captura, clasificación, almacenamiento y recuperación de documentos |
| **Cumplimiento Normativo** | Adherencia a Ley 594/2000, Acuerdos AGN, ISO 15489 |
| **Trazabilidad** | Auditoría completa de todas las operaciones |
| **Seguridad** | Control de acceso granular, 2FA, integridad documental |
| **Interoperabilidad** | APIs REST, importación/exportación XML |

### 1.3 Alcance Funcional

```
┌─────────────────────────────────────────────────────────────┐
│                    ARCHIVEYCLOUD SGDEA                      │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │     TRD     │  │     CCD     │  │    EXPEDIENTES      │  │
│  │  Tablas de  │  │  Cuadros de │  │    ELECTRÓNICOS     │  │
│  │  Retención  │  │Clasificación│  │                     │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ DOCUMENTOS  │  │  BÚSQUEDA   │  │      USUARIOS       │  │
│  │  Captura    │  │ Elasticsearch│  │   Roles/Permisos   │  │
│  │  OCR/Firmas │  │  Avanzada   │  │       2FA           │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  AUDITORÍA  │  │  REPORTES   │  │    WORKFLOWS        │  │
│  │  Pistas     │  │  Dashboard  │  │   Automatización    │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 1.4 Estado de Implementación

| Módulo | Estado | Progreso |
|--------|--------|----------|
| TRD - Tablas de Retención | ✅ Completo | 100% |
| CCD - Cuadros Clasificación | ✅ Completo | 100% |
| Expedientes Electrónicos | ✅ Backend Completo | 100% |
| Autenticación 2FA | ✅ Completo | 100% |
| Búsqueda Elasticsearch | ✅ Completo | 100% |
| Sistema OCR | ✅ Completo | 100% |
| Captura de Correos | ✅ Completo | 100% |
| Roles y Permisos | ✅ Completo | 100% |
| Dashboard Ejecutivo | ✅ Completo | 100% |
| Visualización Dinámica Almacenamiento | ✅ Completo | 100% |
| **TOTAL SISTEMA** | **✅ Operativo** | **~90%** |

---

## 2. Arquitectura del Sistema

### 2.1 Diagrama de Arquitectura

```
┌──────────────────────────────────────────────────────────────────┐
│                         CLIENTE (Browser)                         │
│                    React 18 + TypeScript + Inertia.js            │
└─────────────────────────────┬────────────────────────────────────┘
                              │ HTTPS
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SERVIDOR WEB (Apache/Nginx)                  │
└─────────────────────────────┬────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                       APLICACIÓN LARAVEL 11                       │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  │
│  │ Controllers│  │  Services  │  │   Models   │  │ Middleware │  │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  │
│  │    Jobs    │  │  Observers │  │   Events   │  │  Listeners │  │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘  │
└───────┬──────────────┬───────────────┬───────────────┬───────────┘
        │              │               │               │
        ▼              ▼               ▼               ▼
┌──────────────┐ ┌────────────┐ ┌────────────┐ ┌──────────────────┐
│    MySQL     │ │Elasticsearch│ │   Redis    │ │  File Storage    │
│   Database   │ │   Search   │ │   Queue    │ │  (local/S3)      │
└──────────────┘ └────────────┘ └────────────┘ └──────────────────┘
```

### 2.2 Patrón de Diseño

El sistema implementa una arquitectura en capas:

```
┌─────────────────────────────────────────────┐
│           CAPA DE PRESENTACIÓN              │
│     React + Inertia.js + TailwindCSS        │
├─────────────────────────────────────────────┤
│           CAPA DE CONTROLADORES             │
│      HTTP Controllers + API Controllers     │
├─────────────────────────────────────────────┤
│           CAPA DE SERVICIOS                 │
│   Business Logic (TRDService, CCDService)   │
├─────────────────────────────────────────────┤
│           CAPA DE MODELOS                   │
│        Eloquent ORM + Relationships         │
├─────────────────────────────────────────────┤
│           CAPA DE DATOS                     │
│      MySQL + Elasticsearch + Storage        │
└─────────────────────────────────────────────┘
```

### 2.3 Patrones Implementados

| Patrón | Uso en el Sistema |
|--------|-------------------|
| **MVC** | Estructura principal Laravel |
| **Service Layer** | Lógica de negocio en Services |
| **Repository** | Abstracción de acceso a datos |
| **Observer** | Auto-indexación en Elasticsearch |
| **Strategy** | Múltiples motores OCR |
| **Factory** | Creación de Jobs |
| **Middleware** | Autenticación, permisos, 2FA |
| **Event-Driven** | Notificaciones y auditoría |

---

## 3. Stack Tecnológico

### 3.1 Backend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **PHP** | 8.1+ | Lenguaje principal |
| **Laravel** | 11.x | Framework backend |
| **Eloquent ORM** | - | Mapeo objeto-relacional |
| **Laravel Sanctum** | - | Autenticación API |
| **Laravel Breeze** | - | Autenticación base |

### 3.2 Frontend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **React** | 18.x | Framework UI |
| **TypeScript** | 5.x | Tipado estático |
| **Inertia.js** | 1.x | SPA sin API separada |
| **TailwindCSS** | 3.x | Framework CSS |
| **Radix UI** | - | Componentes accesibles |
| **Lucide React** | - | Iconografía |
| **Recharts** | - | Gráficos y dashboards |
| **Sonner** | - | Notificaciones toast |

### 3.3 Base de Datos y Almacenamiento

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| **MySQL** | 8.0+ | Base de datos principal |
| **Elasticsearch** | 8.x | Motor de búsqueda |
| **Redis** | 7.x | Colas y caché (opcional) |
| **Laravel Storage** | - | Almacenamiento de archivos |

### 3.4 Servicios Externos

| Servicio | Propósito |
|----------|-----------|
| **Tesseract OCR** | Reconocimiento de caracteres |
| **Google Cloud Vision** | OCR avanzado (opcional) |
| **Azure Vision** | OCR avanzado (opcional) |
| **SMTP/Mailtrap** | Envío de correos |
| **IMAP** | Captura de correos |

### 3.5 Dependencias PHP Principales

```json
{
  "require": {
    "php": "^8.1",
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "inertiajs/inertia-laravel": "^1.0",
    "elasticsearch/elasticsearch": "^8.0",
    "smalot/pdfparser": "^2.0",
    "pragmarx/google2fa-qrcode": "^3.0",
    "thiagoalessio/tesseract_ocr": "^2.13"
  }
}
```

### 3.6 Dependencias NPM Principales

```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "@inertiajs/react": "^1.0.0",
    "@radix-ui/react-*": "latest",
    "tailwindcss": "^3.4.0",
    "typescript": "^5.0.0",
    "lucide-react": "latest",
    "recharts": "^2.0.0",
    "sonner": "^1.0.0",
    "zod": "^3.0.0"
  }
}
```

---

## 4. Requisitos de Instalación

### 4.1 Requisitos de Hardware (Mínimos)

| Componente | Desarrollo | Producción |
|------------|------------|------------|
| **CPU** | 2 cores | 4+ cores |
| **RAM** | 4 GB | 8+ GB |
| **Disco** | 20 GB SSD | 100+ GB SSD |
| **Red** | 10 Mbps | 100+ Mbps |

### 4.2 Requisitos de Software

#### Sistema Operativo
- **Linux** (Ubuntu 20.04+, CentOS 8+) - Recomendado
- **Windows** (10/11 con WSL2 o XAMPP)
- **macOS** (12+)

#### Software Base

| Software | Versión Mínima | Comando Verificación |
|----------|----------------|---------------------|
| PHP | 8.1 | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18.x | `node -v` |
| NPM | 9.x | `npm -v` |
| MySQL | 8.0 | `mysql --version` |
| Git | 2.x | `git --version` |

#### Extensiones PHP Requeridas

```ini
; Extensiones obligatorias
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=tokenizer
extension=xml
extension=ctype
extension=json
extension=bcmath
extension=fileinfo
extension=gd

; Extensiones para funcionalidades específicas
extension=imap        ; Captura de correos
extension=imagick     ; Procesamiento de imágenes (opcional)
```

#### Software Opcional

| Software | Propósito | Instalación |
|----------|-----------|-------------|
| **Elasticsearch** | Búsqueda avanzada | Docker o instalación nativa |
| **Tesseract OCR** | Reconocimiento de texto | `apt install tesseract-ocr` |
| **Redis** | Colas y caché | `apt install redis-server` |

### 4.3 Verificación de Requisitos

```bash
# Script de verificación
echo "=== Verificación de Requisitos ===" 

# PHP
php -v
php -m | grep -E "(pdo_mysql|mbstring|gd|imap)"

# Composer
composer -V

# Node.js
node -v
npm -v

# MySQL
mysql --version

# Elasticsearch (si está instalado)
curl -s http://localhost:9200 | head -5

# Tesseract (si está instalado)
tesseract --version
```

---

## 5. Guía de Instalación

### 5.1 Instalación Completa Paso a Paso

#### Paso 1: Clonar el Repositorio

```bash
# Clonar proyecto
git clone https://github.com/jhanleyder08/ArchiveyCloud.git
cd ArchiveyCloud

# O si ya existe
cd /ruta/a/ArchiveyCloud
git pull origin main
```

#### Paso 2: Instalar Dependencias PHP

```bash
# Instalar dependencias de Composer
composer install

# Si hay problemas de memoria
COMPOSER_MEMORY_LIMIT=-1 composer install
```

#### Paso 3: Instalar Dependencias Node.js

```bash
# Instalar dependencias NPM
npm install

# Si hay conflictos
npm install --legacy-peer-deps
```

#### Paso 4: Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

#### Paso 5: Configurar `.env`

```env
# ============================================
# CONFIGURACIÓN DE APLICACIÓN
# ============================================
APP_NAME="ArchiveyCloud SGDEA"
APP_ENV=local
APP_KEY=base64:GENERADA_AUTOMATICAMENTE
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Bogota

# ============================================
# BASE DE DATOS
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=archiveycloud
DB_USERNAME=root
DB_PASSWORD=

# ============================================
# ELASTICSEARCH (Búsqueda Avanzada)
# ============================================
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_SCHEME=http
ELASTICSEARCH_INDEX_PREFIX=sgdea
ELASTICSEARCH_QUEUE_ENABLED=true

# ============================================
# CORREO ELECTRÓNICO
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=tu_usuario_mailtrap
MAIL_PASSWORD=tu_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@archiveycloud.com"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# OCR (Reconocimiento de Texto)
# ============================================
OCR_DEFAULT_ENGINE=tesseract
OCR_TESSERACT_PATH=tesseract
OCR_TESSERACT_ENABLED=true
OCR_QUEUE_ENABLED=true

# ============================================
# CAPTURA DE CORREOS
# ============================================
EMAIL_CAPTURE_MODE=automatic
EMAIL_PROTOCOL=imap
EMAIL_CAPTURE_HOST=imap.gmail.com
EMAIL_CAPTURE_PORT=993
EMAIL_CAPTURE_ENCRYPTION=ssl

# ============================================
# COLAS (Queue)
# ============================================
QUEUE_CONNECTION=database

# ============================================
# SESIÓN
# ============================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

#### Paso 6: Crear Base de Datos

```bash
# MySQL CLI
mysql -u root -p
```

```sql
-- Crear base de datos
CREATE DATABASE archiveycloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario dedicado (recomendado para producción)
CREATE USER 'sgdea_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON archiveycloud.* TO 'sgdea_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

#### Paso 7: Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones
php artisan migrate

# Si necesita refrescar (¡BORRA DATOS!)
php artisan migrate:fresh

# Ver estado de migraciones
php artisan migrate:status
```

#### Paso 8: Ejecutar Seeders

```bash
# Ejecutar seeders principales
php artisan db:seed

# Seeder específico de roles y permisos
php artisan db:seed --class=RolesYPermisosSeeder

# Seeder de datos de prueba (desarrollo)
php artisan db:seed --class=DatabaseSeeder
```

#### Paso 9: Compilar Assets Frontend

```bash
# Desarrollo (con hot reload)
npm run dev

# Producción (optimizado)
npm run build
```

#### Paso 10: Configurar Elasticsearch (Opcional pero Recomendado)

```bash
# Opción A: Docker (Recomendado)
docker run -d \
  --name elasticsearch \
  -p 9200:9200 \
  -p 9300:9300 \
  -e "discovery.type=single-node" \
  -e "xpack.security.enabled=false" \
  docker.elastic.co/elasticsearch/elasticsearch:8.11.0

# Verificar que está corriendo
curl http://localhost:9200

# Crear índices
php artisan elasticsearch:setup

# Indexar documentos existentes
php artisan elasticsearch:reindex
```

#### Paso 11: Instalar Tesseract OCR (Opcional)

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install tesseract-ocr tesseract-ocr-spa

# Windows (Chocolatey)
choco install tesseract
choco install tesseract-lang-spa

# macOS
brew install tesseract
brew install tesseract-lang

# Verificar instalación
tesseract --version
tesseract --list-langs
```

#### Paso 12: Iniciar Workers de Cola

```bash
# Terminal separada para workers
php artisan queue:work --queue=elasticsearch,ocr,email-capture

# O con supervisor (producción)
# Ver sección de configuración avanzada
```

#### Paso 13: Iniciar Servidor de Desarrollo

```bash
# Servidor Laravel integrado
php artisan serve

# Acceder en: http://localhost:8000
```

### 5.2 Verificación Post-Instalación

```bash
# Ejecutar verificaciones
php artisan about

# Verificar rutas
php artisan route:list --compact

# Verificar configuración de caché
php artisan config:show database

# Limpiar cachés
php artisan optimize:clear
```

### 5.3 Crear Usuario Super Administrador

```bash
# Opción 1: Desde Tinker
php artisan tinker
```

```php
// Dentro de Tinker
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

// Obtener rol Super Admin
$superAdminRole = Role::where('name', 'Super Administrador')->first();

// Crear usuario
$user = User::create([
    'name' => 'Administrador Principal',
    'email' => 'admin@archiveycloud.com',
    'password' => Hash::make('password_seguro_123'),
    'email_verified_at' => now(),
    'role_id' => $superAdminRole->id,
]);

echo "Usuario creado: {$user->email}";
exit;
```

```bash
# Opción 2: Comando personalizado (si existe)
php artisan user:create-admin --email=admin@archiveycloud.com
```

### 5.4 Checklist de Instalación

```
□ Repositorio clonado
□ Composer install ejecutado
□ NPM install ejecutado
□ Archivo .env configurado
□ Clave de aplicación generada
□ Base de datos creada
□ Migraciones ejecutadas
□ Seeders ejecutados
□ Assets compilados
□ Elasticsearch configurado (opcional)
□ Tesseract instalado (opcional)
□ Workers de cola iniciados
□ Servidor funcionando
□ Usuario admin creado
□ Login verificado
```

---

## 6. Estructura de Base de Datos

### 6.1 Diagrama Entidad-Relación (Simplificado)

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│      USERS      │────▶│      ROLES      │────▶│    PERMISOS     │
└────────┬────────┘     └─────────────────┘     └─────────────────┘
         │
         │  ┌─────────────────────────────────────────────────────┐
         │  │                    MÓDULO TRD                       │
         ▼  │  ┌───────┐    ┌────────────┐    ┌──────────────┐   │
┌─────────────┐ │  TRDs │───▶│   SERIES   │───▶│  SUBSERIES   │   │
│ DOCUMENTOS  │ └───────┘    └────────────┘    └──────────────┘   │
└──────┬──────┘ │                    │                            │
       │        │                    ▼                            │
       │        │            ┌──────────────┐                     │
       │        │            │ RETENCIONES  │                     │
       │        └────────────┴──────────────┴─────────────────────┘
       │
       │  ┌─────────────────────────────────────────────────────┐
       │  │                    MÓDULO CCD                       │
       │  │  ┌───────┐    ┌────────────┐    ┌──────────────┐   │
       │  │  │  CCD  │───▶│  NIVELES   │───▶│ VOCABULARIOS │   │
       │  │  └───────┘    └────────────┘    └──────────────┘   │
       │  └─────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────┐     ┌─────────────────┐
│   EXPEDIENTES   │────▶│ TRANSFERENCIAS  │
└────────┬────────┘     └─────────────────┘
         │
         ▼
┌─────────────────┐     ┌─────────────────┐
│   PRÉSTAMOS     │     │   VALORACIONES  │
└─────────────────┘     └─────────────────┘
```

### 6.2 Tablas Principales (40+ tablas)

#### Módulo de Usuarios y Seguridad

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `users` | Usuarios del sistema | id, name, email, password, role_id |
| `roles` | Roles del sistema | id, name, nivel_jerarquico, parent_id |
| `permisos` | Permisos granulares | id, nombre, categoria, descripcion |
| `role_permiso` | Relación roles-permisos | role_id, permiso_id |
| `user_permiso` | Permisos directos a usuarios | user_id, permiso_id |
| `two_factor_authentications` | Configuración 2FA | user_id, method, secret, enabled |
| `two_factor_backup_codes` | Códigos de recuperación | user_id, code, used |
| `pista_auditorias` | Log de auditoría | user_id, accion, tabla, registro_id |

#### Módulo TRD (Tablas de Retención Documental)

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `trds` | TRDs principales | id, codigo, nombre, version, estado |
| `series_documentales` | Series documentales | id, trd_id, codigo, nombre |
| `subseries_documentales` | Subseries | id, serie_id, codigo, nombre |
| `tipos_documentales` | Tipos de documento | id, serie_id, codigo, formatos_permitidos |
| `retenciones` | Configuración retención | serie_id, anos_gestion, anos_central, disposicion_final |
| `trd_versiones` | Historial de versiones | trd_id, version_anterior, cambios |
| `trd_importaciones` | Log de importaciones | trd_id, tipo, estado, errores |

#### Módulo CCD (Cuadros de Clasificación)

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `cuadros_clasificacion` | CCDs principales | id, codigo, nombre, version, estado |
| `ccd_niveles` | Estructura jerárquica | id, ccd_id, parent_id, tipo_nivel, ruta |
| `ccd_vocabularios` | Vocabulario controlado | id, ccd_id, termino, definicion |
| `ccd_permisos` | Permisos por nivel | ccd_nivel_id, role_id, tipo_permiso |
| `ccd_versiones` | Historial de versiones | ccd_id, version_anterior, cambios |
| `ccd_trd_relaciones` | Relación CCD-TRD | ccd_nivel_id, serie_id, tipo_relacion |

#### Módulo Expedientes

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `expedientes` | Expedientes principales | id, codigo, titulo, estado, serie_id |
| `expediente_documento` | Documentos en expediente | expediente_id, documento_id, orden |
| `expediente_historial` | Cambios de estado | expediente_id, estado_anterior, estado_nuevo |
| `expediente_transferencias` | Transferencias | expediente_id, origen, destino, estado |
| `expediente_valoraciones` | Valoración documental | expediente_id, decision, justificacion |
| `expediente_prestamos` | Préstamos y consultas | expediente_id, solicitante_id, fecha_devolucion |
| `expediente_accesos` | Auditoría de accesos | expediente_id, user_id, tipo_acceso |
| `expediente_indices` | Índices documentales | expediente_id, tipo_indice, estructura |

#### Módulo Documentos

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `documentos` | Documentos principales | id, codigo_documento, titulo, expediente_id, tamano_bytes |
| `firmas_digitales` | Firmas de documentos | documento_id, user_id, tipo_firma, hash_documento |
| `conversiones_formato` | Conversiones de formato | documento_id, formato_origen, formato_destino |


#### Módulo Email

| Tabla | Descripción | Campos Clave |
|-------|-------------|--------------|
| `email_accounts` | Cuentas de correo | id, email, host, port, encryption |
| `email_captures` | Correos capturados | id, account_id, subject, from, captured_at |
| `email_attachments` | Adjuntos de correos | capture_id, filename, documento_id |

### 6.3 Esquema Detallado de Tablas Críticas

#### Tabla `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NULL,
    
    -- Campos adicionales
    documento_identidad VARCHAR(50) NULL,
    tipo_documento ENUM('CC', 'CE', 'Pasaporte', 'TI') DEFAULT 'CC',
    telefono VARCHAR(20) NULL,
    cargo VARCHAR(100) NULL,
    dependencia VARCHAR(100) NULL,
    
    -- Control de acceso
    active BOOLEAN DEFAULT TRUE,
    estado_cuenta ENUM('activo', 'inactivo', 'bloqueado', 'suspendido') DEFAULT 'activo',
    ultimo_acceso TIMESTAMP NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    
    -- Seguridad
    cambio_password_requerido BOOLEAN DEFAULT FALSE,
    fecha_ultimo_cambio_password TIMESTAMP NULL,
    
    -- Timestamps
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tabla `expedientes`

```sql
CREATE TABLE expedientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    
    -- Clasificación
    serie_id BIGINT UNSIGNED NULL,
    subserie_id BIGINT UNSIGNED NULL,
    ccd_nivel_id BIGINT UNSIGNED NULL,
    tipo_expediente ENUM('administrativo', 'contable', 'juridico', 'tecnico', 'historico', 'personal') DEFAULT 'administrativo',
    nivel_acceso ENUM('publico', 'restringido', 'confidencial', 'reservado') DEFAULT 'publico',
    
    -- Ciclo de vida (11 estados)
    estado ENUM(
        'en_tramite', 'activo', 'semiactivo', 'inactivo', 
        'historico', 'en_transferencia', 'transferido',
        'en_valoracion', 'seleccionado_eliminacion', 
        'eliminado', 'conservacion_permanente'
    ) DEFAULT 'en_tramite',
    
    -- Fechas del ciclo
    fecha_apertura DATE NOT NULL,
    fecha_cierre DATE NULL,
    fecha_transferencia_central DATE NULL,
    fecha_transferencia_historico DATE NULL,
    
    -- Retención
    anos_archivo_gestion INT DEFAULT 0,
    anos_archivo_central INT DEFAULT 0,
    disposicion_final ENUM('eliminacion', 'conservacion_total', 'seleccion', 'microfilmacion', 'digitalizacion') NULL,
    
    -- Ubicación
    ubicacion_fisica VARCHAR(255) NULL,
    ubicacion_digital VARCHAR(255) NULL,
    estante VARCHAR(50) NULL,
    caja VARCHAR(50) NULL,
    carpeta VARCHAR(50) NULL,
    
    -- Responsables
    responsable_id BIGINT UNSIGNED NULL,
    dependencia_id BIGINT UNSIGNED NULL,
    
    -- Integridad
    hash_integridad VARCHAR(128) NULL,
    verificado BOOLEAN DEFAULT FALSE,
    fecha_ultima_verificacion TIMESTAMP NULL,
    
    -- Información
    numero_documentos INT DEFAULT 0,
    tamano_total_bytes BIGINT DEFAULT 0,
    palabras_clave JSON NULL,
    metadata JSON NULL,
    notas TEXT NULL,
    
    -- Control
    version INT DEFAULT 1,
    cerrado BOOLEAN DEFAULT FALSE,
    bloqueado BOOLEAN DEFAULT FALSE,
    bloqueado_por BIGINT UNSIGNED NULL,
    fecha_bloqueo TIMESTAMP NULL,
    
    -- Auditoría
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (serie_id) REFERENCES series_documentales(id) ON DELETE SET NULL,
    FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.4 Índices Recomendados

```sql
-- Índices para búsqueda frecuente
CREATE INDEX idx_expedientes_codigo ON expedientes(codigo);
CREATE INDEX idx_expedientes_estado ON expedientes(estado);
CREATE INDEX idx_expedientes_serie ON expedientes(serie_id);
CREATE INDEX idx_expedientes_responsable ON expedientes(responsable_id);
CREATE INDEX idx_expedientes_fechas ON expedientes(fecha_apertura, fecha_cierre);

-- Índices para documentos
CREATE INDEX idx_documentos_hash ON documentos(hash_integridad);
CREATE INDEX idx_documentos_tipo ON documentos(tipo_documental_id);

-- Índices para auditoría
CREATE INDEX idx_auditoria_usuario ON pista_auditorias(user_id);
CREATE INDEX idx_auditoria_fecha ON pista_auditorias(created_at);
CREATE INDEX idx_auditoria_tabla ON pista_auditorias(tabla_afectada);

-- Índices para TRD
CREATE INDEX idx_series_trd ON series_documentales(trd_id);
CREATE INDEX idx_subseries_serie ON subseries_documentales(serie_id);

-- Índices para CCD
CREATE INDEX idx_niveles_ccd ON ccd_niveles(ccd_id);
CREATE INDEX idx_niveles_parent ON ccd_niveles(parent_id);
CREATE INDEX idx_niveles_ruta ON ccd_niveles(ruta);
```

### 6.5 Relaciones Foreign Key

```sql
-- Usuarios y Roles
ALTER TABLE users ADD CONSTRAINT fk_users_role 
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Expedientes
ALTER TABLE expedientes ADD CONSTRAINT fk_expedientes_serie 
    FOREIGN KEY (serie_id) REFERENCES series_documentales(id) ON DELETE SET NULL;

ALTER TABLE expedientes ADD CONSTRAINT fk_expedientes_responsable 
    FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL;

-- Documentos en Expedientes
ALTER TABLE expediente_documento ADD CONSTRAINT fk_expdoc_expediente 
    FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE CASCADE;

ALTER TABLE expediente_documento ADD CONSTRAINT fk_expdoc_documento 
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE;

-- Estructura CCD
ALTER TABLE ccd_niveles ADD CONSTRAINT fk_niveles_ccd 
    FOREIGN KEY (ccd_id) REFERENCES cuadros_clasificacion(id) ON DELETE CASCADE;

ALTER TABLE ccd_niveles ADD CONSTRAINT fk_niveles_parent 
    FOREIGN KEY (parent_id) REFERENCES ccd_niveles(id) ON DELETE CASCADE;
```

---

## 7. Módulos del Sistema

### 7.1 Módulo TRD - Tablas de Retención Documental

#### Descripción
Gestión completa del instrumento archivístico que define series, subseries, tiempos de retención y disposición final de documentos.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Migración** | `database/migrations/2024_10_07_000001_create_trds_table.php` | Crea 7 tablas relacionadas |
| **Modelo** | `app/Models/TRD.php` | Modelo principal |
| **Modelo** | `app/Models/SerieDocumental.php` | Series documentales |
| **Modelo** | `app/Models/SubserieDocumental.php` | Subseries |
| **Modelo** | `app/Models/TipoDocumental.php` | Tipos documentales |
| **Modelo** | `app/Models/Retencion.php` | Configuración de retención |
| **Servicio** | `app/Services/TRDService.php` | Lógica de negocio |
| **Controlador** | `app/Http/Controllers/TRDController.php` | API REST |
| **Frontend** | `resources/js/pages/Admin/TRD/Index.tsx` | Interfaz de usuario |

#### Funcionalidades

```php
// TRDService.php - Métodos principales
class TRDService
{
    public function crear(array $data): TRD;
    public function actualizar(TRD $trd, array $data): TRD;
    public function aprobar(TRD $trd, User $aprobador): TRD;
    public function archivar(TRD $trd): TRD;
    public function crearVersion(TRD $trd): TRD;
    public function agregarSerie(TRD $trd, array $data): SerieDocumental;
    public function agregarSubserie(SerieDocumental $serie, array $data): SubserieDocumental;
    public function configurarRetencion(array $data): Retencion;
    public function importarXML(UploadedFile $archivo): array;
    public function exportarXML(TRD $trd): string;
    public function validarParaAprobacion(TRD $trd): array;
    public function obtenerEstadisticas(TRD $trd): array;
}
```

#### Estados del Ciclo de Vida

```
┌──────────┐    aprobar()    ┌──────────┐
│ BORRADOR │────────────────▶│  ACTIVA  │
└──────────┘                 └────┬─────┘
                                  │
                          desactivar()
                                  │
                                  ▼
                            ┌──────────┐    archivar()    ┌───────────┐
                            │ INACTIVA │─────────────────▶│ ARCHIVADA │
                            └──────────┘                  └───────────┘
```

#### Endpoints API

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/admin/trd` | Listar TRDs |
| POST | `/admin/trd` | Crear TRD |
| GET | `/admin/trd/{id}` | Ver TRD |
| PUT | `/admin/trd/{id}` | Actualizar TRD |
| DELETE | `/admin/trd/{id}` | Eliminar TRD |
| POST | `/admin/trd/{id}/aprobar` | Aprobar TRD |
| POST | `/admin/trd/{id}/archivar` | Archivar TRD |
| POST | `/admin/trd/{id}/version` | Crear nueva versión |
| POST | `/admin/trd/{id}/serie` | Agregar serie |
| POST | `/admin/trd/importar` | Importar XML |
| GET | `/admin/trd/{id}/exportar` | Exportar XML |

#### Ejemplo de Uso

```php
// Crear TRD con series y retención
$trdService = app(TRDService::class);

// 1. Crear TRD
$trd = $trdService->crear([
    'codigo' => 'TRD-2025-001',
    'nombre' => 'Tabla de Retención Principal',
    'version' => '1.0',
    'fecha_vigencia_inicio' => '2025-01-01',
    'fecha_vigencia_fin' => '2029-12-31',
]);

// 2. Agregar Serie
$serie = $trdService->agregarSerie($trd, [
    'codigo' => 'SER-100',
    'nombre' => 'Documentos Administrativos',
    'dependencia' => 'Secretaría General',
]);

// 3. Configurar Retención
$trdService->configurarRetencion([
    'serie_id' => $serie->id,
    'retencion_archivo_gestion' => 5,
    'retencion_archivo_central' => 10,
    'disposicion_final' => 'conservacion_total',
]);

// 4. Aprobar TRD
$trdService->aprobar($trd, auth()->user());
```

---

### 7.2 Módulo CCD - Cuadros de Clasificación Documental

#### Descripción
Estructura jerárquica de 5 niveles (Fondo > Sección > Subsección > Serie > Subserie) para organizar la documentación institucional.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Migración** | `database/migrations/2024_10_07_000002_create_ccd_table.php` | Crea 7 tablas |
| **Modelo** | `app/Models/CCD.php` | Modelo principal |
| **Modelo** | `app/Models/CCDNivel.php` | Niveles jerárquicos (árbol) |
| **Modelo** | `app/Models/CCDVocabulario.php` | Vocabulario controlado |
| **Modelo** | `app/Models/CCDPermiso.php` | Permisos por nivel |
| **Servicio** | `app/Services/CCDService.php` | Lógica de negocio |
| **Controlador** | `app/Http/Controllers/CCDController.php` | API REST |
| **Frontend** | `resources/js/pages/Admin/CCD/Show.tsx` | Árbol interactivo |

#### Estructura Jerárquica

```
📁 Fondo Documental (Nivel 1)
├── 📂 Sección Administrativa (Nivel 2)
│   ├── 📂 Subsección Talento Humano (Nivel 3)
│   │   ├── 📄 Serie: Hojas de Vida (Nivel 4)
│   │   │   └── 📄 Subserie: Contratos (Nivel 5)
│   │   └── 📄 Serie: Nóminas (Nivel 4)
│   └── 📂 Subsección Financiera (Nivel 3)
└── 📂 Sección Técnica (Nivel 2)
```

#### Funcionalidades

```php
// CCDService.php - Métodos principales
class CCDService
{
    public function crear(array $data): CCD;
    public function agregarNivel(CCD $ccd, array $data): CCDNivel;
    public function moverNivel(CCDNivel $nivel, ?CCDNivel $nuevoPadre): CCDNivel;
    public function eliminarNivel(CCDNivel $nivel): bool;
    public function obtenerEstructura(CCD $ccd): array;
    public function calcularRutas(CCD $ccd): void;
    public function validarIntegridad(CCD $ccd): array;
}
```

#### Componente TreeNode (Frontend)

```tsx
// Show.tsx - Componente recursivo para árbol
interface TreeNodeProps {
    nivel: CCDNivel;
    onAddChild: (parentId: number) => void;
    onEdit: (nivel: CCDNivel) => void;
    onDelete: (nivel: CCDNivel) => void;
}

function TreeNode({ nivel, onAddChild, onEdit, onDelete }: TreeNodeProps) {
    const [expanded, setExpanded] = useState(true);
    
    const iconByType = {
        fondo: <Folder className="text-blue-500" />,
        seccion: <Folder className="text-green-500" />,
        subseccion: <Folder className="text-yellow-500" />,
        serie: <FileText className="text-purple-500" />,
        subserie: <FileText className="text-pink-500" />,
    };

    return (
        <div className="ml-4">
            <div className="flex items-center gap-2 py-1 hover:bg-gray-100 rounded">
                {nivel.hijos?.length > 0 && (
                    <button onClick={() => setExpanded(!expanded)}>
                        {expanded ? <ChevronDown /> : <ChevronRight />}
                    </button>
                )}
                {iconByType[nivel.tipo_nivel]}
                <span>{nivel.codigo} - {nivel.nombre}</span>
                <div className="ml-auto opacity-0 group-hover:opacity-100">
                    <Button size="sm" onClick={() => onAddChild(nivel.id)}>+</Button>
                    <Button size="sm" onClick={() => onEdit(nivel)}>✏️</Button>
                    <Button size="sm" onClick={() => onDelete(nivel)}>🗑️</Button>
                </div>
            </div>
            {expanded && nivel.hijos?.map(hijo => (
                <TreeNode key={hijo.id} nivel={hijo} {...props} />
            ))}
        </div>
    );
}
```

---

### 7.3 Módulo Expedientes Electrónicos

#### Descripción
Gestión integral del ciclo de vida de expedientes desde apertura hasta disposición final, incluyendo 11 estados, transferencias, préstamos y valoración.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Migración** | `database/migrations/2024_10_07_000003_create_expedientes_table.php` | Crea 8 tablas |
| **Modelo** | `app/Models/Expediente.php` | Modelo principal (33 campos) |
| **Modelo** | `app/Models/ExpedienteTransferencia.php` | Transferencias |
| **Servicio** | `app/Services/ExpedienteService.php` | Lógica de negocio |
| **Controlador** | `app/Http/Controllers/ExpedienteController.php` | API REST |

#### Estados del Ciclo de Vida (11 estados)

```
┌────────────┐    ┌────────────┐    ┌────────────┐
│ EN TRÁMITE │───▶│   ACTIVO   │───▶│ SEMIACTIVO │
└────────────┘    │(Gestión)   │    │  (Central) │
                  └────────────┘    └─────┬──────┘
                                          │
                  ┌───────────────────────┼───────────────────────┐
                  │                       │                       │
                  ▼                       ▼                       ▼
           ┌────────────┐         ┌────────────┐         ┌──────────────┐
           │  INACTIVO  │         │ HISTÓRICO  │         │EN VALORACIÓN │
           └────────────┘         └────────────┘         └──────┬───────┘
                                                                │
                  ┌───────────────────────┬───────────────────────┤
                  │                       │                       │
                  ▼                       ▼                       ▼
           ┌────────────┐         ┌────────────┐         ┌──────────────┐
           │ ELIMINADO  │         │CONSERVACIÓN│         │  SELECCIÓN   │
           │            │         │ PERMANENTE │         │              │
           └────────────┘         └────────────┘         └──────────────┘
```

#### Funcionalidades

```php
// ExpedienteService.php - Métodos principales
class ExpedienteService
{
    public function crear(array $data): Expediente;
    public function cambiarEstado(Expediente $exp, string $nuevoEstado, ?string $observacion): Expediente;
    public function cerrar(Expediente $expediente): Expediente;
    public function agregarDocumento(Expediente $exp, Documento $doc, array $data): void;
    public function crearTransferencia(Expediente $exp, array $data): ExpedienteTransferencia;
    public function verificarIntegridad(Expediente $expediente): array;
    public function calcularDisposicion(Expediente $expediente): array;
    public function generarCodigo(SerieDocumental $serie): string;
}
```

#### Endpoints API

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/admin/expedientes` | Listar expedientes |
| POST | `/admin/expedientes` | Crear expediente |
| GET | `/admin/expedientes/{id}` | Ver expediente |
| PUT | `/admin/expedientes/{id}` | Actualizar expediente |
| DELETE | `/admin/expedientes/{id}` | Eliminar expediente |
| POST | `/admin/expedientes/{id}/cambiar-estado` | Cambiar estado |
| POST | `/admin/expedientes/{id}/cerrar` | Cerrar expediente |
| POST | `/admin/expedientes/{id}/agregar-documento` | Agregar documento |
| POST | `/admin/expedientes/{id}/transferencia` | Crear transferencia |
| GET | `/admin/expedientes/{id}/verificar-integridad` | Verificar hash |

---

### 7.4 Módulo 2FA - Autenticación de Dos Factores

#### Descripción
Sistema completo de autenticación multifactor con soporte para TOTP (Google Authenticator), Email y SMS.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Modelo** | `app/Models/TwoFactorAuthentication.php` | Configuración 2FA |
| **Modelo** | `app/Models/TwoFactorChallenge.php` | Desafíos pendientes |
| **Modelo** | `app/Models/TwoFactorBackupCode.php` | Códigos de recuperación |
| **Servicio** | `app/Services/TwoFactorAuthenticationService.php` | Lógica 2FA |
| **Controlador** | `app/Http/Controllers/TwoFactorAuthenticationController.php` | Configuración |
| **Controlador** | `app/Http/Controllers/TwoFactorChallengeController.php` | Verificación |
| **Middleware** | `app/Http/Middleware/TwoFactorAuthentication.php` | Protección de rutas |
| **Frontend** | `resources/js/pages/Profile/TwoFactorAuthentication.tsx` | UI configuración |
| **Frontend** | `resources/js/pages/Auth/TwoFactorChallenge.tsx` | UI verificación |

#### Métodos de Autenticación

| Método | Descripción | Validez |
|--------|-------------|---------|
| **TOTP** | Google Authenticator, Authy | 30 segundos |
| **Email** | Código enviado por correo | 5 minutos |
| **SMS** | Código por mensaje (Twilio) | 5 minutos |

#### Flujo de Autenticación

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   LOGIN     │────▶│  ¿2FA       │─NO─▶│  DASHBOARD  │
│  Usuario    │     │  Habilitado?│     │             │
│  + Password │     └──────┬──────┘     └─────────────┘
└─────────────┘            │
                          YES
                           │
                           ▼
                    ┌─────────────┐
                    │  CHALLENGE  │
                    │  Ingrese    │
                    │  código 2FA │
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              ▼            ▼            ▼
        ┌─────────┐  ┌─────────┐  ┌─────────┐
        │  TOTP   │  │  EMAIL  │  │   SMS   │
        │ 6 dígit │  │ 6 dígit │  │ 6 dígit │
        └────┬────┘  └────┬────┘  └────┬────┘
             │            │            │
             └────────────┼────────────┘
                          │
                          ▼
                   ┌─────────────┐
                   │  VALIDAR    │
                   │   CÓDIGO    │
                   └──────┬──────┘
                          │
              ┌───────────┴───────────┐
              │                       │
        ✅ VÁLIDO               ❌ INVÁLIDO
              │                       │
              ▼                       ▼
        ┌─────────────┐         ┌─────────────┐
        │  DASHBOARD  │         │   RETRY     │
        │  (Sesión    │         │  (máx 3     │
        │   2FA 30m)  │         │  intentos)  │
        └─────────────┘         └─────────────┘
```

#### Configuración

```php
// config/twofactor.php
return [
    'enabled' => env('TWO_FACTOR_ENABLED', true),
    
    'methods' => [
        'totp' => [
            'enabled' => true,
            'issuer' => env('APP_NAME'),
            'digits' => 6,
            'window' => 1, // ±30 segundos
        ],
        'email' => [
            'enabled' => true,
            'expiry_minutes' => 5,
            'cooldown_seconds' => 60,
        ],
        'sms' => [
            'enabled' => env('TWILIO_ENABLED', false),
            'expiry_minutes' => 5,
        ],
    ],
    
    'backup_codes' => [
        'count' => 10,
        'length' => 8,
    ],
    
    'session' => [
        'lifetime' => 30, // minutos
    ],
];
```

#### Ejemplo de Uso

```php
// Habilitar 2FA para usuario
$service = app(TwoFactorAuthenticationService::class);

// 1. Generar secreto TOTP
$setup = $service->generateTOTPSecret($user);
// Retorna: ['secret' => '...', 'qr_code' => 'data:image/png;base64,...']

// 2. Confirmar con código del usuario
$confirmed = $service->confirmTOTP($user, $codigo);

// 3. Generar códigos de recuperación
$backupCodes = $service->generateBackupCodes($user);

// 4. Verificar código en login
$valid = $service->verifyCode($user, $codigo);
```

---

### 7.5 Módulo de Búsqueda con Elasticsearch

#### Descripción
Motor de búsqueda de texto completo con operadores booleanos, autocompletado, facetas y highlighting.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Servicio** | `app/Services/ElasticsearchService.php` | Cliente Elasticsearch |
| **Servicio** | `app/Services/SearchService.php` | Lógica de búsqueda |
| **Servicio** | `app/Services/DocumentIndexingService.php` | Indexación |
| **Servicio** | `app/Services/TextExtractionService.php` | Extracción de texto |
| **Observer** | `app/Observers/DocumentoObserver.php` | Auto-indexación |
| **Observer** | `app/Observers/ExpedienteObserver.php` | Auto-indexación |
| **Comando** | `app/Console/Commands/ElasticsearchSetupCommand.php` | Crear índices |
| **Comando** | `app/Console/Commands/ElasticsearchReindexCommand.php` | Reindexar |
| **Controlador** | `app/Http/Controllers/SearchController.php` | API búsqueda |
| **Frontend** | `resources/js/pages/Search/SearchAdvanced.tsx` | UI avanzada |

#### Operadores de Búsqueda

| Operador | Uso | Ejemplo |
|----------|-----|---------|
| `AND` | Todos los términos | `contrato AND 2025` |
| `OR` | Cualquier término | `factura OR recibo` |
| `NOT` | Excluir término | `documento NOT borrador` |
| `*` | Comodín múltiple | `contra*` → contrato, contratación |
| `?` | Comodín simple | `document?` → documento, documentos |
| `""` | Frase exacta | `"acta de reunión"` |
| `=` | Valor exacto | `codigo=EXP-2025-001` |

#### Configuración de Índices

```php
// config/elasticsearch.php
return [
    'hosts' => [env('ELASTICSEARCH_HOST', 'localhost:9200')],
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'sgdea'),
    
    'indices' => [
        'documentos' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'spanish_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'spanish_stop', 'spanish_stemmer'],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'nombre' => ['type' => 'text', 'analyzer' => 'spanish_analyzer'],
                    'contenido' => ['type' => 'text', 'analyzer' => 'spanish_analyzer'],
                    'codigo' => ['type' => 'keyword'],
                    'tipo_documental' => ['type' => 'keyword'],
                    'serie' => ['type' => 'keyword'],
                    'fecha_creacion' => ['type' => 'date'],
                    'created_by' => ['type' => 'keyword'],
                ],
            ],
        ],
    ],
];
```

#### Ejemplo de Búsqueda Avanzada

```php
// SearchService.php
public function busquedaAvanzada(array $params): array
{
    $query = [
        'bool' => [
            'must' => [],
            'should' => [],
            'must_not' => [],
            'filter' => [],
        ],
    ];

    // Operadores booleanos
    foreach ($params['terms'] as $term) {
        switch ($term['operator']) {
            case 'AND':
                $query['bool']['must'][] = ['match' => [$term['field'] => $term['value']]];
                break;
            case 'OR':
                $query['bool']['should'][] = ['match' => [$term['field'] => $term['value']]];
                break;
            case 'NOT':
                $query['bool']['must_not'][] = ['match' => [$term['field'] => $term['value']]];
                break;
        }
    }

    // Filtros de fecha
    if (!empty($params['fecha_desde']) || !empty($params['fecha_hasta'])) {
        $query['bool']['filter'][] = [
            'range' => [
                'fecha_creacion' => [
                    'gte' => $params['fecha_desde'] ?? null,
                    'lte' => $params['fecha_hasta'] ?? null,
                ],
            ],
        ];
    }

    return $this->elasticsearch->search([
        'index' => 'sgdea_documentos',
        'body' => [
            'query' => $query,
            'highlight' => [
                'fields' => ['nombre' => new \stdClass(), 'contenido' => new \stdClass()],
            ],
            'aggs' => [
                'por_serie' => ['terms' => ['field' => 'serie']],
                'por_tipo' => ['terms' => ['field' => 'tipo_documental']],
            ],
        ],
    ]);
}
```

---

### 7.6 Módulo OCR

#### Descripción
Reconocimiento óptico de caracteres con múltiples motores (Tesseract, Google Cloud Vision, Azure) y preprocesamiento de imágenes.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Servicio** | `app/Services/OCR/OCRService.php` | Servicio principal |
| **Interface** | `app/Services/OCR/OCREngineInterface.php` | Contrato motores |
| **Engine** | `app/Services/OCR/TesseractOCREngine.php` | Motor Tesseract |
| **Engine** | `app/Services/OCR/GoogleCloudVisionEngine.php` | Motor Google |
| **Engine** | `app/Services/OCR/AzureVisionEngine.php` | Motor Azure |
| **Servicio** | `app/Services/OCR/ImagePreprocessor.php` | Preprocesamiento |
| **Job** | `app/Jobs/ProcessOCRJob.php` | Procesamiento async |
| **Controlador** | `app/Http/Controllers/OCRController.php` | API OCR |

#### Flujo de Procesamiento OCR

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  DOCUMENTO  │────▶│ PREPROCESS  │────▶│   ENGINE    │
│  (imagen)   │     │ • Escalar   │     │ • Tesseract │
└─────────────┘     │ • Contraste │     │ • Google    │
                    │ • Binarizar │     │ • Azure     │
                    │ • Deskew    │     └──────┬──────┘
                    └─────────────┘            │
                                               ▼
                                        ┌─────────────┐
                                        │   TEXTO     │
                                        │  EXTRAÍDO   │
                                        └──────┬──────┘
                                               │
                    ┌──────────────────────────┼─────────────────────────┐
                    │                          │                         │
                    ▼                          ▼                         ▼
             ┌─────────────┐           ┌─────────────┐           ┌─────────────┐
             │  INDEXAR    │           │  METADATOS  │           │  CÓDIGOS    │
             │ Elasticsearch│           │  Documento  │           │  Barras/QR  │
             └─────────────┘           └─────────────┘           └─────────────┘
```

#### Configuración

```php
// config/ocr.php
return [
    'default_engine' => env('OCR_DEFAULT_ENGINE', 'tesseract'),
    
    'engines' => [
        'tesseract' => [
            'enabled' => true,
            'path' => env('OCR_TESSERACT_PATH', 'tesseract'),
            'languages' => ['spa', 'eng'],
            'psm' => 3, // Page segmentation mode
            'oem' => 3, // OCR Engine mode
        ],
        'google' => [
            'enabled' => env('GOOGLE_VISION_ENABLED', false),
            'credentials' => env('GOOGLE_CLOUD_CREDENTIALS'),
        ],
        'azure' => [
            'enabled' => env('AZURE_VISION_ENABLED', false),
            'endpoint' => env('AZURE_VISION_ENDPOINT'),
            'key' => env('AZURE_VISION_KEY'),
        ],
    ],
    
    'preprocessing' => [
        'scale' => 2.0,        // Escalar imagen 2x
        'contrast' => 1.5,     // Aumentar contraste
        'denoise' => true,     // Reducir ruido
        'deskew' => true,      // Corregir inclinación
        'binarize' => true,    // Blanco y negro
    ],
    
    'queue' => [
        'enabled' => env('OCR_QUEUE_ENABLED', true),
        'connection' => 'database',
        'queue' => 'ocr',
    ],
];
```

---

### 7.7 Módulo de Roles y Permisos

#### Descripción
Sistema de control de acceso granular con 9 roles predefinidos, 33 permisos y herencia jerárquica.

#### Roles del Sistema

| Rol | Nivel | Permisos | Descripción |
|-----|-------|----------|-------------|
| **Super Administrador** | 1 | 33 (todos) | Control total |
| **Administrador** | 2 | 28 | Gestión general |
| **Admin. Seguridad** | 2 | 20 | Seguridad y accesos |
| **Supervisor** | 3 | 23 | Supervisión procesos |
| **Coordinador** | 4 | 19 | Coordinación operativa |
| **Operativo** | 5 | 14 | Operaciones básicas |
| **Consulta** | 6 | 6 | Solo lectura |
| **Auditor** | 3 | 12 | Auditoría (independiente) |
| **Sin Acceso** | 7 | 2 | Usuario nuevo (solo perfil) |

#### Categorías de Permisos

```php
// RolesYPermisosSeeder.php
$categorias = [
    'administracion' => [
        'administracion.dashboard.ver',
        'administracion.configuracion.gestionar',
    ],
    'usuarios' => [
        'usuarios.crear',
        'usuarios.ver',
        'usuarios.editar',
        'usuarios.eliminar',
        'usuarios.importar',
        'usuarios.exportar',
    ],
    'roles' => [
        'roles.gestionar',
        'roles.asignar',
        'permisos.gestionar',
        'permisos.asignar',
    ],
    'trd' => [
        'trd.crear',
        'trd.ver',
        'trd.editar',
        'trd.aprobar',
    ],
    'series' => [
        'series.crear',
        'series.ver',
        'series.editar',
        'series.eliminar',
    ],
    'ccd' => [
        'ccd.crear',
        'ccd.ver',
    ],
    'expedientes' => [
        'expedientes.crear',
        'expedientes.ver',
        'expedientes.editar',
    ],
    'documentos' => [
        'documentos.crear',
        'documentos.ver',
        'documentos.editar',
    ],
    // ... más categorías
];
```

#### Middleware de Permisos

```php
// app/Http/Middleware/PermissionMiddleware.php
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        
        if (!$user) {
            abort(401, 'No autenticado');
        }
        
        if (!$user->hasPermission($permission)) {
            abort(403, 'No tiene permiso para esta acción');
        }
        
        return $next($request);
    }
}

// Uso en rutas
Route::middleware(['auth', 'permission:trd.crear'])->group(function () {
    Route::post('/admin/trd', [TRDController::class, 'store']);
});
```

#### Hook Frontend para Permisos

```tsx
// resources/js/hooks/usePermissions.ts
export function usePermissions() {
    const { auth } = usePage<{ auth: { user: User; permissions: string[] } }>().props;
    
    const hasPermission = (permission: string): boolean => {
        return auth.permissions?.includes(permission) ?? false;
    };
    
    const hasAnyPermission = (permissions: string[]): boolean => {
        return permissions.some(p => hasPermission(p));
    };
    
    const hasAllPermissions = (permissions: string[]): boolean => {
        return permissions.every(p => hasPermission(p));
    };
    
    return { hasPermission, hasAnyPermission, hasAllPermissions };
}

// Uso en componentes
function AdminPanel() {
    const { hasPermission } = usePermissions();
    
    if (!hasPermission('administracion.dashboard.ver')) {
        return <AccessDenied />;
    }
    
    return <Dashboard />;
}
```

---

### 7.8 Módulo Dashboard Ejecutivo

#### Descripción
Panel de control ejecutivo con métricas, KPIs, alertas críticas y visualización dinámica del almacenamiento del sistema.

#### Archivos del Módulo

| Tipo | Archivo | Descripción |
|------|---------|-------------|
| **Controlador** | `app/Http/Controllers/Admin/DashboardEjecutivoController.php` | Lógica del dashboard |
| **Frontend** | `resources/js/pages/admin/dashboard-ejecutivo/index.tsx` | Interfaz de usuario |
| **Servicio PDF** | `app/Services/DashboardPdfService.php` | Exportación a PDF |

#### Métricas Principales

```php
// DashboardEjecutivoController.php - Métricas retornadas
private function obtenerMetricasGenerales()
{
    return [
        'total_documentos' => Documento::count(),
        'total_expedientes' => Expediente::count(),
        'total_usuarios' => User::where('active', true)->count(),
        'total_series' => SerieDocumental::where('activa', true)->count(),
        'almacenamiento_total' => $this->calcularAlmacenamientoTotal(),
        'indices_generados' => IndiceElectronico::count(),
    ];
}
```

#### Visualización Dinámica de Almacenamiento

El sistema muestra el almacenamiento en **MB o GB dinámicamente** según el tamaño:

```php
/**
 * Calcular almacenamiento total (retorna array con valor y unidad)
 */
private function calcularAlmacenamientoTotal()
{
    $total_bytes = Documento::sum('tamano_bytes') ?? 0;
    $total_mb = $total_bytes / (1024 * 1024);
    $total_gb = $total_mb / 1024;
    
    // Si es menor a 1 GB, mostrar en MB
    if ($total_gb < 1) {
        return [
            'valor' => round($total_mb, 2),
            'unidad' => 'MB',
            'bytes' => $total_bytes,
        ];
    }
    
    return [
        'valor' => round($total_gb, 2),
        'unidad' => 'GB',
        'bytes' => $total_bytes,
    ];
}
```

#### Frontend con Unidades Dinámicas

```tsx
// index.tsx - Visualización dinámica
interface AlmacenamientoInfo {
    valor: number;
    unidad: string;
    bytes?: number;
}

// En el componente
<div className="text-2xl font-bold">
    {metricas_generales.almacenamiento_total?.valor ?? 0} {metricas_generales.almacenamiento_total?.unidad ?? 'MB'}
</div>
<p className="text-xs text-muted-foreground">
    Proy. 12m: {tendencias.proyeccion_almacenamiento?.proyeccion_12_meses?.valor ?? 0} {tendencias.proyeccion_almacenamiento?.proyeccion_12_meses?.unidad ?? 'MB'}
</p>
```

#### Proyección de Almacenamiento

El sistema calcula proyecciones basadas en el crecimiento promedio de los últimos 3 meses:

```php
private function calcularProyeccionAlmacenamiento()
{
    $crecimiento_mensual = []; // Últimos 3 meses
    $promedio_crecimiento = array_sum($crecimiento_mensual) / 3;
    
    return [
        'actual' => $this->calcularAlmacenamientoTotal(),
        'proyeccion_3_meses' => $this->formatearAlmacenamiento($proy_3m),
        'proyeccion_6_meses' => $this->formatearAlmacenamiento($proy_6m),
        'proyeccion_12_meses' => $this->formatearAlmacenamiento($proy_12m),
    ];
}
```

#### Endpoints del Dashboard

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/admin/dashboard-ejecutivo` | Vista del dashboard |
| GET | `/admin/dashboard-ejecutivo/exportar-pdf` | Exportar a PDF |
| POST | `/admin/dashboard-ejecutivo/datos-grafico` | Datos para gráficos AJAX |

---

## 8. API REST

### 8.1 Autenticación

Todas las rutas API requieren autenticación mediante Laravel Sanctum.

#### Headers Requeridos

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
X-Requested-With: XMLHttpRequest
```

#### Obtener Token

```bash
# Login y obtener token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Respuesta
{
  "token": "1|abc123...",
  "user": { "id": 1, "name": "Admin", "email": "admin@example.com" }
}
```

### 8.2 Endpoints Principales

#### Usuarios

| Método | Endpoint | Descripción | Permiso |
|--------|----------|-------------|---------|
| GET | `/api/users` | Listar usuarios | `usuarios.ver` |
| POST | `/api/users` | Crear usuario | `usuarios.crear` |
| GET | `/api/users/{id}` | Ver usuario | `usuarios.ver` |
| PUT | `/api/users/{id}` | Actualizar usuario | `usuarios.editar` |
| DELETE | `/api/users/{id}` | Eliminar usuario | `usuarios.eliminar` |

#### TRD

| Método | Endpoint | Descripción | Permiso |
|--------|----------|-------------|---------|
| GET | `/admin/trd` | Listar TRDs | `trd.ver` |
| POST | `/admin/trd` | Crear TRD | `trd.crear` |
| GET | `/admin/trd/{id}` | Ver TRD | `trd.ver` |
| PUT | `/admin/trd/{id}` | Actualizar | `trd.editar` |
| DELETE | `/admin/trd/{id}` | Eliminar | `trd.editar` |
| POST | `/admin/trd/{id}/aprobar` | Aprobar | `trd.aprobar` |
| POST | `/admin/trd/{id}/version` | Nueva versión | `trd.editar` |
| GET | `/admin/trd/{id}/exportar` | Exportar XML | `trd.ver` |
| POST | `/admin/trd/importar` | Importar XML | `trd.crear` |

#### CCD

| Método | Endpoint | Descripción | Permiso |
|--------|----------|-------------|---------|
| GET | `/admin/ccd` | Listar CCDs | `ccd.ver` |
| POST | `/admin/ccd` | Crear CCD | `ccd.crear` |
| GET | `/admin/ccd/{id}` | Ver CCD | `ccd.ver` |
| GET | `/admin/ccd/{id}/estructura` | Árbol completo | `ccd.ver` |
| POST | `/admin/ccd/{id}/nivel` | Agregar nivel | `ccd.crear` |
| PUT | `/admin/ccd/nivel/{id}` | Editar nivel | `ccd.crear` |
| DELETE | `/admin/ccd/nivel/{id}` | Eliminar nivel | `ccd.crear` |

#### Expedientes

| Método | Endpoint | Descripción | Permiso |
|--------|----------|-------------|---------|
| GET | `/admin/expedientes` | Listar | `expedientes.ver` |
| POST | `/admin/expedientes` | Crear | `expedientes.crear` |
| GET | `/admin/expedientes/{id}` | Ver | `expedientes.ver` |
| PUT | `/admin/expedientes/{id}` | Actualizar | `expedientes.editar` |
| POST | `/admin/expedientes/{id}/cambiar-estado` | Cambiar estado | `expedientes.editar` |
| POST | `/admin/expedientes/{id}/cerrar` | Cerrar | `expedientes.editar` |
| POST | `/admin/expedientes/{id}/agregar-documento` | Agregar doc | `expedientes.editar` |

#### Búsqueda

| Método | Endpoint | Descripción | Permiso |
|--------|----------|-------------|---------|
| POST | `/search/simple` | Búsqueda simple | `busqueda.basica` |
| POST | `/search/advanced` | Búsqueda avanzada | `busqueda.avanzada` |
| GET | `/search/autocomplete` | Autocompletado | `busqueda.basica` |

#### 2FA

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/two-factor/settings` | Ver configuración |
| POST | `/two-factor/enable` | Habilitar 2FA |
| POST | `/two-factor/confirm` | Confirmar código |
| POST | `/two-factor/disable` | Deshabilitar 2FA |
| GET | `/two-factor/challenge` | Pantalla verificación |
| POST | `/two-factor/verify` | Verificar código |
| POST | `/two-factor/resend` | Reenviar código |

### 8.3 Ejemplos de Requests

#### Crear TRD

```bash
curl -X POST http://localhost:8000/admin/trd \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "codigo": "TRD-2025-001",
    "nombre": "Tabla de Retención Principal",
    "descripcion": "TRD para gestión documental",
    "version": "1.0",
    "fecha_vigencia_inicio": "2025-01-01",
    "fecha_vigencia_fin": "2029-12-31"
  }'
```

#### Búsqueda Avanzada

```bash
curl -X POST http://localhost:8000/search/advanced \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "terms": [
      {"field": "nombre", "value": "contrato", "operator": "AND"},
      {"field": "contenido", "value": "2025", "operator": "AND"},
      {"field": "tipo", "value": "borrador", "operator": "NOT"}
    ],
    "fecha_desde": "2025-01-01",
    "fecha_hasta": "2025-12-31",
    "serie": "contratos",
    "per_page": 20
  }'
```

### 8.4 Respuestas de Error

```json
// 400 Bad Request
{
  "message": "Datos de entrada inválidos",
  "errors": {
    "email": ["El email es requerido"],
    "nombre": ["El nombre debe tener al menos 3 caracteres"]
  }
}

// 401 Unauthorized
{
  "message": "No autenticado"
}

// 403 Forbidden
{
  "message": "No tiene permiso para esta acción"
}

// 404 Not Found
{
  "message": "Recurso no encontrado"
}

// 500 Internal Server Error
{
  "message": "Error interno del servidor",
  "error": "Descripción del error (solo en desarrollo)"
}
```

---

## 9. Seguridad y Autenticación

### 9.1 Capas de Seguridad

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA 1: HTTPS/TLS                        │
│              Encriptación en tránsito                       │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 2: CSRF                             │
│              Token en formularios                           │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 3: AUTENTICACIÓN                    │
│              Laravel Breeze + Sanctum                       │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 4: 2FA                              │
│              TOTP / Email / SMS                             │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 5: AUTORIZACIÓN                     │
│              Roles + Permisos Granulares                    │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 6: AUDITORÍA                        │
│              Log de todas las acciones                      │
├─────────────────────────────────────────────────────────────┤
│                    CAPA 7: INTEGRIDAD                       │
│              Hash SHA-512 de documentos                     │
└─────────────────────────────────────────────────────────────┘
```

### 9.2 Políticas de Contraseñas

```php
// Validaciones de contraseña
$rules = [
    'password' => [
        'required',
        'string',
        'min:8',                    // Mínimo 8 caracteres
        'regex:/[a-z]/',            // Al menos una minúscula
        'regex:/[A-Z]/',            // Al menos una mayúscula
        'regex:/[0-9]/',            // Al menos un número
        'regex:/[@$!%*?&]/',        // Al menos un especial
        'confirmed',                // Confirmación
    ],
];
```

### 9.3 Rate Limiting

```php
// routes/web.php o RouteServiceProvider
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 9.4 Auditoría

```php
// Modelo PistaAuditoria
Schema::create('pista_auditorias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('accion');           // crear, editar, eliminar, ver, login, etc.
    $table->string('tabla_afectada');   // documentos, expedientes, etc.
    $table->unsignedBigInteger('registro_id')->nullable();
    $table->json('datos_anteriores')->nullable();
    $table->json('datos_nuevos')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
});

// Trait para auditoría automática
trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            PistaAuditoria::registrar('crear', $model);
        });
        
        static::updated(function ($model) {
            PistaAuditoria::registrar('editar', $model);
        });
        
        static::deleted(function ($model) {
            PistaAuditoria::registrar('eliminar', $model);
        });
    }
}
```

### 9.5 Integridad de Documentos

```php
// Cálculo de hash SHA-512
public function calcularHash(Documento $documento): string
{
    $ruta = Storage::path($documento->ruta_archivo);
    return hash_file('sha512', $ruta);
}

// Verificación de integridad
public function verificarIntegridad(Documento $documento): bool
{
    $hashActual = $this->calcularHash($documento);
    return hash_equals($documento->hash_integridad, $hashActual);
}
```

---

## 10. Configuración Avanzada

### 10.1 Variables de Entorno por Ambiente

#### Desarrollo (.env.local)

```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

#### Producción (.env.production)

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp

# Optimizaciones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 10.2 Configuración de Supervisor (Producción)

```ini
; /etc/supervisor/conf.d/sgdea-worker.conf
[program:sgdea-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sgdea/artisan queue:work --queue=elasticsearch,ocr,email-capture --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sgdea/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Comandos supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sgdea-worker:*
sudo supervisorctl status
```

### 10.3 Configuración de Nginx (Producción)

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name sgdea.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name sgdea.example.com;
    root /var/www/sgdea/public;

    # SSL
    ssl_certificate /etc/letsencrypt/live/sgdea.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sgdea.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    # Seguridad
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    # Archivos estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Límite de subida de archivos
    client_max_body_size 50M;
}
```

### 10.4 Tareas Programadas (Cron)

```bash
# Editar crontab
crontab -e

# Agregar scheduler de Laravel
* * * * * cd /var/www/sgdea && php artisan schedule:run >> /dev/null 2>&1
```

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Captura de correos cada 15 minutos
    $schedule->command('email:capture')
             ->everyFifteenMinutes()
             ->withoutOverlapping();
    
    // Limpieza de tokens expirados
    $schedule->command('sanctum:prune-expired --hours=24')
             ->daily();
    
    // Backup diario
    $schedule->command('backup:run')
             ->dailyAt('02:00');
    
    // Verificación de integridad semanal
    $schedule->command('documentos:verificar-integridad')
             ->weekly();
    
    // Limpieza de logs mensuales
    $schedule->command('activitylog:clean')
             ->monthly();
}
```

---

## 11. Comandos Artisan

### 11.1 Comandos Personalizados

| Comando | Descripción |
|---------|-------------|
| `php artisan elasticsearch:setup` | Crear índices de Elasticsearch |
| `php artisan elasticsearch:reindex` | Reindexar documentos y expedientes |
| `php artisan email:capture` | Capturar correos de cuentas configuradas |
| `php artisan two-factor:manage` | Gestionar 2FA de usuarios |
| `php artisan documentos:verificar-integridad` | Verificar hash de documentos |

### 11.2 Comandos de Mantenimiento

```bash
# Limpiar cachés
php artisan optimize:clear

# Regenerar cachés
php artisan optimize

# Ver configuración
php artisan about

# Listar rutas
php artisan route:list

# Ver estado de migraciones
php artisan migrate:status

# Ejecutar tests
php artisan test

# Ejecutar tinker
php artisan tinker
```

### 11.3 Comandos de Queue

```bash
# Iniciar worker
php artisan queue:work

# Worker con cola específica
php artisan queue:work --queue=elasticsearch,ocr

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Limpiar jobs fallidos
php artisan queue:flush
```

### 11.4 Comandos de Desarrollo

```bash
# Crear modelo con migración, factory, seeder y controlador
php artisan make:model NuevoModelo -mfsc

# Crear servicio
php artisan make:class Services/NuevoService

# Crear middleware
php artisan make:middleware NuevoMiddleware

# Crear job
php artisan make:job NuevoJob

# Crear evento y listener
php artisan make:event NuevoEvento
php artisan make:listener NuevoListener --event=NuevoEvento
```

---

## 12. Troubleshooting

### 12.1 Problemas Comunes y Soluciones

#### Error: "SQLSTATE[HY000] [2002] Connection refused"

```bash
# Verificar MySQL está corriendo
sudo systemctl status mysql

# Reiniciar MySQL
sudo systemctl restart mysql

# Verificar credenciales en .env
DB_HOST=127.0.0.1
DB_PORT=3306
```

#### Error: "Class 'Elasticsearch\Client' not found"

```bash
# Instalar cliente Elasticsearch
composer require elasticsearch/elasticsearch

# Verificar instalación
php artisan tinker
>>> new \Elasticsearch\Client(['hosts' => ['localhost:9200']])
```

#### Error: "Elasticsearch connection failed"

```bash
# Verificar Elasticsearch está corriendo
curl http://localhost:9200

# Si usa Docker
docker ps | grep elasticsearch
docker start elasticsearch

# Ver logs
docker logs elasticsearch
```

#### Error: "IMAP extension not loaded"

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-imap
sudo phpenmod imap
sudo systemctl restart php8.1-fpm

# Windows (XAMPP)
# Editar php.ini y descomentar:
extension=imap
```

#### Error: "Tesseract not found"

```bash
# Verificar instalación
which tesseract
tesseract --version

# Configurar ruta en .env
OCR_TESSERACT_PATH=/usr/bin/tesseract
```

#### Error: "Permission denied" en storage

```bash
# Linux
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Verificar
ls -la storage/
```

#### Error: "Too Many Requests" (429)

```bash
# Limpiar rate limiting
php artisan cache:clear

# Ajustar límites en RouteServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(120); // Aumentar límite
});
```

#### Error: "Session expired" frecuente

```env
# Aumentar tiempo de sesión en .env
SESSION_LIFETIME=240

# Verificar driver de sesión
SESSION_DRIVER=database
```

### 12.2 Logs y Diagnóstico

```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver últimos 100 errores
tail -100 storage/logs/laravel.log | grep -A 5 "ERROR"

# Logs de queue
tail -f storage/logs/worker.log

# Logs de Nginx
tail -f /var/log/nginx/error.log

# Logs de MySQL
tail -f /var/log/mysql/error.log
```

### 12.3 Comandos de Diagnóstico

```bash
# Verificar configuración
php artisan config:show database
php artisan config:show mail
php artisan config:show elasticsearch

# Test de conexión a BD
php artisan tinker
>>> DB::connection()->getPdo()

# Test de conexión a Elasticsearch
php artisan tinker
>>> app('elasticsearch')->info()

# Test de envío de email
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@test.com'))
```

---

## 13. Mantenimiento

### 13.1 Backups

```bash
# Backup de base de datos
mysqldump -u root -p archiveycloud > backup_$(date +%Y%m%d).sql

# Backup de archivos
tar -czvf storage_backup_$(date +%Y%m%d).tar.gz storage/app

# Backup completo con Laravel Backup (si está instalado)
php artisan backup:run
```

### 13.2 Actualizaciones

```bash
# 1. Modo mantenimiento
php artisan down

# 2. Backup
mysqldump -u root -p archiveycloud > backup_pre_update.sql

# 3. Actualizar código
git pull origin main

# 4. Actualizar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 5. Ejecutar migraciones
php artisan migrate --force

# 6. Limpiar cachés
php artisan optimize:clear
php artisan optimize

# 7. Reiniciar workers
sudo supervisorctl restart sgdea-worker:*

# 8. Salir de mantenimiento
php artisan up
```

### 13.3 Monitoreo

#### Verificaciones Periódicas

| Verificación | Frecuencia | Comando |
|--------------|------------|---------|
| Espacio en disco | Diario | `df -h` |
| Estado de servicios | Diario | `systemctl status mysql nginx php8.1-fpm` |
| Jobs fallidos | Diario | `php artisan queue:failed` |
| Logs de errores | Diario | `tail -100 storage/logs/laravel.log` |
| Integridad documentos | Semanal | `php artisan documentos:verificar-integridad` |
| Índices Elasticsearch | Semanal | `curl localhost:9200/_cat/indices` |
| Backups | Semanal | Verificar existencia y tamaño |

### 13.4 Limpieza Periódica

```bash
# Limpiar logs antiguos (más de 30 días)
find storage/logs -name "*.log" -mtime +30 -delete

# Limpiar archivos temporales
php artisan cache:clear
php artisan view:clear

# Limpiar sesiones expiradas (si usa BD)
php artisan session:gc

# Limpiar tokens expirados
php artisan sanctum:prune-expired

# Limpiar auditoría antigua (si es necesario)
php artisan activitylog:clean --days=365
```

---

## 📚 Apéndices

### A. Glosario Técnico

| Término | Definición |
|---------|------------|
| **CCD** | Cuadro de Clasificación Documental |
| **TRD** | Tabla de Retención Documental |
| **SGDEA** | Sistema de Gestión Documental Electrónico de Archivo |
| **2FA** | Autenticación de Dos Factores |
| **TOTP** | Time-based One-Time Password |
| **OCR** | Optical Character Recognition |
| **Soft Delete** | Eliminación lógica (marca deleted_at) |
| **Hash** | Huella digital para verificar integridad |

### B. Referencias Normativas

- **Ley 594 de 2000** - Ley General de Archivos
- **Acuerdo AGN 027 de 2006** - Tablas de Retención Documental
- **Acuerdo AGN 004 de 2013** - Cuadros de Clasificación
- **Acuerdo AGN 003 de 2015** - Gestión Documental
- **NTC-ISO 15489** - Gestión de Documentos
- **NTC-ISO 30300** - Sistemas de Gestión para Documentos

### C. Contacto y Soporte

- **Repositorio:** https://github.com/jhanleyder08/ArchiveyCloud
- **Documentación:** Ver archivos `*.md` en raíz del proyecto
- **Issues:** Reportar en GitHub Issues

---

## 📈 Historial de Versiones del Manual

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | Diciembre 2025 | Versión inicial completa |
| 1.0.1 | Diciembre 2025 | Añadido módulo Dashboard Ejecutivo (7.8), visualización dinámica de almacenamiento, actualización estructura tabla documentos |

---

**Fin del Manual Técnico de Implementación**  
**Versión 1.0.1 - Diciembre 2025**

*Generado automáticamente consolidando la documentación existente del proyecto ArchiveyCloud SGDEA.*

