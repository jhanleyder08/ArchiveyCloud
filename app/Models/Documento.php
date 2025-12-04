<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Modelo de Documento para SGDEA
 * 
 * Basado en requerimientos de Captura e Ingreso de Documentos:
 * REQ-CD-001 a REQ-CD-010: Gestión de formatos y tipos de contenido
 * REQ-CD-012: Gestión de versiones
 * REQ-CD-014: Identificación por tipo documental
 * REQ-CD-015: Firma digital
 * REQ-CD-016: Visualización sin aplicaciones nativas
 */
class Documento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'codigo',
        'codigo_documento',
        'nombre',
        'titulo',
        'descripcion',
        'expediente_id',
        'tipologia_id',
        'tipologia_documental_id',
        'plantilla_id',
        'productor_id',
        'tipo_documental',
        'tipo_soporte',
        'formato',
        'tamaño',
        'tamano_bytes',
        'numero_folios',
        'ruta_archivo',
        'nombre_archivo',
        'ruta_miniatura',
        'hash_integridad',
        'hash_sha256',
        'rutas_conversiones',
        'contenido_ocr',
        'estado_procesamiento',
        'error_procesamiento',
        'fecha_procesamiento',
        'metadatos_archivo',
        'configuracion_procesamiento',
        'firma_digital',
        'fecha_creacion',
        'fecha_modificacion',
        'fecha_digitalizacion',
        'fecha_documento',
        'fecha_captura',
        'version',
        'version_mayor',
        'version_menor',
        'es_version_principal',
        'documento_padre_id',
        'estado',
        'activo',
        'confidencialidad',
        'palabras_clave',
        'metadatos_documento',
        'ubicacion_fisica',
        'observaciones',
        'usuario_creador_id',
        'usuario_modificador_id',
        'created_by',
        'updated_by',
        'firmado_digitalmente',
        'fecha_ultima_firma',
        'estado_firma',
        'total_firmas'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_modificacion' => 'datetime',
        'fecha_digitalizacion' => 'datetime',
        'fecha_procesamiento' => 'datetime',
        'fecha_ultima_firma' => 'datetime',
        'es_version_principal' => 'boolean',
        'firmado_digitalmente' => 'boolean',
        'palabras_clave' => 'array',
        'metadatos_documento' => 'array',
        'metadatos_archivo' => 'array',
        'rutas_conversiones' => 'array',
        'configuracion_procesamiento' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Estados del documento
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_ARCHIVADO = 'archivado';
    const ESTADO_OBSOLETO = 'obsoleto';
    const ESTADO_ELIMINADO = 'eliminado';

    // Tipos de soporte
    const SOPORTE_ELECTRONICO = 'electronico';
    const SOPORTE_FISICO = 'fisico';
    const SOPORTE_HIBRIDO = 'hibrido';

    // Niveles de confidencialidad
    const CONFIDENCIALIDAD_PUBLICA = 'publica';
    const CONFIDENCIALIDAD_INTERNA = 'interna';
    const CONFIDENCIALIDAD_CONFIDENCIAL = 'confidencial';
    const CONFIDENCIALIDAD_RESERVADA = 'reservada';
    const CONFIDENCIALIDAD_CLASIFICADA = 'clasificada';

    // Estados de procesamiento avanzado
    const PROCESAMIENTO_PENDIENTE = 'pendiente';
    const PROCESAMIENTO_PROCESANDO = 'procesando';
    const PROCESAMIENTO_COMPLETADO = 'completado';
    const PROCESAMIENTO_ERROR = 'error';
    const PROCESAMIENTO_FALLIDO = 'fallido';

    // Formatos soportados por categoría
    const FORMATOS_TEXTO = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
    const FORMATOS_IMAGEN = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'];
    const FORMATOS_HOJA_CALCULO = ['xls', 'xlsx', 'csv', 'ods'];
    const FORMATOS_PRESENTACION = ['ppt', 'pptx', 'odp'];
    const FORMATOS_AUDIO = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
    const FORMATOS_VIDEO = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    const FORMATOS_COMPRIMIDOS = ['zip', 'rar', '7z', 'tar', 'gz'];

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático usando campo correcto de la BD
        static::creating(function ($documento) {
            if (empty($documento->codigo_documento)) {
                $year = date('Y');
                $expedienteCodigo = $documento->expediente->codigo ?? 'GEN';
                $lastDoc = self::where('codigo_documento', 'LIKE', $expedienteCodigo . '-DOC' . $year . '%')
                    ->orderBy('codigo_documento', 'desc')
                    ->first();
                $nextNum = 1;
                if ($lastDoc && preg_match('/(\d{4})$/', $lastDoc->codigo_documento, $matches)) {
                    $nextNum = intval($matches[1]) + 1;
                }
                $documento->codigo_documento = $expedienteCodigo . '-DOC' . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
        });
        
        // Actualizar hash cuando se modifica el archivo
        static::updating(function ($documento) {
            if ($documento->isDirty('ruta_archivo')) {
                $documento->calcularHashIntegridad();
            }
        });
        
        // Registrar en auditoría
        static::created(function ($documento) {
            if (class_exists(PistaAuditoria::class)) {
                PistaAuditoria::registrar($documento, PistaAuditoria::ACCION_CREAR, [
                    'descripcion' => 'Documento creado: ' . $documento->codigo_documento,
                    'expediente' => $documento->expediente->codigo ?? null,
                    'formato' => $documento->formato,
                    'tamano' => $documento->tamano_bytes
                ]);
            }
        });
        
        static::updated(function ($documento) {
            if (!class_exists(PistaAuditoria::class)) return;
            
            $cambios = $documento->getDirty();
            
            // Registrar cambio de estado específicamente
            if (isset($cambios['activo'])) {
                PistaAuditoria::registrar($documento, 'cambio_estado', [
                    'descripcion' => 'Estado del documento cambiado: ' . $documento->codigo_documento,
                    'estado_anterior' => $documento->getOriginal('activo') ? 'activo' : 'inactivo',
                    'estado_nuevo' => $documento->activo ? 'activo' : 'inactivo'
                ]);
            }
            
            PistaAuditoria::registrar($documento, PistaAuditoria::ACCION_ACTUALIZAR, [
                'descripcion' => 'Documento actualizado: ' . $documento->codigo_documento,
                'valores_anteriores' => $documento->getOriginal(),
                'valores_nuevos' => $documento->getAttributes()
            ]);
        });
    }

    /**
     * Relación con expediente
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'expediente_id');
    }

    /**
     * Relación con tipología documental
     */
    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(TipologiaDocumental::class, 'tipologia_documental_id');
    }

    /**
     * Relación con plantilla documental (si fue generado desde una plantilla)
     */
    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaDocumental::class, 'plantilla_id');
    }

    /**
     * Relación con documento padre (para versiones)
     */
    public function documentoPadre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'documento_padre_id');
    }

    /**
     * Relación con versiones del documento
     */
    public function versiones(): HasMany
    {
        return $this->hasMany(self::class, 'documento_padre_id');
    }

    /**
     * Relación con usuario creador
     */
    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    /**
     * Relación con usuario modificador
     */
    public function usuarioModificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_modificador_id');
    }

    /**
     * Relación con metadatos específicos
     */
    public function metadatos(): MorphMany
    {
        return $this->morphMany(MetadatoDocumento::class, 'entidad');
    }

    /**
     * Relación con firmas digitales
     */
    public function firmas(): HasMany
    {
        return $this->hasMany(FirmaDigital::class, 'documento_id');
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria(): MorphMany
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Relación con conversiones de formato
     */
    public function conversiones(): HasMany
    {
        return $this->hasMany(ConversionFormato::class, 'documento_id');
    }

    /**
     * Relación con disposición final
     */
    public function disposicionFinal()
    {
        return $this->hasOne(DisposicionFinal::class, 'documento_id');
    }

    /**
     * Scope para documentos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para versiones principales
     */
    public function scopeVersionesPrincipales($query)
    {
        return $query->where('es_version_principal', true);
    }

    /**
     * Scope por tipo de soporte
     */
    public function scopePorSoporte($query, $soporte)
    {
        return $query->where('tipo_soporte', $soporte);
    }

    /**
     * Scope por formato
     */
    public function scopePorFormato($query, $formato)
    {
        return $query->where('formato', $formato);
    }

    /**
     * Scope por expediente
     */
    public function scopePorExpediente($query, $expedienteId)
    {
        return $query->where('expediente_id', $expedienteId);
    }

    /**
     * Scope por tipología
     */
    public function scopePorTipologia($query, $tipologiaId)
    {
        return $query->where('tipologia_id', $tipologiaId);
    }

    /**
     * Generar código de documento automático
     */
    public function generarCodigo()
    {
        $prefijo = 'DOC';
        $year = now()->format('Y');
        
        // Incluir código de expediente si está disponible
        if ($this->expediente) {
            $prefijo = $this->expediente->codigo . '-DOC';
        }
        
        // Obtener último número del documento en el año
        $ultimoDocumento = static::where('codigo_documento', 'LIKE', $prefijo . $year . '%')
                                ->orderBy('codigo_documento', 'desc')
                                ->first();
        
        if ($ultimoDocumento) {
            $ultimoNumero = intval(substr($ultimoDocumento->codigo_documento, -6));
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }
        
        return $prefijo . $year . str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Heredar metadatos del expediente
     */
    public function heredarMetadatos()
    {
        if (!$this->expediente) {
            return;
        }
        
        $metadatos = $this->expediente->metadatos_expediente ?? [];
        
        // Agregar metadatos específicos del documento
        $metadatos['documento_codigo'] = $this->codigo_documento ?? 'EN_GENERACION';
        $metadatos['titulo'] = $this->titulo;
        $metadatos['fecha_creacion_documento'] = $this->fecha_creacion ?? now();
        
        $this->metadatos_documento = array_merge($this->metadatos_documento ?? [], $metadatos);
    }

    /**
     * REQ-CD-012: Crear nueva versión del documento
     */
    public function crearNuevaVersion($rutaArchivo, $observaciones = null)
    {
        // Verificar que no sea ya la versión principal
        if (!$this->es_version_principal) {
            throw new \Exception('Solo se puede crear versión desde el documento principal');
        }
        
        // Obtener el número de la siguiente versión
        $ultimaVersion = $this->versiones()->orderBy('version', 'desc')->first();
        $numeroVersion = $ultimaVersion ? 
                        $this->incrementarVersion($ultimaVersion->version) : 
                        $this->incrementarVersion($this->version);
        
        // Crear nueva versión
        $nuevaVersion = $this->replicate();
        $nuevaVersion->documento_padre_id = $this->id;
        $nuevaVersion->es_version_principal = false;
        $nuevaVersion->version = $numeroVersion;
        $nuevaVersion->ruta_archivo = $rutaArchivo;
        $nuevaVersion->fecha_modificacion = now();
        $nuevaVersion->observaciones = $observaciones;
        $nuevaVersion->codigo = $this->codigo . '.v' . $numeroVersion;
        
        // Calcular nuevo hash de integridad
        $nuevaVersion->calcularHashIntegridad();
        
        $nuevaVersion->save();
        
        PistaAuditoria::registrar($nuevaVersion, 'version_creada', [
            'descripcion' => "Nueva versión {$numeroVersion} creada para documento {$this->codigo}",
            'documento_principal' => $this->codigo,
            'version_anterior' => $this->version,
            'version_nueva' => $numeroVersion,
            'observaciones' => $observaciones
        ]);
        
        return $nuevaVersion;
    }

    /**
     * Incrementar número de versión
     */
    private function incrementarVersion($versionActual)
    {
        $partes = explode('.', $versionActual);
        $mayor = intval($partes[0]);
        $menor = isset($partes[1]) ? intval($partes[1]) : 0;
        
        $menor++;
        
        return $mayor . '.' . $menor;
    }

    /**
     * REQ-CD-001: Validar formato de archivo
     */
    public function validarFormato()
    {
        $formatosPermitidos = array_merge(
            self::FORMATOS_TEXTO,
            self::FORMATOS_IMAGEN,
            self::FORMATOS_HOJA_CALCULO,
            self::FORMATOS_PRESENTACION,
            self::FORMATOS_AUDIO,
            self::FORMATOS_VIDEO,
            self::FORMATOS_COMPRIMIDOS
        );
        
        if (!in_array(strtolower($this->formato), $formatosPermitidos)) {
            throw new \Exception("Formato de archivo no permitido: {$this->formato}");
        }
        
        // Validar contra tipología si existe
        if ($this->tipologia && !empty($this->tipologia->formato_archivo)) {
            if (!in_array(strtolower($this->formato), $this->tipologia->formato_archivo)) {
                throw new \Exception("El formato {$this->formato} no está permitido para la tipología {$this->tipologia->nombre}");
            }
        }
    }

    /**
     * REQ-CD-015: Firmar documento digitalmente
     */
    public function firmarDigitalmente(User $usuario, $tipoFirma = 'CADES')
    {
        // Verificar que el archivo existe
        if (!$this->existe()) {
            throw new \Exception('No se puede firmar un documento sin archivo');
        }
        
        // Crear registro de firma
        $firma = new FirmaDigital();
        $firma->documento_id = $this->id;
        $firma->usuario_id = $usuario->id;
        $firma->tipo_firma = $tipoFirma;
        $firma->fecha_firma = now();
        $firma->hash_documento = $this->hash_integridad;
        $firma->certificado = $this->obtenerCertificadoUsuario($usuario);
        
        // Generar firma digital (simulada para este ejemplo)
        $firma->firma_digital = $this->generarFirmaDigital($usuario, $tipoFirma);
        
        $firma->save();
        
        // Actualizar estado del documento
        $this->firma_digital = $firma->firma_digital;
        $this->save();
        
        PistaAuditoria::registrar($this, 'firma_digital', [
            'descripcion' => "Documento firmado digitalmente: {$this->codigo}",
            'usuario_firmante' => $usuario->name,
            'tipo_firma' => $tipoFirma
        ]);
        
        return $firma;
    }

    /**
     * REQ-CD-016: Verificar si existe el archivo físico
     */
    public function existe()
    {
        if (empty($this->metadatos_archivo)) {
            // Intentar usar ruta_archivo directa si existe
            if ($this->ruta_archivo) {
                return Storage::disk('public')->exists($this->ruta_archivo);
            }
            return false;
        }
        
        $metadatos = $this->metadatos_archivo;
        if (is_string($metadatos)) {
            $metadatos = json_decode($metadatos, true);
        }
        
        $ruta = $metadatos['ruta'] ?? null;
        
        // Si no hay ruta en metadatos, usar la directa
        if (!$ruta && $this->ruta_archivo) {
            $ruta = $this->ruta_archivo;
        }
        
        if (!$ruta) {
            return false;
        }
        
        return Storage::disk('public')->exists($ruta);
    }

    /**
     * Obtener URL de descarga
     */
    public function getUrlDescarga()
    {
        if (!$this->existe()) {
            return null;
        }
        
        $metadatos = json_decode($this->metadatos_archivo, true);
        $ruta = $metadatos['ruta'] ?? null;
        
        return $ruta ? Storage::url($ruta) : null;
    }

    /**
     * REQ-CD-016: Generar miniatura para visualización
     */
    public function generarMiniatura()
    {
        if (!$this->existe()) {
            throw new \Exception('No se puede generar miniatura sin archivo');
        }
        
        $formatosImagen = self::FORMATOS_IMAGEN;
        
        if (in_array(strtolower($this->formato), $formatosImagen)) {
            // Generar miniatura de imagen
            $rutaMiniatura = $this->generarMiniaturaImagen();
        } elseif (strtolower($this->formato) === 'pdf') {
            // Generar miniatura de PDF
            $rutaMiniatura = $this->generarMiniaturaPdf();
        } else {
            // Usar icono genérico según el tipo
            $rutaMiniatura = $this->obtenerIconoGenerico();
        }
        
        $this->ruta_miniatura = $rutaMiniatura;
        $this->save();
        
        return $rutaMiniatura;
    }

    /**
     * Calcular hash de integridad del archivo
     */
    public function calcularHashIntegridad()
    {
        if (!$this->existe()) {
            $this->hash_integridad = null;
            return;
        }
        
        $contenido = Storage::get($this->ruta_archivo);
        $this->hash_integridad = hash('sha256', $contenido);
    }


    /**
     * REQ-CD-003: Convertir formato de archivo
     */
    public function convertirFormato($formatoDestino)
    {
        if (!$this->existe()) {
            throw new \Exception('No se puede convertir un documento sin archivo');
        }
        
        // Verificar que la conversión es posible
        $conversionesPermitidas = $this->obtenerConversionesPermitidas();
        
        if (!in_array($formatoDestino, $conversionesPermitidas)) {
            throw new \Exception("No se puede convertir de {$this->formato} a {$formatoDestino}");
        }
        
        // Crear registro de conversión
        $conversion = new ConversionFormato();
        $conversion->documento_id = $this->id;
        $conversion->formato_origen = $this->formato;
        $conversion->formato_destino = $formatoDestino;
        $conversion->estado = 'en_proceso';
        $conversion->fecha_inicio = now();
        
        $conversion->save();
        
        try {
            // Realizar conversión (aquí iría la lógica específica de conversión)
            $rutaConvertida = $this->ejecutarConversion($formatoDestino);
            
            $conversion->ruta_resultado = $rutaConvertida;
            $conversion->estado = 'completada';
            $conversion->fecha_fin = now();
            $conversion->save();
            
            PistaAuditoria::registrar($this, 'conversion_formato', [
                'descripcion' => "Documento convertido de {$this->formato} a {$formatoDestino}",
                'formato_origen' => $this->formato,
                'formato_destino' => $formatoDestino
            ]);
            
            return $conversion;
            
        } catch (\Exception $e) {
            $conversion->estado = 'error';
            $conversion->mensaje_error = $e->getMessage();
            $conversion->fecha_fin = now();
            $conversion->save();
            
            throw $e;
        }
    }

    /**
     * Obtener estadísticas del documento
     */
    public function getEstadisticas()
    {
        return [
            'codigo' => $this->codigo_documento,
            'nombre' => $this->titulo,
            'tipo_documental' => $this->tipo_documental,
            'formato' => $this->formato,
            'tamaño_mb' => $this->tamano_bytes ? round($this->tamano_bytes / 1024 / 1024, 2) : 0,
            'numero_folios' => $this->numero_folios,
            'version' => $this->version,
            'es_version_principal' => $this->es_version_principal,
            'total_versiones' => 1, // Por ahora solo versión principal
            'activo' => $this->activo,
            'estado_procesamiento' => $this->estado_procesamiento,
            'confidencialidad' => $this->confidencialidad,
            'tiene_firma_digital' => $this->firmado_digitalmente || !empty($this->firma_digital),
            'total_firmas' => $this->total_firmas ?? 0,
            'fecha_creacion' => $this->fecha_creacion?->format('Y-m-d H:i:s'),
            'fecha_modificacion' => $this->fecha_modificacion?->format('Y-m-d H:i:s'),
            'usuario_creador' => $this->usuarioCreador->name ?? null,
            'expediente' => $this->expediente->codigo ?? null,
            'integridad_verificada' => $this->verificarIntegridad(),
            'archivo_existe' => $this->existe()
        ];
    }

    /**
     * Exportar información del documento
     */
    public function exportar($formato = 'json', $incluirVersiones = false)
    {
        $data = [
            'documento' => [
                'codigo' => $this->codigo_documento,
                'nombre' => $this->titulo,
                'descripcion' => $this->descripcion,
                'tipo_documental' => $this->tipo_documental,
                'tipo_soporte' => $this->tipo_soporte,
                'formato' => $this->formato,
                'tamano_bytes' => $this->tamano_bytes,
                'numero_folios' => $this->numero_folios,
                'version' => $this->version,
                'activo' => $this->activo,
                'estado_procesamiento' => $this->estado_procesamiento,
                'confidencialidad' => $this->confidencialidad,
                'fecha_creacion' => $this->fecha_creacion?->format('Y-m-d H:i:s'),
                'fecha_modificacion' => $this->fecha_modificacion?->format('Y-m-d H:i:s'),
                'hash_integridad' => $this->hash_sha256,
                'tiene_firma_digital' => !empty($this->firma_digital)
            ],
            'expediente' => [
                'codigo' => $this->expediente->codigo ?? null,
                'titulo' => $this->expediente->titulo ?? null
            ],
            'tipologia' => [
                'nombre' => $this->tipologia->nombre ?? null,
                'categoria' => $this->tipologia->categoria ?? null
            ],
            'metadatos' => $this->metadatos_documento,
            'palabras_clave' => $this->palabras_clave,
            'estadisticas' => $this->getEstadisticas(),
            'fecha_exportacion' => now()->toISOString()
        ];
        
        if ($incluirVersiones && $this->es_version_principal) {
            $data['versiones'] = $this->versiones->map(function ($version) {
                return [
                    'codigo' => $version->codigo_documento,
                    'version' => $version->version,
                    'fecha_modificacion' => $version->fecha_modificacion?->format('Y-m-d H:i:s'),
                    'observaciones' => $version->observaciones,
                    'tamano_bytes' => $version->tamano_bytes,
                    'hash_integridad' => $version->hash_sha256
                ];
            });
        }
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($data, 'documento');
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Métodos privados auxiliares
     */
    private function obtenerCertificadoUsuario(User $usuario)
    {
        // Aquí iría la lógica para obtener el certificado digital del usuario
        return 'certificado_' . $usuario->id;
    }

    private function generarFirmaDigital(User $usuario, $tipoFirma)
    {
        // Aquí iría la lógica real de firma digital
        return hash('sha256', $this->hash_integridad . $usuario->id . $tipoFirma . time());
    }

    private function obtenerConversionesPermitidas()
    {
        // Definir conversiones posibles según el formato actual
        $conversiones = [
            'pdf' => ['jpg', 'png', 'txt'],
            'doc' => ['pdf', 'txt', 'rtf'],
            'docx' => ['pdf', 'txt', 'rtf', 'doc'],
            'xls' => ['csv', 'pdf'],
            'xlsx' => ['csv', 'pdf', 'xls'],
            'jpg' => ['png', 'pdf'],
            'png' => ['jpg', 'pdf']
        ];
        
        return $conversiones[strtolower($this->formato)] ?? [];
    }

    private function ejecutarConversion($formatoDestino)
    {
        // Aquí iría la lógica real de conversión de archivos
        // Por ahora retornamos una ruta simulada
        $rutaDestino = str_replace('.' . $this->formato, '.' . $formatoDestino, $this->ruta_archivo);
        return $rutaDestino;
    }

    private function generarMiniaturaImagen()
    {
        // Lógica para generar miniatura de imagen
        return str_replace('.' . $this->formato, '_thumb.jpg', $this->ruta_archivo);
    }

    private function generarMiniaturaPdf()
    {
        // Lógica para generar miniatura de PDF
        return str_replace('.pdf', '_thumb.jpg', $this->ruta_archivo);
    }

    private function obtenerIconoGenerico()
    {
        // Retornar ruta de icono genérico según el formato
        return 'icons/generic_' . $this->formato . '.png';
    }

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
     * REQ-CP-007: Métodos para procesamiento avanzado
     */

    /**
     * Verificar si el documento requiere procesamiento
     */
    public function requiereProcesamiento(): bool
    {
        return $this->estado_procesamiento === self::PROCESAMIENTO_PENDIENTE;
    }

    /**
     * Verificar si el documento está siendo procesado
     */
    public function estaProcesando(): bool
    {
        return $this->estado_procesamiento === self::PROCESAMIENTO_PROCESANDO;
    }

    /**
     * Verificar si el procesamiento está completado
     */
    public function procesamientoCompletado(): bool
    {
        return $this->estado_procesamiento === self::PROCESAMIENTO_COMPLETADO;
    }

    /**
     * Verificar si hubo error en el procesamiento
     */
    public function tieneErrorProcesamiento(): bool
    {
        return in_array($this->estado_procesamiento, [
            self::PROCESAMIENTO_ERROR,
            self::PROCESAMIENTO_FALLIDO
        ]);
    }

    /**
     * Obtener el contenido OCR extraído
     */
    public function getContenidoOcr(): ?string
    {
        return $this->contenido_ocr;
    }

    /**
     * Verificar si tiene contenido OCR
     */
    public function tieneOcr(): bool
    {
        return !empty($this->contenido_ocr);
    }

    /**
     * Obtener URL de la miniatura
     */
    public function getUrlMiniatura(): ?string
    {
        if (!$this->ruta_miniatura) {
            return null;
        }

        return Storage::disk('public')->url($this->ruta_miniatura);
    }

    /**
     * Verificar si tiene miniatura
     */
    public function tieneMiniatura(): bool
    {
        return !empty($this->ruta_miniatura) && 
               Storage::disk('public')->exists($this->ruta_miniatura);
    }

    /**
     * Obtener metadatos de archivo procesado
     */
    public function getMetadatosArchivo(): array
    {
        return $this->metadatos_archivo ?? [];
    }

    /**
     * Obtener configuración de procesamiento aplicada
     */
    public function getConfiguracionProcesamiento(): array
    {
        return $this->configuracion_procesamiento ?? [];
    }

    /**
     * Obtener rutas de conversiones realizadas
     */
    public function getRutasConversiones(): array
    {
        return $this->rutas_conversiones ?? [];
    }

    /**
     * Verificar integridad del archivo usando hash SHA-256
     */
    public function verificarIntegridad(): bool
    {
        if (!$this->hash_sha256 || !$this->ruta_archivo) {
            return false;
        }

        if (!Storage::disk('public')->exists($this->ruta_archivo)) {
            return false;
        }

        $rutaCompleta = storage_path('app/public/' . $this->ruta_archivo);
        $hashActual = hash_file('sha256', $rutaCompleta);

        return $hashActual === $this->hash_sha256;
    }

    /**
     * Actualizar hash de integridad
     */
    public function actualizarHashIntegridad(): bool
    {
        if (!$this->ruta_archivo || !Storage::disk('public')->exists($this->ruta_archivo)) {
            return false;
        }

        $rutaCompleta = storage_path('app/public/' . $this->ruta_archivo);
        $this->hash_sha256 = hash_file('sha256', $rutaCompleta);
        $this->save();

        return true;
    }

    /**
     * Marcar como procesado correctamente
     */
    public function marcarProcesamientoCompletado(array $metadatos = []): void
    {
        $this->update([
            'estado_procesamiento' => self::PROCESAMIENTO_COMPLETADO,
            'fecha_procesamiento' => now(),
            'error_procesamiento' => null,
            'metadatos_archivo' => array_merge($this->getMetadatosArchivo(), $metadatos)
        ]);
    }

    /**
     * Marcar como error en procesamiento
     */
    public function marcarErrorProcesamiento(string $error): void
    {
        $this->update([
            'estado_procesamiento' => self::PROCESAMIENTO_ERROR,
            'error_procesamiento' => $error
        ]);
    }

    /**
     * Iniciar procesamiento
     */
    public function iniciarProcesamiento(): void
    {
        $this->update([
            'estado_procesamiento' => self::PROCESAMIENTO_PROCESANDO,
            'error_procesamiento' => null
        ]);
    }
}
