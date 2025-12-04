<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmaDigital extends Model
{
    use HasFactory;

    protected $table = 'firmas_digitales';

    protected $fillable = [
        'documento_id',
        'usuario_id',
        'certificado_id',
        'solicitud_firma_id',
        'hash_documento',
        'hash_firma',
        'certificado_info',
        'motivo_firma',
        'fecha_firma',
        'algoritmo_hash',
        'tipo_firma',
        'nivel_firma',
        'ruta_archivo_firmado',
        'metadatos_firma',
        'estado',
        'datos_certificado',
        'politica_firma',
        'resultado_validacion',
        'fecha_validacion',
        'valida',
        'sello_tiempo',
        'cadena_certificacion',
        'metadata'
    ];

    protected $casts = [
        'certificado_info' => 'array',
        'metadata' => 'array',
        'metadatos_firma' => 'array',
        'datos_certificado' => 'array',
        'politica_firma' => 'array',
        'resultado_validacion' => 'array',
        'fecha_firma' => 'datetime',
        'fecha_validacion' => 'datetime',
        'valida' => 'boolean',
        'sello_tiempo' => 'array',
        'cadena_certificacion' => 'array'
    ];

    protected $dates = [
        'fecha_firma',
        'created_at',
        'updated_at'
    ];

    // Tipos de firma
    const TIPO_ELECTRONICA = 'electronica';
    const TIPO_AVANZADA = 'avanzada';
    const TIPO_CUALIFICADA = 'cualificada';

    // Algoritmos de hash soportados
    const HASH_SHA256 = 'SHA-256';
    const HASH_SHA384 = 'SHA-384';
    const HASH_SHA512 = 'SHA-512';

    /**
     * Relación con el documento firmado
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    /**
     * Relación con el usuario que firmó
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con el certificado digital utilizado
     */
    public function certificado(): BelongsTo
    {
        return $this->belongsTo(CertificadoDigital::class, 'certificado_id');
    }

    /**
     * Relación con la solicitud de firma
     */
    public function solicitudFirma(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirma::class, 'solicitud_firma_id');
    }

    /**
     * Scope para firmas válidas
     */
    public function scopeValidas($query)
    {
        return $query->where('valida', true);
    }

    /**
     * Scope para firmas de un usuario específico
     */
    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para firmas de un período específico
     */
    public function scopeEnPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_firma', [$fechaInicio, $fechaFin]);
    }

    /**
     * Accessor para obtener el nombre del usuario que firmó
     */
    public function getNombreUsuarioAttribute()
    {
        return $this->usuario ? $this->usuario->name : 'Usuario eliminado';
    }

    /**
     * Scope para firmas con certificado PKI
     */
    public function scopeConCertificado($query)
    {
        return $query->whereNotNull('certificado_id');
    }

    /**
     * Scope para firmas de solicitud específica
     */
    public function scopeDeSolicitud($query, $solicitudId)
    {
        return $query->where('solicitud_firma_id', $solicitudId);
    }

    /**
     * Scope para firmas por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_firma', $tipo);
    }

    /**
     * Accessor para verificar si la firma está vigente
     */
    public function getVigenteAttribute()
    {
        if (!$this->valida) {
            return false;
        }

        // Verificar vigencia del certificado si existe
        if ($this->certificado && !$this->certificado->vigente) {
            return false;
        }

        $diasValidez = config('archivey.firma_validez_dias', 365);
        return $this->fecha_firma->addDays($diasValidez)->isFuture();
    }

    /**
     * Verificar si tiene sello de tiempo válido
     */
    public function getTieneSelloTiempoAttribute(): bool
    {
        return !empty($this->sello_tiempo) && 
               isset($this->sello_tiempo['timestamp']) && 
               isset($this->sello_tiempo['authority']);
    }

    /**
     * Obtener nivel de seguridad de la firma
     */
    public function getNivelSeguridadAttribute(): string
    {
        if ($this->tipo_firma === self::TIPO_CUALIFICADA && $this->certificado && $this->tiene_sello_tiempo) {
            return 'muy_alto';
        }

        if ($this->tipo_firma === self::TIPO_AVANZADA && $this->certificado) {
            return 'alto';
        }

        if ($this->certificado) {
            return 'medio';
        }

        return 'basico';
    }

    /**
     * Verificar integridad completa de la firma
     */
    public function verificarIntegridad(): array
    {
        $resultado = [
            'valida' => false,
            'errores' => [],
            'advertencias' => [],
            'detalles' => []
        ];

        try {
            // Verificar que la firma base sea válida
            if (!$this->valida) {
                $resultado['errores'][] = 'Firma marcada como inválida en el sistema';
                return $resultado;
            }

            // Verificar certificado si exists
            if ($this->certificado_id) {
                if (!$this->certificado) {
                    $resultado['errores'][] = 'Certificado digital no encontrado';
                    return $resultado;
                }

                $validacionCert = $this->certificado->verificarValidez();
                if (!$validacionCert['valido']) {
                    $resultado['errores'] = array_merge($resultado['errores'], $validacionCert['errores']);
                    return $resultado;
                }

                if (!empty($validacionCert['advertencias'])) {
                    $resultado['advertencias'] = array_merge($resultado['advertencias'], $validacionCert['advertencias']);
                }
            }

            // Verificar hash del documento
            if ($this->documento && $this->documento->existe()) {
                $hashActual = $this->documento->calcularHash();
                if ($hashActual !== $this->hash_documento) {
                    $resultado['errores'][] = 'El documento ha sido modificado después de la firma';
                    return $resultado;
                }
            } else {
                $resultado['errores'][] = 'Documento original no disponible para verificación';
                return $resultado;
            }

            // Verificar sello de tiempo si exists
            if ($this->tiene_sello_tiempo) {
                $validacionSello = $this->verificarSelloTiempo();
                if (!$validacionSello['valido']) {
                    $resultado['advertencias'][] = 'Sello de tiempo no válido: ' . implode(', ', $validacionSello['errores']);
                }
            }

            // Verificar cadena de certificación
            if (!empty($this->cadena_certificacion)) {
                $validacionCadena = $this->verificarCadenaCertificacion();
                if (!$validacionCadena['valida']) {
                    $resultado['advertencias'][] = 'Cadena de certificación no válida';
                }
            }

            $resultado['valida'] = true;
            $resultado['detalles'] = [
                'firmado_por' => $this->usuario->name,
                'fecha_firma' => $this->fecha_firma,
                'algoritmo' => $this->algoritmo_hash,
                'tipo_firma' => $this->tipo_firma,
                'nivel_seguridad' => $this->nivel_seguridad,
                'certificado' => $this->certificado ? [
                    'numero_serie' => $this->certificado->numero_serie,
                    'emisor' => $this->certificado->emisor,
                    'vigente' => $this->certificado->vigente
                ] : null,
                'sello_tiempo' => $this->tiene_sello_tiempo,
                'verificado_en' => now()
            ];

        } catch (\Exception $e) {
            $resultado['errores'][] = 'Error durante la verificación: ' . $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Verificar sello de tiempo
     */
    private function verificarSelloTiempo(): array
    {
        $resultado = ['valido' => false, 'errores' => []];

        if (!$this->tiene_sello_tiempo) {
            $resultado['errores'][] = 'No tiene sello de tiempo';
            return $resultado;
        }

        // Implementar verificación de sello de tiempo
        // Por ahora, verificación básica
        $sello = $this->sello_tiempo;
        
        if (!isset($sello['timestamp']) || !isset($sello['authority'])) {
            $resultado['errores'][] = 'Sello de tiempo incompleto';
            return $resultado;
        }

        // Verificar que el timestamp sea coherente con la fecha de firma
        $timestampSello = Carbon::createFromTimestamp($sello['timestamp']);
        $diferencia = abs($this->fecha_firma->diffInMinutes($timestampSello));

        if ($diferencia > 5) { // Permitir 5 minutos de diferencia
            $resultado['errores'][] = 'Timestamp del sello no coherente con fecha de firma';
            return $resultado;
        }

        $resultado['valido'] = true;
        return $resultado;
    }

    /**
     * Verificar cadena de certificación
     */
    private function verificarCadenaCertificacion(): array
    {
        $resultado = ['valida' => false, 'errores' => []];

        if (empty($this->cadena_certificacion)) {
            $resultado['errores'][] = 'No hay cadena de certificación';
            return $resultado;
        }

        // Implementar verificación de cadena PKI
        // Por ahora, verificación básica
        $cadena = $this->cadena_certificacion;
        
        if (!is_array($cadena) || empty($cadena)) {
            $resultado['errores'][] = 'Cadena de certificación inválida';
            return $resultado;
        }

        // Verificar que cada certificado en la cadena sea válido
        foreach ($cadena as $index => $certData) {
            if (!isset($certData['certificado']) || !isset($certData['emisor'])) {
                $resultado['errores'][] = "Certificado en posición {$index} incompleto";
                return $resultado;
            }
        }

        $resultado['valida'] = true;
        return $resultado;
    }

    /**
     * Obtener resumen de verificación para mostrar al usuario
     */
    public function getResumenVerificacionAttribute(): array
    {
        $verificacion = $this->verificarIntegridad();
        
        return [
            'estado' => $verificacion['valida'] ? 'valida' : 'invalida',
            'nivel_confianza' => $this->nivel_seguridad,
            'firmante' => $this->usuario->name,
            'fecha' => $this->fecha_firma->format('d/m/Y H:i:s'),
            'certificado_vigente' => $this->certificado ? $this->certificado->vigente : false,
            'documento_integro' => empty($verificacion['errores']),
            'sello_tiempo' => $this->tiene_sello_tiempo,
            'errores_criticos' => count($verificacion['errores']),
            'advertencias' => count($verificacion['advertencias'])
        ];
    }

    /**
     * Invalidar la firma
     */
    public function invalidar(string $motivo = 'No especificado'): bool
    {
        $this->update([
            'valida' => false,
            'metadata' => array_merge($this->metadata ?? [], [
                'invalidada_en' => now(),
                'motivo_invalidacion' => $motivo
            ])
        ]);

        // Registrar en auditoría
        \App\Models\PistaAuditoria::create([
            'user_id' => auth()->id(),
            'tabla_afectada' => 'firmas_digitales',
            'registro_id' => $this->id,
            'accion' => 'firma_invalidada',
            'descripcion' => "Firma digital invalidada: {$motivo}",
            'datos_anteriores' => ['valida' => true],
            'datos_nuevos' => ['valida' => false, 'motivo' => $motivo],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }
}
