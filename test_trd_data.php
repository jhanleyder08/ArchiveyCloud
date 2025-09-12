<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TrdTable;
use App\Models\TrdSection;
use App\Models\TrdSeries;
use App\Models\TrdSubseries;

// Crear estructura completa de TRD de prueba
$trd = TrdTable::first();

if ($trd) {
    echo "TRD encontrada: " . $trd->name . "\n";
    
    // Crear sección
    $section = $trd->sections()->create([
        'section_code' => '001',
        'section_name' => 'Administración General',
        'description' => 'Documentos de administración general',
        'order_index' => 1
    ]);
    
    echo "Sección creada: " . $section->section_name . "\n";
    
    // Crear serie
    $series = $section->series()->create([
        'series_code' => '001.001',
        'series_name' => 'Actas y Resoluciones',
        'description' => 'Documentos de decisiones administrativas',
        'order_index' => 1
    ]);
    
    echo "Serie creada: " . $series->series_name . "\n";
    
    // Crear subserie
    $subseries = $series->subseries()->create([
        'subseries_code' => '001.001.001',
        'subseries_name' => 'Actas de Directorio',
        'description' => 'Actas de reuniones del directorio',
        'document_type' => 'digital',
        'retention_archive_management' => 2,
        'retention_central_archive' => 5,
        'final_disposition' => 'conservation_total',
        'order_index' => 1
    ]);
    
    echo "Subserie creada: " . $subseries->subseries_name . "\n";
    echo "Estructura TRD completa creada exitosamente!\n";
} else {
    echo "No se encontró ninguna TRD\n";
}
