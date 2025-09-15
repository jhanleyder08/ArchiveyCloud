<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Serie Documental
 * 
 * Basado en REQ-CL-016: Herencia de metadatos de serie/subserie
 * REQ-CL-017: Herencia de tiempos de conservación de TRD
 * REQ-CL-018: Asociación obligatoria a TRD configurada
 */
class SerieDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'series_documentales';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'trd_id',
        'ccd_id',
        'tiempo_archivo_gestion',
        'tiempo_archivo_central',
        'disposicion_final',
        'procedimiento',
        'metadatos_heredables',
        'palabras_clave',
        'usuario_responsable_id',
        'area_responsable',
        'activa',
        'observaciones',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'metadatos_heredables' => 'array',
        'palabras_clave' => 'array',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Tipos de disposición final
    const DISPOSICION_CONSERVACION_TOTAL = 'conservacion_total';
    const DISPOSICION_ELIMINACION = 'eliminacion';
    const DISPOSICION_SELECCION = 'seleccion';
    const DISPOSICION_TRANSFERENCIA = 'transferencia';
    const DISPOSICION_MIGRACION = 'migracion';

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático
        static::creating(function ($serie) {
            if (empty($serie->codigo)) {
                $serie->codigo = $serie->generarCodigo();
            }
            
            // REQ-CL-017: Heredar tiempos de conservación de TRD
            if ($serie->tabla_retencion_id && !$serie->tiempo_archivo_gestion) {
                $serie->heredarTiemposTRD();
            }
        });
        
        // Registrar en auditoría
        static::created(function ($serie) {
            PistaAuditoria::registrar($serie, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Serie documental creada: ' . $serie->nombre
            ]);
        });
        
        static::updated(function ($serie) {
            PistaAuditoria::registrar($serie, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Serie documental actualizada: ' . $serie->nombre,
                'valores_anteriores' => $serie->getOriginal(),
                'valores_nuevos' => $serie->getAttributes()
            ]);
        });
    }

    /**
     * REQ-CL-018: Relación obligatoria con TRD
     */
    public function trd()
    {
        return $this->belongsTo(TablaRetencionDocumental::class, 'tabla_retencion_id');
    }

    /**
     * Relación con Cuadro de Clasificación Documental
     */
    public function ccd()
    {
        return $this->belongsTo(CuadroClasificacionDocumental::class, 'cuadro_clasificacion_id');
    }

    /**
     * Relación con subseries
     */
    public function subseries()
    {
        return $this->hasMany(SubserieDocumental::class, 'serie_documental_id');
    }

    /**
     * Relación con expedientes
     */
    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'serie_documental_id');
    }

    /**
     * Relación con documentos a través de expedientes
     */
    public function documentos()
    {
        return $this->hasManyThrough(Documento::class, Expediente::class, 'serie_documental_id', 'expediente_id');
    }

    /**
     * Relación con usuario responsable
     */
    public function usuarioResponsable()
    {
        return $this->belongsTo(User::class, 'usuario_responsable_id');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para series activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Scope por TRD
     */
    public function scopePorTrd($query, $trdId)
    {
        return $query->where('tabla_retencion_id', $trdId);
    }

    /**
     * Scope por área responsable
     */
    public function scopePorArea($query, $area)
    {
        return $query->where('area_responsable', $area);
    }

    /**
     * Generar código de serie automático
     */
    public function generarCodigo()
    {
        $prefijo = 'SER';
        $year = now()->format('Y');
        
        // Obtener último número de la serie en el año
        $ultimaSerie = static::where('codigo', 'LIKE', $prefijo . $year . '%')
                            ->orderBy('codigo', 'desc')
                            ->first();
        
        if ($ultimaSerie) {
            $ultimoNumero = intval(substr($ultimaSerie->codigo, -4));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $prefijo . $year . str_pad($nuevoNumero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * REQ-CL-017: Heredar tiempos de conservación de TRD
     */
    public function heredarTiemposTRD()
    {
        if ($this->trd) {
            // En una implementación real, la TRD tendría reglas específicas
            // Por ahora, establecemos valores por defecto
            $this->tiempo_archivo_gestion = $this->tiempo_archivo_gestion ?? 5; // años
            $this->tiempo_archivo_central = $this->tiempo_archivo_central ?? 10; // años
            $this->disposicion_final = $this->disposicion_final ?? self::DISPOSICION_SELECCION;
        }
    }

    /**
     * REQ-CL-005: Asignar palabras clave basadas en vocabularios controlados
     */
    public function asignarPalabrasClave($palabras, $vocabulario = null)
    {
        // Validar palabras contra vocabulario controlado si se proporciona
        if ($vocabulario && $this->ccd && $this->ccd->vocabulario_controlado) {
            $vocabularioValido = $this->ccd->vocabulario_controlado;
            $palabras = array_filter($palabras, function($palabra) use ($vocabularioValido) {
                return in_array($palabra, $vocabularioValido);
            });
        }
        
        $this->palabras_clave = array_merge($this->palabras_clave ?? [], $palabras);
        $this->save();
        
        // Registrar en auditoría
        PistaAuditoria::registrar($this, 'palabras_clave_asignadas', [
            'descripcion' => 'Palabras clave asignadas a serie: ' . $this->nombre,
            'palabras_agregadas' => $palabras,
            'vocabulario_utilizado' => $vocabulario
        ]);
    }

    /**
     * REQ-CL-016: Obtener metadatos heredables para subseries/expedientes/documentos
     */
    public function getMetadatosHeredables()
    {
        $metadatos = $this->metadatos_heredables ?? [];
        
        // Agregar metadatos básicos de la serie
        $metadatos['serie_codigo'] = $this->codigo;
        $metadatos['serie_nombre'] = $this->nombre;
        $metadatos['area_responsable'] = $this->area_responsable;
        $metadatos['tiempo_archivo_gestion'] = $this->tiempo_archivo_gestion;
        $metadatos['tiempo_archivo_central'] = $this->tiempo_archivo_central;
        $metadatos['disposicion_final'] = $this->disposicion_final;
        $metadatos['palabras_clave_serie'] = $this->palabras_clave;
        
        // Heredar metadatos del CCD si existe
        if ($this->ccd) {
            $metadatos['ccd_codigo'] = $this->ccd->codigo;
            $metadatos['ccd_nombre'] = $this->ccd->nombre;
            $metadatos['ccd_ruta'] = $this->ccd->getRutaCompleta();
        }
        
        // Heredar metadatos de TRD si existe
        if ($this->trd) {
            $metadatos['trd_codigo'] = $this->trd->codigo;
            $metadatos['trd_version'] = $this->trd->version;
            $metadatos['trd_vigente'] = $this->trd->vigente;
        }
        
        return $metadatos;
    }

    /**
     * Calcular fecha de disposición final para un documento
     */
    public function calcularFechaDisposicion($fechaCreacion = null)
    {
        $fechaCreacion = $fechaCreacion ?? now();
        $tiempoTotal = ($this->tiempo_archivo_gestion ?? 0) + ($this->tiempo_archivo_central ?? 0);
        
        return $fechaCreacion->copy()->addYears($tiempoTotal);
    }

    /**
     * Verificar si la serie puede ser eliminada
     */
    public function puedeSerEliminada()
    {
        // No se puede eliminar si tiene expedientes asociados
        if ($this->expedientes()->exists()) {
            return false;
        }
        
        // No se puede eliminar si tiene subseries asociadas
        if ($this->subseries()->exists()) {
            return false;
        }
        
        return true;
    }

    /**
     * REQ-CL-037: Exportar directorio de expedientes por serie
     */
    public function exportarDirectorioExpedientes($formato = 'json')
    {
        $expedientes = $this->expedientes()
            ->with(['documentos', 'metadatos'])
            ->get()
            ->map(function ($expediente) {
                return [
                    'codigo' => $expediente->codigo,
                    'nombre' => $expediente->nombre,
                    'descripcion' => $expediente->descripcion,
                    'fecha_apertura' => $expediente->fecha_apertura,
                    'fecha_cierre' => $expediente->fecha_cierre,
                    'estado' => $expediente->estado,
                    'num_documentos' => $expediente->documentos->count(),
                    'ubicacion_fisica' => $expediente->ubicacion_fisica,
                    'metadatos' => $expediente->metadatos
                ];
            });
        
        $directorio = [
            'serie' => [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'area_responsable' => $this->area_responsable
            ],
            'total_expedientes' => $expedientes->count(),
            'expedientes' => $expedientes,
            'fecha_exportacion' => now()->toISOString()
        ];
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($directorio, 'directorio_serie');
            case 'csv':
                return $this->expedientesToCsv($expedientes);
            default:
                return json_encode($directorio, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Validar datos de la serie
     */
    public function validar()
    {
        $errores = [];
        
        // Validar asociación obligatoria con TRD
        if (!$this->tabla_retencion_id || !$this->trd) {
            $errores[] = 'La serie debe estar asociada a una TRD válida';
        }
        
        // Validar código único
        $existente = static::where('codigo', $this->codigo)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe una serie con el código: ' . $this->codigo;
        }
        
        // Validar tiempos de retención
        if ($this->tiempo_archivo_gestion < 0) {
            $errores[] = 'El tiempo de archivo de gestión no puede ser negativo';
        }
        
        if ($this->tiempo_archivo_central < 0) {
            $errores[] = 'El tiempo de archivo central no puede ser negativo';
        }
        
        // Validar disposición final
        $disposicionesValidas = [
            self::DISPOSICION_CONSERVACION_TOTAL,
            self::DISPOSICION_ELIMINACION,
            self::DISPOSICION_SELECCION,
            self::DISPOSICION_TRANSFERENCIA,
            self::DISPOSICION_MIGRACION
        ];
        
        if (!in_array($this->disposicion_final, $disposicionesValidas)) {
            $errores[] = 'La disposición final no es válida';
        }
        
        return $errores;
    }

    /**
     * Obtener estadísticas de la serie
     */
    public function getEstadisticas()
    {
        return [
            'total_subseries' => $this->subseries()->count(),
            'total_expedientes' => $this->expedientes()->count(),
            'expedientes_abiertos' => $this->expedientes()->where('estado', 'abierto')->count(),
            'expedientes_cerrados' => $this->expedientes()->where('estado', 'cerrado')->count(),
            'total_documentos' => $this->documentos()->count(),
            'documentos_por_mes' => $this->documentos()
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'proximos_vencimientos' => $this->expedientes()
                ->whereNotNull('fecha_vencimiento_disposicion')
                ->where('fecha_vencimiento_disposicion', '<=', now()->addMonths(6))
                ->count()
        ];
    }

    /**
     * Convertir array a XML
     */
    private function arrayToXml($data, $rootElement = 'data', $xml = null)
    {
        if ($xml === null) {
            $xml = new \SimpleXMLElement('<' . $rootElement . '/>');
        }
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $this->arrayToXml($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Convertir expedientes a CSV
     */
    private function expedientesToCsv($expedientes)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($output, [
            'Código', 'Nombre', 'Descripción', 'Fecha Apertura', 
            'Fecha Cierre', 'Estado', 'Num Documentos', 'Ubicación Física'
        ]);
        
        // Data
        foreach ($expedientes as $exp) {
            fputcsv($output, [
                $exp['codigo'],
                $exp['nombre'],
                $exp['descripcion'],
                $exp['fecha_apertura'],
                $exp['fecha_cierre'],
                $exp['estado'],
                $exp['num_documentos'],
                $exp['ubicacion_fisica']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
