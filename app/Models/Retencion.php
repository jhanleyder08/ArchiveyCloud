<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Retencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retenciones';

    protected $fillable = [
        'serie_id',
        'subserie_id',
        'tipo_documental_id',
        'retencion_archivo_gestion',
        'retencion_archivo_central',
        'disposicion_final',
        'procedimiento_disposicion',
        'justificacion',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Constantes de disposición final
    const CONSERVACION_TOTAL = 'conservacion_total';
    const ELIMINACION = 'eliminacion';
    const SELECCION = 'seleccion';
    const TRANSFERENCIA_HISTORICA = 'transferencia_historica';
    const DIGITALIZACION_ELIMINACION = 'digitalizacion_eliminacion_fisica';

    /**
     * Serie documental
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_id');
    }

    /**
     * Subserie documental
     */
    public function subserie(): BelongsTo
    {
        return $this->belongsTo(SubserieDocumental::class, 'subserie_id');
    }

    /**
     * Tipo documental
     */
    public function tipoDocumental(): BelongsTo
    {
        return $this->belongsTo(TipoDocumental::class, 'tipo_documental_id');
    }

    /**
     * Calcular tiempo total de retención
     */
    public function getTiempoTotalAttribute(): int
    {
        return $this->retencion_archivo_gestion + $this->retencion_archivo_central;
    }

    /**
     * Calcular fecha de disposición final para un documento
     */
    public function calcularFechaDisposicion(Carbon $fechaCreacion): Carbon
    {
        return $fechaCreacion->copy()->addYears($this->tiempo_total);
    }

    /**
     * Verificar si un documento debe ser dispuesto
     */
    public function debeDisponerse(Carbon $fechaCreacion): bool
    {
        $fechaDisposicion = $this->calcularFechaDisposicion($fechaCreacion);
        return now()->greaterThanOrEqualTo($fechaDisposicion);
    }

    /**
     * Obtener disposiciones finales disponibles
     */
    public static function getDisposicionesFinales(): array
    {
        return [
            self::CONSERVACION_TOTAL => 'Conservación Total',
            self::ELIMINACION => 'Eliminación',
            self::SELECCION => 'Selección',
            self::TRANSFERENCIA_HISTORICA => 'Transferencia Histórica',
            self::DIGITALIZACION_ELIMINACION => 'Digitalización y Eliminación Física',
        ];
    }
}
