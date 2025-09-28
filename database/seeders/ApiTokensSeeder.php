<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ApiToken;
use App\Models\ApiTokenLog;
use App\Models\User;
use Carbon\Carbon;

class ApiTokensSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios para asignar tokens
        $adminUser = User::where('email', 'admin@archiveycloud.com')->first();
        $users = User::limit(3)->get();

        if (!$adminUser || $users->count() === 0) {
            $this->command->error('❌ No se encontraron usuarios. Ejecuta primero UserSeeder.');
            return;
        }

        $this->command->info('🔑 Creando tokens API de ejemplo...');

        // Token 1: Sistema CRM - Admin
        $tokenCRM = ApiToken::crearToken([
            'nombre' => 'Sistema CRM Empresarial',
            'descripcion' => 'Token para integración con sistema CRM para sincronizar documentos y expedientes',
            'usuario_id' => $adminUser->id,
            'permisos' => ['documentos:read', 'documentos:write', 'expedientes:read'],
            'fecha_expiracion' => now()->addYear(),
            'limite_usos' => 10000,
            'ips_permitidas' => ['192.168.1.100', '10.0.0.50'],
        ]);

        // Token 2: Aplicación Móvil
        $tokenMovil = ApiToken::crearToken([
            'nombre' => 'App Móvil ArchiveyCloud',
            'descripcion' => 'Token para aplicación móvil con permisos de consulta',
            'usuario_id' => $users->first()->id,
            'permisos' => ['documentos:read', 'expedientes:read', 'usuarios:read'],
            'limite_usos' => 5000,
        ]);

        // Token 3: Sistema de Auditoría - Solo lectura
        $tokenAuditoria = ApiToken::crearToken([
            'nombre' => 'Sistema Auditoría Externa',
            'descripcion' => 'Token para sistema de auditoría con acceso de solo lectura',
            'usuario_id' => $users->count() > 1 ? $users[1]->id : $users->first()->id,
            'permisos' => ['auditoria:read', 'documentos:read', 'expedientes:read'],
            'fecha_expiracion' => now()->addMonths(6),
            'limite_usos' => 2000,
            'ips_permitidas' => ['203.0.113.10'],
        ]);

        // Token 4: Sistema Legacy - Expirado (para demostrar estados)
        $tokenLegacy = ApiToken::crearToken([
            'nombre' => 'Sistema Legacy Antiguo',
            'descripcion' => 'Token heredado del sistema anterior (expirado)',
            'usuario_id' => $users->count() > 2 ? $users[2]->id : $users->first()->id,
            'permisos' => ['documentos:read'],
            'fecha_expiracion' => now()->subMonth(), // Expirado
            'limite_usos' => 1000,
        ]);

        // Crear algunos logs de ejemplo para simular uso
        $this->crearLogsEjemplo($tokenCRM['token']);
        $this->crearLogsEjemplo($tokenMovil['token']);
        $this->crearLogsEjemplo($tokenAuditoria['token']);

        $this->command->info('✅ Tokens API creados exitosamente:');
        $this->command->line("   🏢 Sistema CRM Empresarial - {$tokenCRM['token']->usos_realizados} usos");
        $this->command->line("   📱 App Móvil ArchiveyCloud - {$tokenMovil['token']->usos_realizados} usos"); 
        $this->command->line("   🔍 Sistema Auditoría Externa - {$tokenAuditoria['token']->usos_realizados} usos");
        $this->command->line("   🗂️ Sistema Legacy Antiguo - {$tokenLegacy['token']->usos_realizados} usos (expirado)");
        
        $this->command->newLine();
        $this->command->info('💡 Tokens disponibles en: /admin/api-tokens');
    }

    /**
     * Crear logs de ejemplo para un token
     */
    private function crearLogsEjemplo(ApiToken $token)
    {
        $rutas = [
            '/api/documentos',
            '/api/expedientes',
            '/api/usuarios',
            '/api/documentos/search',
            '/api/expedientes/estadisticas',
            '/api/auditoria/logs',
        ];

        $ips = ['192.168.1.100', '10.0.0.50', '203.0.113.10', '172.16.0.20'];
        $userAgents = [
            'ArchiveyCloud-CRM/1.0',
            'ArchiveyCloud-Mobile/2.1.0 (iOS)',
            'ArchiveyCloud-Audit/1.5',
            'System-Integration/3.0',
        ];

        // Crear logs de los últimos 30 días
        for ($i = 0; $i < rand(50, 200); $i++) {
            $fechaLog = now()->subDays(rand(0, 30))
                ->addHours(rand(8, 18))
                ->addMinutes(rand(0, 59));

            ApiTokenLog::create([
                'api_token_id' => $token->id,
                'ruta' => $rutas[array_rand($rutas)],
                'metodo' => ['GET', 'POST', 'PUT', 'DELETE'][array_rand(['GET', 'POST', 'PUT', 'DELETE'])],
                'ip' => $ips[array_rand($ips)],
                'user_agent' => $userAgents[array_rand($userAgents)],
                'codigo_respuesta' => $this->getRandomStatusCode(),
                'tiempo_respuesta' => rand(50, 2000) / 1000, // 0.05-2 segundos
                'parametros' => [
                    'params' => ['limit' => rand(10, 100)],
                    'size' => rand(1024, 10240),
                ],
                'created_at' => $fechaLog,
            ]);
        }

        // Actualizar contador de usos en el token
        $totalUsos = $token->logs()->count();
        $token->update(['usos_realizados' => $totalUsos]);
    }

    /**
     * Obtener código de estado HTTP aleatorio realista
     */
    private function getRandomStatusCode(): int
    {
        $statusCodes = [
            200 => 85, // 85% éxito
            201 => 5,  // 5% creado
            400 => 3,  // 3% bad request
            401 => 2,  // 2% unauthorized
            404 => 3,  // 3% not found
            500 => 2,  // 2% server error
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($statusCodes as $code => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $code;
            }
        }

        return 200; // fallback
    }
}
