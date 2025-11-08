<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PlantillaDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plantillas_documentales';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria',
        'tipo_documento',
        'serie_documental_id',
        'subserie_documental_id',
        'contenido_html',
        'contenido_json',
        'campos_variables',
        'metadatos_predefinidos',
        'configuracion_formato',
        'usuario_creador_id',
        'estado',
        'es_publica',
        'version',
        'plantilla_padre_id',
        'tags',
        'observaciones'
    ];

    protected $casts = [
        'campos_variables' => 'array',
        'metadatos_predefinidos' => 'array',
        'configuracion_formato' => 'array',
        'tags' => 'array',
        'es_publica' => 'boolean',
        'version' => 'decimal:2'
    ];

    protected $dates = ['deleted_at'];

    // Estados posibles
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_REVISION = 'revision';
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_ARCHIVADA = 'archivada';
    const ESTADO_OBSOLETA = 'obsoleta';

    // Categorías de plantillas
    const CATEGORIA_MEMORANDO = 'memorando';
    const CATEGORIA_OFICIO = 'oficio';
    const CATEGORIA_RESOLUCION = 'resolucion';
    const CATEGORIA_ACTA = 'acta';
    const CATEGORIA_INFORME = 'informe';
    const CATEGORIA_CIRCULAR = 'circular';
    const CATEGORIA_COMUNICACION = 'comunicacion';
    const CATEGORIA_OTRO = 'otro';

    /**
     * Relación con el usuario creador
     */
    public function usuarioCreador()
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    /**
     * Relación con serie documental
     */
    public function serieDocumental()
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_documental_id');
    }

    /**
     * Relación con subserie documental
     */
    public function subserieDocumental()
    {
        return $this->belongsTo(SubserieDocumental::class, 'subserie_documental_id');
    }

    /**
     * Relación con plantilla padre (para versionado)
     */
    public function plantillaPadre()
    {
        return $this->belongsTo(PlantillaDocumental::class, 'plantilla_padre_id');
    }

    /**
     * Relación con versiones hijas
     */
    public function versiones()
    {
        return $this->hasMany(PlantillaDocumental::class, 'plantilla_padre_id');
    }

    /**
     * Relación con documentos generados desde esta plantilla
     */
    public function documentosGenerados()
    {
        return $this->hasMany(Documento::class, 'plantilla_id');
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    public function scopePublicas($query)
    {
        return $query->where('es_publica', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorSerie($query, $serieId)
    {
        return $query->where('serie_documental_id', $serieId);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('descripcion', 'like', "%{$termino}%")
              ->orWhere('codigo', 'like', "%{$termino}%");
        });
    }

    /**
     * Métodos de ayuda
     */
    public function puedeSerEditadaPor(User $usuario): bool
    {
        // El creador siempre puede editar
        if ($this->usuario_creador_id === $usuario->id) {
            return true;
        }

        // Administradores pueden editar cualquiera
        if ($usuario->hasRole(['Super Administrador', 'Administrador SGDEA'])) {
            return true;
        }

        // Gestores documentales pueden editar plantillas públicas
        if ($usuario->hasRole('Gestor Documental') && $this->es_publica) {
            return true;
        }

        return false;
    }

    public function esVersionMasReciente(): bool
    {
        if (!$this->plantilla_padre_id) {
            // Es una plantilla original, verificar si hay versiones más nuevas
            return !$this->versiones()->where('version', '>', $this->version)->exists();
        }

        // Es una versión, verificar si es la más reciente de su grupo
        return !PlantillaDocumental::where('plantilla_padre_id', $this->plantilla_padre_id)
            ->where('version', '>', $this->version)
            ->exists();
    }

    public function obtenerSiguienteVersion(): float
    {
        $ultimaVersion = PlantillaDocumental::where('plantilla_padre_id', $this->plantilla_padre_id ?: $this->id)
            ->max('version') ?: $this->version;

        return $ultimaVersion + 0.1;
    }

    public function generarCodigo(): string
    {
        $prefijo = 'PLT';
        $categoria = strtoupper(substr($this->categoria, 0, 3));
        $numero = str_pad($this->id, 4, '0', STR_PAD_LEFT);
        $version = str_replace('.', '', (string)$this->version);

        return "{$prefijo}-{$categoria}-{$numero}-V{$version}";
    }

    public function obtenerCamposVariablesConValores(): array
    {
        $campos = $this->campos_variables ?: [];
        $camposConValores = [];

        foreach ($campos as $campo) {
            $camposConValores[] = [
                'nombre' => $campo['nombre'],
                'tipo' => $campo['tipo'],
                'etiqueta' => $campo['etiqueta'],
                'requerido' => $campo['requerido'] ?? false,
                'valor_defecto' => $campo['valor_defecto'] ?? '',
                'opciones' => $campo['opciones'] ?? [],
                'validacion' => $campo['validacion'] ?? ''
            ];
        }

        return $camposConValores;
    }

    /**
     * Obtener estadísticas de uso de plantillas
     */
    public static function obtenerEstadisticas(): array
    {
        try {
            $total = static::count();
            $activas = static::where('estado', static::ESTADO_ACTIVA)->count();
            $borradores = static::where('estado', static::ESTADO_BORRADOR)->count();
            $publicas = static::where('es_publica', true)->count();
            
            $por_categoria = static::select('categoria')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('categoria')
                ->pluck('total', 'categoria')
                ->toArray();
            
            // Obtener plantillas más usadas con manejo de errores
            try {
                $mas_usadas = static::withCount('documentosGenerados')
                    ->orderBy('documentos_generados_count', 'desc')
                    ->take(5)
                    ->get(['id', 'nombre', 'documentos_generados_count'])
                    ->toArray();
            } catch (\Exception $e) {
                // Si falla, usar datos básicos sin conteos
                $mas_usadas = static::orderBy('created_at', 'desc')
                    ->take(5)
                    ->get(['id', 'nombre', 'created_at'])
                    ->map(function($plantilla) {
                        return [
                            'id' => $plantilla->id,
                            'nombre' => $plantilla->nombre,
                            'documentos_generados_count' => 0
                        ];
                    })
                    ->toArray();
            }

            return [
                'total' => $total,
                'activas' => $activas,
                'borradores' => $borradores,
                'publicas' => $publicas,
                'por_categoria' => $por_categoria,
                'mas_usadas' => $mas_usadas
            ];
        } catch (\Exception $e) {
            // Retornar valores por defecto si todo falla
            return [
                'total' => 0,
                'activas' => 0,
                'borradores' => 0,
                'publicas' => 0,
                'por_categoria' => [],
                'mas_usadas' => []
            ];
        }
    }

    /**
     * Procesar contenido HTML con variables
     */
    public function procesarContenidoConVariables(array $variables = []): string
    {
        $contenido = $this->contenido_html ?: '';
        
        // Variables del sistema
        $variablesSistema = [
            'FECHA_ACTUAL' => now()->format('d/m/Y'),
            'USUARIO_ACTUAL' => auth()->user()->name ?? 'Sistema',
            'CODIGO_DOCUMENTO' => 'DOC-' . now()->format('YmdHis')
        ];
        
        // Combinar variables del usuario con las del sistema
        $todasVariables = array_merge($variables, $variablesSistema);
        
        // Reemplazar variables en el contenido
        foreach ($todasVariables as $nombre => $valor) {
            $contenido = str_replace('{{' . $nombre . '}}', $valor, $contenido);
        }
        
        return $contenido;
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plantilla) {
            // Solo asignar usuario si no está ya establecido
            if (empty($plantilla->usuario_creador_id)) {
                $plantilla->usuario_creador_id = auth()->id();
            }
            
            // El código ya debe estar asignado desde el controlador
            // Si no está, generar uno temporal que se actualizará después
            if (empty($plantilla->codigo)) {
                $prefijo = 'PLT';
                $categoria = strtoupper(substr($plantilla->categoria ?? 'otro', 0, 3));
                $ultimoId = static::withTrashed()->max('id') ?? 0;
                $numero = str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);
                $version = str_replace('.', '', (string)($plantilla->version ?? 1.0));
                $plantilla->codigo = "{$prefijo}-{$categoria}-{$numero}-V{$version}";
            }
        });
    }
}
