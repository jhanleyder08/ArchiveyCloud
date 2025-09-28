<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notificacion;
use App\Models\User;
use Carbon\Carbon;

class NotificacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔔 Creando notificaciones de prueba...');
        
        $users = User::limit(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->error('No hay usuarios disponibles. Ejecuta primero los seeders de usuarios.');
            return;
        }

        $tipos = [
            'expedientes_vencidos',
            'documentos_sin_clasificar', 
            'prestamos_vencidos',
            'test_interfaz',
            'sistema_mantenimiento'
        ];
        
        $prioridades = ['baja', 'media', 'alta', 'critica'];
        $estados = ['pendiente', 'leida', 'archivada'];

        // Crear 50 notificaciones distribuidas en los últimos 15 días
        for ($i = 0; $i < 50; $i++) {
            $user = $users->random();
            $tipo = $tipos[array_rand($tipos)];
            $prioridad = $prioridades[array_rand($prioridades)];
            $estado = $estados[array_rand($estados)];
            $fechaCreacion = Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23));
            
            $titulos = [
                'expedientes_vencidos' => 'Expedientes próximos a vencer',
                'documentos_sin_clasificar' => 'Documentos pendientes de clasificación',
                'prestamos_vencidos' => 'Préstamos con vencimiento próximo',
                'test_interfaz' => 'Prueba desde Interfaz Web - ArchiveyCloud',
                'sistema_mantenimiento' => 'Notificación de sistema'
            ];
            
            $mensajes = [
                'expedientes_vencidos' => 'Tienes expedientes que vencen próximamente. Revisa el estado de retención documental.',
                'documentos_sin_clasificar' => 'Hay documentos que requieren clasificación en series documentales.',
                'prestamos_vencidos' => 'Algunos préstamos están próximos a vencer. Considera renovar o devolver.',
                'test_interfaz' => 'Este es un email de prueba enviado desde la interfaz de administración de servicios externos.',
                'sistema_mantenimiento' => 'El sistema ha ejecutado operaciones de mantenimiento programado.'
            ];

            $notificacion = Notificacion::create([
                'user_id' => $user->id,
                'tipo' => $tipo,
                'titulo' => $titulos[$tipo],
                'mensaje' => $mensajes[$tipo],
                'prioridad' => $prioridad,
                'estado' => $estado,
                'es_automatica' => in_array($tipo, ['expedientes_vencidos', 'documentos_sin_clasificar', 'sistema_mantenimiento']),
                'accion_url' => $tipo === 'test_interfaz' ? '/admin/servicios-externos' : null,
                'datos' => [
                    'test_interfaz' => $tipo === 'test_interfaz',
                    'enviado_por' => $tipo === 'test_interfaz' ? 'Sistema de Testing' : 'Sistema Automático',
                    'timestamp' => $fechaCreacion->toISOString()
                ],
                'created_at' => $fechaCreacion,
                'updated_at' => $fechaCreacion
            ]);

            // Actualizar fechas de leído según el estado (usar leida_en, no leida_at)
            if ($estado === 'leida' && rand(0, 1)) {
                $notificacion->leida_en = $fechaCreacion->copy()->addMinutes(rand(5, 120));
            }
            
            if ($estado === 'archivada') {
                $notificacion->leida_en = $fechaCreacion->copy()->addMinutes(rand(5, 60));
                // No hay columna archivada_at en la tabla
            }
            
            $notificacion->save();
        }

        $total = Notificacion::count();
        $pendientes = Notificacion::where('estado', 'pendiente')->count();
        $criticas = Notificacion::where('prioridad', 'critica')->count();
        $automaticas = Notificacion::where('es_automatica', true)->count();

        $this->command->info("✅ {$total} notificaciones creadas exitosamente");
        $this->command->info("📊 Estadísticas:");
        $this->command->info("   • Pendientes: {$pendientes}");
        $this->command->info("   • Críticas: {$criticas}");
        $this->command->info("   • Automáticas: {$automaticas}");
        $this->command->info("   • Últimos 7 días: " . Notificacion::where('created_at', '>=', Carbon::now()->subDays(7))->count());
    }
}
