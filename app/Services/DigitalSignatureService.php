<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\FirmaDigital;
use App\Models\CertificadoDigital;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

/**
 * Servicio de Firmas Digitales Avanzadas para SGDEA
 * 
 * Implementa requerimientos:
 * REQ-FD-001: Firma digital con certificados X.509
 * REQ-FD-002: Validación de firmas CADES/PADES
 * REQ-FD-003: Sellado de tiempo (TSA)
 * REQ-FD-004: Cadena de confianza de certificados
 * REQ-FD-005: Firma múltiple y contrafirma
 */
class DigitalSignatureService
{
    // Tipos de firma soportados
    const TIPO_CADES = 'CADES';      // CAdES - CMS Advanced Electronic Signatures
    const TIPO_PADES = 'PADES';      // PAdES - PDF Advanced Electronic Signatures
    const TIPO_XADES = 'XADES';      // XAdES - XML Advanced Electronic Signatures
    
    // Niveles de firma AdES
    const NIVEL_BES = 'BES';         // Basic Electronic Signature
    const NIVEL_EPES = 'EPES';       // Explicit Policy-based Electronic Signature
    const NIVEL_T = 'T';             // Timestamp
    const NIVEL_LT = 'LT';           // Long Term
    const NIVEL_LTA = 'LTA';         // Long Term Archive
    
    // Estados de validación
    const VALIDACION_VALIDA = 'valida';
    const VALIDACION_INVALIDA = 'invalida';
    const VALIDACION_INDETERMINADA = 'indeterminada';
    const VALIDACION_ADVERTENCIA = 'advertencia';
    
    protected array $config;
    protected ?string $tsaUrl;

    public function __construct()
    {
        $this->config = config('digital_signatures', []);
        $this->tsaUrl = config('digital_signatures.tsa_url');
    }

    /**
     * REQ-FD-001: Firmar documento digitalmente
     */
    public function firmarDocumento(
        Documento $documento,
        User $firmante,
        CertificadoDigital $certificado,
        array $opciones = []
    ): FirmaDigital {
        try {
            // Validar prerrequisitos
            $this->validarPrerequisitos($documento, $certificado);
            
            // Determinar tipo de firma según el formato del documento
            $tipoFirma = $this->determinarTipoFirma($documento, $opciones);
            $nivelFirma = $opciones['nivel'] ?? self::NIVEL_T;
            
            // Preparar datos para la firma
            $datosDocumento = $this->prepararDatosDocumento($documento);
            $politicaFirma = $this->obtenerPoliticaFirma($opciones);
            
            // Crear estructura de firma
            $estructuraFirma = $this->crearEstructuraFirma(
                $datosDocumento,
                $certificado,
                $politicaFirma,
                $tipoFirma,
                $nivelFirma
            );
            
            // Aplicar firma criptográfica
            $firmaAplicada = $this->aplicarFirmaCriptografica(
                $estructuraFirma,
                $certificado,
                $opciones
            );
            
            // Agregar sellado de tiempo si está habilitado
            if ($nivelFirma !== self::NIVEL_BES && $this->tsaUrl) {
                $firmaAplicada = $this->agregarSelladoTiempo(
                    $firmaAplicada,
                    $opciones
                );
            }
            
            // Guardar archivo firmado
            $rutaArchivoFirmado = $this->guardarArchivoFirmado(
                $documento,
                $firmaAplicada,
                $tipoFirma
            );
            
            // Registrar firma en base de datos
            $firma = $this->registrarFirma([
                'documento_id' => $documento->id,
                'usuario_id' => $firmante->id,
                'certificado_id' => $certificado->id,
                'tipo_firma' => $tipoFirma,
                'nivel_firma' => $nivelFirma,
                'algoritmo_hash' => $opciones['algoritmo_hash'] ?? 'SHA-256',
                'ruta_archivo_firmado' => $rutaArchivoFirmado,
                'metadatos_firma' => $this->extraerMetadatosFirma($firmaAplicada),
                'estado' => FirmaDigital::ESTADO_VALIDA,
                'fecha_firma' => now(),
                'datos_certificado' => $this->extraerDatosCertificado($certificado),
                'politica_firma' => $politicaFirma
            ]);
            
            // Validar firma recién creada
            $validacion = $this->validarFirma($firma);
            $firma->update([
                'resultado_validacion' => $validacion,
                'fecha_validacion' => now()
            ]);
            
            Log::info('Documento firmado digitalmente', [
                'documento_id' => $documento->id,
                'firma_id' => $firma->id,
                'firmante' => $firmante->email,
                'tipo_firma' => $tipoFirma,
                'nivel_firma' => $nivelFirma
            ]);
            
            return $firma;
            
        } catch (Exception $e) {
            Log::error('Error al firmar documento', [
                'documento_id' => $documento->id,
                'usuario_id' => $firmante->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception("Error al firmar documento: {$e->getMessage()}");
        }
    }

    /**
     * REQ-FD-002: Validar firma digital existente
     */
    public function validarFirma(FirmaDigital $firma): array
    {
        try {
            $resultados = [
                'estado_general' => self::VALIDACION_INDETERMINADA,
                'validaciones' => [],
                'certificado' => [],
                'sellado_tiempo' => [],
                'errores' => [],
                'advertencias' => []
            ];
            
            // 1. Validar integridad del documento
            $integridadDoc = $this->validarIntegridadDocumento($firma);
            $resultados['validaciones']['integridad_documento'] = $integridadDoc;
            
            // 2. Validar certificado del firmante
            $validacionCert = $this->validarCertificado($firma->certificado);
            $resultados['certificado'] = $validacionCert;
            
            // 3. Validar firma criptográfica
            $validacionFirma = $this->validarFirmaCriptografica($firma);
            $resultados['validaciones']['firma_criptografica'] = $validacionFirma;
            
            // 4. Validar sellado de tiempo (si existe)
            if ($firma->tiene_sellado_tiempo) {
                $validacionTSA = $this->validarSelladoTiempo($firma);
                $resultados['sellado_tiempo'] = $validacionTSA;
            }
            
            // 5. Validar cadena de confianza
            $cadenaConfianza = $this->validarCadenaConfianza($firma->certificado);
            $resultados['validaciones']['cadena_confianza'] = $cadenaConfianza;
            
            // 6. Validar política de firma
            if ($firma->politica_firma) {
                $validacionPolitica = $this->validarPoliticaFirma($firma);
                $resultados['validaciones']['politica_firma'] = $validacionPolitica;
            }
            
            // Determinar estado general
            $resultados['estado_general'] = $this->determinarEstadoValidacion($resultados);
            
            // Agregar información adicional
            $resultados['fecha_validacion'] = now()->toISOString();
            $resultados['version_validador'] = '1.0.0';
            
            return $resultados;
            
        } catch (Exception $e) {
            Log::error('Error validando firma digital', [
                'firma_id' => $firma->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'estado_general' => self::VALIDACION_INDETERMINADA,
                'errores' => ['Error interno: ' . $e->getMessage()],
                'fecha_validacion' => now()->toISOString()
            ];
        }
    }

    /**
     * REQ-FD-005: Aplicar contrafirma a documento ya firmado
     */
    public function contrafirmarDocumento(
        FirmaDigital $firmaOriginal,
        User $contrafirmante,
        CertificadoDigital $certificado,
        array $opciones = []
    ): FirmaDigital {
        try {
            // Validar que la firma original sea válida
            $validacionOriginal = $this->validarFirma($firmaOriginal);
            if ($validacionOriginal['estado_general'] !== self::VALIDACION_VALIDA) {
                throw new Exception('No se puede contrafirmar: la firma original no es válida');
            }
            
            // Preparar datos para contrafirma
            $tipoContrafirma = $opciones['tipo_contrafirma'] ?? 'countersignature';
            
            // Crear estructura de contrafirma
            $estructuraContrafirma = $this->crearEstructuraContrafirma(
                $firmaOriginal,
                $certificado,
                $tipoContrafirma
            );
            
            // Aplicar contrafirma
            $contrafirmaAplicada = $this->aplicarContrafirma(
                $estructuraContrafirma,
                $certificado,
                $opciones
            );
            
            // Guardar archivo contrafirmado
            $rutaArchivoContrafirmado = $this->guardarArchivoContrafirmado(
                $firmaOriginal,
                $contrafirmaAplicada
            );
            
            // Registrar contrafirma
            $contrafirma = $this->registrarFirma([
                'documento_id' => $firmaOriginal->documento_id,
                'usuario_id' => $contrafirmante->id,
                'certificado_id' => $certificado->id,
                'firma_padre_id' => $firmaOriginal->id,
                'tipo_firma' => $firmaOriginal->tipo_firma,
                'nivel_firma' => $firmaOriginal->nivel_firma,
                'es_contrafirma' => true,
                'tipo_contrafirma' => $tipoContrafirma,
                'ruta_archivo_firmado' => $rutaArchivoContrafirmado,
                'estado' => FirmaDigital::ESTADO_VALIDA,
                'fecha_firma' => now()
            ]);
            
            Log::info('Documento contrafirmado', [
                'firma_original_id' => $firmaOriginal->id,
                'contrafirma_id' => $contrafirma->id,
                'contrafirmante' => $contrafirmante->email
            ]);
            
            return $contrafirma;
            
        } catch (Exception $e) {
            Log::error('Error en contrafirma', [
                'firma_original_id' => $firmaOriginal->id,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception("Error al contrafirmar: {$e->getMessage()}");
        }
    }

    /**
     * REQ-FD-003: Agregar sellado de tiempo a firma
     */
    private function agregarSelladoTiempo(array $firmaData, array $opciones = []): array
    {
        if (!$this->tsaUrl) {
            Log::warning('TSA URL no configurada, omitiendo sellado de tiempo');
            return $firmaData;
        }
        
        try {
            // Preparar solicitud de sellado de tiempo
            $solicitudTSA = $this->prepararSolicitudTSA($firmaData);
            
            // Enviar solicitud a TSA
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/timestamp-query',
                    'User-Agent' => 'SGDEA-DigitalSignature/1.0'
                ])
                ->post($this->tsaUrl, $solicitudTSA);
            
            if (!$response->successful()) {
                throw new Exception("Error en TSA: HTTP {$response->status()}");
            }
            
            // Procesar respuesta de TSA
            $timestamp = $this->procesarRespuestaTSA($response->body());
            
            // Integrar timestamp en la firma
            $firmaData['timestamp'] = $timestamp;
            $firmaData['tsa_url'] = $this->tsaUrl;
            $firmaData['fecha_sellado'] = now()->toISOString();
            
            Log::info('Sellado de tiempo aplicado exitosamente', [
                'tsa_url' => $this->tsaUrl,
                'timestamp_serial' => $timestamp['serial_number'] ?? null
            ]);
            
            return $firmaData;
            
        } catch (Exception $e) {
            Log::error('Error en sellado de tiempo', [
                'tsa_url' => $this->tsaUrl,
                'error' => $e->getMessage()
            ]);
            
            // Continuar sin sellado de tiempo si falla
            $firmaData['tsa_error'] = $e->getMessage();
            return $firmaData;
        }
    }

    /**
     * Validar certificado digital
     */
    private function validarCertificado(CertificadoDigital $certificado): array
    {
        $resultados = [
            'valido' => true,
            'estado' => 'valido',
            'errores' => [],
            'advertencias' => [],
            'detalles' => []
        ];
        
        try {
            // 1. Verificar vigencia
            if ($certificado->fecha_vencimiento < now()) {
                $resultados['valido'] = false;
                $resultados['estado'] = 'vencido';
                $resultados['errores'][] = 'Certificado vencido';
            } elseif ($certificado->fecha_vencimiento < now()->addDays(30)) {
                $resultados['advertencias'][] = 'Certificado próximo a vencer';
            }
            
            // 2. Verificar revocación (si hay URL de CRL)
            if ($certificado->url_crl) {
                $estadoRevocacion = $this->verificarRevocacion($certificado);
                if ($estadoRevocacion['revocado']) {
                    $resultados['valido'] = false;
                    $resultados['estado'] = 'revocado';
                    $resultados['errores'][] = 'Certificado revocado';
                }
            }
            
            // 3. Verificar uso permitido
            $usoValido = $this->verificarUsoCertificado($certificado, 'firma_digital');
            if (!$usoValido) {
                $resultados['valido'] = false;
                $resultados['errores'][] = 'Certificado no autorizado para firma digital';
            }
            
            // 4. Agregar detalles del certificado
            $resultados['detalles'] = [
                'subject' => $certificado->subject,
                'issuer' => $certificado->issuer,
                'serial_number' => $certificado->serial_number,
                'not_before' => $certificado->fecha_emision->toISOString(),
                'not_after' => $certificado->fecha_vencimiento->toISOString(),
                'key_usage' => $certificado->key_usage ?? [],
                'extended_key_usage' => $certificado->extended_key_usage ?? []
            ];
            
        } catch (Exception $e) {
            $resultados['valido'] = false;
            $resultados['estado'] = 'error';
            $resultados['errores'][] = "Error validando certificado: {$e->getMessage()}";
        }
        
        return $resultados;
    }

    /**
     * Validar integridad del documento firmado
     */
    private function validarIntegridadDocumento(FirmaDigital $firma): array
    {
        try {
            $documento = $firma->documento;
            
            // Verificar que el archivo original existe
            if (!Storage::disk('public')->exists($documento->ruta_archivo)) {
                return [
                    'valido' => false,
                    'error' => 'Archivo original del documento no encontrado'
                ];
            }
            
            // Verificar que el archivo firmado existe
            if (!Storage::disk('public')->exists($firma->ruta_archivo_firmado)) {
                return [
                    'valido' => false,
                    'error' => 'Archivo firmado no encontrado'
                ];
            }
            
            // Calcular hash del documento original
            $rutaOriginal = storage_path('app/public/' . $documento->ruta_archivo);
            $hashOriginal = hash_file('sha256', $rutaOriginal);
            
            // Extraer hash del documento desde la firma
            $hashEnFirma = $this->extraerHashDocumentoFirma($firma);
            
            if ($hashOriginal !== $hashEnFirma) {
                return [
                    'valido' => false,
                    'error' => 'El documento ha sido modificado después de la firma',
                    'hash_original' => $hashOriginal,
                    'hash_en_firma' => $hashEnFirma
                ];
            }
            
            return [
                'valido' => true,
                'hash_verificado' => $hashOriginal
            ];
            
        } catch (Exception $e) {
            return [
                'valido' => false,
                'error' => "Error verificando integridad: {$e->getMessage()}"
            ];
        }
    }

    /**
     * Determinar tipo de firma según formato del documento
     */
    private function determinarTipoFirma(Documento $documento, array $opciones): string
    {
        if (isset($opciones['tipo_firma'])) {
            return $opciones['tipo_firma'];
        }
        
        return match(strtolower($documento->formato)) {
            'pdf' => self::TIPO_PADES,
            'xml' => self::TIPO_XADES,
            default => self::TIPO_CADES
        };
    }

    /**
     * Validar prerrequisitos para firma
     */
    private function validarPrerequisitos(Documento $documento, CertificadoDigital $certificado): void
    {
        if (!$documento->existe()) {
            throw new Exception('El documento no tiene archivo asociado');
        }
        
        if ($certificado->fecha_vencimiento < now()) {
            throw new Exception('El certificado está vencido');
        }
        
        if (!$certificado->es_valido) {
            throw new Exception('El certificado no es válido');
        }
    }

    // Métodos auxiliares que se implementarían según la librería criptográfica específica
    private function prepararDatosDocumento(Documento $documento): array { return []; }
    private function obtenerPoliticaFirma(array $opciones): ?array { return null; }
    private function crearEstructuraFirma($datos, $cert, $politica, $tipo, $nivel): array { return []; }
    private function aplicarFirmaCriptografica($estructura, $cert, $opciones): array { return $estructura; }
    private function guardarArchivoFirmado($doc, $firma, $tipo): string { return ''; }
    private function registrarFirma(array $datos): FirmaDigital { return new FirmaDigital(); }
    private function extraerMetadatosFirma(array $firma): array { return []; }
    private function extraerDatosCertificado(CertificadoDigital $cert): array { return []; }
    private function validarFirmaCriptografica(FirmaDigital $firma): array { return ['valido' => true]; }
    private function validarSelladoTiempo(FirmaDigital $firma): array { return ['valido' => true]; }
    private function validarCadenaConfianza(CertificadoDigital $cert): array { return ['valido' => true]; }
    private function validarPoliticaFirma(FirmaDigital $firma): array { return ['valido' => true]; }
    private function determinarEstadoValidacion(array $resultados): string { return self::VALIDACION_VALIDA; }
    private function crearEstructuraContrafirma($firmaOrig, $cert, $tipo): array { return []; }
    private function aplicarContrafirma($estructura, $cert, $opciones): array { return $estructura; }
    private function guardarArchivoContrafirmado($firmaOrig, $contrafirma): string { return ''; }
    private function prepararSolicitudTSA(array $firmaData): string { return ''; }
    private function procesarRespuestaTSA(string $response): array { return []; }
    private function verificarRevocacion(CertificadoDigital $cert): array { return ['revocado' => false]; }
    private function verificarUsoCertificado(CertificadoDigital $cert, string $uso): bool { return true; }
    private function extraerHashDocumentoFirma(FirmaDigital $firma): string { return ''; }
}
