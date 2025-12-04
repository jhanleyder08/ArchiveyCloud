<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class CertificadoDigital extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'certificados_digitales';

    protected $fillable = [
        'usuario_id',
        'nombre_certificado',
        'numero_serie',
        'emisor',
        'sujeto',
        'algoritmo_firma',
        'longitud_clave',
        'huella_digital',
        'certificado_x509',
        'clave_publica',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'tipo_certificado',
        'uso_permitido',
        'revocado_en',
        'razon_revocacion',
        'metadata_pki'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'revocado_en' => 'datetime',
        'uso_permitido' => 'array',
        'metadata_pki' => 'array'
    ];

    protected $dates = [
        'fecha_emision',
        'fecha_vencimiento',
        'revocado_en',
        'deleted_at'
    ];

    // Estados del certificado
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_VENCIDO = 'vencido';
    const ESTADO_REVOCADO = 'revocado';
    const ESTADO_SUSPENDIDO = 'suspendido';

    // Tipos de certificado
    const TIPO_USUARIO = 'usuario';
    const TIPO_SERVIDOR = 'servidor';
    const TIPO_CA = 'autoridad_certificadora';
    const TIPO_SELLO_TIEMPO = 'sello_tiempo';

    // Usos permitidos
    const USO_FIRMA_DIGITAL = 'firma_digital';
    const USO_AUTENTICACION = 'autenticacion';
    const USO_CIFRADO = 'cifrado';
    const USO_SELLO_TIEMPO = 'sello_tiempo';

    /**
     * Relación con el usuario propietario del certificado
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con las firmas realizadas con este certificado
     */
    public function firmas(): HasMany
    {
        return $this->hasMany(FirmaDigital::class, 'certificado_id');
    }

    /**
     * Scope para certificados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO)
                    ->where('fecha_vencimiento', '>', now());
    }

    /**
     * Scope para certificados vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_vencimiento', '<=', now())
                    ->where('estado', '!=', self::ESTADO_REVOCADO);
    }

    /**
     * Scope para certificados próximos a vencer
     */
    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->where('estado', self::ESTADO_ACTIVO)
                    ->whereBetween('fecha_vencimiento', [
                        now(),
                        now()->addDays($dias)
                    ]);
    }

    /**
     * Scope para certificados de un tipo específico
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_certificado', $tipo);
    }

    /**
     * Scope para certificados con uso específico
     */
    public function scopeConUso($query, $uso)
    {
        return $query->whereJsonContains('uso_permitido', $uso);
    }

    /**
     * Verificar si el certificado está vigente
     */
    public function getVigenteAttribute(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO && 
               $this->fecha_vencimiento > now() && 
               !$this->revocado_en;
    }

    /**
     * Obtener días restantes hasta vencimiento
     */
    public function getDiasRestantesAttribute(): int
    {
        if ($this->fecha_vencimiento <= now()) {
            return 0;
        }
        
        return now()->diffInDays($this->fecha_vencimiento);
    }

    /**
     * Verificar si está próximo a vencer
     */
    public function getProximoAVencerAttribute(): bool
    {
        return $this->vigente && $this->dias_restantes <= 30;
    }

    /**
     * Obtener información del sujeto del certificado
     */
    public function getInfoSujetoAttribute(): array
    {
        if (!$this->sujeto) {
            return [];
        }

        // Parsear el Distinguished Name (DN) del certificado
        $partes = explode(',', $this->sujeto);
        $info = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (strpos($parte, '=') !== false) {
                [$clave, $valor] = explode('=', $parte, 2);
                $info[trim($clave)] = trim($valor);
            }
        }

        return $info;
    }

    /**
     * Obtener información del emisor del certificado
     */
    public function getInfoEmisorAttribute(): array
    {
        if (!$this->emisor) {
            return [];
        }

        // Parsear el Distinguished Name (DN) del emisor
        $partes = explode(',', $this->emisor);
        $info = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (strpos($parte, '=') !== false) {
                [$clave, $valor] = explode('=', $parte, 2);
                $info[trim($clave)] = trim($valor);
            }
        }

        return $info;
    }

    /**
     * Revocar el certificado
     */
    public function revocar(string $razon = 'No especificada'): bool
    {
        $this->update([
            'estado' => self::ESTADO_REVOCADO,
            'revocado_en' => now(),
            'razon_revocacion' => $razon
        ]);

        // Marcar todas las firmas con este certificado como inválidas
        $this->firmas()->update(['valida' => false]);

        return true;
    }

    /**
     * Verificar la validez del certificado
     */
    public function verificarValidez(): array
    {
        $resultado = [
            'valido' => false,
            'errores' => [],
            'advertencias' => []
        ];

        // Verificar estado
        if ($this->estado !== self::ESTADO_ACTIVO) {
            $resultado['errores'][] = "Certificado en estado: {$this->estado}";
            return $resultado;
        }

        // Verificar revocación
        if ($this->revocado_en) {
            $resultado['errores'][] = "Certificado revocado el: {$this->revocado_en->format('d/m/Y H:i')}";
            return $resultado;
        }

        // Verificar vencimiento
        if ($this->fecha_vencimiento <= now()) {
            $resultado['errores'][] = "Certificado vencido el: {$this->fecha_vencimiento->format('d/m/Y H:i')}";
            return $resultado;
        }

        // Advertencia por próximo vencimiento
        if ($this->proximo_a_vencer) {
            $resultado['advertencias'][] = "Certificado vence en {$this->dias_restantes} días";
        }

        $resultado['valido'] = true;
        return $resultado;
    }

    /**
     * Generar huella digital del certificado
     */
    public function generarHuellaDigital(): string
    {
        if (!$this->certificado_x509) {
            throw new \Exception('No hay certificado X.509 disponible');
        }

        return hash('sha256', base64_decode($this->certificado_x509));
    }

    /**
     * Validar firma con este certificado
     */
    public function validarFirma(string $datos, string $firma): bool
    {
        if (!$this->vigente) {
            return false;
        }

        if (!$this->clave_publica) {
            return false;
        }

        try {
            $clavePublica = openssl_pkey_get_public($this->clave_publica);
            if (!$clavePublica) {
                return false;
            }

            $resultado = openssl_verify(
                $datos,
                base64_decode($firma),
                $clavePublica,
                OPENSSL_ALGO_SHA256
            );

            return $resultado === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener estadísticas de uso del certificado
     */
    public function obtenerEstadisticasUso(): array
    {
        return [
            'total_firmas' => $this->firmas()->count(),
            'firmas_validas' => $this->firmas()->where('valida', true)->count(),
            'primera_firma' => $this->firmas()->orderBy('fecha_firma')->first()?->fecha_firma,
            'ultima_firma' => $this->firmas()->orderBy('fecha_firma', 'desc')->first()?->fecha_firma,
            'dias_desde_emision' => $this->fecha_emision->diffInDays(now()),
            'porcentaje_vida_util' => $this->fecha_emision->diffInDays(now()) / 
                                    $this->fecha_emision->diffInDays($this->fecha_vencimiento) * 100
        ];
    }
}
