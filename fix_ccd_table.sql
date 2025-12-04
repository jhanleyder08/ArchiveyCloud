-- Script para agregar la columna 'estado' faltante en la tabla cuadros_clasificacion_documental
-- Ejecutar este script directamente en phpMyAdmin o línea de comandos MySQL

USE archivey_cloud;

-- Verificar si la columna 'estado' existe
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'archivey_cloud' 
  AND TABLE_NAME = 'cuadros_clasificacion_documental' 
  AND COLUMN_NAME = 'estado';

-- Si la consulta anterior no devuelve resultados, entonces la columna no existe
-- Ejecutar las siguientes líneas para agregarla:

ALTER TABLE cuadros_clasificacion_documental 
ADD COLUMN estado ENUM('borrador', 'activo', 'inactivo', 'historico') 
DEFAULT 'borrador' 
AFTER orden_jerarquico;

-- Agregar índice para optimizar búsquedas
ALTER TABLE cuadros_clasificacion_documental 
ADD INDEX idx_estado (estado);

-- Verificar que se agregó correctamente
DESCRIBE cuadros_clasificacion_documental;
