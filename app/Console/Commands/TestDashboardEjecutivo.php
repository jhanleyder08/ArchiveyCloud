<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\DashboardEjecutivoController;
use App\Models\User;

class TestDashboardEjecutivo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:dashboard-ejecutivo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Dashboard Ejecutivo functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Dashboard Ejecutivo...');
        
        try {
            // Verificar usuario admin
            $user = User::where('email', 'admin@archiveycloud.com')->first();
            if (!$user) {
                $this->error('❌ Admin user not found');
                return 1;
            }
            
            $this->info("✅ Admin user found: {$user->email}");
            
            // Verificar rol del usuario
            if ($user->role) {
                $this->info("📋 User role: {$user->role->name}");
                $this->info("🔑 Role ID: {$user->role->id}");
            } else {
                $this->warn("⚠️ User has no role assigned!");
            }
            
            // Verificar permisos específicos del middleware
            $requiredRoles = ['admin', 'super_admin', 'gestor_documental'];
            $this->info('🔐 Checking role permissions...');
            
            foreach ($requiredRoles as $role) {
                $hasRole = $user->hasRole($role);
                $status = $hasRole ? '✅' : '❌';
                $this->line("  {$status} {$role}: " . ($hasRole ? 'Yes' : 'No'));
            }
            
            // Verificar algunos métodos del controlador
            $this->info('🎛️ Testing controller methods...');
            
            // Simular autenticación
            auth()->login($user);
            
            $controller = new DashboardEjecutivoController();
            $this->info('✅ Controller instantiated');
            
            // Intentar obtener métricas
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('obtenerMetricasGenerales');
            $method->setAccessible(true);
            
            $metricas = $method->invokeArgs($controller, []);
            $this->info("📊 Metrics obtained successfully:");
            $this->line("  - Documents: {$metricas['total_documentos']}");
            $this->line("  - Expedientes: {$metricas['total_expedientes']}");
            $this->line("  - Users: {$metricas['total_usuarios']}");
            
            $this->info('🎯 Dashboard Ejecutivo is working correctly!');
            $this->info('💡 Access: http://127.0.0.1:8000/admin/dashboard-ejecutivo');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->error("📍 Stack trace: {$e->getTraceAsString()}");
            return 1;
        }
    }
}
