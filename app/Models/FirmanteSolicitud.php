<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FirmanteSolicitud extends Model
{
    use HasFactory;

    protected $table = 'firmantes_solicitud';

    protected $fillable = [
        'solicitud_firma_id',
        'usuario_id',
        'orden',
        'es_obligatorio',
        'rol_firmante',
        'estado',
        'notificado_en',
        'firmado_en',
        'rechazado_en',
        'comentario',
        'ip_firma',
        'metadata_firmante'
    ];

    protected $casts = [
        'es_obligatorio' => 'boolean',
        'notificado_en' => 'datetime',
        'firmado_en' => 'datetime',
        'rechazado_en' => 'datetime',
        'metadata_firmante' => 'array'
    ];

    protected $dates = [
        'notificado_en',
        'firmado_en',
        'rechazado_en'
    ];

    // Estados del firmante
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_NOTIFICADO = 'notificado';
    const ESTADO_FIRMADO = 'firmado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_DELEGADO = 'delegado';

    // Roles de firmante
    const ROL_APROBADOR = 'aprobador';
    const ROL_REVISOR = 'revisor';
    const ROL_TESTIGO = 'testigo';
    const ROL_AUTORIDAD = 'autoridad';
    const ROL_VALIDADOR = 'validador';

    /**
     * Relación con la solicitud de firma
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirma::class, 'solicitud_firma_id');
    }

    /**
     * Relación con el usuario firmante
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scope para firmantes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para firmantes obligatorios
     */
    public function scopeObligatorios($query)
    {
        return $query->where('es_obligatorio', true);
    }

    /**
     * Scope para firmantes por rol
     */
    public function scopePorRol($query, $rol)
    {
        return $query->where('rol_firmante', $rol);
    }

    /**
     * Scope para firmantes de un usuario específico
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Verificar si puede firmar ahora
     */
    public function getPuedeFirmarAttribute(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE || 
               $this->estado === self::ESTADO_NOTIFICADO;
    }

    /**
     * Obtener tiempo transcurrido desde notificación
     */
    public function getTiempoEsperaAttribute(): ?int
    {
        if (!$this->notificado_en) {
            return null;
        }

        $fechaFin = $this->firmado_en ?: $this->rechazado_en ?: now();
        return $this->notificado_en->diffInHours($fechaFin);
    }

    /**
     * Verificar si está atrasado
     */
    public function getAtrasadoAttribute(): bool
    {
        if (!$this->solicitud->fecha_limite || $this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        return $this->solicitud->fecha_limite < now();
    }

    /**
     * Marcar como notificado
     */
    public function marcarNotificado(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_NOTIFICADO,
            'notificado_en' => now()
        ]);

        return true;
    }

    /**
     * Firmar documento
     */
    public function firmar(string $comentario = null, array $metadata = []): bool
    {
        if (!$this->puede_firmar) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_FIRMADO,
            'firmado_en' => now(),
            'comentario' => $comentario,
            'ip_firma' => request()->ip(),
            'metadata_firmante' => array_merge($this->metadata_firmante ?? [], $metadata)
        ]);

        // Verificar si se debe continuar con el siguiente firmante o completar la solicitud
        $this->verificarProgreso();

        return true;
    }

    /**
     * Rechazar firma
     */
    public function rechazar(string $comentario): bool
    {
        if (!$this->puede_firmar) {
            return false;
        }

        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'rechazado_en' => now(),
            'comentario' => $comentario,
            'ip_firma' => request()->ip()
        ]);

        // Si es obligatorio, cancelar toda la solicitud
        if ($this->es_obligatorio) {
            $this->solicitud->cancelar("Rechazado por firmante obligatorio: {$this->usuario->name}");
        } else {
            $this->verificarProgreso();
        }

        return true;
    }

    /**
     * Delegar firma a otro usuario
     */
    public function delegar(User $usuario, string $motivo): bool
    {
        if (!$this->puede_firmar) {
            return false;
        }

        // Crear nuevo firmante delegado
        $firmanteDelegado = $this->solicitud->firmantes()->create([
            'usuario_id' => $usuario->id,
            'orden' => $this->orden,
            'es_obligatorio' => $this->es_obligatorio,
            'rol_firmante' => $this->rol_firmante,
            'estado' => self::ESTADO_PENDIENTE,
            'metadata_firmante' => [
                'delegado_por' => $this->usuario->id,
                'motivo_delegacion' => $motivo,
                'fecha_delegacion' => now()
            ]
        ]);

        // Marcar este firmante como delegado
        $this->update([
            'estado' => self::ESTADO_DELEGADO,
            'comentario' => "Delegado a {$usuario->name}: {$motivo}"
        ]);

        // Notificar al nuevo firmante
        $firmanteDelegado->notificar();

        return true;
    }

    /**
     * Enviar notificación al firmante
     */
    public function notificar(): void
    {
        $this->marcarNotificado();

        // Implementar notificación usando el sistema existente
        // Por ejemplo:
        /*
        Notification::create([
            'user_id' => $this->usuario_id,
            'tipo' => 'solicitud_firma',
            'titulo' => 'Solicitud de Firma Pendiente',
            'mensaje' => "Tienes una solicitud de firma pendiente: {$this->solicitud->titulo}",
            'metadata' => [
                'solicitud_id' => $this->solicitud_firma_id,
                'documento_id' => $this->solicitud->documento_id,
                'prioridad' => $this->solicitud->prioridad
            ]
        ]);
        */
    }

    /**
     * Notificar cancelación de solicitud
     */
    public function notificarCancelacion(): void
    {
        // Implementar notificación de cancelación
        /*
        Notification::create([
            'user_id' => $this->usuario_id,
            'tipo' => 'solicitud_firma_cancelada',
            'titulo' => 'Solicitud de Firma Cancelada',
            'mensaje' => "La solicitud de firma '{$this->solicitud->titulo}' ha sido cancelada",
            'metadata' => [
                'solicitud_id' => $this->solicitud_firma_id,
                'razon' => $this->solicitud->razon_cancelacion
            ]
        ]);
        */
    }

    /**
     * Verificar progreso de la solicitud
     */
    private function verificarProgreso(): void
    {
        $solicitud = $this->solicitud;
        
        // Si es flujo secuencial, notificar al siguiente
        if ($solicitud->tipo_flujo === SolicitudFirma::FLUJO_SECUENCIAL && 
            $this->estado === self::ESTADO_FIRMADO) {
            
            $siguienteFirmante = $solicitud->firmantes()
                                          ->where('estado', self::ESTADO_PENDIENTE)
                                          ->where('orden', '>', $this->orden)
                                          ->orderBy('orden')
                                          ->first();
            
            if ($siguienteFirmante) {
                $siguienteFirmante->notificar();
            }
        }

        // Verificar si se puede completar la solicitud
        $pendientes = $solicitud->firmantes()
                               ->where('es_obligatorio', true)
                               ->where('estado', self::ESTADO_PENDIENTE)
                               ->count();

        if ($pendientes === 0) {
            $solicitud->completar();
        }
    }

    /**
     * Obtener información resumida del firmante
     */
    public function getResumenAttribute(): array
    {
        return [
            'id' => $this->id,
            'usuario' => [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ],
            'orden' => $this->orden,
            'rol' => $this->rol_firmante,
            'es_obligatorio' => $this->es_obligatorio,
            'estado' => $this->estado,
            'puede_firmar' => $this->puede_firmar,
            'atrasado' => $this->atrasado,
            'tiempo_espera' => $this->tiempo_espera,
            'fecha_notificado' => $this->notificado_en,
            'fecha_firmado' => $this->firmado_en,
            'fecha_rechazado' => $this->rechazado_en,
            'comentario' => $this->comentario
        ];
    }
}
