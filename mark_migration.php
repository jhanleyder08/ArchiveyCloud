<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=archivey_cloud', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fix cuadros_clasificacion_documental - add activo column if it doesn't exist
    $pdo->exec("ALTER TABLE cuadros_clasificacion_documental ADD COLUMN IF NOT EXISTS activo tinyint(1) NOT NULL DEFAULT 1");
    echo "Columna activo verificada en cuadros_clasificacion_documental\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
