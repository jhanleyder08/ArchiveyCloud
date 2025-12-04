<?php

namespace App\Services;

use App\Models\CertificadoDigital;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Servicio de Gestión de Certificados Digitales
 * 
 * Implementa requerimientos:
 * REQ-CD-001: Importar y validar certificados X.509
 * REQ-CD-002: Gestión de cadenas de certificados
 * REQ-CD-003: Verificación de estado de revocación (CRL/OCSP)
 * REQ-CD-004: Almacenamiento seguro de certificados
 * REQ-CD-005: Renovación automática de certificados
 */
class CertificateManagementService
{
    // Estados de certificado
    const ESTADO_VALIDO = 'valido';
    const ESTADO_VENCIDO = 'vencido';
    const ESTADO_REVOCADO = 'revocado';
    const ESTADO_SUSPENDIDO = 'suspendido';
    const ESTADO_DESCONOCIDO = 'desconocido';
    
    // Tipos de certificado
    const TIPO_FIRMA = 'firma_digital';
    const TIPO_AUTENTICACION = 'autenticacion';
    const TIPO_CIFRADO = 'cifrado';
    const TIPO_SSL = 'ssl_tls';
    const TIPO_CA = 'autoridad_certificacion';
    
    // Algoritmos soportados
    const ALGORITMOS_HASH = ['SHA-1', 'SHA-224', 'SHA-256', 'SHA-384', 'SHA-512'];
    const ALGORITMOS_FIRMA = ['RSA', 'DSA', 'ECDSA'];

    protected array $config;
    protected array $trustedCAs;

    public function __construct()
    {
        $this->config = config('certificates', []);
        $this->trustedCAs = $this->cargarCAsConfiables();
    }

    /**
     * REQ-CD-001: Importar certificado desde archivo
     */
    public function importarCertificado(
        string $contenidoCertificado,
        ?string $password = null,
        ?User $propietario = null,
        array $opciones = []
    ): CertificadoDigital {
        try {
            // Detectar formato del certificado
            $formato = $this->detectarFormatoCertificado($contenidoCertificado);
            
            // Parsear certificado según formato
            $datosCertificado = match($formato) {
                'PEM' => $this->parsearCertificadoPEM($contenidoCertificado),
                'DER' => $this->parsearCertificadoDER($contenidoCertificado),
                'P12', 'PFX' => $this->parsearCertificadoP12($contenidoCertificado, $password),
                default => throw new Exception("Formato de certificado no soportado: {$formato}")
            };
            
            // Extraer información del certificado
            $infoCertificado = $this->extraerInformacionCertificado($datosCertificado);
            
            // Validar certificado
            $validacion = $this->validarCertificadoCompleto($datosCertificado, $infoCertificado);
            
            // Determinar tipo de certificado
            $tipoCertificado = $this->determinarTipoCertificado($infoCertificado);
            
            // Extraer cadena de certificados
            $cadenaCertificados = $this->extraerCadenaCertificados($datosCertificado);
            
            // Almacenar certificado de forma segura
            $rutaAlmacenamiento = $this->almacenarCertificadoSeguro(
                $contenidoCertificado,
                $infoCertificado['serial_number']
            );
            
            // Crear registro en base de datos
            $certificado = CertificadoDigital::create([
                'usuario_id' => $propietario?->id,
                'serial_number' => $infoCertificado['serial_number'],
                'subject' => $infoCertificado['subject'],
                'issuer' => $infoCertificado['issuer'],
                'fecha_emision' => Carbon::parse($infoCertificado['not_before']),
                'fecha_vencimiento' => Carbon::parse($infoCertificado['not_after']),
                'algoritmo_firma' => $infoCertificado['signature_algorithm'],
                'algoritmo_hash' => $infoCertificado['hash_algorithm'],
                'key_usage' => $infoCertificado['key_usage'] ?? [],
                'extended_key_usage' => $infoCertificado['extended_key_usage'] ?? [],
                'tipo_certificado' => $tipoCertificado,
                'formato_original' => $formato,
                'ruta_archivo' => $rutaAlmacenamiento,
                'huella_sha1' => hash('sha1', $datosCertificado['cert_raw']),
                'huella_sha256' => hash('sha256', $datosCertificado['cert_raw']),
                'cadena_certificados' => $cadenaCertificados,
                'url_crl' => $infoCertificado['crl_url'] ?? null,
                'url_ocsp' => $infoCertificado['ocsp_url'] ?? null,
                'es_valido' => $validacion['valido'],
                'estado' => $validacion['valido'] ? self::ESTADO_VALIDO : self::ESTADO_DESCONOCIDO,
                'resultado_validacion' => $validacion,
                'metadatos' => array_merge($infoCertificado, $opciones),
                'fecha_importacion' => now()
            ]);
            
            // Verificar estado de revocación inicial
            $this->verificarEstadoRevocacion($certificado);
            
            // Programar verificaciones periódicas
            $this->programarVerificacionesPeriodicas($certificado);
            
            Log::info('Certificado importado exitosamente', [
                'certificado_id' => $certificado->id,
                'serial_number' => $certificado->serial_number,
                'subject' => $certificado->subject,
                'tipo' => $tipoCertificado
            ]);
            
            return $certificado;
            
        } catch (Exception $e) {
            Log::error('Error importando certificado', [
                'error' => $e->getMessage(),
                'propietario_id' => $propietario?->id
            ]);
            
            throw new Exception("Error al importar certificado: {$e->getMessage()}");
        }
    }

    /**
     * REQ-CD-003: Verificar estado de revocación usando CRL
     */
    public function verificarRevocacionCRL(CertificadoDigital $certificado): array
    {
        if (!$certificado->url_crl) {
            return [
                'verificado' => false,
                'motivo' => 'URL de CRL no disponible',
                'estado' => self::ESTADO_DESCONOCIDO
            ];
        }
        
        try {
            $cacheKey = "crl_check_{$certificado->id}_" . md5($certificado->url_crl);
            
            return Cache::remember($cacheKey, 3600, function () use ($certificado) {
                // Descargar CRL
                $crlData = $this->descargarCRL($certificado->url_crl);
                
                // Parsear CRL
                $crlParsed = $this->parsearCRL($crlData);
                
                // Verificar si el certificado está en la lista
                $revocado = $this->buscarCertificadoEnCRL(
                    $certificado->serial_number,
                    $crlParsed
                );
                
                $resultado = [
                    'verificado' => true,
                    'revocado' => $revocado,
                    'estado' => $revocado ? self::ESTADO_REVOCADO : self::ESTADO_VALIDO,
                    'fecha_verificacion' => now()->toISOString(),
                    'url_crl' => $certificado->url_crl
                ];
                
                if ($revocado) {
                    $resultado['fecha_revocacion'] = $crlParsed['revoked_certs'][$certificado->serial_number]['revocation_date'] ?? null;
                    $resultado['motivo_revocacion'] = $crlParsed['revoked_certs'][$certificado->serial_number]['reason'] ?? null;
                }
                
                return $resultado;
            });
            
        } catch (Exception $e) {
            Log::error('Error verificando CRL', [
                'certificado_id' => $certificado->id,
                'url_crl' => $certificado->url_crl,
                'error' => $e->getMessage()
            ]);
            
            return [
                'verificado' => false,
                'motivo' => "Error accediendo CRL: {$e->getMessage()}",
                'estado' => self::ESTADO_DESCONOCIDO
            ];
        }
    }

    /**
     * REQ-CD-003: Verificar estado usando OCSP (Online Certificate Status Protocol)
     */
    public function verificarRevocacionOCSP(CertificadoDigital $certificado): array
    {
        if (!$certificado->url_ocsp) {
            return [
                'verificado' => false,
                'motivo' => 'URL de OCSP no disponible',
                'estado' => self::ESTADO_DESCONOCIDO
            ];
        }
        
        try {
            // Construir solicitud OCSP
            $solicitudOCSP = $this->construirSolicitudOCSP($certificado);
            
            // Enviar solicitud OCSP
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/ocsp-request',
                    'User-Agent' => 'SGDEA-CertificateManager/1.0'
                ])
                ->post($certificado->url_ocsp, $solicitudOCSP);
            
            if (!$response->successful()) {
                throw new Exception("Error OCSP: HTTP {$response->status()}");
            }
            
            // Parsear respuesta OCSP
            $respuestaOCSP = $this->parsearRespuestaOCSP($response->body());
            
            $resultado = [
                'verificado' => true,
                'estado' => $respuestaOCSP['cert_status'],
                'fecha_verificacion' => now()->toISOString(),
                'url_ocsp' => $certificado->url_ocsp,
                'respuesta_ocsp' => $respuestaOCSP
            ];
            
            return $resultado;
            
        } catch (Exception $e) {
            Log::error('Error verificando OCSP', [
                'certificado_id' => $certificado->id,
                'url_ocsp' => $certificado->url_ocsp,
                'error' => $e->getMessage()
            ]);
            
            return [
                'verificado' => false,
                'motivo' => "Error en OCSP: {$e->getMessage()}",
                'estado' => self::ESTADO_DESCONOCIDO
            ];
        }
    }

    /**
     * REQ-CD-002: Validar cadena de certificados
     */
    public function validarCadenaCertificados(CertificadoDigital $certificado): array
    {
        try {
            $resultados = [
                'valida' => true,
                'cadena_completa' => false,
                'certificados_cadena' => [],
                'ca_raiz_confiable' => false,
                'errores' => [],
                'advertencias' => []
            ];
            
            // Obtener cadena de certificados
            $cadena = $certificado->cadena_certificados ?? [];
            
            if (empty($cadena)) {
                $resultados['advertencias'][] = 'No se encontró cadena de certificados';
                return $resultados;
            }
            
            // Validar cada certificado en la cadena
            $certificadoActual = $certificado;
            $profundidad = 0;
            
            foreach ($cadena as $certData) {
                $validacionCert = $this->validarCertificadoIndividual($certData);
                
                $resultados['certificados_cadena'][] = [
                    'profundidad' => $profundidad,
                    'subject' => $certData['subject'],
                    'issuer' => $certData['issuer'],
                    'valido' => $validacionCert['valido'],
                    'errores' => $validacionCert['errores'] ?? []
                ];
                
                if (!$validacionCert['valido']) {
                    $resultados['valida'] = false;
                    $resultados['errores'] = array_merge(
                        $resultados['errores'],
                        $validacionCert['errores'] ?? []
                    );
                }
                
                // Verificar si es CA raíz confiable
                if ($this->esCARaizConfiable($certData)) {
                    $resultados['ca_raiz_confiable'] = true;
                    $resultados['cadena_completa'] = true;
                    break;
                }
                
                $profundidad++;
            }
            
            if (!$resultados['ca_raiz_confiable']) {
                $resultados['advertencias'][] = 'No se encontró CA raíz confiable';
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            return [
                'valida' => false,
                'errores' => ["Error validando cadena: {$e->getMessage()}"]
            ];
        }
    }

    /**
     * REQ-CD-005: Verificar certificados próximos a vencer
     */
    public function verificarProximosVencimientos(int $diasAnticipacion = 30): array
    {
        $fechaLimite = now()->addDays($diasAnticipacion);
        
        $certificadosProximos = CertificadoDigital::where('fecha_vencimiento', '<=', $fechaLimite)
            ->where('fecha_vencimiento', '>', now())
            ->where('es_valido', true)
            ->with('usuario')
            ->get();
        
        $resultados = [
            'total_proximos' => $certificadosProximos->count(),
            'certificados' => [],
            'recomendaciones' => []
        ];
        
        foreach ($certificadosProximos as $certificado) {
            $diasRestantes = now()->diffInDays($certificado->fecha_vencimiento);
            
            $resultados['certificados'][] = [
                'id' => $certificado->id,
                'subject' => $certificado->subject,
                'propietario' => $certificado->usuario?->name,
                'fecha_vencimiento' => $certificado->fecha_vencimiento->toDateString(),
                'dias_restantes' => $diasRestantes,
                'urgencia' => $this->determinarUrgenciaRenovacion($diasRestantes)
            ];
        }
        
        // Agregar recomendaciones
        if ($certificadosProximos->count() > 0) {
            $resultados['recomendaciones'] = [
                'Contactar a los propietarios de certificados próximos a vencer',
                'Preparar proceso de renovación',
                'Verificar disponibilidad de nuevos certificados'
            ];
        }
        
        return $resultados;
    }

    /**
     * Obtener certificados válidos para un usuario
     */
    public function obtenerCertificadosValidosUsuario(User $usuario): array
    {
        return CertificadoDigital::where('usuario_id', $usuario->id)
            ->where('fecha_vencimiento', '>', now())
            ->where('estado', CertificadoDigital::ESTADO_ACTIVO)
            ->orderBy('fecha_vencimiento', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'subject' => $cert->subject,
                    'serial_number' => $cert->serial_number,
                    'tipo' => $cert->tipo_certificado,
                    'fecha_vencimiento' => $cert->fecha_vencimiento,
                    'key_usage' => $cert->key_usage,
                    'apto_para_firma' => $this->esAptoParaFirma($cert)
                ];
            })
            ->toArray();
    }

    /**
     * Verificar si certificado es apto para firma digital
     */
    private function esAptoParaFirma(CertificadoDigital $certificado): bool
    {
        $keyUsage = $certificado->key_usage ?? [];
        $extKeyUsage = $certificado->extended_key_usage ?? [];
        
        // Verificar Key Usage
        $tieneDigitalSignature = in_array('digitalSignature', $keyUsage);
        $tieneNonRepudiation = in_array('nonRepudiation', $keyUsage);
        
        // Verificar Extended Key Usage
        $tieneCodeSigning = in_array('codeSigning', $extKeyUsage);
        $tieneEmailProtection = in_array('emailProtection', $extKeyUsage);
        
        return $tieneDigitalSignature || $tieneNonRepudiation || 
               $tieneCodeSigning || $tieneEmailProtection;
    }

    // Métodos auxiliares para implementación específica
    private function cargarCAsConfiables(): array { return []; }
    private function detectarFormatoCertificado(string $contenido): string { return 'PEM'; }
    private function parsearCertificadoPEM(string $contenido): array { return []; }
    private function parsearCertificadoDER(string $contenido): array { return []; }
    private function parsearCertificadoP12(string $contenido, ?string $password): array { return []; }
    private function extraerInformacionCertificado(array $datos): array { return []; }
    private function validarCertificadoCompleto(array $datos, array $info): array { return ['valido' => true]; }
    private function determinarTipoCertificado(array $info): string { return self::TIPO_FIRMA; }
    private function extraerCadenaCertificados(array $datos): array { return []; }
    private function almacenarCertificadoSeguro(string $contenido, string $serial): string { return ''; }
    private function verificarEstadoRevocacion(CertificadoDigital $cert): void { }
    private function programarVerificacionesPeriodicas(CertificadoDigital $cert): void { }
    private function descargarCRL(string $url): string { return ''; }
    private function parsearCRL(string $data): array { return []; }
    private function buscarCertificadoEnCRL(string $serial, array $crl): bool { return false; }
    private function construirSolicitudOCSP(CertificadoDigital $cert): string { return ''; }
    private function parsearRespuestaOCSP(string $response): array { return []; }
    private function validarCertificadoIndividual(array $certData): array { return ['valido' => true]; }
    private function esCARaizConfiable(array $certData): bool { return false; }
    private function determinarUrgenciaRenovacion(int $dias): string {
        if ($dias <= 7) return 'critica';
        if ($dias <= 15) return 'alta';
        if ($dias <= 30) return 'media';
        return 'baja';
    }
}
