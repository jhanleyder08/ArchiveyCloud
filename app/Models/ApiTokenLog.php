<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiTokenLog extends Model
{
    use HasFactory;

    protected $table = 'api_token_logs';
    
    // Solo usar created_at, no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'api_token_id',
        'ruta',
        'metodo',
        'ip',
        'user_agent',
        'parametros',
        'codigo_respuesta',
        'tiempo_respuesta',
    ];

    protected $casts = [
        'parametros' => 'array',
        'tiempo_respuesta' => 'decimal:3',
    ];

    /**
     * Relación con el token API
     */
    public function apiToken()
    {
        return $this->belongsTo(ApiToken::class);
    }

    /**
     * Scope para logs de un rango de fechas
     */
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    /**
     * Scope para logs exitosos
     */
    public function scopeExitosos($query)
    {
        return $query->where('codigo_respuesta', '>=', 200)->where('codigo_respuesta', '<', 300);
    }

    /**
     * Scope para logs con errores
     */
    public function scopeConErrores($query)
    {
        return $query->where('codigo_respuesta', '>=', 400);
    }
}
