<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCDVocabulario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ccd_vocabularios';

    protected $fillable = [
        'ccd_id',
        'termino',
        'termino_preferido',
        'definicion',
        'tipo',
        'termino_padre_id',
        'terminos_relacionados',
        'metadata',
    ];

    protected $casts = [
        'terminos_relacionados' => 'array',
        'metadata' => 'array',
    ];

    // Tipos de términos
    const TIPO_DESCRIPTOR = 'descriptor';
    const TIPO_NO_PREFERIDO = 'termino_no_preferido';
    const TIPO_RELACIONADO = 'termino_relacionado';

    /**
     * CCD al que pertenece
     */
    public function ccd(): BelongsTo
    {
        return $this->belongsTo(CCD::class, 'ccd_id');
    }

    /**
     * Término padre (para jerarquías)
     */
    public function terminoPadre(): BelongsTo
    {
        return $this->belongsTo(CCDVocabulario::class, 'termino_padre_id');
    }

    /**
     * Obtener término preferido si es sinónimo
     */
    public function getTerminoPreferidoOActual(): string
    {
        return $this->termino_preferido ?? $this->termino;
    }

    /**
     * Scope para descriptores
     */
    public function scopeDescriptores($query)
    {
        return $query->where('tipo', self::TIPO_DESCRIPTOR);
    }
}
