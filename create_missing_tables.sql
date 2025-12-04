CREATE TABLE IF NOT EXISTS subseries_documentales (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    codigo varchar(255) UNIQUE NOT NULL,
    nombre varchar(255) NOT NULL,
    descripcion text NOT NULL,
    serie_documental_id bigint unsigned NOT NULL,
    tiempo_archivo_gestion int NULL,
    tiempo_archivo_central int NULL,
    disposicion_final enum('conservacion_permanente', 'eliminacion', 'seleccion', 'microfilmacion') NOT NULL,
    procedimiento text NULL,
    activa tinyint(1) DEFAULT 1,
    metadatos_especificos json NULL,
    tipologias_documentales json NULL,
    created_by bigint unsigned NOT NULL,
    updated_by bigint unsigned NULL,
    observaciones text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
);

CREATE TABLE IF NOT EXISTS tipologias_documentales (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    codigo varchar(255) UNIQUE NOT NULL,
    nombre varchar(255) NOT NULL,
    descripcion text NOT NULL,
    subserie_documental_id bigint unsigned NOT NULL,
    soporte enum('fisico', 'electronico', 'mixto') NOT NULL,
    formato varchar(255) NULL,
    metadatos_obligatorios json NULL,
    metadatos_opcionales json NULL,
    activa tinyint(1) DEFAULT 1,
    created_by bigint unsigned NOT NULL,
    updated_by bigint unsigned NULL,
    observaciones text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
);

CREATE TABLE IF NOT EXISTS expedientes (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    codigo varchar(255) UNIQUE NOT NULL,
    nombre varchar(255) NOT NULL,
    descripcion text NULL,
    subserie_documental_id bigint unsigned NOT NULL,
    fecha_apertura date NOT NULL,
    fecha_cierre date NULL,
    ubicacion_fisica varchar(255) NULL,
    ubicacion_electronica varchar(255) NULL,
    estado enum('abierto', 'cerrado', 'transferido', 'eliminado') DEFAULT 'abierto',
    responsable_id bigint unsigned NOT NULL,
    metadatos json NULL,
    created_by bigint unsigned NOT NULL,
    updated_by bigint unsigned NULL,
    observaciones text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
);

CREATE TABLE IF NOT EXISTS documentos (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    codigo varchar(255) UNIQUE NOT NULL,
    nombre varchar(255) NOT NULL,
    descripcion text NULL,
    expediente_id bigint unsigned NULL,
    tipologia_documental_id bigint unsigned NOT NULL,
    fecha_documento date NOT NULL,
    archivo_fisico varchar(255) NULL,
    archivo_electronico varchar(255) NULL,
    tamaño_archivo bigint NULL,
    hash_archivo varchar(255) NULL,
    estado enum('borrador', 'activo', 'obsoleto', 'eliminado') DEFAULT 'activo',
    metadatos json NULL,
    created_by bigint unsigned NOT NULL,
    updated_by bigint unsigned NULL,
    observaciones text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
);

CREATE TABLE IF NOT EXISTS pistas_auditoria (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    usuario_id bigint unsigned NOT NULL,
    accion varchar(255) NOT NULL,
    entidad_type varchar(255) NOT NULL,
    entidad_id bigint unsigned NULL,
    descripcion text NULL,
    valores_anteriores json NULL,
    valores_nuevos json NULL,
    ip_address varchar(45) NULL,
    user_agent text NULL,
    created_at timestamp NULL
);
