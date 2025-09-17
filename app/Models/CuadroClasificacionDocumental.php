<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para Cuadro de Clasificación Documental (CCD)
 * 
 * Basado en REQ-CL-006: Representación de organización de expedientes y documentos
 * REQ-CL-007: Múltiples niveles para el esquema de CCD
 * REQ-CL-008: Control exclusivo por rol administrador
 */
class CuadroClasificacionDocumental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuadros_clasificacion_documental';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'entidad',
        'dependencia',
        'nivel',
        'padre_id',
        'orden_jerarquico',
        // 'estado', // Temporalmente comentado hasta agregar la columna a la BD
        'vocabularios_controlados',
        'notas',
        'alcance',
        'razon_reubicacion',
        'fecha_reubicacion',
        'reubicado_por',
        'created_by',
        'updated_by',
        'activo'
    ];

    protected $casts = [
        'vocabularios_controlados' => 'array',
        'activo' => 'boolean',
        'fecha_reubicacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Niveles jerárquicos del CCD
    const NIVEL_FONDO = 1;
    const NIVEL_SECCION = 2;
    const NIVEL_SUBSECCION = 3;
    const NIVEL_SERIE = 4;
    const NIVEL_SUBSERIE = 5;

    // Estados del CCD
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_INACTIVO = 'inactivo';
    const ESTADO_HISTORICO = 'historico';

    protected static function boot()
    {
        parent::boot();
        
        // Generar código automático si no se proporciona
        static::creating(function ($ccd) {
            if (empty($ccd->codigo)) {
                $ccd->codigo = $ccd->generarCodigo();
            }
        });
    }

    /**
     * Relación con el padre (autoreferencial)
     * REQ-CL-007: Múltiples niveles jerárquicos
     */
    public function padre()
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    /**
     * Relación con los hijos (autoreferencial)
     */
    public function hijos()
    {
        return $this->hasMany(self::class, 'padre_id')->orderBy('orden_jerarquico');
    }

    /**
     * Relación recursiva para obtener todos los descendientes
     */
    public function descendientes()
    {
        return $this->hijos()->with('descendientes');
    }

    /**
     * Relación con el usuario creador
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');  // Corregido: usar nombre real de la columna
    }

    /**
     * Relación con el usuario modificador
     */
    public function modificador()
    {
        return $this->belongsTo(User::class, 'updated_by');  // Corregido: usar nombre real de la columna
    }

    /**
     * Relación con series documentales
     */
    public function series()
    {
        return $this->hasMany(SerieDocumental::class, 'ccd_id');
    }

    /**
     * Relación con expedientes a través de series
     */
    public function expedientes()
    {
        return $this->hasManyThrough(Expediente::class, SerieDocumental::class, 'ccd_id', 'serie_id');
    }

    /**
     * Relación con documentos a través de expedientes
     */
    public function documentos()
    {
        return $this->hasManyThrough(
            Documento::class, 
            Expediente::class, 
            'serie_id', 
            'expediente_id',
            'id',
            'id'
        )->join('series_documentales', 'expedientes.serie_id', '=', 'series_documentales.id')
         ->where('series_documentales.ccd_id', $this->id);
    }

    /**
     * Relación con pistas de auditoría
     */
    public function auditoria()
    {
        return $this->morphMany(PistaAuditoria::class, 'entidad');
    }

    /**
     * Scope para elementos raíz (sin padre)
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('padre_id');
    }

    /**
     * Scope para elementos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope por nivel jerárquico
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Generar código automático basado en jerarquía
     */
    public function generarCodigo()
    {
        $prefijo = '';
        
        switch ($this->nivel) {
            case self::NIVEL_FONDO:
                $prefijo = 'F';
                break;
            case self::NIVEL_SECCION:
                $prefijo = 'S';
                break;
            case self::NIVEL_SUBSECCION:
                $prefijo = 'SS';
                break;
            case self::NIVEL_SERIE:
                $prefijo = 'SE';
                break;
            case self::NIVEL_SUBSERIE:
                $prefijo = 'SU';
                break;
        }
        
        // Obtener código del padre si existe
        $codigoPadre = $this->padre ? $this->padre->codigo . '.' : '';
        
        // Generar número secuencial
        $ultimoHermano = static::where('padre_id', $this->padre_id)
                                ->where('nivel', $this->nivel)
                                ->orderBy('orden_jerarquico', 'desc')
                                ->first();
        
        $secuencial = $ultimoHermano ? ($ultimoHermano->orden_jerarquico + 1) : 1;
        
        return $codigoPadre . $prefijo . str_pad($secuencial, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener la ruta completa desde la raíz
     */
    public function getRutaCompleta()
    {
        $ruta = [$this->nombre];
        $actual = $this;
        
        while ($actual->padre) {
            $actual = $actual->padre;
            array_unshift($ruta, $actual->nombre);
        }
        
        return implode(' > ', $ruta);
    }

    /**
     * Obtener el código completo jerárquico
     */
    public function getCodigoCompleto()
    {
        $codigos = [$this->codigo];
        $actual = $this;
        
        while ($actual->padre) {
            $actual = $actual->padre;
            array_unshift($codigos, $actual->codigo);
        }
        
        return implode('.', $codigos);
    }

    /**
     * REQ-CL-009: Asignar vocabulario controlado normalizado
     */
    public function asignarVocabularioControlado($vocabulario)
    {
        $this->vocabulario_controlado = array_merge(
            $this->vocabulario_controlado ?? [],
            $vocabulario
        );
        $this->save();
        
        // Registrar en auditoría
        $this->registrarEnAuditoria('vocabulario_asignado', [
            'vocabulario_agregado' => $vocabulario
        ]);
    }

    /**
     * REQ-CL-021: Reubicación manteniendo metadatos y atributos
     */
    public function reubicar($nuevoPadreId, $motivo = null)
    {
        $padreAnterior = $this->padre_id;
        $rutaAnterior = $this->getRutaCompleta();
        
        $this->padre_id = $nuevoPadreId;
        $this->nivel = $nuevoPadreId ? (self::find($nuevoPadreId)->nivel + 1) : self::NIVEL_FONDO;
        $this->codigo = $this->generarCodigo();
        $this->save();
        
        // REQ-CL-022: Registrar en pista de auditoría
        $this->registrarEnAuditoria('reubicacion', [
            'padre_anterior' => $padreAnterior,
            'padre_nuevo' => $nuevoPadreId,
            'ruta_anterior' => $rutaAnterior,
            'ruta_nueva' => $this->getRutaCompleta(),
            'motivo' => $motivo
        ]);
        
        // Actualizar códigos de todos los descendientes
        $this->actualizarCodigosDescendientes();
    }

    /**
     * Actualizar códigos de descendientes tras reubicación
     */
    private function actualizarCodigosDescendientes()
    {
        foreach ($this->hijos as $hijo) {
            $hijo->codigo = $hijo->generarCodigo();
            $hijo->save();
            $hijo->actualizarCodigosDescendientes();
        }
    }

    /**
     * REQ-CL-008: Verificar si usuario tiene permisos de administrador
     */
    public function puedeAdministrar($usuario)
    {
        return $usuario->hasRole('administrador_ccd') || 
               $usuario->hasRole('super_administrador');
    }

    /**
     * Validar estructura antes de guardar
     */
    public function validarEstructura()
    {
        $errores = [];
        
        // Validar nivel jerárquico
        if ($this->padre_id) {
            $padre = $this->padre;
            if ($this->nivel <= $padre->nivel) {
                $errores[] = 'El nivel debe ser mayor al del padre';
            }
        }
        
        // Validar código único en el mismo nivel
        $existente = static::where('codigo', $this->codigo)
                           ->where('padre_id', $this->padre_id)
                           ->where('id', '!=', $this->id)
                           ->first();
        
        if ($existente) {
            $errores[] = 'Ya existe un elemento con el mismo código en este nivel';
        }
        
        return $errores;
    }

    /**
     * Exportar estructura CCD a XML
     */
    public function exportarXML($incluirDescendientes = true)
    {
        $xml = new \SimpleXMLElement('<cuadro_clasificacion/>');
        $this->agregarNodoXML($xml, $incluirDescendientes);
        
        return $xml->asXML();
    }

    /**
     * Agregar nodo al XML
     */
    private function agregarNodoXML($xml, $incluirDescendientes = true)
    {
        $nodo = $xml->addChild('elemento');
        $nodo->addChild('id', $this->id);
        $nodo->addChild('codigo', htmlspecialchars($this->codigo));
        $nodo->addChild('nombre', htmlspecialchars($this->nombre));
        $nodo->addChild('descripcion', htmlspecialchars($this->descripcion));
        $nodo->addChild('nivel', $this->nivel);
        $nodo->addChild('orden_jerarquico', $this->orden_jerarquico);
        
        if ($incluirDescendientes && $this->hijos->count() > 0) {
            $hijosXml = $nodo->addChild('hijos');
            foreach ($this->hijos as $hijo) {
                $hijo->agregarNodoXML($hijosXml, $incluirDescendientes);
            }
        }
    }

    /**
     * Registrar acción en pista de auditoría
     */
    private function registrarEnAuditoria($accion, $detalles = [])
    {
        PistaAuditoria::create([
            'entidad_type' => self::class,
            'entidad_id' => $this->id,
            'usuario_id' => auth()->id(),
            'accion' => $accion,
            'detalles' => $detalles,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
