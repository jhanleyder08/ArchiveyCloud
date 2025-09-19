<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Tabla de Retención Documental (TRD)
 * 
 * Basado en REQ-CL-001: Creación, importación, parametrización, automatización,
 * administración y versionamiento de las Tablas de Retención Documental
 */
class TablaRetencionDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tablas_retencion_documental';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'entidad',
        'dependencia',
        'version',
        'fecha_aprobacion',
        'fecha_vigencia_inicio',
        'fecha_vigencia_fin',
        'estado',
        'vigente',
        'observaciones_generales',
        'metadatos_adicionales',
        'created_by',
        'updated_by',
        'aprobado_por'
    ];

    protected $casts = [
        'fecha_aprobacion' => 'date',
        'fecha_vigencia_inicio' => 'date',
        'fecha_vigencia_fin' => 'date',
        'vigente' => 'boolean',
        'metadatos_adicionales' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Estados posibles de la TRD
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_REVISION = 'revision';
    const ESTADO_APROBADA = 'aprobada';    
    const ESTADO_VIGENTE = 'vigente';
    const ESTADO_HISTORICA = 'historica';

    protected static function boot()
    {
        parent::boot();
        
        // REQ-CL-002: Generar identificador único cuando se crea
        static::creating(function ($trd) {
            if (empty($trd->identificador_unico)) {
                $trd->identificador_unico = 'TRD-' . now()->format('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relación con el usuario que creó la TRD
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que modificó la TRD
     */
    public function modificador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relación con el usuario que aprobó la TRD
     */
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Relación con las series documentales asociadas
     */
    public function series()
    {
        return $this->hasMany(SerieDocumental::class, 'tabla_retencion_id');
    }

    /**
     * Relación con subseries documentales
     */
    public function subseries()
    {
        return $this->hasManyThrough(SubserieDocumental::class, SerieDocumental::class, 'tabla_retencion_id', 'serie_documental_id');
    }

    /**
     * Relación con expedientes
     */
    public function expedientes()
    {
        return $this->hasManyThrough(Expediente::class, SerieDocumental::class, 'tabla_retencion_id', 'serie_documental_id');
    }

    /**
     * Relación con las pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para obtener solo TRD vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where('estado', 'vigente');
    }

    /**
     * Scope para obtener TRD por versión
     */
    public function scopeVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    /**
     * REQ-CL-004: Obtener diferentes versiones de la TRD
     */
    public function versiones()
    {
        return static::where('codigo', $this->codigo)
                    ->orderBy('version', 'desc')
                    ->get();
    }

    /**
     * REQ-CL-003: Verificar si hay documentos asociados que mantienen criterios
     */
    public function tieneDocumentosAsociados()
    {
        return $this->expedientes()
                   ->join('documentos', 'expedientes.id', '=', 'documentos.expediente_id')
                   ->exists();
    }

    /**
     * REQ-CL-005: Validar información antes de guardar
     */
    public function validarInformacion()
    {
        $errores = [];
        
        // Validar información similar o igual
        $similar = static::where('nombre', 'LIKE', '%' . $this->nombre . '%')
                         ->where('id', '!=', $this->id)
                         ->first();
        
        if ($similar) {
            $errores[] = 'Existe una TRD similar: ' . $similar->nombre;
        }
        
        // Validar campos obligatorios
        if (empty($this->codigo)) {
            $errores[] = 'El código es obligatorio';
        }
        
        if (empty($this->nombre)) {
            $errores[] = 'El nombre es obligatorio';
        }
        
        return $errores;
    }

    /**
     * REQ-CL-006: Exportar TRD con metadatos
     */
    public function exportar($formato = 'json', $incluirMetadatos = true)
    {
        $datos = $this->toArray();
        
        if ($incluirMetadatos) {
            $datos['series'] = $this->series()->with('subseries')->get();
            $datos['metadatos_completos'] = $this->metadatos_asociados;
            
            if ($incluirMetadatos) {
                $datos['pistas_auditoria'] = $this->auditoria()->get();
            }
        }
        
        switch ($formato) {
            case 'xml':
                return $this->arrayToXml($datos);
            case 'csv':
                return $this->arrayToCsv($datos);
            default:
                return json_encode($datos, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Convertir array a XML para exportación
     */
    private function arrayToXml($data, $rootElement = 'TRD', $xml = null)
    {
        if ($xml === null) {
            $xml = new \SimpleXMLElement('<' . $rootElement . '/>');
        }
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->arrayToXml($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Convertir array a CSV para exportación
     */
    private function arrayToCsv($data)
    {
        $output = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($output, array_keys($data));
        
        // Data
        fputcsv($output, array_values($data));
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
