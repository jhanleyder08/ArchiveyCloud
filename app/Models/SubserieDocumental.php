<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Subserie Documental
 * 
 * Basado en REQ-CL-016: Herencia de metadatos de serie/subserie
 * REQ-CL-017: Herencia de tiempos de conservación
 * REQ-CL-002: Asignación de tipologías documentales
 */
class SubserieDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subseries_documentales';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'serie_id',
        'tiempo_archivo_gestion',
        'tiempo_archivo_central',
        'disposicion_final',
        'procedimiento',
        'activa',
        'metadatos_especificos',
        'tipologias_documentales',
        'observaciones',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'metadatos_especificos' => 'array',
        'tipologias_documentales' => 'array',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Estados de disposición final (según migración)
    const DISPOSICION_CONSERVACION_PERMANENTE = 'conservacion_permanente';
    const DISPOSICION_ELIMINACION = 'eliminacion';
    const DISPOSICION_SELECCION = 'seleccion';
    const DISPOSICION_MICROFILMACION = 'microfilmacion';

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático y heredar metadatos
        static::creating(function ($subserie) {
            if (empty($subserie->codigo)) {
                $subserie->codigo = $subserie->generarCodigo();
            }
            
            // REQ-CL-016 y REQ-CL-017: Heredar metadatos y tiempos de la serie
            if ($subserie->serie_id) {
                $subserie->heredarDeSerie();
            }
        });
        
        // Registrar en auditoría - COMENTADO TEMPORALMENTE PARA EVITAR ERRORES
        /*
        static::created(function ($subserie) {
            PistaAuditoria::registrar($subserie, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Subserie documental creada: ' . $subserie->nombre,
                'serie_asociada' => $subserie->serie->codigo ?? null
            ]);
        });
        
        static::updated(function ($subserie) {
            PistaAuditoria::registrar($subserie, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Subserie documental actualizada: ' . $subserie->nombre,
                'valores_anteriores' => $subserie->getOriginal(),
                'valores_nuevos' => $subserie->getAttributes()
            ]);
        });
        */
    }

    /**
     * Relación con serie documental (obligatoria)
     */
    public function serie()
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_id');
    }

    /**
     * Relación con expedientes
     */
    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'subserie_id');
    }

    /**
     * Relación con documentos a través de expedientes
     */
    public function documentos()
    {
        return $this->hasManyThrough(Documento::class, Expediente::class, 'subserie_id', 'expediente_id');
    }

    /**
     * Relación con tipologías documentales
     */
    public function tipologias()
    {
        return $this->belongsToMany(TipologiaDocumental::class, 'subserie_tipologia', 'subserie_id', 'tipologia_id');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para subseries activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Scope por serie
     */
    public function scopePorSerie($query, $serieId)
    {
        return $query->where('serie_id', $serieId);
    }

    /**
     * Scope con tipologías específicas
     */
    public function scopeConTipologia($query, $tipologiaId)
    {
        return $query->whereHas('tipologias', function ($q) use ($tipologiaId) {
            $q->where('tipologia_documental_id', $tipologiaId);
        });
    }

    /**
     * Generar código de subserie automático
     */
    public function generarCodigo()
    {
        $serie = $this->serie;
        if (!$serie) {
            throw new \Exception('Subserie debe tener una serie asociada para generar código');
        }
        
        // Obtener último número de la subserie dentro de la serie
        $ultimaSubserie = static::where('serie_id', $this->serie_id)
                               ->where('codigo', 'LIKE', $serie->codigo . '.%')
                               ->orderBy('codigo', 'desc')
                               ->first();
        
        if ($ultimaSubserie) {
            $ultimaParte = explode('.', $ultimaSubserie->codigo);
            $ultimoNumero = intval(end($ultimaParte));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $serie->codigo . '.' . str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * REQ-CL-016 y REQ-CL-017: Heredar metadatos y tiempos de la serie
     */
    public function heredarDeSerie()
    {
        if (!$this->serie) {
            return;
        }
        
        // COMENTADO: Heredar metadatos de la serie - campos no existen en BD
        // $metadatosHeredados = $this->serie->getMetadatosHeredables();
        // $this->metadatos_heredables = array_merge($this->metadatos_heredables ?? [], $metadatosHeredados);
        
        // Heredar tiempos de retención si no están definidos
        $this->tiempo_archivo_gestion = $this->tiempo_archivo_gestion ?? $this->serie->tiempo_archivo_gestion;
        $this->tiempo_archivo_central = $this->tiempo_archivo_central ?? $this->serie->tiempo_archivo_central;
        $this->disposicion_final = $this->disposicion_final ?? $this->serie->disposicion_final;
        
        // COMENTADO: Heredar palabras clave - campo no existe en BD
        // $palabrasClaveHeredadas = $this->serie->palabras_clave ?? [];
        // $this->palabras_clave = array_unique(array_merge($this->palabras_clave ?? [], $palabrasClaveHeredadas));
    }

    /**
     * REQ-CL-002: Asignar tipologías documentales
     */
    public function asignarTipologias($tipologiasIds)
    {
        // Validar que las tipologías existan
        $tipologiasValidas = TipologiaDocumental::whereIn('id', $tipologiasIds)->pluck('id')->toArray();
        
        if (count($tipologiasValidas) !== count($tipologiasIds)) {
            throw new \Exception('Algunas tipologías documentales no son válidas');
        }
        
        // Sincronizar tipologías
        $this->tipologias()->sync($tipologiasIds);
        
        // Actualizar array en el modelo para consultas rápidas
        $this->tipologias_documentales = $tipologiasIds;
        $this->save();
        
        // Registrar en auditoría
        PistaAuditoria::registrar($this, 'tipologias_asignadas', [
            'descripcion' => 'Tipologías documentales asignadas a subserie: ' . $this->nombre,
            'tipologias_asignadas' => $tipologiasIds,
            'total_tipologias' => count($tipologiasIds)
        ]);
    }

    /**
     * Obtener todos los metadatos (heredados + propios)
     */
    public function getMetadatosCompletos()
    {
        $metadatosHeredados = $this->metadatos_heredables ?? [];
        $metadatosPropios = $this->metadatos_propios ?? [];
        
        // Agregar metadatos específicos de la subserie
        $metadatosPropios['subserie_codigo'] = $this->codigo;
        $metadatosPropios['subserie_nombre'] = $this->nombre;
        $metadatosPropios['tipologias_documentales'] = $this->tipologias_documentales;
        $metadatosPropios['palabras_clave_subserie'] = $this->palabras_clave;
        
        // Los metadatos propios tienen precedencia sobre los heredados
        return array_merge($metadatosHeredados, $metadatosPropios);
    }

    /**
     * REQ-CL-016: Obtener metadatos heredables para expedientes/documentos
     */
    public function getMetadatosHeredables()
    {
        $metadatos = $this->getMetadatosCompletos();
        
        // Agregar información de herencia
        $metadatos['herencia'] = [
            'desde_serie' => $this->serie->codigo ?? null,
            'desde_subserie' => $this->codigo
        ];
        
        return $metadatos;
    }

    /**
     * Calcular fecha de disposición final
     */
    public function calcularFechaDisposicion($fechaCreacion = null)
    {
        $fechaCreacion = $fechaCreacion ?? now();
        $tiempoTotal = ($this->tiempo_archivo_gestion ?? 0) + ($this->tiempo_archivo_central ?? 0);
        
        return $fechaCreacion->copy()->addYears($tiempoTotal);
    }

    /**
     * REQ-CL-037: Exportar inventario de documentos por subserie
     */
    public function exportarInventarioDocumentos($formato = 'json')
    {
        $documentos = $this->documentos()
            ->with(['expediente', 'metadatos'])
            ->get()
            ->map(function ($documento) {
                return [
                    'codigo' => $documento->codigo,
                    'nombre' => $documento->nombre,
                    'tipo_documental' => $documento->tipo_documental,
                    'fecha_creacion' => $documento->fecha_creacion,
                    'expediente_codigo' => $documento->expediente->codigo ?? null,
                    'expediente_nombre' => $documento->expediente->nombre ?? null,
                    'formato' => $documento->formato,
                    'tamaño' => $documento->tamaño,
                    'ubicacion' => $documento->ubicacion_fisica,
                    'estado' => $documento->estado,
                    'metadatos' => $documento->metadatos
                ];
            });
        
        $inventario = [
            'subserie' => [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'serie' => [
                    'codigo' => $this->serie->codigo ?? null,
                    'nombre' => $this->serie->nombre ?? null
                ]
            ],
            'tipologias_documentales' => $this->tipologias_documentales,
            'total_documentos' => $documentos->count(),
            'documentos' => $documentos,
            'fecha_exportacion' => now()->toISOString()
        ];
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($inventario, 'inventario_subserie');
            case 'csv':
                return $this->documentosToCsv($documentos);
            default:
                return json_encode($inventario, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Verificar si la subserie puede ser eliminada
     */
    public function puedeSerEliminada()
    {
        // No se puede eliminar si tiene expedientes asociados
        if ($this->expedientes()->exists()) {
            return false;
        }
        
        return true;
    }

    /**
     * Validar datos de la subserie
     */
    public function validar()
    {
        $errores = [];
        
        // Validar asociación obligatoria con serie
        if (!$this->serie_id || !$this->serie) {
            $errores[] = 'La subserie debe estar asociada a una serie válida';
        }
        
        // Validar código único dentro de la serie
        $existente = static::where('codigo', $this->codigo)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe una subserie con el código: ' . $this->codigo;
        }
        
        // Validar tiempos de retención
        if ($this->tiempo_archivo_gestion < 0) {
            $errores[] = 'El tiempo de archivo de gestión no puede ser negativo';
        }
        
        if ($this->tiempo_archivo_central < 0) {
            $errores[] = 'El tiempo de archivo central no puede ser negativo';
        }
        
        // Validar que los tiempos no sean menores a los de la serie
        if ($this->serie) {
            if ($this->tiempo_archivo_gestion < $this->serie->tiempo_archivo_gestion) {
                $errores[] = 'El tiempo de archivo de gestión no puede ser menor al de la serie';
            }
            
            if ($this->tiempo_archivo_central < $this->serie->tiempo_archivo_central) {
                $errores[] = 'El tiempo de archivo central no puede ser menor al de la serie';
            }
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
     * Obtener estadísticas de la subserie
     */
    public function getEstadisticas()
    {
        return [
            'total_expedientes' => $this->expedientes()->count(),
            'expedientes_abiertos' => $this->expedientes()->where('estado', 'abierto')->count(),
            'expedientes_cerrados' => $this->expedientes()->where('estado', 'cerrado')->count(),
            'total_documentos' => $this->documentos()->count(),
            'documentos_por_tipologia' => $this->documentos()
                ->selectRaw('tipo_documental, COUNT(*) as total')
                ->groupBy('tipo_documental')
                ->get(),
            'volumen_total' => $this->documentos()->sum('tamaño'),
            'documentos_recientes' => $this->documentos()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'tipologias_asignadas' => count($this->tipologias_documentales ?? [])
        ];
    }

    /**
     * Obtener ruta jerárquica completa
     */
    public function getRutaCompleta()
    {
        $ruta = [];
        
        if ($this->serie) {
            if ($this->serie->ccd) {
                $ruta[] = $this->serie->ccd->getRutaCompleta();
            }
            $ruta[] = $this->serie->nombre;
        }
        
        $ruta[] = $this->nombre;
        
        return implode(' > ', array_filter($ruta));
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
     * Convertir documentos a CSV
     */
    private function documentosToCsv($documentos)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($output, [
            'Código', 'Nombre', 'Tipo Documental', 'Fecha Creación', 
            'Expediente', 'Formato', 'Tamaño', 'Ubicación', 'Estado'
        ]);
        
        // Data
        foreach ($documentos as $doc) {
            fputcsv($output, [
                $doc['codigo'],
                $doc['nombre'],
                $doc['tipo_documental'],
                $doc['fecha_creacion'],
                $doc['expediente_codigo'] . ' - ' . $doc['expediente_nombre'],
                $doc['formato'],
                $doc['tamaño'],
                $doc['ubicacion'],
                $doc['estado']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
