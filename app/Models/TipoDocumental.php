<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_documentales';

    protected $fillable = [
        'subserie_id',
        'serie_id',
        'codigo',
        'nombre',
        'descripcion',
        'formatos_permitidos',
        'orden',
        'activo',
    ];

    protected $casts = [
        'formatos_permitidos' => 'array',
        'activo' => 'boolean',
    ];

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
     * Scope para tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Validar formato de archivo
     */
    public function validarFormato(string $formato): bool
    {
        if (empty($this->formatos_permitidos)) {
            return true; // Sin restricción
        }

        return in_array(strtolower($formato), array_map('strtolower', $this->formatos_permitidos));
    }
}
