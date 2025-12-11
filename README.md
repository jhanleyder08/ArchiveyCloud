<p align="center">
  <img src="public/logo.svg" alt="ArchiveyCloud Logo" width="200">
</p>

<h1 align="center">🏛️ ArchiveyCloud SGDEA</h1>

<p align="center">
  <strong>Sistema de Gestión Documental Electrónico de Archivo</strong><br>
  <em>Solución integral para la gestión del ciclo de vida documental conforme a normativa colombiana</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version">
  <img src="https://img.shields.io/badge/PHP-8.1+-purple.svg" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel">
  <img src="https://img.shields.io/badge/React-18.x-61DAFB.svg" alt="React">
  <img src="https://img.shields.io/badge/TypeScript-5.x-3178C6.svg" alt="TypeScript">
  <img src="https://img.shields.io/badge/Estado-85%25%20Completado-green.svg" alt="Estado">
  <img src="https://img.shields.io/badge/Licencia-Propietario-yellow.svg" alt="Licencia">
</p>

<p align="center">
  <a href="#-características">Características</a> •
  <a href="#-arquitectura">Arquitectura</a> •
  <a href="#-módulos">Módulos</a> •
  <a href="#-instalación">Instalación</a> •
  <a href="#-documentación">Documentación</a>
</p>

---

## 📋 Descripción

**ArchiveyCloud** es un **Sistema de Gestión Documental Electrónico de Archivo (SGDEA)** de nivel empresarial, diseñado para cumplir con la normativa archivística colombiana y estándares internacionales. Proporciona una solución integral para la captura, clasificación, almacenamiento, búsqueda, preservación y disposición final de documentos electrónicos.

### 🎯 ¿Por qué ArchiveyCloud?

| Característica | Beneficio |
|---------------|-----------|
| **📜 Cumplimiento Normativo** | Adherencia a Ley 594/2000, Acuerdos AGN, ISO 15489 |
| **🔒 Seguridad Empresarial** | 7 capas de seguridad incluyendo 2FA y auditoría completa |
| **🔍 Búsqueda Potente** | Motor Elasticsearch con operadores booleanos y OCR |
| **📊 Gestión Completa** | TRD, CCD, Expedientes, Transferencias, Valoración |
| **⚡ Tecnología Moderna** | Laravel 11 + React 18 + TypeScript + Inertia.js |
| **📱 Responsive** | Interfaz adaptable a cualquier dispositivo |

---

## ✨ Características

### 🏆 Módulos Principales

\`\`\`
┌─────────────────────────────────────────────────────────────────────────┐
│                         ARCHIVEYCLOUD SGDEA                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  📋 TRD                    🗂️ CCD                    📁 EXPEDIENTES     │
│  ├─ Series documentales    ├─ Estructura jerárquica  ├─ 11 estados      │
│  ├─ Subseries              ├─ 5 niveles              ├─ Transferencias  │
│  ├─ Retención              ├─ Vocabulario            ├─ Préstamos       │
│  ├─ Disposición final      ├─ Permisos granulares    ├─ Valoración      │
│  └─ Versionamiento         └─ Relaciones TRD         └─ Integridad      │
│                                                                         │
│  📄 DOCUMENTOS             🔍 BÚSQUEDA               👥 USUARIOS        │
│  ├─ Captura múltiple       ├─ Elasticsearch          ├─ 9 roles         │
│  ├─ OCR inteligente        ├─ Operadores AND/OR/NOT  ├─ 33 permisos     │
│  ├─ Firmas digitales       ├─ Autocompletado         ├─ 2FA (TOTP/Email)│
│  ├─ Hash SHA-512           ├─ Facetas y filtros      ├─ Soft delete     │
│  └─ Versionado             └─ Highlighting           └─ Auditoría       │
│                                                                         │
│  📧 EMAIL                  📊 DASHBOARD              🔐 SEGURIDAD       │
│  ├─ Captura IMAP/POP3      ├─ KPIs ejecutivos        ├─ CSRF/XSS        │
│  ├─ Procesamiento adjuntos ├─ Gráficos interactivos  ├─ Rate limiting   │
│  ├─ Filtros personalizados ├─ Cumplimiento TRD       ├─ Encriptación    │
│  └─ Jobs asíncronos        └─ Exportación PDF/Excel  └─ Logs completos  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
\`\`\`

### ✅ Estado de Implementación

| Módulo | Estado | Progreso |
|--------|--------|----------|
| 📋 TRD - Tablas de Retención Documental | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 🗂️ CCD - Cuadros de Clasificación | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 📁 Expedientes Electrónicos | ✅ Backend Completo | ![100%](https://progress-bar.dev/100) |
| 🔐 Autenticación 2FA | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 🔍 Búsqueda Elasticsearch | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 📷 Sistema OCR | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 📧 Captura de Correos | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 👥 Roles y Permisos | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 📊 Dashboard Ejecutivo | ✅ Completo | ![100%](https://progress-bar.dev/100) |
| 📝 Auditoría Completa | ✅ Completo | ![100%](https://progress-bar.dev/100) |

**Estado General del Proyecto: ~85% Completado** 🚀

---

## 🏗️ Arquitectura

### Stack Tecnológico

<table>
<tr>
<td valign="top" width="50%">

#### 🔧 Backend
| Tecnología | Versión |
|------------|---------|
| PHP | 8.1+ |
| Laravel | 11.x |
| Eloquent ORM | - |
| Laravel Sanctum | - |
| Laravel Breeze | - |

#### 🗄️ Base de Datos
| Tecnología | Uso |
|------------|-----|
| MySQL | Principal |
| Elasticsearch | Búsqueda |
| Redis | Colas (opcional) |

</td>
<td valign="top" width="50%">

#### 🎨 Frontend
| Tecnología | Versión |
|------------|---------|
| React | 18.x |
| TypeScript | 5.x |
| Inertia.js | 1.x |
| TailwindCSS | 3.x |
| Radix UI | - |
| Recharts | - |

#### 🔌 Servicios
| Servicio | Uso |
|----------|-----|
| Tesseract OCR | Reconocimiento texto |
| SMTP/IMAP | Correos |
| Google Cloud Vision | OCR (opcional) |

</td>
</tr>
</table>

### Diagrama de Arquitectura

\`\`\`
┌──────────────────────────────────────────────────────────────────┐
│                       CLIENTE (Browser)                          │
│                  React 18 + TypeScript + Inertia.js              │
└─────────────────────────────┬────────────────────────────────────┘
                              │ HTTPS
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                    SERVIDOR WEB (Apache/Nginx)                    │
└─────────────────────────────┬────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                      APLICACIÓN LARAVEL 11                        │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  │
│  │Controllers │  │  Services  │  │   Models   │  │ Middleware │  │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  │
│  │    Jobs    │  │  Observers │  │   Events   │  │  Listeners │  │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘  │
└───────┬──────────────┬───────────────┬───────────────┬───────────┘
        │              │               │               │
        ▼              ▼               ▼               ▼
┌──────────────┐ ┌────────────┐ ┌────────────┐ ┌──────────────────┐
│    MySQL     │ │Elasticsearch│ │   Redis    │ │  File Storage    │
│   40+ tablas │ │   Search   │ │   Queue    │ │  (local/S3)      │
└──────────────┘ └────────────┘ └────────────┘ └──────────────────┘
\`\`\`

### Patrones de Diseño Implementados

- ✅ **MVC** - Estructura principal Laravel
- ✅ **Service Layer** - Lógica de negocio encapsulada
- ✅ **Repository Pattern** - Abstracción de acceso a datos
- ✅ **Observer Pattern** - Auto-indexación en Elasticsearch
- ✅ **Strategy Pattern** - Múltiples motores OCR
- ✅ **Middleware Pattern** - Seguridad y autenticación
- ✅ **Event-Driven** - Notificaciones y auditoría

---

## 📦 Módulos en Detalle

### 📋 TRD - Tablas de Retención Documental

Gestión completa del instrumento archivístico que define el ciclo de vida documental.

\`\`\`php
// Funcionalidades principales
- Creación y edición de TRDs
- Gestión de series y subseries documentales
- Configuración de tiempos de retención
- Definición de disposición final (5 opciones)
- Versionamiento con historial completo
- Importación/Exportación XML
- Aprobación con workflow
\`\`\`

### 🗂️ CCD - Cuadros de Clasificación Documental

Estructura jerárquica de 5 niveles para organización documental.

\`\`\`
📁 Fondo (Nivel 1)
├── 📂 Sección (Nivel 2)
│   ├── 📂 Subsección (Nivel 3)
│   │   ├── 📄 Serie (Nivel 4)
│   │   │   └── 📄 Subserie (Nivel 5)
\`\`\`

- Árbol interactivo con expand/collapse
- Vocabulario controlado
- Permisos granulares por nivel
- Relaciones con TRD

### 📁 Expedientes Electrónicos

Gestión integral del ciclo de vida con **11 estados**:

\`\`\`
En Trámite → Activo → Semiactivo → Inactivo → Histórico
                ↓
         En Transferencia → Transferido
                ↓
         En Valoración → Selección → Eliminación/Conservación
\`\`\`

- Transferencias entre archivos
- Préstamos y consultas
- Valoración documental
- Verificación de integridad (SHA-512)

### �� Autenticación de Dos Factores (2FA)

Sistema completo de seguridad multi-factor.

| Método | Descripción |
|--------|-------------|
| **TOTP** | Google Authenticator, Authy (códigos 30 seg) |
| **Email** | Código por correo (válido 5 min) |
| **SMS** | Código por mensaje (Twilio) |
| **Backup** | 10 códigos de recuperación |

### 🔍 Búsqueda con Elasticsearch

Motor de búsqueda de texto completo con capacidades avanzadas.

\`\`\`
Operadores soportados:
├── AND  → contrato AND 2025
├── OR   → factura OR recibo
├── NOT  → documento NOT borrador
├── *    → contra* (comodín)
├── ""   → "frase exacta"
└── =    → codigo=EXP-001
\`\`\`

- Autocompletado inteligente
- Highlighting de resultados
- Facetas y agregaciones
- Indexación automática

### 👥 Sistema de Roles y Permisos

Control de acceso granular con **9 roles** y **33 permisos**.

| Rol | Nivel | Permisos |
|-----|-------|----------|
| Super Administrador | 1 | 33 (todos) |
| Administrador | 2 | 28 |
| Admin. Seguridad | 2 | 20 |
| Supervisor | 3 | 23 |
| Coordinador | 4 | 19 |
| Operativo | 5 | 14 |
| Consulta | 6 | 6 |
| Auditor | 3 | 12 |
| Sin Acceso | 7 | 2 |

---

## 🚀 Instalación

### Requisitos Previos

| Software | Versión Mínima |
|----------|----------------|
| PHP | 8.1+ |
| Composer | 2.x |
| Node.js | 18.x |
| MySQL | 8.0+ |
| Git | 2.x |

### Extensiones PHP Requeridas

\`\`\`ini
pdo_mysql, mbstring, openssl, tokenizer, xml, 
ctype, json, bcmath, fileinfo, gd, imap
\`\`\`

### Instalación Rápida

\`\`\`bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/ArchiveyCloud.git
cd ArchiveyCloud

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar .env (base de datos, mail, etc.)
nano .env

# 5. Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed --class=RolesYPermisosSeeder

# 6. Compilar assets
npm run build

# 7. Iniciar servidor
php artisan serve
\`\`\`

### Configuración Adicional (Opcional)

\`\`\`bash
# Elasticsearch (búsqueda avanzada)
docker run -d --name elasticsearch -p 9200:9200 \\
  -e "discovery.type=single-node" \\
  docker.elastic.co/elasticsearch/elasticsearch:8.11.0

php artisan elasticsearch:setup
php artisan elasticsearch:reindex

# Tesseract OCR (reconocimiento de texto)
sudo apt install tesseract-ocr tesseract-ocr-spa

# Workers de cola
php artisan queue:work --queue=elasticsearch,ocr,email-capture
\`\`\`

### Variables de Entorno Principales

\`\`\`env
# Aplicación
APP_NAME="ArchiveyCloud SGDEA"
APP_URL=http://localhost:8000

# Base de datos
DB_DATABASE=archiveycloud
DB_USERNAME=root
DB_PASSWORD=

# Elasticsearch
ELASTICSEARCH_HOST=localhost:9200

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io

# OCR
OCR_TESSERACT_PATH=tesseract
\`\`\`

### ⚠️ Configuración de APP_URL para Trabajo en Equipo

**Cada desarrollador debe configurar su propia \`APP_URL\` en el archivo \`.env\`:**

- **Desarrollo local (recomendado)**: \`APP_URL=http://127.0.0.1:8000\`
- **Acceso desde red local**: \`APP_URL=http://TU_IP_LOCAL:8000\`

**Para encontrar tu IP local:**
- **Windows**: \`ipconfig\` (buscar "IPv4 Address")
- **Linux/Mac**: \`ifconfig\` o \`ip addr\`

**Después de cambiar APP_URL, siempre ejecuta:**
\`\`\`bash
php artisan ziggy:generate
npm run build
\`\`\`

---

## 📚 Documentación

### Manuales Disponibles

| Manual | Descripción | Líneas |
|--------|-------------|--------|
| 📘 [MANUAL_USUARIO_SGDEA.md](MANUAL_USUARIO_SGDEA.md) | Guía completa para usuarios finales | 1,071 |
| 📗 [MANUAL_IMPLEMENTACION_SGDEA.md](MANUAL_IMPLEMENTACION_SGDEA.md) | Manual técnico para desarrolladores | 2,454 |
| 📙 [API_DOCUMENTATION.md](API_DOCUMENTATION.md) | Documentación de endpoints REST | 492 |

### Documentación Técnica Adicional

\`\`\`
📁 Documentación del Proyecto
├── IMPLEMENTACION_TRD_COMPLETA.md      # Módulo TRD
├── IMPLEMENTACION_CCD_COMPLETA.md      # Módulo CCD
├── IMPLEMENTACION_EXPEDIENTES.md       # Módulo Expedientes
├── IMPLEMENTACION_2FA_COMPLETADA.md    # Sistema 2FA
├── ESTRUCTURA_USUARIOS_PERMISOS.md     # Roles y permisos
├── GUIA_INSTALACION.md                 # Instalación detallada
└── PLAN_IMPLEMENTACION_COMPLETO.md     # Roadmap del proyecto
\`\`\`

---

## 🗄️ Estructura de Base de Datos

### Resumen de Tablas (40+)

| Categoría | Tablas | Descripción |
|-----------|--------|-------------|
| **Usuarios** | 8 | users, roles, permisos, 2FA, auditoría |
| **TRD** | 7 | trds, series, subseries, tipos, retenciones, versiones |
| **CCD** | 7 | ccds, niveles, vocabularios, permisos, relaciones |
| **Expedientes** | 8 | expedientes, documentos, historial, transferencias, préstamos |
| **Email** | 3 | cuentas, capturas, adjuntos |
| **Sistema** | 7+ | configuración, logs, sesiones, jobs |

### Diagrama ER Simplificado

\`\`\`
USERS ─────────► ROLES ─────────► PERMISOS
  │                                   
  ▼                                   
DOCUMENTOS ◄───► EXPEDIENTES ◄───► SERIES ◄───► TRD
  │                   │                │
  ▼                   ▼                ▼
FIRMAS           TRANSFERENCIAS    RETENCIONES
\`\`\`

---

## 🔒 Seguridad

### 7 Capas de Protección

\`\`\`
┌─────────────────────────────────────────┐
│ Capa 1: HTTPS/TLS (Encriptación)        │
├─────────────────────────────────────────┤
│ Capa 2: CSRF Token (Formularios)        │
├─────────────────────────────────────────┤
│ Capa 3: Autenticación (Breeze/Sanctum)  │
├─────────────────────────────────────────┤
│ Capa 4: 2FA (TOTP/Email/SMS)            │
├─────────────────────────────────────────┤
│ Capa 5: Autorización (Roles/Permisos)   │
├─────────────────────────────────────────┤
│ Capa 6: Auditoría (Logs completos)      │
├─────────────────────────────────────────┤
│ Capa 7: Integridad (Hash SHA-512)       │
└─────────────────────────────────────────┘
\`\`\`

### Características de Seguridad

- ✅ Contraseñas hasheadas con BCrypt
- ✅ Rate limiting en APIs y login
- ✅ Protección XSS y SQL Injection
- ✅ Soft delete para preservar historial
- ✅ Auditoría completa de acciones
- ✅ Tokens de sesión seguros
- ✅ Verificación de integridad documental

---

## 📊 Métricas del Proyecto

### Estadísticas de Código

| Métrica | Valor |
|---------|-------|
| **Archivos PHP** | 100+ |
| **Componentes React** | 50+ |
| **Modelos Eloquent** | 48 |
| **Controladores** | 25+ |
| **Servicios** | 15+ |
| **Migraciones** | 30+ |
| **Líneas de Código** | ~25,000+ |
| **Líneas Documentación** | ~5,000+ |

### Requerimientos Cumplidos

- ✅ **111** requerimientos completados
- 🟡 **38** requerimientos parciales
- ⏳ **20** requerimientos pendientes

---

## 🛠️ Comandos Útiles

\`\`\`bash
# Desarrollo
npm run dev                    # Compilar con hot reload
php artisan serve              # Servidor de desarrollo

# Producción
npm run build                  # Compilar para producción
php artisan optimize           # Optimizar cachés

# Base de datos
php artisan migrate            # Ejecutar migraciones
php artisan db:seed            # Ejecutar seeders

# Elasticsearch
php artisan elasticsearch:setup    # Crear índices
php artisan elasticsearch:reindex  # Reindexar datos

# Colas
php artisan queue:work         # Iniciar workers

# Mantenimiento
php artisan optimize:clear     # Limpiar cachés
php artisan route:list         # Ver rutas disponibles
\`\`\`

---

## 🔄 Actualización del Proyecto

Cuando recibas cambios del repositorio:

\`\`\`bash
# 1. Actualizar código
git pull origin main

# 2. Actualizar dependencias
composer install
npm install

# 3. Regenerar rutas (si APP_URL cambió)
php artisan ziggy:generate

# 4. Recompilar assets
npm run build

# 5. Ejecutar migraciones (si hay nuevas)
php artisan migrate
\`\`\`

---

## 🐛 Solución de Problemas

**Error: "Could not resolve ./ziggy"**
\`\`\`bash
php artisan ziggy:generate
\`\`\`

**Error: "ERR_CONNECTION_TIMED_OUT"**
- Verifica que \`APP_URL\` en \`.env\` coincida con la URL que estás usando
- Regenera Ziggy: \`php artisan ziggy:generate\`
- Recompila assets: \`npm run build\`

**Las rutas no funcionan correctamente**
\`\`\`bash
php artisan config:clear && php artisan cache:clear
php artisan ziggy:generate
\`\`\`

---

## 🤝 Contribución

### Para Desarrolladores

1. Fork el repositorio
2. Crea una rama (\`git checkout -b feature/NuevaCaracteristica\`)
3. Commit tus cambios (\`git commit -m 'Agregar nueva característica'\`)
4. Push a la rama (\`git push origin feature/NuevaCaracteristica\`)
5. Abre un Pull Request

### Estándares de Código

- PHP: PSR-12
- TypeScript: ESLint + Prettier
- Commits: Conventional Commits
- Documentación: Markdown

---

## 📞 Soporte

| Canal | Contacto |
|-------|----------|
| 📧 Email | soporte@archiveycloud.com |
| 🐛 Issues | [GitHub Issues](https://github.com/tu-usuario/ArchiveyCloud/issues) |
| 📖 Docs | Ver archivos \`.md\` en el proyecto |

---

## 📜 Cumplimiento Normativo

ArchiveyCloud cumple con:

| Normativa | Descripción |
|-----------|-------------|
| **Ley 594/2000** | Ley General de Archivos de Colombia |
| **Acuerdo AGN 027/2006** | Tablas de Retención Documental |
| **Acuerdo AGN 004/2013** | Cuadros de Clasificación |
| **Acuerdo AGN 003/2015** | Gestión Documental |
| **NTC-ISO 15489** | Gestión de Documentos |
| **NTC-ISO 30300** | Sistemas de Gestión para Documentos |

---

## 📝 Notas Importantes

- **NUNCA** subas el archivo \`.env\` al repositorio (está en \`.gitignore\`)
- **SIEMPRE** usa \`.env.example\` como base para tu \`.env\`
- **RECUERDA** ejecutar \`php artisan ziggy:generate\` después de cambiar \`APP_URL\`
- El archivo \`ziggy.js\` se genera automáticamente y NO debe ser editado manualmente

---

## 📄 Licencia

Este proyecto es software propietario. Todos los derechos reservados.

Copyright © 2025 ArchiveyCloud

---

<p align="center">
  <strong>Desarrollado con ❤️ para la gestión documental colombiana</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Made%20with-Laravel-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Made%20with-React-61DAFB?style=for-the-badge&logo=react" alt="React">
  <img src="https://img.shields.io/badge/Made%20with-TypeScript-3178C6?style=for-the-badge&logo=typescript" alt="TypeScript">
</p>

<p align="center">
  ⭐ Si este proyecto te resulta útil, considera darle una estrella en GitHub ⭐
</p>
