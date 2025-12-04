<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Cuadro de Clasificación Documental (CCD)
 */
class CuadroClasificacionDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuadros_clasificacion_documental';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'fecha_aprobacion',
        'version',
        'created_by',
        'updated_by',
        'observaciones'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_aprobacion' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relación con el usuario creador
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con series documentales
     */
    public function series()
    {
        return $this->hasMany(SerieDocumental::class, 'cuadro_clasificacion_id');
    }

    /**
     * Scope para elementos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
