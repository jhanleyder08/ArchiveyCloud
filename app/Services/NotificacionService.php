<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Expediente;
use App\Models\Prestamo;
use App\Models\DisposicionFinal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Ejecutar todas las verificaciones automáticas
     */
    public function ejecutarTodasLasVerificaciones(): array
    {
        $resultados = [
            'expedientes_proximos' => $this->verificarExpedientesProximosVencer(),
            'expedientes_vencidos' => $this->verificarExpedientesVencidos(),
            'prestamos_proximos' => $this->verificarPrestamosProximosVencer(),
            'prestamos_vencidos' => $this->verificarPrestamosVencidos(),
            'disposiciones_pendientes' => $this->verificarDisposicionesPendientes(),
            'limpieza_antiguas' => $this->limpiarNotificacionesAntiguas(),
        ];

        $total = array_sum($resultados);
        
        Log::info('Sistema de notificaciones ejecutado', [
            'total_notificaciones_creadas' => $total,
            'detalles' => $resultados
        ]);

        return $resultados;
    }

    /**
     * Verificar expedientes próximos a vencer
     */
    public function verificarExpedientesProximosVencer(): int
    {
        // Simular lógica con expedientes antiguos (más de 1 año abiertos)
        $expedientesProximos = Expediente::where('fecha_apertura', '<', Carbon::now()->subYear())
            ->where('fecha_cierre', null)
            ->where('estado', '!=', 'eliminado')
            ->limit(5) // Limitar para pruebas
            ->get();

        $notificacionesCreadas = 0;

        foreach ($expedientesProximos as $expediente) {
            $diasAbierto = Carbon::parse($expediente->fecha_apertura)->diffInDays(Carbon::now());
            
            $prioridad = match(true) {
                $diasAbierto > 720 => 'critica', // Más de 2 años
                $diasAbierto > 540 => 'alta', // Más de 1.5 años
                default => 'media'
            };

            if (!$this->existeNotificacionReciente($expediente->id, 'App\\Models\\Expediente', 'expediente_proximo_vencer', 7)) {
                $usuarios = $this->obtenerUsuariosRelevantes($expediente->responsable_id ?? null);

                $datos = [
                    'tipo' => 'expediente_proximo_vencer',
                    'titulo' => "Expediente requiere revisión",
                    'mensaje' => "El expediente '{$expediente->codigo}' lleva abierto {$diasAbierto} días sin cierre.",
                    'prioridad' => $prioridad,
                    'accion_url' => "/admin/expedientes/{$expediente->id}",
                    'relacionado_id' => $expediente->id,
                    'relacionado_tipo' => 'App\\Models\\Expediente',
                    'es_automatica' => true,
                ];

                $creadas = Notificacion::crearParaUsuarios($usuarios, $datos);
                $notificacionesCreadas += $creadas;
            }
        }

        return $notificacionesCreadas;
    }

    /**
     * Verificar expedientes vencidos
     */
    public function verificarExpedientesVencidos(): int
    {
        // Simular expedientes muy antiguos que requieren cierre
        $expedientesVencidos = Expediente::where('fecha_apertura', '<', Carbon::now()->subYears(2))
            ->where('fecha_cierre', null)
            ->where('estado_ciclo_vida', 'tramite')
            ->limit(3) // Limitar para pruebas
            ->get();

        $notificacionesCreadas = 0;

        foreach ($expedientesVencidos as $expediente) {
            $diasAbierto = Carbon::now()->diffInDays(Carbon::parse($expediente->fecha_apertura));
            
            if (!$this->existeNotificacionReciente($expediente->id, 'App\\Models\\Expediente', 'expediente_vencido', 14)) {
                $usuarios = $this->obtenerUsuariosRelevantes($expediente->responsable_id ?? null);

                $datos = [
                    'tipo' => 'expediente_vencido',
                    'titulo' => "Expediente requiere cierre urgente",
                    'mensaje' => "El expediente '{$expediente->codigo}' lleva {$diasAbierto} días abierto. Requiere cierre inmediato.",
                    'prioridad' => 'critica',
                    'accion_url' => "/admin/expedientes/{$expediente->id}",
                    'relacionado_id' => $expediente->id,
                    'relacionado_tipo' => 'App\\Models\\Expediente',
                    'es_automatica' => true,
                ];

                $creadas = Notificacion::crearParaUsuarios($usuarios, $datos);
                $notificacionesCreadas += $creadas;
            }
        }

        return $notificacionesCreadas;
    }

    /**
     * Verificar préstamos próximos a vencer
     */
    public function verificarPrestamosProximosVencer(): int
    {
        // Verificar si la tabla Prestamo existe, si no, retornar 0
        try {
            if (!class_exists('App\\Models\\Prestamo')) {
                return 0;
            }
            return 0; // Por ahora retornar 0 hasta implementar completamente
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Verificar préstamos vencidos
     */
    public function verificarPrestamosVencidos(): int
    {
        // Verificar si la tabla Prestamo existe, si no, retornar 0
        try {
            if (!class_exists('App\\Models\\Prestamo')) {
                return 0;
            }
            return 0; // Por ahora retornar 0 hasta implementar completamente
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Verificar disposiciones pendientes
     */
    public function verificarDisposicionesPendientes(): int
    {
        // Verificar si la tabla DisposicionFinal existe, si no, retornar 0
        try {
            if (!class_exists('App\\Models\\DisposicionFinal')) {
                return 0;
            }
            return 0; // Por ahora retornar 0 hasta implementar completamente
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public function limpiarNotificacionesAntiguas(): int
    {
        return Notificacion::limpiarAntiguas(30);
    }

    /**
     * Verificar si existe una notificación reciente
     */
    private function existeNotificacionReciente($relacionadoId, $relacionadoTipo, $tipo, $dias): bool
    {
        return Notificacion::where('relacionado_id', $relacionadoId)
            ->where('relacionado_tipo', $relacionadoTipo)
            ->where('tipo', $tipo)
            ->where('created_at', '>=', Carbon::now()->subDays($dias))
            ->exists();
    }

    /**
     * Obtener usuarios relevantes (responsable + administradores)
     */
    private function obtenerUsuariosRelevantes($responsableId = null): array
    {
        $usuarios = collect();
        
        if ($responsableId) {
            $usuarios->push($responsableId);
        }
        
        $admins = User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->pluck('id');
        
        return $usuarios->merge($admins)->unique()->toArray();
    }

    /**
     * Crear notificación manual
     */
    public function crearNotificacionManual(array $datos): int
    {
        return Notificacion::crearParaUsuarios($datos['user_ids'], [
            'tipo' => $datos['tipo'],
            'titulo' => $datos['titulo'],
            'mensaje' => $datos['mensaje'],
            'prioridad' => $datos['prioridad'] ?? 'media',
            'accion_url' => $datos['accion_url'] ?? null,
            'programada_para' => $datos['programada_para'] ?? null,
            'es_automatica' => false,
            'creado_por' => $datos['creado_por'],
        ]);
    }
}
