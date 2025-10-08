<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CCDNivel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ccd_niveles';

    protected $fillable = [
        'ccd_id',
        'parent_id',
        'codigo',
        'nombre',
        'descripcion',
        'nivel',
        'tipo_nivel',
        'orden',
        'activo',
        'ruta',
        'palabras_clave',
        'metadata',
    ];

    protected $casts = [
        'palabras_clave' => 'array',
        'metadata' => 'array',
        'activo' => 'boolean',
    ];

    // Tipos de nivel
    const TIPO_FONDO = 'fondo';
    const TIPO_SECCION = 'seccion';
    const TIPO_SUBSECCION = 'subseccion';
    const TIPO_SERIE = 'serie';
    const TIPO_SUBSERIE = 'subserie';

    /**
     * CCD al que pertenece
     */
    public function ccd(): BelongsTo
    {
        return $this->belongsTo(CCD::class, 'ccd_id');
    }

    /**
     * Nivel padre
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(CCDNivel::class, 'parent_id');
    }

    /**
     * Niveles hijos
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(CCDNivel::class, 'parent_id')->orderBy('orden');
    }

    /**
     * Permisos asignados a este nivel
     */
    public function permisos(): HasMany
    {
        return $this->hasMany(CCDPermiso::class, 'ccd_nivel_id');
    }

    /**
     * Relaciones con TRD
     */
    public function relacionesTRD(): HasMany
    {
        return $this->hasMany(CCDTRDRelacion::class, 'ccd_nivel_id');
    }

    /**
     * Scope para niveles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope por tipo de nivel
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_nivel', $tipo);
    }

    /**
     * Scope para niveles raíz
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Obtener ruta jerárquica completa
     */
    public function getRutaCompletaAttribute(): string
    {
        if ($this->ruta) {
            return $this->ruta;
        }

        $ruta = [$this->codigo];
        $actual = $this;

        while ($actual->parent_id) {
            $actual = $actual->padre;
            if ($actual) {
                array_unshift($ruta, $actual->codigo);
            } else {
                break;
            }
        }

        return implode(' > ', $ruta);
    }

    /**
     * Actualizar ruta jerárquica
     */
    public function actualizarRuta(): void
    {
        $this->ruta = $this->getRutaCompletaAttribute();
        $this->save();

        // Actualizar rutas de hijos recursivamente
        foreach ($this->hijos as $hijo) {
            $hijo->actualizarRuta();
        }
    }

    /**
     * Obtener todos los ancestros
     */
    public function getAncestros(): \Illuminate\Support\Collection
    {
        $ancestros = collect();
        $actual = $this;

        while ($actual->parent_id) {
            $actual = $actual->padre;
            if ($actual) {
                $ancestros->prepend($actual);
            } else {
                break;
            }
        }

        return $ancestros;
    }

    /**
     * Obtener todos los descendientes (recursivo)
     */
    public function getDescendientes(): \Illuminate\Support\Collection
    {
        $descendientes = collect();

        foreach ($this->hijos as $hijo) {
            $descendientes->push($hijo);
            $descendientes = $descendientes->merge($hijo->getDescendientes());
        }

        return $descendientes;
    }

    /**
     * Verificar si es nivel raíz
     */
    public function esRaiz(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Verificar si es nivel hoja (sin hijos)
     */
    public function esHoja(): bool
    {
        return $this->hijos()->count() === 0;
    }

    /**
     * Obtener profundidad del nodo
     */
    public function getProfundidad(): int
    {
        return $this->nivel;
    }

    /**
     * Mover a otro padre
     */
    public function moverA(?CCDNivel $nuevoPadre): bool
    {
        // Validar que no se mueva a sí mismo o a sus descendientes
        if ($nuevoPadre && $nuevoPadre->id === $this->id) {
            return false;
        }

        if ($nuevoPadre) {
            $descendientes = $this->getDescendientes();
            if ($descendientes->contains('id', $nuevoPadre->id)) {
                return false;
            }
        }

        $this->parent_id = $nuevoPadre?->id;
        $this->nivel = $nuevoPadre ? $nuevoPadre->nivel + 1 : 1;
        $this->save();

        $this->actualizarRuta();
        $this->actualizarNivelesHijos();

        return true;
    }

    /**
     * Actualizar niveles de hijos recursivamente
     */
    private function actualizarNivelesHijos(): void
    {
        foreach ($this->hijos as $hijo) {
            $hijo->nivel = $this->nivel + 1;
            $hijo->save();
            $hijo->actualizarRuta();
            $hijo->actualizarNivelesHijos();
        }
    }

    /**
     * Convertir a array con hijos recursivamente
     */
    public function toArrayConHijos(): array
    {
        $array = $this->toArray();
        $array['hijos'] = $this->hijos->map(function ($hijo) {
            return $hijo->toArrayConHijos();
        })->toArray();
        return $array;
    }

    /**
     * Obtener tipos de nivel disponibles
     */
    public static function getTiposNivel(): array
    {
        return [
            self::TIPO_FONDO => 'Fondo',
            self::TIPO_SECCION => 'Sección',
            self::TIPO_SUBSECCION => 'Subsección',
            self::TIPO_SERIE => 'Serie',
            self::TIPO_SUBSERIE => 'Subserie',
        ];
    }

    /**
     * Clonar nivel con hijos
     */
    public function clonar(?CCDNivel $nuevoPadre = null): CCDNivel
    {
        $clone = $this->replicate();
        $clone->parent_id = $nuevoPadre?->id ?? $this->parent_id;
        $clone->codigo = $this->codigo . '_COPIA';
        $clone->save();

        // Clonar hijos recursivamente
        foreach ($this->hijos as $hijo) {
            $hijo->clonar($clone);
        }

        return $clone;
    }
}
