# SGDEA - Análisis de Requerimientos: Requerimientos No Funcionales

## Resumen Ejecutivo
Este documento analiza los requerimientos no funcionales para el Sistema de Gestión Documental Electrónico de Archivo (SGDEA). Estos requerimientos definen las características de calidad del sistema, incluyendo rendimiento, disponibilidad, usabilidad, escalabilidad y otras propiedades técnicas que determinan la experiencia del usuario y la viabilidad operacional del sistema.

## Clasificación de Requerimientos

### 1. Disponibilidad y Confiabilidad

#### 1.1 Disponibilidad del Sistema
- **REQ-NF-002**: Disponibilidad 24/7/365
  - **Especificación**: 24 horas del día, 7 días de la semana, 365 días del año
  - **SLA objetivo**: 99.9% de uptime anual
  - **Implicaciones técnicas**:
    - Arquitectura de alta disponibilidad
    - Redundancia en componentes críticos
    - Balanceadores de carga
    - Monitoreo continuo
    - Procedimientos de failover automático

#### 1.2 Tiempo de Inactividad Permitido
- **REQ-NF-001**: Límites de inactividad no prevista
  - **Especificaciones métricas**:
    - Máximo 10 horas por trimestre
    - Máximo 40 horas por año
  - **Cálculo de disponibilidad**:
    - Trimestral: 99.95% (10h/2160h)
    - Anual: 99.54% (40h/8760h)
  - **Consideraciones**:
    - Solo incluye inactividad no planificada
    - Mantenimientos programados no cuentan
    - Medición continua requerida

#### 1.3 Recuperación ante Fallos
- **REQ-NF-003**: Tiempo de recuperación objetivo (RTO)
  - **Meta**: Menos de 2 horas para recuperación completa
  - **Estado objetivo**: Más reciente que backup del día anterior  
  - **Requisitos técnicos**:
    - Punto de recuperación objetivo (RPO): < 24 horas
    - Procedimientos automatizados de recovery
    - Hardware de respaldo disponible
    - Personal capacitado 24/7
    - Documentación detallada de procedimientos

### 2. Rendimiento

#### 2.1 Tiempos de Respuesta de Búsqueda
- **REQ-NF-004**: Rendimiento de búsquedas
  - **Búsqueda sencilla**: Máximo 3 segundos
  - **Búsqueda compleja**: Máximo 5 segundos (combinando criterios)
  - **Independencia de escala**: Sin degradación por volumen de datos
  - **Condiciones**:
    - Aplica independientemente del almacenamiento
    - Aplica independientemente del número de documentos
    - Medido desde interfaz de usuario
    - Incluye tiempo de renderizado

#### 2.2 Rendimiento General del Sistema
- **REQ-NF-005**: Tiempo de respuesta transaccional
  - **Meta**: Menos de 5 segundos para toda funcionalidad
  - **Alcance**: 
    - Todas las funcionalidades del sistema
    - Todas las transacciones de negocio
    - Respuesta percibida por el usuario
  - **Consideraciones de diseño**:
    - Optimización de consultas de base de datos
    - Cacheo inteligente de datos frecuentes
    - Compresión de respuestas
    - CDN para contenido estático

### 3. Escalabilidad

#### 3.1 Escalabilidad Organizacional
- **REQ-NF-006**: Escalabilidad sin restricciones organizacionales
  - **Capacidades requeridas**:
    - Soporte para organizaciones pequeñas y grandes
    - Aumento de capacidad sin degradación
    - Crecimiento de usuarios sin límites arquitectónicos
    - Mantenimiento de calidad de servicio
  - **Arquitectura escalable**:
    - Escalabilidad horizontal (scale-out)
    - Microservicios desacoplados
    - Balanceadores de carga inteligentes
    - Auto-scaling basado en demanda

### 4. Arquitectura y Tecnología

#### 4.1 Arquitectura Web
- **REQ-NF-007**: Sistema 100% web
  - **Características**:
    - Acceso completo vía navegador web
    - Administración y parametrización web
    - Interfaces de escritorio opcionales
  - **Beneficios**:
    - Independencia de plataforma
    - Facilidad de despliegue
    - Actualizaciones centralizadas
    - Acceso remoto universal

#### 4.2 Soporte de Almacenamiento
- **REQ-NF-008**: Compatibilidad con sistemas de almacenamiento
  - **Tipos soportados**:
    - **NAS** (Network Attached Storage): Almacenamiento en red
    - **DAS** (Direct Attached Storage): Almacenamiento directo
    - **SAN** (Storage Area Network): Red de área de almacenamiento
  - **Beneficios**:
    - Flexibilidad en infraestructura
    - Aprovechamiento de inversiones existentes
    - Opciones de crecimiento graduales

#### 4.3 Extensibilidad
- **REQ-NF-009**: Instalación de plugins y desarrollos personalizados
  - **Capacidades**:
    - Instalación fácil de plugins
    - Despliegue de desarrollos personalizados
    - API extensible para terceros
    - Sistema de módulos plug-and-play
  - **Arquitectura de plugins**:
    - Interfaces estándar para extensiones
    - Aislamiento de código personalizado
    - Versionado de APIs
    - Documentación para desarrolladores

### 5. Interfaces de Usuario

#### 5.1 Interfaces de Gestión
- **REQ-NF-011**: Múltiples interfaces para ECM
  - **Interfaces requeridas (mínimo)**:
    - **Interfaz de comandos**: Para automatización y scripting
    - **Interfaz gráfica de usuario**: Para operación interactiva
  - **Características adicionales**:
    - APIs RESTful para integraciones
    - Interfaces móviles optimizadas
    - Herramientas de administración especializadas

#### 5.2 Diseño Responsivo
- **REQ-NF-015**: Diseño responsive
  - **Dispositivos soportados**:
    - Computadores personales (desktop)
    - Dispositivos móviles (smartphones)
    - Tabletas (tablets)
    - Diferentes resoluciones de pantalla
  - **Características técnicas**:
    - CSS responsive framework
    - Touch-friendly interfaces
    - Optimización para diferentes tamaños
    - Rendimiento en dispositivos móviles

### 6. Usabilidad

#### 6.1 Facilidad de Uso
- **REQ-NF-012**: Funciones fáciles e intuitivas
  - **Principios de diseño**:
    - Interfaz intuitiva para usuarios finales
    - Simplicidad para administradores
    - Curva de aprendizaje mínima
    - Consistencia en toda la aplicación
  - **Características UX**:
    - Navegación clara y lógica
    - Feedback inmediato de acciones
    - Mensajes de error comprensibles
    - Wizards para tareas complejas

#### 6.2 Documentación y Ayuda
- **REQ-NF-013**: Manuales de usuario estructurados
- **REQ-NF-014**: Módulo de ayuda en línea
  - **Componentes de documentación**:
    - Manuales estructurados por rol
    - Ayuda contextual en línea
    - Tutoriales interactivos
    - FAQs y base de conocimiento
    - Videos de capacitación
  - **Características**:
    - Búsqueda en documentación
    - Actualización continua
    - Múltiples formatos (PDF, HTML, video)
    - Disponibilidad offline opcional

### 7. Personalización

#### 7.1 Flexibilidad de Parametrización
- **REQ-NF-010 & REQ-NF-016**: Máxima flexibilidad en parametrización
  - **Objetivos**:
    - Administración por usuario funcional (no técnico)
    - Personalización completa de tipos de datos
    - Configuración sin programación
  - **Capacidades**:
    - Configurador visual de formularios
    - Definición de campos personalizados
    - Reglas de negocio configurables
    - Flujos de trabajo parametrizables

#### 7.2 Personalización de Interfaz
- **REQ-NF-017**: Personalización de interfaz por usuario
  - **Elementos personalizables**:
    - **Contenidos de menús**: Organización personalizada
    - **Disposición de pantallas**: Layout adaptable
    - **Teclas de funciones**: Atajos personalizados
    - **Atajos de teclado**: Combinaciones definibles
    - **Colores y fuentes**: Temas visuales
    - **Tamaño de fuentes**: Accesibilidad visual
  - **Persistencia**:
    - Perfil de usuario individual
    - Sincronización entre dispositivos
    - Backup de configuraciones
    - Reseteo a valores por defecto

### 8. Accesibilidad

#### 8.1 Acceso por Teclado
- **REQ-NF-018**: Acceso completo via teclado
  - **Requisitos**:
    - Todas las funcionalidades accesibles
    - Todas las interfaces navegables
    - Orden lógico de tabulación
    - Indicadores visuales de foco
  - **Estándares de cumplimiento**:
    - WCAG 2.1 AA como mínimo
    - Compatibilidad con lectores de pantalla
    - Soporte para tecnologías asistivas

#### 8.2 Información Contextual
- **REQ-NF-019**: Información de contexto y estado
  - **Elementos informativos**:
    - Estado actual del usuario
    - Ubicación en la aplicación
    - Acciones disponibles
    - Progreso de operaciones
  - **Características**:
    - Breadcrumbs de navegación
    - Indicadores de estado
    - Mensajes de confirmación
    - Tooltips explicativos

#### 8.3 Gestión de Ventanas
- **REQ-NF-020**: Control completo de ventanas
  - **Operaciones soportadas**:
    - Modificar tamaño y posición
    - Minimizar y maximizar
    - Cerrar ventanas
    - Mover entre pantallas
  - **Persistencia**:
    - Guardar especificaciones en perfil
    - Restaurar estado al iniciar sesión
    - Configuración por aplicación
    - Reseteo a valores por defecto

## Métricas y Monitoreo

### Indicadores de Disponibilidad
- **Uptime**: Porcentaje de tiempo operativo
- **MTBF** (Mean Time Between Failures): Tiempo promedio entre fallos
- **MTTR** (Mean Time To Repair): Tiempo promedio de reparación
- **RTO** (Recovery Time Objective): Tiempo objetivo de recuperación
- **RPO** (Recovery Point Objective): Punto objetivo de recuperación

### Indicadores de Rendimiento
- **Tiempo de respuesta promedio**: Por tipo de operación
- **Throughput**: Transacciones por segundo
- **Concurrencia**: Usuarios simultáneos soportados
- **Utilización de recursos**: CPU, memoria, almacenamiento
- **Latencia de red**: Tiempo de respuesta de red

### Indicadores de Usabilidad
- **Tiempo de aprendizaje**: Para nuevos usuarios
- **Tasa de errores**: Errores por sesión de usuario
- **Satisfacción del usuario**: Encuestas periódicas
- **Eficiencia de tareas**: Tiempo para completar tareas comunes
- **Adopción de funcionalidades**: Uso de características avanzadas

## Consideraciones de Implementación

### Arquitectura de Alta Disponibilidad
- **Load Balancers**: Distribución de carga inteligente
- **Clustering**: Múltiples nodos de aplicación
- **Database Replication**: Bases de datos replicadas
- **Backup Systems**: Sistemas de respaldo automático
- **Monitoring**: Monitoreo 24/7 automatizado

### Optimización de Rendimiento
- **Cacheing Strategy**: Redis/Memcached para datos frecuentes
- **Database Optimization**: Índices optimizados, particionamiento
- **CDN Integration**: Content Delivery Network para archivos
- **Compression**: Compresión de respuestas HTTP
- **Asynchronous Processing**: Procesamiento asíncrono de tareas pesadas

### Escalabilidad Técnica
- **Microservices Architecture**: Servicios independientes escalables
- **Container Orchestration**: Kubernetes para despliegue
- **Auto-scaling**: Escalado automático basado en métricas
- **Database Sharding**: Particionamiento horizontal de datos
- **Message Queues**: Colas de mensajes para desacoplamiento

## Herramientas de Monitoreo Recomendadas

### Monitoreo de Infraestructura
- **Nagios/Zabbix**: Monitoreo de servidores y servicios
- **Prometheus + Grafana**: Métricas y visualización
- **ELK Stack**: Logs centralizados y análisis
- **New Relic/Dynatrace**: APM (Application Performance Monitoring)

### Monitoreo de Usuario
- **Google Analytics**: Análisis de uso web
- **Hotjar/FullStory**: Análisis de experiencia de usuario
- **Survey Tools**: Encuestas de satisfacción
- **A/B Testing**: Pruebas de usabilidad

## Riesgos y Mitigaciones

### Riesgos de Disponibilidad
1. **Fallo de hardware**: Redundancia y failover automático
2. **Fallo de software**: Versionado y rollback rápido
3. **Sobrecarga del sistema**: Auto-scaling y balanceamiento
4. **Problemas de red**: Múltiples conexiones y rutas

### Riesgos de Rendimiento
1. **Crecimiento de datos**: Archivado automático y optimización
2. **Picos de usuarios**: Escalado horizontal automático
3. **Consultas lentas**: Optimización continua de BD
4. **Ancho de banda**: CDN y compresión

### Riesgos de Usabilidad
1. **Resistencia al cambio**: Capacitación y soporte continuo
2. **Complejidad técnica**: Interfaces simplificadas
3. **Accesibilidad**: Cumplimiento de estándares WCAG
4. **Dispositivos diversos**: Testing en múltiples plataformas

## Plan de Testing

### Testing de Rendimiento
- **Load Testing**: Cargas normales y picos esperados
- **Stress Testing**: Límites del sistema
- **Volume Testing**: Grandes volúmenes de datos
- **Endurance Testing**: Rendimiento sostenido

### Testing de Disponibilidad
- **Failover Testing**: Pruebas de conmutación
- **Recovery Testing**: Procedimientos de recuperación
- **Backup Testing**: Integridad de respaldos
- **Disaster Recovery**: Simulacros completos

### Testing de Usabilidad
- **User Acceptance Testing**: Pruebas con usuarios reales
- **Accessibility Testing**: Cumplimiento de estándares
- **Cross-browser Testing**: Compatibilidad navegadores
- **Mobile Testing**: Funcionalidad en dispositivos móviles

---

**Fecha de análisis**: 2025-09-12  
**Categoría**: Requerimientos No Funcionales  
**Total de requerimientos identificados**: 20  
**Criticidad**: Muy Alta (define la calidad del sistema completo)
