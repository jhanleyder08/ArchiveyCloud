<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\DashboardEjecutivoController;
use App\Models\User;
use Illuminate\Http\Request;

class TestDashboardResponse extends Command
{
    protected $signature = 'test:dashboard-response';
    protected $description = 'Test Dashboard Ejecutivo Response';

    public function handle()
    {
        $this->info('🧪 Testing Dashboard Ejecutivo Response...');
        
        try {
            // Autenticar usuario
            $user = User::where('email', 'admin@archiveycloud.com')->first();
            auth()->login($user);
            
            $this->info("✅ User authenticated: {$user->email}");
            
            // Instanciar controlador
            $controller = new DashboardEjecutivoController();
            
            // Simular request
            $request = new Request();
            
            // Obtener respuesta
            $response = $controller->index();
            
            $this->info("📊 Response Type: " . get_class($response));
            
            // Si es respuesta Inertia, obtener props
            if (method_exists($response, 'toResponse')) {
                $this->info("🎯 Inertia Response detected");
                
                // Crear respuesta fake para extraer datos
                $fakeRequest = new Request();
                $inertiaResponse = $response->toResponse($fakeRequest);
                
                $this->info("📱 Status Code: " . $inertiaResponse->getStatusCode());
                
                // Obtener contenido
                $content = $inertiaResponse->getContent();
                $this->info("📄 Content length: " . strlen($content));
                
                // Verificar si contiene datos del dashboard
                if (strpos($content, 'metricas_generales') !== false) {
                    $this->info("✅ Contains metricas_generales");
                } else {
                    $this->warn("⚠️ Missing metricas_generales");
                }
                
                if (strpos($content, 'admin/dashboard-ejecutivo/index') !== false) {
                    $this->info("✅ Correct component path");
                } else {
                    $this->warn("⚠️ Incorrect component path");
                }
            }
            
            $this->info('🎉 Test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->error("📍 File: {$e->getFile()}:{$e->getLine()}");
            return 1;
        }
        
        return 0;
    }
}
