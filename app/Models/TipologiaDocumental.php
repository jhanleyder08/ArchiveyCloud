<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Tipología Documental
 * 
 * Basado en REQ-CL-002: Asignación de tipologías documentales a series/subseries
 * REQ-CL-003: Agrupación por tipología documental
 * REQ-CD-014: Identificación de documentos por tipo
 */
class TipologiaDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipologias_documentales';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'definicion',
        'soporte',
        'formato_archivo',
        'metadatos_requeridos',
        'metadatos_opcionales',
        'validaciones',
        'tiempo_retencion_defecto',
        'disposicion_final_defecto',
        'palabras_clave',
        'categoria',
        'subcategoria',
        'activa',
        'observaciones'
    ];

    protected $casts = [
        'metadatos_requeridos' => 'array',
        'metadatos_opcionales' => 'array',
        'validaciones' => 'array',
        'palabras_clave' => 'array',
        'formato_archivo' => 'array',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Tipos de soporte
    const SOPORTE_FISICO = 'fisico';
    const SOPORTE_ELECTRONICO = 'electronico';
    const SOPORTE_HIBRIDO = 'hibrido';

    // Categorías principales
    const CATEGORIA_ADMINISTRATIVO = 'administrativo';
    const CATEGORIA_CONTABLE = 'contable';
    const CATEGORIA_JURIDICO = 'juridico';
    const CATEGORIA_TECNICO = 'tecnico';
    const CATEGORIA_HISTORICO = 'historico';
    const CATEGORIA_COMUNICACIONES = 'comunicaciones';
    const CATEGORIA_RECURSOS_HUMANOS = 'recursos_humanos';
    const CATEGORIA_FINANCIERO = 'financiero';

    // Disposiciones finales por defecto
    const DISPOSICION_CONSERVACION_TOTAL = 'conservacion_total';
    const DISPOSICION_ELIMINACION = 'eliminacion';
    const DISPOSICION_SELECCION = 'seleccion';
    const DISPOSICION_TRANSFERENCIA = 'transferencia';
    const DISPOSICION_MIGRACION = 'migracion';

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático
        static::creating(function ($tipologia) {
            if (empty($tipologia->codigo)) {
                $tipologia->codigo = $tipologia->generarCodigo();
            }
        });
        
        // Registrar en auditoría
        static::created(function ($tipologia) {
            PistaAuditoria::registrar($tipologia, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Tipología documental creada: ' . $tipologia->nombre,
                'categoria' => $tipologia->categoria
            ]);
        });
        
        static::updated(function ($tipologia) {
            PistaAuditoria::registrar($tipologia, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Tipología documental actualizada: ' . $tipologia->nombre,
                'valores_anteriores' => $tipologia->getOriginal(),
                'valores_nuevos' => $tipologia->getAttributes()
            ]);
        });
    }

    /**
     * Relación con subseries documentales
     */
    public function subseries()
    {
        return $this->belongsToMany(SubserieDocumental::class, 'subserie_tipologia', 'tipologia_id', 'subserie_id');
    }

    /**
     * Relación con documentos
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'tipologia_id');
    }

    /**
     * Relación con formularios de captura
     */
    public function formularioCaptura()
    {
        return $this->hasOne(FormularioCaptura::class, 'tipologia_id');
    }

    /**
     * Relación con plantillas de metadatos
     */
    public function plantillaMetadatos()
    {
        return $this->hasOne(PlantillaMetadatos::class, 'tipologia_id');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para tipologías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Scope por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope por subcategoría
     */
    public function scopePorSubcategoria($query, $subcategoria)
    {
        return $query->where('subcategoria', $subcategoria);
    }

    /**
     * Scope por soporte
     */
    public function scopePorSoporte($query, $soporte)
    {
        return $query->where('soporte', $soporte);
    }

    /**
     * Scope con formato específico
     */
    public function scopeConFormato($query, $formato)
    {
        return $query->whereJsonContains('formato_archivo', $formato);
    }

    /**
     * Generar código de tipología automático
     */
    public function generarCodigo()
    {
        $prefijo = $this->getPrefijoCategoria($this->categoria);
        $year = now()->format('Y');
        
        // Obtener último número de la tipología en la categoría y año
        $ultimaTipologia = static::where('codigo', 'LIKE', $prefijo . $year . '%')
                                ->orderBy('codigo', 'desc')
                                ->first();
        
        if ($ultimaTipologia) {
            $ultimoNumero = intval(substr($ultimaTipologia->codigo, -4));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $prefijo . $year . str_pad($nuevoNumero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener prefijo según categoría
     */
    private function getPrefijoCategoria($categoria)
    {
        $prefijos = [
            self::CATEGORIA_ADMINISTRATIVO => 'TDA',
            self::CATEGORIA_CONTABLE => 'TDC',
            self::CATEGORIA_JURIDICO => 'TDJ',
            self::CATEGORIA_TECNICO => 'TDT',
            self::CATEGORIA_HISTORICO => 'TDH',
            self::CATEGORIA_COMUNICACIONES => 'TDCOM',
            self::CATEGORIA_RECURSOS_HUMANOS => 'TDRH',
            self::CATEGORIA_FINANCIERO => 'TDF'
        ];
        
        return $prefijos[$categoria] ?? 'TDG'; // TDG = Tipología Documental General
    }

    /**
     * REQ-CD-014: Validar documento según tipología
     */
    public function validarDocumento($datosDocumento)
    {
        $errores = [];
        $validaciones = $this->validaciones ?? [];
        
        // Validar metadatos requeridos
        foreach ($this->metadatos_requeridos ?? [] as $metadato) {
            if (!isset($datosDocumento['metadatos'][$metadato]) || 
                empty($datosDocumento['metadatos'][$metadato])) {
                $errores[] = "El metadato '$metadato' es requerido para esta tipología";
            }
        }
        
        // Validar formato de archivo
        if (!empty($this->formato_archivo) && isset($datosDocumento['formato'])) {
            if (!in_array($datosDocumento['formato'], $this->formato_archivo)) {
                $errores[] = "El formato '{$datosDocumento['formato']}' no es válido para esta tipología. Formatos permitidos: " . 
                           implode(', ', $this->formato_archivo);
            }
        }
        
        // Validaciones personalizadas
        foreach ($validaciones as $regla => $parametros) {
            switch ($regla) {
                case 'tamaño_maximo':
                    if (isset($datosDocumento['tamaño']) && 
                        $datosDocumento['tamaño'] > $parametros['valor']) {
                        $errores[] = "El tamaño del documento excede el máximo permitido de {$parametros['valor']} bytes";
                    }
                    break;
                    
                case 'nombre_patron':
                    if (isset($datosDocumento['nombre']) && 
                        !preg_match($parametros['patron'], $datosDocumento['nombre'])) {
                        $errores[] = "El nombre del documento no cumple con el patrón requerido: {$parametros['descripcion']}";
                    }
                    break;
                    
                case 'fecha_requerida':
                    foreach ($parametros['campos'] as $campo) {
                        if (!isset($datosDocumento['metadatos'][$campo]) || 
                            !$this->esFechaValida($datosDocumento['metadatos'][$campo])) {
                            $errores[] = "El campo de fecha '$campo' es requerido y debe ser una fecha válida";
                        }
                    }
                    break;
            }
        }
        
        return $errores;
    }

    /**
     * REQ-CL-003: Agrupar documentos por tipología
     */
    public function agruparDocumentos($filtros = [])
    {
        $query = $this->documentos();
        
        // Aplicar filtros
        if (isset($filtros['fecha_desde'])) {
            $query->where('fecha_creacion', '>=', $filtros['fecha_desde']);
        }
        
        if (isset($filtros['fecha_hasta'])) {
            $query->where('fecha_creacion', '<=', $filtros['fecha_hasta']);
        }
        
        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        
        if (isset($filtros['expediente_id'])) {
            $query->where('expediente_id', $filtros['expediente_id']);
        }
        
        return $query->with(['expediente', 'metadatos'])
                    ->orderBy('fecha_creacion', 'desc')
                    ->get()
                    ->groupBy(function ($documento) {
                        return $documento->fecha_creacion->format('Y-m');
                    });
    }

    /**
     * Generar plantilla de metadatos
     */
    public function generarPlantillaMetadatos()
    {
        $plantilla = [];
        
        // Metadatos requeridos
        foreach ($this->metadatos_requeridos ?? [] as $metadato) {
            $plantilla[$metadato] = [
                'requerido' => true,
                'tipo' => $this->inferirTipoMetadato($metadato),
                'descripcion' => $this->getDescripcionMetadato($metadato),
                'valor' => ''
            ];
        }
        
        // Metadatos opcionales
        foreach ($this->metadatos_opcionales ?? [] as $metadato) {
            $plantilla[$metadato] = [
                'requerido' => false,
                'tipo' => $this->inferirTipoMetadato($metadato),
                'descripcion' => $this->getDescripcionMetadato($metadato),
                'valor' => ''
            ];
        }
        
        // Metadatos por defecto según tipología
        $plantilla['tipologia_documental'] = [
            'requerido' => true,
            'tipo' => 'texto',
            'descripcion' => 'Tipología documental',
            'valor' => $this->nombre
        ];
        
        $plantilla['categoria'] = [
            'requerido' => true,
            'tipo' => 'texto',
            'descripcion' => 'Categoría documental',
            'valor' => $this->categoria
        ];
        
        return $plantilla;
    }

    /**
     * Exportar tipología con sus documentos asociados
     */
    public function exportar($formato = 'json', $incluirDocumentos = false)
    {
        $data = [
            'tipologia' => [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'definicion' => $this->definicion,
                'soporte' => $this->soporte,
                'categoria' => $this->categoria,
                'subcategoria' => $this->subcategoria,
                'formato_archivo' => $this->formato_archivo,
                'metadatos_requeridos' => $this->metadatos_requeridos,
                'metadatos_opcionales' => $this->metadatos_opcionales,
                'validaciones' => $this->validaciones,
                'tiempo_retencion_defecto' => $this->tiempo_retencion_defecto,
                'disposicion_final_defecto' => $this->disposicion_final_defecto,
                'palabras_clave' => $this->palabras_clave
            ],
            'estadisticas' => $this->getEstadisticas(),
            'fecha_exportacion' => now()->toISOString()
        ];
        
        if ($incluirDocumentos) {
            $data['documentos'] = $this->documentos()
                ->with(['expediente'])
                ->get()
                ->map(function ($doc) {
                    return [
                        'codigo' => $doc->codigo,
                        'nombre' => $doc->nombre,
                        'fecha_creacion' => $doc->fecha_creacion,
                        'expediente' => $doc->expediente->codigo ?? null,
                        'estado' => $doc->estado
                    ];
                });
        }
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($data, 'tipologia_documental');
            case 'csv':
                return $this->tipologiaToCsv($data);
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Validar datos de la tipología
     */
    public function validar()
    {
        $errores = [];
        
        // Validar código único
        $existente = static::where('codigo', $this->codigo)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe una tipología con el código: ' . $this->codigo;
        }
        
        // Validar categoría
        $categoriasValidas = [
            self::CATEGORIA_ADMINISTRATIVO,
            self::CATEGORIA_CONTABLE,
            self::CATEGORIA_JURIDICO,
            self::CATEGORIA_TECNICO,
            self::CATEGORIA_HISTORICO,
            self::CATEGORIA_COMUNICACIONES,
            self::CATEGORIA_RECURSOS_HUMANOS,
            self::CATEGORIA_FINANCIERO
        ];
        
        if (!in_array($this->categoria, $categoriasValidas)) {
            $errores[] = 'La categoría no es válida';
        }
        
        // Validar soporte
        $soportesValidos = [self::SOPORTE_FISICO, self::SOPORTE_ELECTRONICO, self::SOPORTE_HIBRIDO];
        if (!in_array($this->soporte, $soportesValidos)) {
            $errores[] = 'El tipo de soporte no es válido';
        }
        
        // Validar formatos de archivo para soporte electrónico
        if ($this->soporte === self::SOPORTE_ELECTRONICO || $this->soporte === self::SOPORTE_HIBRIDO) {
            if (empty($this->formato_archivo)) {
                $errores[] = 'Debe especificar al menos un formato de archivo para soporte electrónico';
            }
        }
        
        return $errores;
    }

    /**
     * Obtener estadísticas de la tipología
     */
    public function getEstadisticas()
    {
        return [
            'total_documentos' => $this->documentos()->count(),
            'documentos_activos' => $this->documentos()->where('estado', 'activo')->count(),
            'documentos_archivados' => $this->documentos()->where('estado', 'archivado')->count(),
            'subseries_asociadas' => $this->subseries()->count(),
            'volumen_total' => $this->documentos()->sum('tamaño'),
            'documentos_por_mes' => $this->documentos()
                ->selectRaw('YEAR(fecha_creacion) as year, MONTH(fecha_creacion) as month, COUNT(*) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'formatos_utilizados' => $this->documentos()
                ->selectRaw('formato, COUNT(*) as total')
                ->groupBy('formato')
                ->orderBy('total', 'desc')
                ->get()
        ];
    }

    /**
     * Inferir tipo de metadato basado en el nombre
     */
    private function inferirTipoMetadato($nombreMetadato)
    {
        $tipos = [
            'fecha' => ['fecha', 'date', 'creacion', 'modificacion', 'vencimiento'],
            'numero' => ['numero', 'cantidad', 'valor', 'precio', 'monto'],
            'email' => ['email', 'correo'],
            'url' => ['url', 'enlace', 'link'],
            'texto_largo' => ['descripcion', 'observaciones', 'comentarios', 'detalle'],
            'booleano' => ['activo', 'vigente', 'publico', 'confidencial']
        ];
        
        $nombreLower = strtolower($nombreMetadato);
        
        foreach ($tipos as $tipo => $palabrasClave) {
            foreach ($palabrasClave as $palabra) {
                if (strpos($nombreLower, $palabra) !== false) {
                    return $tipo;
                }
            }
        }
        
        return 'texto';
    }

    /**
     * Obtener descripción de metadato
     */
    private function getDescripcionMetadato($nombreMetadato)
    {
        $descripciones = [
            'titulo' => 'Título del documento',
            'autor' => 'Autor o creador del documento',
            'fecha_creacion' => 'Fecha de creación del documento',
            'fecha_modificacion' => 'Fecha de última modificación',
            'asunto' => 'Asunto o tema principal',
            'descripcion' => 'Descripción detallada del contenido',
            'palabras_clave' => 'Palabras clave para búsqueda',
            'idioma' => 'Idioma del documento',
            'formato' => 'Formato del archivo',
            'tamaño' => 'Tamaño del archivo en bytes',
            'version' => 'Versión del documento',
            'estado' => 'Estado actual del documento',
            'confidencialidad' => 'Nivel de confidencialidad',
            'destinatario' => 'Destinatario del documento',
            'remitente' => 'Remitente o emisor',
            'numero_folios' => 'Número de folios o páginas'
        ];
        
        return $descripciones[$nombreMetadato] ?? ucfirst(str_replace('_', ' ', $nombreMetadato));
    }

    /**
     * Validar si un valor es una fecha válida
     */
    private function esFechaValida($valor)
    {
        try {
            return \Carbon\Carbon::parse($valor) !== false;
        } catch (\Exception $e) {
            return false;
        }
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
     * Convertir tipología a CSV
     */
    private function tipologiaToCsv($data)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers de tipología
        fputcsv($output, [
            'Código', 'Nombre', 'Descripción', 'Categoría', 'Subcategoría',
            'Soporte', 'Formatos', 'Total Documentos', 'Documentos Activos'
        ]);
        
        // Data de tipología
        fputcsv($output, [
            $data['tipologia']['codigo'],
            $data['tipologia']['nombre'],
            $data['tipologia']['descripcion'],
            $data['tipologia']['categoria'],
            $data['tipologia']['subcategoria'],
            $data['tipologia']['soporte'],
            implode(', ', $data['tipologia']['formato_archivo'] ?? []),
            $data['estadisticas']['total_documentos'],
            $data['estadisticas']['documentos_activos']
        ]);
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
