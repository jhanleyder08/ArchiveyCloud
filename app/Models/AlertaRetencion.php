<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modelo para Alertas de Retención del SGDEA
 * 
 * Implementa requerimientos:
 * - REQ-RD-007: Sistema de alertas (notificaciones automáticas)
 * - REQ-RD-003: Propagación de cambios
 * - REQ-RD-010: Reportes y seguimiento
 */
class AlertaRetencion extends Model
{
    use HasFactory;

    protected $table = 'alertas_retencion';

    protected $fillable = [
        'proceso_retencion_id',
        'tipo_alerta',
        'nivel_prioridad',
        'titulo_alerta',
        'mensaje_alerta',
        'fecha_vencimiento_relacionada',
        'destinatarios_usuarios',
        'destinatarios_roles',
        'canales_envio',
        'estado_alerta',
        'fecha_envio',
        'fecha_lectura',
        'fecha_atencion',
        'repetir_hasta_atencion',
        'intervalo_repeticion_horas',
        'max_repeticiones',
        'repeticiones_enviadas'
    ];

    protected $casts = [
        'fecha_vencimiento_relacionada' => 'date',
        'destinatarios_usuarios' => 'array',
        'destinatarios_roles' => 'array',
        'canales_envio' => 'array',
        'fecha_envio' => 'datetime',
        'fecha_lectura' => 'datetime',
        'fecha_atencion' => 'datetime',
        'repetir_hasta_atencion' => 'boolean'
    ];

    // Tipos de alerta
    const TIPO_VENCIMIENTO_PROXIMO = 'vencimiento_proximo';
    const TIPO_VENCIMIENTO_ACTUAL = 'vencimiento_actual';
    const TIPO_ACCION_REQUERIDA = 'accion_requerida';
    const TIPO_ERROR_PROCESO = 'error_proceso';
    const TIPO_CONFIRMACION_DISPOSICION = 'confirmacion_disposicion';

    // Niveles de prioridad
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_CRITICA = 'critica';

    // Estados de alerta
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_ENVIADA = 'enviada';
    const ESTADO_LEIDA = 'leida';
    const ESTADO_ATENDIDA = 'atendida';
    const ESTADO_DESCARTADA = 'descartada';

    // Canales de envío
    const CANAL_EMAIL = 'email';
    const CANAL_SMS = 'sms';
    const CANAL_SISTEMA = 'sistema';
    const CANAL_PUSH = 'push';

    protected static function boot()
    {
        parent::boot();

        // Configurar valores por defecto al crear
        static::creating(function ($alerta) {
            if (empty($alerta->canales_envio)) {
                $alerta->canales_envio = [self::CANAL_SISTEMA, self::CANAL_EMAIL];
            }
            
            if (empty($alerta->max_repeticiones)) {
                $alerta->max_repeticiones = $alerta->nivel_prioridad === self::PRIORIDAD_CRITICA ? 10 : 3;
            }
        });
    }

    /**
     * Relaciones
     */
    public function procesoRetencion()
    {
        return $this->belongsTo(ProcesoRetencionDisposicion::class, 'proceso_retencion_id');
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_alerta', self::ESTADO_PENDIENTE);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('estado_alerta', self::ESTADO_ENVIADA);
    }

    public function scopeRequierenAtencion($query)
    {
        return $query->whereIn('estado_alerta', [self::ESTADO_PENDIENTE, self::ESTADO_ENVIADA]);
    }

    public function scopePorPrioridad($query, string $prioridad)
    {
        return $query->where('nivel_prioridad', $prioridad);
    }

    public function scopeCriticas($query)
    {
        return $query->where('nivel_prioridad', self::PRIORIDAD_CRITICA);
    }

    public function scopeProximasVencer($query, int $dias = 7)
    {
        return $query->where('fecha_vencimiento_relacionada', '<=', now()->addDays($dias));
    }

    public function scopeRequierenRepeticion($query)
    {
        return $query->where('repetir_hasta_atencion', true)
                    ->where('estado_alerta', self::ESTADO_ENVIADA)
                    ->where('repeticiones_enviadas', '<', 'max_repeticiones')
                    ->whereRaw('TIMESTAMPDIFF(HOUR, fecha_envio, NOW()) >= intervalo_repeticion_horas');
    }

    /**
     * REQ-RD-007: Generar alerta automática para un proceso
     */
    public static function generarAlertaAutomatica(ProcesoRetencionDisposicion $proceso): ?self
    {
        $diasRestantes = $proceso->dias_hasta_vencimiento;
        
        // Determinar tipo y prioridad de alerta
        if ($diasRestantes <= 0) {
            $tipo = self::TIPO_VENCIMIENTO_ACTUAL;
            $prioridad = self::PRIORIDAD_CRITICA;
            $titulo = 'Documento Vencido - Acción Requerida';
            $mensaje = "El documento/expediente ha vencido su período de retención y requiere disposición inmediata.";
        } elseif ($diasRestantes <= 7) {
            $tipo = self::TIPO_VENCIMIENTO_PROXIMO;
            $prioridad = self::PRIORIDAD_ALTA;
            $titulo = 'Vencimiento Inminente';
            $mensaje = "El documento/expediente vencerá en {$diasRestantes} días. Prepare la disposición correspondiente.";
        } elseif ($diasRestantes <= 30) {
            $tipo = self::TIPO_VENCIMIENTO_PROXIMO;
            $prioridad = self::PRIORIDAD_MEDIA;
            $titulo = 'Vencimiento Próximo';
            $mensaje = "El documento/expediente vencerá en {$diasRestantes} días. Revise y prepare la disposición.";
        } else {
            return null; // No generar alerta si falta mucho tiempo
        }

        // Verificar si ya existe una alerta similar reciente
        $alertaExistente = self::where('proceso_retencion_id', $proceso->id)
            ->where('tipo_alerta', $tipo)
            ->where('created_at', '>=', now()->subDays(1))
            ->first();

        if ($alertaExistente) {
            return $alertaExistente; // No duplicar alertas
        }

        // Obtener destinatarios basado en roles y responsabilidades
        $destinatarios = self::obtenerDestinatariosAutomaticos($proceso);

        return self::create([
            'proceso_retencion_id' => $proceso->id,
            'tipo_alerta' => $tipo,
            'nivel_prioridad' => $prioridad,
            'titulo_alerta' => $titulo,
            'mensaje_alerta' => $mensaje,
            'fecha_vencimiento_relacionada' => $proceso->fecha_vencimiento_gestion,
            'destinatarios_usuarios' => $destinatarios['usuarios'],
            'destinatarios_roles' => $destinatarios['roles'],
            'canales_envio' => self::determinarCanalesEnvio($prioridad),
            'repetir_hasta_atencion' => $prioridad === self::PRIORIDAD_CRITICA,
            'intervalo_repeticion_horas' => $prioridad === self::PRIORIDAD_CRITICA ? 4 : 24
        ]);
    }

    /**
     * Obtener destinatarios automáticos basado en el proceso
     */
    private static function obtenerDestinatariosAutomaticos(ProcesoRetencionDisposicion $proceso): array
    {
        $roles = ['Archivista', 'Administrador'];
        $usuarios = [];

        // Incluir al creador del documento/expediente
        if ($proceso->documento && $proceso->documento->created_by) {
            $usuarios[] = $proceso->documento->created_by;
        } elseif ($proceso->expediente && $proceso->expediente->created_by) {
            $usuarios[] = $proceso->expediente->created_by;
        }

        // Incluir responsables de archivo según prioridad
        if (in_array($proceso->estado, [ProcesoRetencionDisposicion::ESTADO_VENCIDO, ProcesoRetencionDisposicion::ESTADO_ALERTA_PREVIA])) {
            $roles[] = 'Administrador General';
            $roles[] = 'Jefe Archivo';
        }

        return [
            'usuarios' => array_unique($usuarios),
            'roles' => array_unique($roles)
        ];
    }

    /**
     * Determinar canales de envío basado en prioridad
     */
    private static function determinarCanalesEnvio(string $prioridad): array
    {
        return match($prioridad) {
            self::PRIORIDAD_CRITICA => [self::CANAL_EMAIL, self::CANAL_SISTEMA, self::CANAL_PUSH],
            self::PRIORIDAD_ALTA => [self::CANAL_EMAIL, self::CANAL_SISTEMA],
            self::PRIORIDAD_MEDIA => [self::CANAL_SISTEMA, self::CANAL_EMAIL],
            self::PRIORIDAD_BAJA => [self::CANAL_SISTEMA],
            default => [self::CANAL_SISTEMA]
        };
    }

    /**
     * REQ-RD-007: Marcar alerta como enviada
     */
    public function marcarComoEnviada(): void
    {
        $this->estado_alerta = self::ESTADO_ENVIADA;
        $this->fecha_envio = now();
        $this->repeticiones_enviadas = $this->repeticiones_enviadas + 1;
        $this->save();
    }

    /**
     * Marcar alerta como leída
     */
    public function marcarComoLeida(User $usuario = null): void
    {
        $this->estado_alerta = self::ESTADO_LEIDA;
        $this->fecha_lectura = now();
        $this->save();

        // Registrar en auditoría del proceso
        if ($this->procesoRetencion) {
            $this->procesoRetencion->historialAcciones()->create([
                'tipo_accion' => 'lectura_alerta',
                'descripcion_accion' => "Alerta '{$this->titulo_alerta}' marcada como leída",
                'datos_adicionales' => ['alerta_id' => $this->id],
                'fecha_accion' => now(),
                'usuario_accion' => $usuario?->id ?? auth()->id(),
                'ip_origen' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }

    /**
     * Marcar alerta como atendida
     */
    public function marcarComoAtendida(User $usuario = null, string $observaciones = null): void
    {
        $this->estado_alerta = self::ESTADO_ATENDIDA;
        $this->fecha_atencion = now();
        $this->save();

        // Registrar en auditoría del proceso
        if ($this->procesoRetencion) {
            $this->procesoRetencion->historialAcciones()->create([
                'tipo_accion' => 'atencion_alerta',
                'descripcion_accion' => "Alerta '{$this->titulo_alerta}' atendida" . ($observaciones ? ": {$observaciones}" : ""),
                'datos_adicionales' => [
                    'alerta_id' => $this->id,
                    'observaciones' => $observaciones
                ],
                'fecha_accion' => now(),
                'usuario_accion' => $usuario?->id ?? auth()->id(),
                'ip_origen' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }

    /**
     * Verificar si la alerta requiere repetición
     */
    public function requiereRepeticion(): bool
    {
        if (!$this->repetir_hasta_atencion || $this->estado_alerta !== self::ESTADO_ENVIADA) {
            return false;
        }

        if ($this->repeticiones_enviadas >= $this->max_repeticiones) {
            return false;
        }

        if (!$this->fecha_envio || !$this->intervalo_repeticion_horas) {
            return false;
        }

        $horasTranscurridas = $this->fecha_envio->diffInHours(now());
        return $horasTranscurridas >= $this->intervalo_repeticion_horas;
    }

    /**
     * Obtener color para UI basado en prioridad
     */
    public function getColorPrioridadAttribute(): string
    {
        return match($this->nivel_prioridad) {
            self::PRIORIDAD_CRITICA => 'red',
            self::PRIORIDAD_ALTA => 'orange',
            self::PRIORIDAD_MEDIA => 'yellow',
            self::PRIORIDAD_BAJA => 'blue',
            default => 'gray'
        };
    }

    /**
     * Obtener icono para UI basado en tipo
     */
    public function getIconoTipoAttribute(): string
    {
        return match($this->tipo_alerta) {
            self::TIPO_VENCIMIENTO_PROXIMO => 'clock',
            self::TIPO_VENCIMIENTO_ACTUAL => 'alert-triangle',
            self::TIPO_ACCION_REQUERIDA => 'exclamation-circle',
            self::TIPO_ERROR_PROCESO => 'x-circle',
            self::TIPO_CONFIRMACION_DISPOSICION => 'check-circle',
            default => 'bell'
        };
    }

    /**
     * Verificar si la alerta está vencida (no atendida después del vencimiento)
     */
    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_vencimiento_relacionada < now() && 
               in_array($this->estado_alerta, [self::ESTADO_PENDIENTE, self::ESTADO_ENVIADA]);
    }

    /**
     * Obtener días restantes hasta vencimiento
     */
    public function getDiasHastaVencimientoAttribute(): int
    {
        return now()->diffInDays($this->fecha_vencimiento_relacionada, false);
    }
}
