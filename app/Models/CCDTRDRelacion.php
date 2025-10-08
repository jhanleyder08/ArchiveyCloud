<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCDTRDRelacion extends Model
{
    use HasFactory;

    protected $table = 'ccd_trd_relaciones';

    protected $fillable = [
        'ccd_nivel_id',
        'serie_id',
        'subserie_id',
        'tipo_relacion',
        'notas',
    ];

    // Tipos de relación
    const RELACION_MAPEO_DIRECTO = 'mapeo_directo';
    const RELACION_EQUIVALENCIA = 'equivalencia';
    const RELACION_INCLUYE = 'incluye';

    /**
     * Nivel CCD asociado
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(CCDNivel::class, 'ccd_nivel_id');
    }

    /**
     * Serie documental asociada
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_id');
    }

    /**
     * Subserie documental asociada
     */
    public function subserie(): BelongsTo
    {
        return $this->belongsTo(SubserieDocumental::class, 'subserie_id');
    }

    /**
     * Obtener tipos de relación disponibles
     */
    public static function getTiposRelacion(): array
    {
        return [
            self::RELACION_MAPEO_DIRECTO => 'Mapeo Directo',
            self::RELACION_EQUIVALENCIA => 'Equivalencia',
            self::RELACION_INCLUYE => 'Incluye',
        ];
    }
}
