<?php

use App\Models\DisposicionFinal;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminDisposicionController;

try {
    echo "Testing index method...\n";
    
    // Create a dummy request
    $request = Request::create('/admin/disposiciones', 'GET');
    
    $controller = new AdminDisposicionController();
    $response = $controller->index($request);
    
    echo "Index method executed.\n";
    
    // Check if response is Inertia response
    if ($response instanceof \Inertia\Response) {
        echo "Response is Inertia Response.\n";
        $props = $response->toResponse($request)->getData(true); // Get props
        // Note: Inertia response handling in tinker is tricky, but we can check if it threw an exception
        echo "Index loaded successfully.\n";
    } else {
        echo "Response type: " . get_class($response) . "\n";
    }

} catch (\Exception $e) {
    echo "Error in index: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
