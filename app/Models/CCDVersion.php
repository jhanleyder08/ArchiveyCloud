<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCDVersion extends Model
{
    use HasFactory;

    protected $table = 'ccd_versiones';

    protected $fillable = [
        'ccd_id',
        'version_anterior',
        'version_nueva',
        'cambios',
        'datos_anteriores',
        'modificado_por',
        'fecha_cambio',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'fecha_cambio' => 'datetime',
    ];

    /**
     * CCD asociado
     */
    public function ccd(): BelongsTo
    {
        return $this->belongsTo(CCD::class, 'ccd_id');
    }

    /**
     * Usuario que modificó
     */
    public function modificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }
}
