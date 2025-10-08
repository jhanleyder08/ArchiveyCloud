<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trds';

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
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'fecha_aprobacion' => 'date',
        'fecha_vigencia_inicio' => 'date',
        'fecha_vigencia_fin' => 'date',
    ];

    protected $appends = ['vigente'];

    /**
     * Verificar si la TRD está vigente
     */
    public function getVigenteAttribute(): bool
    {
        if ($this->estado !== 'activa') {
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
     * Series documentales de esta TRD
     */
    public function series(): HasMany
    {
        return $this->hasMany(SerieDocumental::class, 'trd_id');
    }

    /**
     * Versiones históricas
     */
    public function versiones(): HasMany
    {
        return $this->hasMany(TRDVersion::class, 'trd_id');
    }

    /**
     * Usuario que creó la TRD
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que actualizó la TRD
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Usuario que aprobó la TRD
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
        return $this->hasMany(TRDImportacion::class, 'trd_id');
    }

    /**
     * Scope para TRDs activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    /**
     * Scope para TRDs vigentes
     */
    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        
        return $query->where('estado', 'activa')
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
     * Activar TRD
     */
    public function activar(): bool
    {
        $this->estado = 'activa';
        return $this->save();
    }

    /**
     * Archivar TRD
     */
    public function archivar(): bool
    {
        $this->estado = 'archivada';
        return $this->save();
    }

    /**
     * Crear nueva versión
     */
    public function crearVersion(string $nuevaVersion, string $cambios, User $usuario): TRDVersion
    {
        $version = new TRDVersion([
            'trd_id' => $this->id,
            'version_anterior' => $this->version,
            'version_nueva' => $nuevaVersion,
            'cambios' => $cambios,
            'datos_anteriores' => [
                'series' => $this->series()->with('subseries')->get()->toArray(),
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
     * Obtener estadísticas de la TRD
     */
    public function getEstadisticas(): array
    {
        return [
            'total_series' => $this->series()->count(),
            'total_subseries' => SubserieDocumental::whereHas('serie', function ($q) {
                $q->where('trd_id', $this->id);
            })->count(),
            'total_tipos_documentales' => TipoDocumental::whereHas('serie', function ($q) {
                $q->where('trd_id', $this->id);
            })->count(),
            'series_activas' => $this->series()->where('activa', true)->count(),
            'disposicion_final' => Retencion::whereHas('serie', function ($q) {
                $q->where('trd_id', $this->id);
            })->selectRaw('disposicion_final, COUNT(*) as total')
              ->groupBy('disposicion_final')
              ->pluck('total', 'disposicion_final')
              ->toArray(),
        ];
    }
}
