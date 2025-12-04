<?php

use PDO;
use PDOException;

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=archivey_cloud', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fix cuadros_clasificacion_documental table - deleted_at
    $result = $pdo->query("SHOW COLUMNS FROM cuadros_clasificacion_documental LIKE 'deleted_at'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cuadros_clasificacion_documental ADD COLUMN deleted_at timestamp NULL");
        echo "Columna 'deleted_at' agregada a cuadros_clasificacion_documental\n";
    }
    
    // Fix cuadros_clasificacion_documental table - activo
    $result = $pdo->query("SHOW COLUMNS FROM cuadros_clasificacion_documental LIKE 'activo'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cuadros_clasificacion_documental ADD COLUMN activo tinyint(1) NOT NULL DEFAULT 1");
        echo "Columna 'activo' agregada a cuadros_clasificacion_documental\n";
    }
    
    // Fix roles table - add activo
    $result = $pdo->query("SHOW COLUMNS FROM roles LIKE 'activo'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN activo tinyint(1) NOT NULL DEFAULT 1");
        echo "Columna 'activo' agregada a roles\n";
    }
    
    // Fix roles table - add nivel_jerarquico
    $result = $pdo->query("SHOW COLUMNS FROM roles LIKE 'nivel_jerarquico'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN nivel_jerarquico int NOT NULL DEFAULT 5");
        echo "Columna 'nivel_jerarquico' agregada a roles\n";
    }
    
    // Fix roles table - add deleted_at
    $result = $pdo->query("SHOW COLUMNS FROM roles LIKE 'deleted_at'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN deleted_at timestamp NULL");
        echo "Columna 'deleted_at' agregada a roles\n";
    }
    
    // Fix any other missing columns in series_documentales
    $result = $pdo->query("SHOW COLUMNS FROM series_documentales LIKE 'deleted_at'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE series_documentales ADD COLUMN deleted_at timestamp NULL");
        echo "Columna 'deleted_at' agregada a series_documentales\n";
    }
    
    echo "Todas las columnas faltantes han sido agregadas\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
