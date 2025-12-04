<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN DE TABLAS PRINCIPALES ===\n\n";

$tables = [
    'expedientes',
    'documentos', 
    'prestamos',
    'disposicion_finals',
    'series_documentales',
    'subseries_documentales',
    'tablas_retencion_documental',
    'cuadros_clasificacion_documental',
    'notificacions',
    'roles',
    'users'
];

foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo sprintf("%-35s: %s\n", $table, $exists ? '✅ EXISTS' : '❌ NOT EXISTS');
}

echo "\n=== FIN VERIFICACIÓN ===\n";
