<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRDTiempoRetencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trd_tiempos_retencion';

    protected $fillable = [
        'trd_id',
        'ccd_nivel_id',
        'retencion_archivo_gestion',
        'retencion_archivo_central',
        'disposicion_final',
        'soporte_fisico',
        'soporte_electronico',
        'soporte_hibrido',
        'procedimiento',
        'observaciones',
        'metadatos_adicionales',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'soporte_fisico' => 'boolean',
        'soporte_electronico' => 'boolean',
        'soporte_hibrido' => 'boolean',
        'metadatos_adicionales' => 'array',
        'retencion_archivo_gestion' => 'integer',
        'retencion_archivo_central' => 'integer',
    ];

    /**
     * Relación con la TRD
     */
    public function trd(): BelongsTo
    {
        return $this->belongsTo(TablaRetencionDocumental::class, 'trd_id');
    }

    /**
     * Relación con el nivel del CCD
     */
    public function ccdNivel(): BelongsTo
    {
        return $this->belongsTo(CCDNivel::class, 'ccd_nivel_id');
    }

    /**
     * Usuario que creó el registro
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que modificó el registro
     */
    public function modificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtener el texto de la disposición final
     */
    public function getDisposicionFinalTextoAttribute(): string
    {
        $disposiciones = [
            'CT' => 'Conservación Total',
            'E' => 'Eliminación',
            'D' => 'Digitalización',
            'S' => 'Selección',
            'M' => 'Microfilmación',
        ];

        return $disposiciones[$this->disposicion_final] ?? 'No definida';
    }

    /**
     * Obtener el total de años de retención
     */
    public function getTotalRetencionAttribute(): int
    {
        return $this->retencion_archivo_gestion + $this->retencion_archivo_central;
    }
}
