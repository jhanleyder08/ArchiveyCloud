<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuadros_clasificacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'version',
        'estado',
        'fecha_aprobacion',
        'fecha_vigencia_inicio',
        'fecha_vigencia_fin',
        'aprobado_por',
        'vocabulario_controlado',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vocabulario_controlado' => 'array',
        'metadata' => 'array',
        'fecha_aprobacion' => 'date',
        'fecha_vigencia_inicio' => 'date',
        'fecha_vigencia_fin' => 'date',
    ];

    protected $appends = ['vigente'];

    /**
     * Verificar si el CCD está vigente
     */
    public function getVigenteAttribute(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        $hoy = now()->toDateString();

        if ($this->fecha_vigencia_inicio && $hoy < $this->fecha_vigencia_inicio) {
            return false;
        }

        if ($this->fecha_vigencia_fin && $hoy > $this->fecha_vigencia_fin) {
            return false;
        }

        return true;
    }

    /**
     * Niveles jerárquicos del CCD
     */
    public function niveles(): HasMany
    {
        return $this->hasMany(CCDNivel::class, 'ccd_id');
    }

    /**
     * Niveles raíz (primer nivel)
     */
    public function nivelesRaiz(): HasMany
    {
        return $this->hasMany(CCDNivel::class, 'ccd_id')
            ->whereNull('parent_id')
            ->orderBy('orden');
    }

    /**
     * Vocabulario controlado
     */
    public function vocabularios(): HasMany
    {
        return $this->hasMany(CCDVocabulario::class, 'ccd_id');
    }

    /**
     * Versiones históricas
     */
    public function versiones(): HasMany
    {
        return $this->hasMany(CCDVersion::class, 'ccd_id');
    }

    /**
     * Usuario creador
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario actualizador
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Usuario aprobador
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Importaciones/Exportaciones
     */
    public function importaciones(): HasMany
    {
        return $this->hasMany(CCDImportacion::class, 'ccd_id');
    }

    /**
     * Scope para CCDs activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para CCDs vigentes
     */
    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        
        return $query->where('estado', 'activo')
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_vigencia_inicio')
                  ->orWhere('fecha_vigencia_inicio', '<=', $hoy);
            })
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_vigencia_fin')
                  ->orWhere('fecha_vigencia_fin', '>=', $hoy);
            });
    }

    /**
     * Activar CCD
     */
    public function activar(): bool
    {
        $this->estado = 'activo';
        return $this->save();
    }

    /**
     * Archivar CCD
     */
    public function archivar(): bool
    {
        $this->estado = 'archivado';
        return $this->save();
    }

    /**
     * Obtener estructura jerárquica completa
     */
    public function getEstructuraJerarquica(): array
    {
        return $this->nivelesRaiz()
            ->with('hijos')
            ->get()
            ->map(function ($nivel) {
                return $nivel->toArrayConHijos();
            })
            ->toArray();
    }

    /**
     * Buscar nivel por código
     */
    public function buscarNivelPorCodigo(string $codigo): ?CCDNivel
    {
        return $this->niveles()->where('codigo', $codigo)->first();
    }

    /**
     * Obtener todos los niveles de un tipo específico
     */
    public function getNivelesPorTipo(string $tipo): \Illuminate\Database\Eloquent\Collection
    {
        return $this->niveles()->where('tipo_nivel', $tipo)->get();
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(string $nuevaVersion, string $cambios, User $usuario): CCDVersion
    {
        $version = new CCDVersion([
            'ccd_id' => $this->id,
            'version_anterior' => $this->version,
            'version_nueva' => $nuevaVersion,
            'cambios' => $cambios,
            'datos_anteriores' => [
                'niveles' => $this->niveles()->with('hijos')->get()->toArray(),
            ],
            'modificado_por' => $usuario->id,
            'fecha_cambio' => now(),
        ]);

        $version->save();

        $this->version = $nuevaVersion;
        $this->updated_by = $usuario->id;
        $this->save();

        return $version;
    }

    /**
     * Obtener estadísticas del CCD
     */
    public function getEstadisticas(): array
    {
        $niveles = $this->niveles;

        return [
            'total_niveles' => $niveles->count(),
            'niveles_activos' => $niveles->where('activo', true)->count(),
            'por_tipo' => $niveles->groupBy('tipo_nivel')->map(function ($grupo) {
                return $grupo->count();
            })->toArray(),
            'niveles_por_profundidad' => $niveles->groupBy('nivel')->map(function ($grupo) {
                return $grupo->count();
            })->toArray(),
            'total_vocabularios' => $this->vocabularios()->count(),
            'profundidad_maxima' => $niveles->max('nivel') ?? 0,
        ];
    }

    /**
     * Validar estructura del CCD
     */
    public function validar(): array
    {
        $errores = [];

        // Validar que tenga al menos un nivel raíz
        if ($this->nivelesRaiz()->count() === 0) {
            $errores[] = 'El CCD debe tener al menos un nivel raíz (Fondo)';
        }

        // Validar integridad de jerarquía
        $nivelesHuerfanos = $this->niveles()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('padre')
            ->count();

        if ($nivelesHuerfanos > 0) {
            $errores[] = "Hay {$nivelesHuerfanos} niveles con padres inválidos";
        }

        return $errores;
    }
}
