<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Modelo de Expediente para SGDEA
 * 
 * Basado en requerimientos de Clasificación y Organización Documental:
 * REQ-CL-019: Generación automática de expedientes electrónicos
 * REQ-CL-020: Gestión del ciclo de vida de expedientes
 * REQ-CL-021: Expedientes híbridos
 * REQ-CL-025: Control de volúmenes de expedientes
 * REQ-CL-037: Exportación de directorios
 */
class Expediente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expedientes';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'serie_id',
        'subserie_id',
        'trd_id',
        'tipo_expediente',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'fecha_vencimiento_disposicion',
        'ubicacion_fisica',
        'ubicacion_digital',
        'volumen_actual',
        'volumen_maximo',
        'numero_folios',
        'metadatos_expediente',
        'palabras_clave',
        'usuario_responsable_id',
        'area_responsable',
        'confidencialidad',
        'acceso_publico',
        'observaciones',
        'firma_digital',
        'hash_integridad'
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'fecha_vencimiento_disposicion' => 'datetime',
        'metadatos_expediente' => 'array',
        'palabras_clave' => 'array',
        'acceso_publico' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Estados del expediente
    const ESTADO_ABIERTO = 'abierto';
    const ESTADO_CERRADO = 'cerrado';
    const ESTADO_TRANSFERIDO = 'transferido';
    const ESTADO_ARCHIVADO = 'archivado';
    const ESTADO_EN_DISPOSICION = 'en_disposicion';
    const ESTADO_ELIMINADO = 'eliminado';

    // Tipos de expediente
    const TIPO_ELECTRONICO = 'electronico';
    const TIPO_FISICO = 'fisico';
    const TIPO_HIBRIDO = 'hibrido';

    // Niveles de confidencialidad
    const CONFIDENCIALIDAD_PUBLICA = 'publica';
    const CONFIDENCIALIDAD_INTERNA = 'interna';
    const CONFIDENCIALIDAD_CONFIDENCIAL = 'confidencial';
    const CONFIDENCIALIDAD_RESERVADA = 'reservada';
    const CONFIDENCIALIDAD_CLASIFICADA = 'clasificada';

    // Volumen máximo por defecto (en MB)
    const VOLUMEN_MAXIMO_DEFAULT = 1024; // 1GB

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático y heredar metadatos
        static::creating(function ($expediente) {
            if (empty($expediente->codigo)) {
                $expediente->codigo = $expediente->generarCodigo();
            }
            
            // Heredar metadatos de serie/subserie
            $expediente->heredarMetadatos();
            
            // Establecer fecha de apertura si no existe
            if (!$expediente->fecha_apertura) {
                $expediente->fecha_apertura = now();
            }
            
            // Calcular fecha de vencimiento de disposición
            if (!$expediente->fecha_vencimiento_disposicion) {
                $expediente->calcularFechaVencimientoDisposicion();
            }
        });
        
        // Validar antes de cerrar
        static::updating(function ($expediente) {
            if ($expediente->isDirty('estado') && $expediente->estado === self::ESTADO_CERRADO) {
                $expediente->validarCierre();
            }
            
            // Actualizar hash de integridad
            if ($expediente->isDirty()) {
                $expediente->actualizarHashIntegridad();
            }
        });
        
        // Registrar en auditoría
        static::created(function ($expediente) {
            PistaAuditoria::registrar($expediente, PistaAuditoria::ACCION_CREAR, [
                'descripcion' => 'Expediente creado: ' . $expediente->codigo,
                'serie' => $expediente->serie->codigo ?? null,
                'subserie' => $expediente->subserie->codigo ?? null,
                'tipo' => $expediente->tipo_expediente
            ]);
        });
        
        static::updated(function ($expediente) {
            $cambios = $expediente->getDirty();
            
            // Registrar cambio de estado específicamente
            if (isset($cambios['estado'])) {
                PistaAuditoria::registrar($expediente, 'cambio_estado', [
                    'descripcion' => 'Estado del expediente cambiado: ' . $expediente->codigo,
                    'estado_anterior' => $expediente->getOriginal('estado'),
                    'estado_nuevo' => $expediente->estado
                ]);
            }
            
            PistaAuditoria::registrar($expediente, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Expediente actualizado: ' . $expediente->codigo,
                'valores_anteriores' => $expediente->getOriginal(),
                'valores_nuevos' => $expediente->getAttributes()
            ]);
        });
    }

    /**
     * Relación con Serie Documental
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class, 'serie_id');
    }

    /**
     * Relación con Subserie Documental
     */
    public function subserie(): BelongsTo
    {
        return $this->belongsTo(SubserieDocumental::class, 'subserie_id');
    }

    /**
     * Relación con TRD
     */
    public function trd(): BelongsTo
    {
        return $this->belongsTo(TablaRetencionDocumental::class, 'trd_id');
    }

    /**
     * Relación con documentos
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'expediente_id');
    }

    /**
     * Relación con préstamos
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function disposicionFinal(): HasOne
    {
        return $this->hasOne(DisposicionFinal::class);
    }

    /**
     * Relación con usuario responsable
     */
    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_responsable_id');
    }

    /**
     * Relación con metadatos específicos
     */
    public function metadatos(): MorphMany
    {
        return $this->morphMany(MetadatoExpediente::class, 'entidad');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria(): MorphMany
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Relación con firmas digitales
     */
    public function firmas(): HasMany
    {
        return $this->hasMany(FirmaDigital::class, 'expediente_id');
    }

    /**
     * Scope para expedientes abiertos
     */
    public function scopeAbiertos($query)
    {
        return $query->where('estado', self::ESTADO_ABIERTO);
    }

    /**
     * Scope para expedientes cerrados
     */
    public function scopeCerrados($query)
    {
        return $query->where('estado', self::ESTADO_CERRADO);
    }

    /**
     * Scope por tipo de expediente
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_expediente', $tipo);
    }

    /**
     * Scope por serie
     */
    public function scopePorSerie($query, $serieId)
    {
        return $query->where('serie_id', $serieId);
    }

    /**
     * Scope por área responsable
     */
    public function scopePorArea($query, $area)
    {
        return $query->where('area_responsable', $area);
    }

    /**
     * Scope para expedientes próximos a vencer
     */
    public function scopeProximosVencer($query, $dias = 30)
    {
        return $query->where('fecha_vencimiento_disposicion', '<=', 
                           now()->addDays($dias))
                    ->where('fecha_vencimiento_disposicion', '>=', now());
    }

    /**
     * Scope para expedientes vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_vencimiento_disposicion', '<', now());
    }

    /**
     * Generar código de expediente automático
     */
    public function generarCodigo()
    {
        $prefijo = 'EXP';
        $year = now()->format('Y');
        
        // Incluir código de serie si está disponible
        if ($this->serie) {
            $prefijo = $this->serie->codigo . '-EXP';
        } elseif ($this->subserie && $this->subserie->serie) {
            $prefijo = $this->subserie->serie->codigo . '-EXP';
        }
        
        // Obtener último número del expediente en el año
        $ultimoExpediente = static::where('codigo', 'LIKE', $prefijo . $year . '%')
                                 ->orderBy('codigo', 'desc')
                                 ->first();
        
        if ($ultimoExpediente) {
            $ultimoNumero = intval(substr($ultimoExpediente->codigo, -6));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $prefijo . $year . str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * REQ-CL-016: Heredar metadatos de serie/subserie
     */
    public function heredarMetadatos()
    {
        $metadatos = [];
        
        // Heredar de subserie si existe
        if ($this->subserie) {
            $metadatos = array_merge($metadatos, $this->subserie->getMetadatosHeredables());
        }
        // Si no hay subserie, heredar de serie
        elseif ($this->serie) {
            $metadatos = array_merge($metadatos, $this->serie->getMetadatosHeredables());
        }
        
        // Heredar de TRD si existe
        if ($this->trd) {
            $metadatos['trd_codigo'] = $this->trd->codigo;
            $metadatos['trd_version'] = $this->trd->version;
        }
        
        // Agregar metadatos específicos del expediente
        $metadatos['expediente_codigo'] = $this->codigo ?? 'EN_GENERACION';
        $metadatos['fecha_apertura'] = $this->fecha_apertura ?? now();
        $metadatos['tipo_expediente'] = $this->tipo_expediente;
        
        $this->metadatos_expediente = array_merge($this->metadatos_expediente ?? [], $metadatos);
    }

    /**
     * Calcular fecha de vencimiento de disposición
     */
    public function calcularFechaVencimientoDisposicion()
    {
        $fechaBase = $this->fecha_cierre ?? $this->fecha_apertura ?? now();
        
        $tiempoTotal = 0;
        
        // Obtener tiempos de la subserie o serie
        if ($this->subserie) {
            $tiempoTotal = ($this->subserie->tiempo_archivo_gestion ?? 0) + 
                          ($this->subserie->tiempo_archivo_central ?? 0);
        } elseif ($this->serie) {
            $tiempoTotal = ($this->serie->tiempo_archivo_gestion ?? 0) + 
                          ($this->serie->tiempo_archivo_central ?? 0);
        }
        
        if ($tiempoTotal > 0) {
            $this->fecha_vencimiento_disposicion = $fechaBase->copy()->addYears($tiempoTotal);
        }
    }

    /**
     * REQ-CL-020: Gestionar ciclo de vida del expediente
     */
    public function cambiarEstado($nuevoEstado, $observaciones = null)
    {
        $estadoAnterior = $this->estado;
        
        // Validar transición de estado
        $this->validarTransicionEstado($estadoAnterior, $nuevoEstado);
        
        $this->estado = $nuevoEstado;
        
        // Acciones específicas por estado
        switch ($nuevoEstado) {
            case self::ESTADO_CERRADO:
                $this->fecha_cierre = now();
                $this->calcularFechaVencimientoDisposicion();
                break;
                
            case self::ESTADO_TRANSFERIDO:
                $this->validarTransferencia();
                break;
                
            case self::ESTADO_ARCHIVADO:
                $this->validarArchivado();
                break;
        }
        
        if ($observaciones) {
            $this->observaciones = $observaciones;
        }
        
        $this->save();
        
        PistaAuditoria::registrar($this, 'cambio_estado', [
            'descripcion' => "Estado del expediente {$this->codigo} cambiado de {$estadoAnterior} a {$nuevoEstado}",
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
            'observaciones' => $observaciones
        ]);
    }

    /**
     * Validar transición entre estados
     */
    private function validarTransicionEstado($estadoAnterior, $nuevoEstado)
    {
        $transicionesValidas = [
            self::ESTADO_ABIERTO => [self::ESTADO_CERRADO, self::ESTADO_ARCHIVADO],
            self::ESTADO_CERRADO => [self::ESTADO_TRANSFERIDO, self::ESTADO_ARCHIVADO, self::ESTADO_EN_DISPOSICION],
            self::ESTADO_TRANSFERIDO => [self::ESTADO_ARCHIVADO, self::ESTADO_EN_DISPOSICION],
            self::ESTADO_ARCHIVADO => [self::ESTADO_EN_DISPOSICION],
            self::ESTADO_EN_DISPOSICION => [self::ESTADO_ELIMINADO, self::ESTADO_ARCHIVADO]
        ];
        
        if (!isset($transicionesValidas[$estadoAnterior]) || 
            !in_array($nuevoEstado, $transicionesValidas[$estadoAnterior])) {
            throw new \Exception("Transición de estado no válida: {$estadoAnterior} -> {$nuevoEstado}");
        }
    }

    /**
     * Validar cierre del expediente
     */
    private function validarCierre()
    {
        // Verificar que no hay documentos en estado pendiente
        $documentosPendientes = $this->documentos()->where('estado', 'pendiente')->count();
        if ($documentosPendientes > 0) {
            throw new \Exception('No se puede cerrar el expediente. Hay documentos en estado pendiente.');
        }
        
        // Actualizar número de folios
        $this->numero_folios = $this->documentos()->sum('numero_folios');
        
        // Actualizar volumen actual
        $this->volumen_actual = $this->documentos()->sum('tamaño');
    }

    /**
     * REQ-CL-025: Verificar límite de volumen
     */
    public function verificarLimiteVolumen()
    {
        $volumenMaximo = $this->volumen_maximo ?? self::VOLUMEN_MAXIMO_DEFAULT;
        return $this->volumen_actual <= $volumenMaximo;
    }

    /**
     * Agregar documento al expediente
     */
    public function agregarDocumento(Documento $documento)
    {
        // Verificar que el expediente esté abierto
        if ($this->estado !== self::ESTADO_ABIERTO) {
            throw new \Exception('Solo se pueden agregar documentos a expedientes abiertos');
        }
        
        // Verificar límite de volumen
        $nuevoVolumen = $this->volumen_actual + $documento->tamaño;
        $volumenMaximo = $this->volumen_maximo ?? self::VOLUMEN_MAXIMO_DEFAULT;
        
        if ($nuevoVolumen > $volumenMaximo) {
            throw new \Exception("El documento excede el límite de volumen del expediente ({$volumenMaximo} MB)");
        }
        
        // Asignar el documento al expediente
        $documento->expediente_id = $this->id;
        $documento->save();
        
        // Actualizar volumen y folios
        $this->volumen_actual = $nuevoVolumen;
        $this->numero_folios += $documento->numero_folios ?? 1;
        $this->save();
        
        PistaAuditoria::registrar($this, 'documento_agregado', [
            'descripcion' => "Documento {$documento->codigo} agregado al expediente {$this->codigo}",
            'documento_codigo' => $documento->codigo,
            'nuevo_volumen' => $this->volumen_actual
        ]);
    }

    /**
     * REQ-CL-037: Exportar directorio del expediente
     */
    public function exportarDirectorio($formato = 'json', $incluirDocumentos = true)
    {
        $directorio = [
            'expediente' => [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'tipo' => $this->tipo_expediente,
                'estado' => $this->estado,
                'fecha_apertura' => $this->fecha_apertura->format('Y-m-d H:i:s'),
                'fecha_cierre' => $this->fecha_cierre?->format('Y-m-d H:i:s'),
                'numero_folios' => $this->numero_folios,
                'volumen_actual' => $this->volumen_actual,
                'ubicacion_fisica' => $this->ubicacion_fisica,
                'ubicacion_digital' => $this->ubicacion_digital,
                'confidencialidad' => $this->confidencialidad,
                'area_responsable' => $this->area_responsable
            ],
            'clasificacion' => [
                'serie' => [
                    'codigo' => $this->serie->codigo ?? null,
                    'nombre' => $this->serie->nombre ?? null
                ],
                'subserie' => [
                    'codigo' => $this->subserie->codigo ?? null,
                    'nombre' => $this->subserie->nombre ?? null
                ],
                'trd' => [
                    'codigo' => $this->trd->codigo ?? null,
                    'version' => $this->trd->version ?? null
                ]
            ],
            'metadatos' => $this->metadatos_expediente,
            'estadisticas' => [
                'total_documentos' => $this->documentos()->count(),
                'documentos_electronicos' => $this->documentos()->where('tipo_soporte', 'electronico')->count(),
                'documentos_fisicos' => $this->documentos()->where('tipo_soporte', 'fisico')->count(),
                'documentos_hibridos' => $this->documentos()->where('tipo_soporte', 'hibrido')->count()
            ],
            'fecha_exportacion' => now()->toISOString()
        ];
        
        if ($incluirDocumentos) {
            $directorio['documentos'] = $this->documentos()
                ->orderBy('created_at')
                ->get()
                ->map(function ($doc) {
                    return [
                        'codigo' => $doc->codigo,
                        'nombre' => $doc->nombre,
                        'tipo_documental' => $doc->tipo_documental,
                        'formato' => $doc->formato,
                        'tamaño' => $doc->tamaño,
                        'fecha_creacion' => $doc->fecha_creacion,
                        'estado' => $doc->estado,
                        'ubicacion' => $doc->ubicacion_fisica,
                        'hash_integridad' => $doc->hash_integridad
                    ];
                });
        }
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($directorio, 'directorio_expediente');
            case 'csv':
                return $this->directorioToCsv($directorio);
            default:
                return json_encode($directorio, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Validar integridad del expediente
     */
    public function validarIntegridad()
    {
        $errores = [];
        
        // Verificar documentos faltantes
        $documentosRequeridos = $this->documentos()->where('requerido', true);
        foreach ($documentosRequeridos as $doc) {
            if (!$doc->existe()) {
                $errores[] = "Documento requerido faltante: {$doc->codigo}";
            }
        }
        
        // Verificar hash de integridad
        $hashCalculado = $this->calcularHashIntegridad();
        if ($this->hash_integridad && $this->hash_integridad !== $hashCalculado) {
            $errores[] = "Hash de integridad no coincide";
        }
        
        // Verificar consistencia de metadatos
        if ($this->serie_id && $this->subserie_id) {
            if ($this->subserie->serie_id !== $this->serie_id) {
                $errores[] = "Inconsistencia: La subserie no pertenece a la serie asignada";
            }
        }
        
        return $errores;
    }

    /**
     * Actualizar hash de integridad
     */
    public function actualizarHashIntegridad()
    {
        $this->hash_integridad = $this->calcularHashIntegridad();
    }

    /**
     * Calcular hash de integridad
     */
    private function calcularHashIntegridad()
    {
        $datos = [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'estado' => $this->estado,
            'fecha_apertura' => $this->fecha_apertura?->timestamp,
            'fecha_cierre' => $this->fecha_cierre?->timestamp,
            'documentos' => $this->documentos()->pluck('hash_integridad')->sort()->toArray()
        ];
        
        return hash('sha256', json_encode($datos));
    }

    /**
     * Obtener estadísticas del expediente
     */
    public function getEstadisticas()
    {
        return [
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            'tipo' => $this->tipo_expediente,
            'total_documentos' => $this->documentos()->count(),
            'numero_folios' => $this->numero_folios,
            'volumen_actual_mb' => round($this->volumen_actual / 1024 / 1024, 2),
            'volumen_maximo_mb' => round(($this->volumen_maximo ?? self::VOLUMEN_MAXIMO_DEFAULT) / 1024 / 1024, 2),
            'porcentaje_ocupacion' => round(($this->volumen_actual / ($this->volumen_maximo ?? self::VOLUMEN_MAXIMO_DEFAULT)) * 100, 2),
            'dias_abierto' => $this->fecha_apertura->diffInDays($this->fecha_cierre ?? now()),
            'dias_hasta_vencimiento' => $this->fecha_vencimiento_disposicion ? 
                                       now()->diffInDays($this->fecha_vencimiento_disposicion, false) : null,
            'documentos_por_tipo' => $this->documentos()
                ->selectRaw('tipo_documental, COUNT(*) as total')
                ->groupBy('tipo_documental')
                ->get(),
            'documentos_por_formato' => $this->documentos()
                ->selectRaw('formato, COUNT(*) as total')
                ->groupBy('formato')
                ->get()
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
     * Convertir directorio a CSV
     */
    private function directorioToCsv($directorio)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers del expediente
        fputcsv($output, ['INFORMACIÓN DEL EXPEDIENTE']);
        fputcsv($output, ['Código', 'Nombre', 'Estado', 'Tipo', 'Fecha Apertura', 'Folios', 'Volumen MB']);
        fputcsv($output, [
            $directorio['expediente']['codigo'],
            $directorio['expediente']['nombre'],
            $directorio['expediente']['estado'],
            $directorio['expediente']['tipo'],
            $directorio['expediente']['fecha_apertura'],
            $directorio['expediente']['numero_folios'],
            round($directorio['expediente']['volumen_actual'] / 1024 / 1024, 2)
        ]);
        
        fputcsv($output, []);
        
        // Headers de documentos si existen
        if (isset($directorio['documentos'])) {
            fputcsv($output, ['DOCUMENTOS DEL EXPEDIENTE']);
            fputcsv($output, ['Código', 'Nombre', 'Tipo', 'Formato', 'Tamaño', 'Estado']);
            
            foreach ($directorio['documentos'] as $doc) {
                fputcsv($output, [
                    $doc['codigo'],
                    $doc['nombre'],
                    $doc['tipo_documental'],
                    $doc['formato'],
                    $doc['tamaño'],
                    $doc['estado']
                ]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Relación con notificaciones (polimórfica)
     */
    public function notificaciones(): MorphMany
    {
        return $this->morphMany(Notificacion::class, 'relacionado', 'relacionado_tipo', 'relacionado_id');
    }
}
