<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCDPermiso extends Model
{
    use HasFactory;

    protected $table = 'ccd_permisos';

    protected $fillable = [
        'ccd_nivel_id',
        'role_id',
        'user_id',
        'tipo_permiso',
        'heredable',
    ];

    protected $casts = [
        'heredable' => 'boolean',
    ];

    // Tipos de permisos
    const PERMISO_LECTURA = 'lectura';
    const PERMISO_ESCRITURA = 'escritura';
    const PERMISO_ADMINISTRACION = 'administracion';

    /**
     * Nivel CCD asociado
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(CCDNivel::class, 'ccd_nivel_id');
    }

    /**
     * Rol asociado
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Usuario asociado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtener tipos de permisos disponibles
     */
    public static function getTiposPermiso(): array
    {
        return [
            self::PERMISO_LECTURA => 'Lectura',
            self::PERMISO_ESCRITURA => 'Escritura',
            self::PERMISO_ADMINISTRACION => 'Administración',
        ];
    }
}
