<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Documento;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    
    $doc = new Documento();
    $doc->codigo_documento = 'DOC-2025-0001';
    $doc->titulo = 'Test Document';
    $doc->descripcion = 'Test description';
    $doc->expediente_id = 1;
    $doc->tipologia_documental_id = null;
    $doc->created_by = 10;
    $doc->activo = true;
    $doc->version_mayor = 1;
    $doc->version_menor = 0;
    $doc->fecha_documento = now();
    $doc->fecha_captura = now();
    
    // Forzar guardar sin eventos
    $doc->saveQuietly();
    
    DB::commit();
    echo "SUCCESS! Document ID: " . $doc->id . "\n";
    
} catch (Exception $e) {
    DB::rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
