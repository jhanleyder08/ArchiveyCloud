<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ImportacionDatos extends Model
{
    use HasFactory;

    protected $table = 'importaciones_datos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'formato_origen',
        'estado',
        'archivo_origen',
        'archivo_procesado',
        'archivo_errores',
        'archivo_log',
        'configuracion',
        'estadisticas',
        'metadatos',
        'total_registros',
        'registros_procesados',
        'registros_exitosos',
        'registros_fallidos',
        'porcentaje_avance',
        'fecha_inicio',
        'fecha_finalizacion',
        'tiempo_procesamiento',
        'mensaje_error',
        'usuario_id'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'estadisticas' => 'array',
        'metadatos' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'porcentaje_avance' => 'decimal:2'
    ];

    // Estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_PROCESANDO = 'procesando';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_FALLIDA = 'fallida';
    const ESTADO_CANCELADA = 'cancelada';

    // Tipos
    const TIPO_EXPEDIENTES = 'expedientes';
    const TIPO_DOCUMENTOS = 'documentos';
    const TIPO_SERIES = 'series';
    const TIPO_SUBSERIES = 'subseries';
    const TIPO_USUARIOS = 'usuarios';
    const TIPO_TRD = 'trd';
    const TIPO_CERTIFICADOS = 'certificados';
    const TIPO_MIXTO = 'mixto';

    // Formatos
    const FORMATO_CSV = 'csv';
    const FORMATO_EXCEL = 'excel';
    const FORMATO_JSON = 'json';
    const FORMATO_XML = 'xml';
    const FORMATO_SQL = 'sql';
    const FORMATO_ZIP = 'zip';

    /**
     * Relación con el usuario que creó la importación
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeProcesando($query)
    {
        return $query->where('estado', self::ESTADO_PROCESANDO);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    public function scopeFallidas($query)
    {
        return $query->where('estado', self::ESTADO_FALLIDA);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Métodos de ayuda
     */
    public function iniciarProcesamiento()
    {
        $this->update([
            'estado' => self::ESTADO_PROCESANDO,
            'fecha_inicio' => now(),
            'porcentaje_avance' => 0
        ]);
    }

    public function completarProcesamiento()
    {
        $this->update([
            'estado' => self::ESTADO_COMPLETADA,
            'fecha_finalizacion' => now(),
            'porcentaje_avance' => 100,
            'tiempo_procesamiento' => $this->fecha_inicio ? 
                now()->diffInSeconds($this->fecha_inicio) : null
        ]);
    }

    public function fallarProcesamiento($error = null)
    {
        $this->update([
            'estado' => self::ESTADO_FALLIDA,
            'fecha_finalizacion' => now(),
            'mensaje_error' => $error,
            'tiempo_procesamiento' => $this->fecha_inicio ? 
                now()->diffInSeconds($this->fecha_inicio) : null
        ]);
    }

    public function cancelarProcesamiento()
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADA,
            'fecha_finalizacion' => now(),
            'tiempo_procesamiento' => $this->fecha_inicio ? 
                now()->diffInSeconds($this->fecha_inicio) : null
        ]);
    }

    public function actualizarProgreso($procesados, $exitosos, $fallidos, $porcentaje = null)
    {
        $porcentajeCalculado = $porcentaje ?? 
            ($this->total_registros > 0 ? ($procesados / $this->total_registros) * 100 : 0);

        $this->update([
            'registros_procesados' => $procesados,
            'registros_exitosos' => $exitosos,
            'registros_fallidos' => $fallidos,
            'porcentaje_avance' => $porcentajeCalculado
        ]);
    }

    /**
     * Accessors
     */
    public function getEstadoBadgeAttribute()
    {
        $badges = [
            self::ESTADO_PENDIENTE => 'bg-yellow-100 text-yellow-800',
            self::ESTADO_PROCESANDO => 'bg-blue-100 text-blue-800',
            self::ESTADO_COMPLETADA => 'bg-green-100 text-green-800',
            self::ESTADO_FALLIDA => 'bg-red-100 text-red-800',
            self::ESTADO_CANCELADA => 'bg-gray-100 text-gray-800',
        ];

        return $badges[$this->estado] ?? 'bg-gray-100 text-gray-800';
    }

    public function getTiempoFormateadoAttribute()
    {
        if (!$this->tiempo_procesamiento) {
            return null;
        }

        $segundos = $this->tiempo_procesamiento;
        
        if ($segundos < 60) {
            return "{$segundos}s";
        } elseif ($segundos < 3600) {
            $minutos = floor($segundos / 60);
            $seg = $segundos % 60;
            return "{$minutos}m {$seg}s";
        } else {
            $horas = floor($segundos / 3600);
            $min = floor(($segundos % 3600) / 60);
            return "{$horas}h {$min}m";
        }
    }

    public function getEstaCompletadaAttribute()
    {
        return $this->estado === self::ESTADO_COMPLETADA;
    }

    public function getEstaFallidaAttribute()
    {
        return $this->estado === self::ESTADO_FALLIDA;
    }

    public function getEstaProcesandoAttribute()
    {
        return $this->estado === self::ESTADO_PROCESANDO;
    }

    public function getArchivosGeneradosAttribute()
    {
        $archivos = [];
        
        if ($this->archivo_procesado) {
            $archivos[] = [
                'tipo' => 'procesado',
                'nombre' => 'Datos Procesados',
                'archivo' => $this->archivo_procesado
            ];
        }

        if ($this->archivo_errores) {
            $archivos[] = [
                'tipo' => 'errores',
                'nombre' => 'Errores',
                'archivo' => $this->archivo_errores
            ];
        }

        if ($this->archivo_log) {
            $archivos[] = [
                'tipo' => 'log',
                'nombre' => 'Log de Proceso',
                'archivo' => $this->archivo_log
            ];
        }

        return $archivos;
    }

    /**
     * Métodos estáticos de utilidad
     */
    public static function obtenerEstadisticas()
    {
        return [
            'total' => static::count(),
            'pendientes' => static::pendientes()->count(),
            'procesando' => static::procesando()->count(),
            'completadas' => static::completadas()->count(),
            'fallidas' => static::fallidas()->count(),
            'hoy' => static::whereDate('created_at', today())->count(),
            'esta_semana' => static::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'este_mes' => static::whereMonth('created_at', now()->month)->count(),
        ];
    }

    public static function obtenerEstadisticasPorTipo()
    {
        return static::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();
    }

    public static function obtenerTiempoPromedioVelocidad()
    {
        return [
            'tiempo_promedio' => static::completadas()
                ->whereNotNull('tiempo_procesamiento')
                ->avg('tiempo_procesamiento'),
            'registros_por_minuto' => static::completadas()
                ->whereNotNull('tiempo_procesamiento')
                ->where('tiempo_procesamiento', '>', 0)
                ->selectRaw('AVG(total_registros / (tiempo_procesamiento / 60)) as velocidad')
                ->value('velocidad')
        ];
    }
}
