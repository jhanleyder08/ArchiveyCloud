<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class IndiceElectronico extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_entidad',
        'entidad_id',
        'codigo_clasificacion',
        'titulo',
        'descripcion',
        'metadatos',
        'palabras_clave',
        'serie_documental',
        'subserie_documental',
        'fecha_inicio',
        'fecha_fin',
        'responsable',
        'ubicacion_fisica',
        'ubicacion_digital',
        'nivel_acceso',
        'estado_conservacion',
        'cantidad_folios',
        'formato_archivo',
        'tamaño_bytes',
        'hash_integridad',
        'es_vital',
        'es_historico',
        'fecha_indexacion',
        'usuario_indexacion_id',
        'fecha_ultima_actualizacion',
        'usuario_actualizacion_id',
    ];

    protected $casts = [
        'metadatos' => 'array',
        'palabras_clave' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_indexacion' => 'date',
        'fecha_ultima_actualizacion' => 'datetime',
        'es_vital' => 'boolean',
        'es_historico' => 'boolean',
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'fecha_indexacion',
        'fecha_ultima_actualizacion',
    ];

    // Relaciones
    public function usuarioIndexacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_indexacion_id');
    }

    public function usuarioActualizacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_actualizacion_id');
    }

    // Relación polimórfica para obtener la entidad indexada
    public function entidad()
    {
        switch ($this->tipo_entidad) {
            case 'expediente':
                return $this->belongsTo(Expediente::class, 'entidad_id');
            case 'documento':
                return $this->belongsTo(Documento::class, 'entidad_id');
            case 'serie':
                return $this->belongsTo(SerieDocumental::class, 'entidad_id');
            case 'subserie':
                return $this->belongsTo(SubserieDocumental::class, 'entidad_id');
            default:
                return null;
        }
    }

    // Scopes
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_entidad', $tipo);
    }

    public function scopePorSerie(Builder $query, string $serie): Builder
    {
        return $query->where('serie_documental', $serie);
    }

    public function scopePorNivelAcceso(Builder $query, string $nivel): Builder
    {
        return $query->where('nivel_acceso', $nivel);
    }

    public function scopeVitales(Builder $query): Builder
    {
        return $query->where('es_vital', true);
    }

    public function scopeHistoricos(Builder $query): Builder
    {
        return $query->where('es_historico', true);
    }

    public function scopePorFechas(Builder $query, $fechaInicio = null, $fechaFin = null): Builder
    {
        if ($fechaInicio) {
            $query->where('fecha_inicio', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('fecha_fin', '<=', $fechaFin);
        }
        return $query;
    }

    public function scopeBusquedaTexto(Builder $query, string $termino): Builder
    {
        return $query->whereRaw("MATCH(titulo, descripcion) AGAINST(? IN BOOLEAN MODE)", [$termino]);
    }

    public function scopePorPalabrasClave(Builder $query, array $palabras): Builder
    {
        foreach ($palabras as $palabra) {
            $query->whereJsonContains('palabras_clave', $palabra);
        }
        return $query;
    }

    // Métodos de ayuda
    public function esReciente(): bool
    {
        return $this->fecha_indexacion && $this->fecha_indexacion->diffInDays(now()) <= 30;
    }

    public function necesitaActualizacion(): bool
    {
        if (!$this->fecha_ultima_actualizacion) {
            return $this->fecha_indexacion->diffInMonths(now()) >= 6;
        }
        return $this->fecha_ultima_actualizacion->diffInMonths(now()) >= 6;
    }

    public function getTamaño(): string
    {
        if (!$this->tamaño_bytes) {
            return 'N/A';
        }

        $bytes = $this->tamaño_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getEtiquetaNivelAcceso(): string
    {
        $etiquetas = [
            'publico' => 'Público',
            'restringido' => 'Restringido',
            'confidencial' => 'Confidencial',
            'secreto' => 'Secreto'
        ];
        
        return $etiquetas[$this->nivel_acceso] ?? $this->nivel_acceso;
    }

    public function getEtiquetaEstadoConservacion(): string
    {
        $etiquetas = [
            'excelente' => 'Excelente',
            'bueno' => 'Bueno',
            'regular' => 'Regular',
            'malo' => 'Malo',
            'critico' => 'Crítico'
        ];
        
        return $etiquetas[$this->estado_conservacion] ?? $this->estado_conservacion;
    }

    public function getCodigoCompleto(): string
    {
        $codigo = $this->codigo_clasificacion ?? '';
        
        if ($this->serie_documental) {
            $codigo .= $this->serie_documental;
        }
        
        if ($this->subserie_documental) {
            $codigo .= '.' . $this->subserie_documental;
        }
        
        return $codigo ?: 'Sin clasificar';
    }

    public function getPeriodoConservacion(): ?string
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }
        
        $años = $this->fecha_inicio->diffInYears($this->fecha_fin);
        $meses = $this->fecha_inicio->diffInMonths($this->fecha_fin) % 12;
        
        $periodo = '';
        if ($años > 0) {
            $periodo = $años . ' año' . ($años > 1 ? 's' : '');
        }
        if ($meses > 0) {
            $periodo .= ($periodo ? ' y ' : '') . $meses . ' mes' . ($meses > 1 ? 'es' : '');
        }
        
        return $periodo ?: 'Menos de 1 mes';
    }

    // Métodos estáticos para estadísticas
    public static function estadisticasPorTipo(): array
    {
        return self::selectRaw('tipo_entidad, COUNT(*) as total')
            ->groupBy('tipo_entidad')
            ->pluck('total', 'tipo_entidad')
            ->toArray();
    }

    public static function estadisticasPorSerie(): array
    {
        return self::selectRaw('serie_documental, COUNT(*) as total')
            ->whereNotNull('serie_documental')
            ->groupBy('serie_documental')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'serie_documental')
            ->toArray();
    }

    public static function documentosVitales(): int
    {
        return self::where('es_vital', true)->count();
    }

    public static function documentosHistoricos(): int
    {
        return self::where('es_historico', true)->count();
    }

    public static function totalTamaño(): int
    {
        return self::sum('tamaño_bytes') ?? 0;
    }
}
