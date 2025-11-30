-- Script para agregar nivel raíz (Fondo) al CCD ID 8
-- Ejecutar en MySQL/MariaDB

-- Verificar el CCD
SELECT id, codigo, nombre, version, estado 
FROM cuadros_clasificacion 
WHERE id = 8;

-- Insertar nivel raíz (Fondo)
INSERT INTO ccd_niveles (
    ccd_id,
    parent_id,
    codigo,
    nombre,
    descripcion,
    nivel,
    tipo_nivel,
    orden,
    activo,
    ruta,
    created_at,
    updated_at
) VALUES (
    8,                                                    -- ccd_id
    NULL,                                                 -- parent_id (nivel raíz)
    'FOND-HUV-01',                                       -- codigo
    'Fondo Hospital Universitario del Valle',            -- nombre
    'Fondo raíz del cuadro de clasificación documental', -- descripcion
    1,                                                    -- nivel
    'fondo',                                             -- tipo_nivel
    0,                                                    -- orden
    1,                                                    -- activo
    '/FOND-HUV-01',                                      -- ruta
    NOW(),                                               -- created_at
    NOW()                                                -- updated_at
);

-- Verificar que se insertó correctamente
SELECT * FROM ccd_niveles WHERE ccd_id = 8;

-- Verificar que el CCD ya no tiene errores de validación
SELECT 
    ccd.id,
    ccd.nombre,
    COUNT(n.id) as total_niveles,
    COUNT(CASE WHEN n.parent_id IS NULL THEN 1 END) as niveles_raiz
FROM cuadros_clasificacion ccd
LEFT JOIN ccd_niveles n ON n.ccd_id = ccd.id
WHERE ccd.id = 8
GROUP BY ccd.id, ccd.nombre;
