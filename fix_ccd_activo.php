<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=archivey_cloud', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if activo column exists
    $result = $pdo->query("SHOW COLUMNS FROM cuadros_clasificacion_documental LIKE 'activo'");
    
    if ($result->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE cuadros_clasificacion_documental ADD activo tinyint(1) NOT NULL DEFAULT 1");
        echo "Columna 'activo' agregada exitosamente a cuadros_clasificacion_documental\n";
    } else {
        echo "Columna 'activo' ya existe en cuadros_clasificacion_documental\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
