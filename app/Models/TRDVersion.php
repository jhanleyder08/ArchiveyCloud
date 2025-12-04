<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRDVersion extends Model
{
    use HasFactory;

    protected $table = 'trd_versiones';

    protected $fillable = [
        'trd_id',
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
     * TRD asociada
     */
    public function trd(): BelongsTo
    {
        return $this->belongsTo(TRD::class, 'trd_id');
    }

    /**
     * Usuario que modificó
     */
    public function modificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }
}
