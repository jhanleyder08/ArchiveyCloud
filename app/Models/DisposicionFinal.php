<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DisposicionFinal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expediente_id',
        'documento_id',
        'responsable_id',
        'aprobado_por',
        'tipo_disposicion',
        'estado',
        'fecha_vencimiento_retencion',
        'fecha_propuesta',
        'fecha_aprobacion',
        'fecha_ejecucion',
        'justificacion',
        'observaciones',
        'observaciones_rechazo',
        'documentos_soporte',
        'metadata_proceso',
        'cumple_normativa',
        'validacion_legal',
        'acta_comite',
        'metodo_eliminacion',
        'empresa_ejecutora',
        'certificado_destruccion',
        'datos_responsable_externo',
    ];

    protected $casts = [
        'fecha_vencimiento_retencion' => 'date',
        'fecha_propuesta' => 'date',
        'fecha_aprobacion' => 'date',
        'fecha_ejecucion' => 'date',
        'documentos_soporte' => 'array',
        'metadata_proceso' => 'array',
        'cumple_normativa' => 'boolean',
        'datos_responsable_externo' => 'array',
    ];

    // Constantes para tipos de disposición
    const TIPO_CONSERVACION_PERMANENTE = 'conservacion_permanente';
    const TIPO_ELIMINACION_CONTROLADA = 'eliminacion_controlada';
    const TIPO_TRANSFERENCIA_HISTORICA = 'transferencia_historica';
    const TIPO_DIGITALIZACION = 'digitalizacion';
    const TIPO_MICROFILMACION = 'microfilmacion';

    // Constantes para estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_REVISION = 'en_revision';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_EJECUTADO = 'ejecutado';
    const ESTADO_CANCELADO = 'cancelado';

    // Relaciones
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Métodos auxiliares
    public function puedeSerAprobada(): bool
    {
        return $this->estado === self::ESTADO_EN_REVISION && 
               $this->cumple_normativa;
    }

    public function puedeSerEjecutada(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    public function estaVencida(): bool
    {
        if (!$this->fecha_vencimiento_retencion) {
            return false;
        }
        return $this->fecha_vencimiento_retencion < Carbon::now();
    }

    public function diasParaVencimiento(): int
    {
        if (!$this->fecha_vencimiento_retencion) {
            return 0;
        }
        return Carbon::now()->diffInDays($this->fecha_vencimiento_retencion, false);
    }

    public function getItemAfectadoAttribute(): string
    {
        if ($this->expediente) {
            return "Expediente: {$this->expediente->codigo} - {$this->expediente->titulo}";
        }
        
        if ($this->documento) {
            return "Documento: {$this->documento->nombre}";
        }
        
        return 'Item no disponible';
    }

    public function getTipoDisposicionLabelAttribute(): string
    {
        return match($this->tipo_disposicion) {
            self::TIPO_CONSERVACION_PERMANENTE => 'Conservación Permanente',
            self::TIPO_ELIMINACION_CONTROLADA => 'Eliminación Controlada',
            self::TIPO_TRANSFERENCIA_HISTORICA => 'Transferencia Histórica',
            self::TIPO_DIGITALIZACION => 'Digitalización',
            self::TIPO_MICROFILMACION => 'Microfilmación',
            default => 'No definido'
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_EN_REVISION => 'En Revisión',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_EJECUTADO => 'Ejecutado',
            self::ESTADO_CANCELADO => 'Cancelado',
            default => 'No definido'
        };
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento_retencion', '<', Carbon::now());
    }

    public function scopeProximasAVencer($query, $dias = 30)
    {
        return $query->whereBetween('fecha_vencimiento_retencion', [
            Carbon::now(),
            Carbon::now()->addDays($dias)
        ]);
    }
}
