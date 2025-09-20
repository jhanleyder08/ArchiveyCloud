<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmaDigital extends Model
{
    use HasFactory;

    protected $table = 'firmas_digitales';

    protected $fillable = [
        'documento_id',
        'usuario_id',
        'hash_documento',
        'hash_firma',
        'certificado_info',
        'motivo_firma',
        'fecha_firma',
        'algoritmo_hash',
        'tipo_firma',
        'valida',
        'metadata'
    ];

    protected $casts = [
        'certificado_info' => 'array',
        'metadata' => 'array',
        'fecha_firma' => 'datetime',
        'valida' => 'boolean'
    ];

    protected $dates = [
        'fecha_firma',
        'created_at',
        'updated_at'
    ];

    /**
     * Relación con el documento firmado
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    /**
     * Relación con el usuario que firmó
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scope para firmas válidas
     */
    public function scopeValidas($query)
    {
        return $query->where('valida', true);
    }

    /**
     * Scope para firmas de un usuario específico
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para firmas de un período específico
     */
    public function scopeEnPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_firma', [$fechaInicio, $fechaFin]);
    }

    /**
     * Accessor para obtener el nombre del usuario que firmó
     */
    public function getNombreUsuarioAttribute()
    {
        return $this->usuario ? $this->usuario->name : 'Usuario eliminado';
    }

    /**
     * Accessor para verificar si la firma está vigente
     */
    public function getVigenteAttribute()
    {
        $diasValidez = config('archivey.firma_validez_dias', 365);
        return $this->fecha_firma->addDays($diasValidez)->isFuture();
    }
}
