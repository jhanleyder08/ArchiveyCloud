<?php

use PDO;
use PDOException;

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=archivey_cloud', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if activo column exists
    $result = $pdo->query("SHOW COLUMNS FROM roles LIKE 'activo'");
    
    if ($result->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE roles ADD COLUMN activo tinyint(1) NOT NULL DEFAULT 1");
        echo "Columna 'activo' agregada a tabla roles\n";
    } else {
        echo "Columna 'activo' ya existe en tabla roles\n";
    }
    
    // Also add nivel_jerarquico if it doesn't exist
    $result2 = $pdo->query("SHOW COLUMNS FROM roles LIKE 'nivel_jerarquico'");
    
    if ($result2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN nivel_jerarquico int NOT NULL DEFAULT 5");
        echo "Columna 'nivel_jerarquico' agregada a tabla roles\n";
    } else {
        echo "Columna 'nivel_jerarquico' ya existe en tabla roles\n";
    }
    
    // Add deleted_at if it doesn't exist
    $result3 = $pdo->query("SHOW COLUMNS FROM roles LIKE 'deleted_at'");
    
    if ($result3->rowCount() == 0) {
        $pdo->exec("ALTER TABLE roles ADD COLUMN deleted_at timestamp NULL");
        echo "Columna 'deleted_at' agregada a tabla roles\n";
    } else {
        echo "Columna 'deleted_at' ya existe en tabla roles\n";
    }
    
    echo "Tabla roles actualizada correctamente\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
