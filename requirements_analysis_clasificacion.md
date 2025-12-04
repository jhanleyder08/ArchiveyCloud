# SGDEA - Análisis de Requerimientos: Clasificación y Organización Documental

## Resumen Ejecutivo
Este documento analiza los requerimientos funcionales de clasificación y organización documental para el Sistema de Gestión Documental Electrónico de Archivo (SGDEA).

## Clasificación de Requerimientos

### 1. Tablas de Retención Documental (TRD)

#### 1.1 Gestión de TRD
- **REQ-CL-001**: Creación, importación, parametrización, automatización, administración y versionamiento de TRD
  - Plantillas predefinidas
  - Asistentes de configuración
  - Carga de archivos planos
  - Mecanismos alternativos de administración

#### 1.2 Campos Opcionales de TRD
- **REQ-CL-002**: Campos asociados opcionales:
  - Descripción y/o justificación
  - Versión de la TRD
  - Fecha de actualización en el sistema
  - Identificador único al crear

#### 1.3 Consistencia y Versionamiento
- **REQ-CL-003**: Garantía de mantenimiento de criterios de tiempos y disposición final por versión
- **REQ-CL-004**: Selección y uso de diferentes versiones por usuarios autorizados

#### 1.4 Validación de TRD
- **REQ-CL-005**: Validación de información ingresada mediante:
  - Generación de alertas
  - Asistentes paso a paso
  - Listas desplegables
  - Listas de chequeo
  - Ventanas de ayuda
  - Detección de información similar/igual

### 2. Cuadro de Clasificación Documental (CCD)

#### 2.1 Estructura y Organización
- **REQ-CL-006**: Representación de organización de expedientes y documentos con metadatos
- **REQ-CL-007**: Múltiples niveles para el esquema de CCD
- **REQ-CL-008**: Control exclusivo por rol administrador con capacidades de:
  - Agregar elementos
  - Modificar estructura
  - Reorganizar elementos

#### 2.2 Vocabulario y Normalización
- **REQ-CL-009**: Asignación de vocabulario controlado y normalizado compatible con:
  - Normas nacionales
  - Estándares internacionales

### 3. Expedientes Electrónicos

#### 3.1 Generación y Componentes
- **REQ-CL-010**: Generación de expedientes electrónicos con componentes:
  - Documento electrónico
  - Foliado
  - Índice firmado
  - Metadatos

#### 3.2 Ciclo de Vida del Expediente
- **REQ-CL-011**: Cierre de expediente (manual o automático) al finalizar trámite
- **REQ-CL-012**: Restricción de adición/supresión tras cierre
- **REQ-CL-013**: Excepción de reapertura por:
  - Disposiciones legales/administrativas
  - Perfil administrativo
  - Registro en pistas de auditoría con justificación

#### 3.3 Expedientes Híbridos
- **REQ-CL-014**: Ingreso de datos de localización (referencia cruzada al físico)
- **REQ-CL-015**: Metadatos de ubicación para unidades documentales

### 4. Gestión de Documentos

#### 4.1 Asociación y Herencia
- **REQ-CL-016**: Herencia de metadatos de serie/subserie
- **REQ-CL-017**: Herencia de tiempos de conservación de TRD
- **REQ-CL-018**: Asociación obligatoria a TRD configurada

#### 4.2 Identificación y Numeración
- **REQ-CL-019**: Número único de identificación al cargar documento
- **REQ-CL-020**: Registro de fecha/hora de carga como metadatos

#### 4.3 Reubicación y Clasificación
- **REQ-CL-021**: Reubicación de carpetas/documentos manteniendo:
  - Metadatos
  - Atributos
  - Permisos
- **REQ-CL-022**: Registro de razones de reubicación como metadato
- **REQ-CL-023**: Ubicación múltiple sin duplicación física

### 5. Seguridad y Permisos

#### 5.1 Control de Acceso
- **REQ-CL-024**: Acceso a expedientes según roles y permisos
- **REQ-CL-025**: Niveles de seguridad configurables por entidad

#### 5.2 Protección de Integridad
- **REQ-CL-026**: Impedimento de eliminación de expedientes/contenido
- **REQ-CL-027**: Excepciones de eliminación:
  - Según TRD establecidas
  - Por rol administrativo en procedimiento auditado

### 6. Firmas Electrónicas y Autenticidad

#### 6.1 Firmas Electrónicas
- **REQ-CL-028**: Incorporación de firma electrónica en índices
- **REQ-CL-029**: Múltiples firmas electrónicas/digitales en documentos

#### 6.2 Integridad y Autenticidad
- **REQ-CL-030**: Cotejo de composición de documentos electrónicos
- **REQ-CL-031**: Aseguramiento de integridad y autenticidad

### 7. Importación y Exportación

#### 7.1 Importación
- **REQ-CL-032**: Importación total/parcial de TRD en formato abierto y editable
- **REQ-CL-033**: Importación de metadatos asociados
- **REQ-CL-034**: Validación y reporte de errores de estructura/formato
- **REQ-CL-035**: Transferencia de estructura TRD mediante XML

#### 7.2 Exportación
- **REQ-CL-036**: Exportación total/parcial de TRD con metadatos y pistas de auditoría
- **REQ-CL-037**: Exportación de directorio de expedientes/carpetas por serie
- **REQ-CL-038**: Exportación de historial de eventos
- **REQ-CL-039**: Exportación de índice electrónico a XML

#### 7.3 Registros y Reportes
- **REQ-CL-040**: Generación de reportes de importación/exportación
- **REQ-CL-041**: Registro en pistas de auditoría

### 8. Auditoría y Trazabilidad

#### 8.1 Historial de Eventos
- **REQ-CL-042**: Registro de todas las acciones en historial consultable
- **REQ-CL-043**: Trazabilidad completa mostrando: qué, quién, cuándo, cómo

#### 8.2 Pistas de Auditoría
- **REQ-CL-044**: Registro en pista de auditoría para reubicaciones
- **REQ-CL-045**: Registro de reaperturas de expedientes con justificación

### 9. Integración y Conversión

#### 9.1 Integración de Sistemas
- **REQ-CL-046**: Integración con servidores de correo electrónico

#### 9.2 Conversión de Formatos
- **REQ-CL-047**: Conversión a formatos establecidos por el Archivo General de la Nación

### 10. Administración y Reportes

#### 10.1 Herramientas Administrativas
- **REQ-CL-048**: Informes estadísticos de actividad en TRD
- **REQ-CL-049**: Modificación de tiempos de retención para series/expedientes

## Análisis de Dependencias

### Dependencias Críticas
1. **TRD como base**: Todos los documentos deben asociarse a una TRD
2. **CCD como estructura**: Define la organización jerárquica
3. **Roles y permisos**: Impactan todas las funcionalidades
4. **Metadatos**: Base para búsqueda y gestión

### Componentes Transversales
- Sistema de metadatos
- Pistas de auditoría
- Gestión de roles y permisos
- Validaciones y alertas

## Recomendaciones de Implementación

### Fase 1: Fundamentos
1. Diseño del modelo de datos para TRD y CCD
2. Sistema básico de metadatos
3. Gestión de roles y permisos

### Fase 2: Funcionalidades Core
1. CRUD de TRD con validaciones
2. Gestión de expedientes electrónicos
3. Sistema de auditoría básico

### Fase 3: Funcionalidades Avanzadas
1. Importación/exportación
2. Firmas electrónicas
3. Integración con sistemas externos

## Consideraciones Técnicas

### Estándares a Considerar
- XML para intercambio de datos
- Formatos del Archivo General de la Nación
- Normas nacionales e internacionales de archivo

### Aspectos de Seguridad
- Integridad documental
- Autenticidad
- Control de acceso granular
- Auditoría completa

---

**Fecha de análisis**: 2025-09-12  
**Categoría**: Clasificación y Organización Documental  
**Total de requerimientos identificados**: 49
