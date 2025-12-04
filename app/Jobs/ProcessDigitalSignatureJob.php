<?php

namespace App\Jobs;

use App\Models\FirmaDigital;
use App\Services\DigitalSignatureService;
use App\Services\CertificateManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job para procesamiento asíncrono de firmas digitales
 */
class ProcessDigitalSignatureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $firmaId;
    protected string $operacion;
    protected array $opciones;
    
    public $timeout = 300; // 5 minutos
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Reintentos con backoff

    public function __construct(int $firmaId, string $operacion, array $opciones = [])
    {
        $this->firmaId = $firmaId;
        $this->operacion = $operacion;
        $this->opciones = $opciones;
    }

    public function handle(
        DigitalSignatureService $signatureService,
        CertificateManagementService $certificateService
    ): void {
        try {
            $firma = FirmaDigital::findOrFail($this->firmaId);
            
            Log::info("Iniciando procesamiento de firma digital", [
                'firma_id' => $this->firmaId,
                'operacion' => $this->operacion,
                'job_id' => $this->job->getJobId()
            ]);

            switch ($this->operacion) {
                case 'validar_firma':
                    $this->procesarValidacionFirma($firma, $signatureService);
                    break;
                    
                case 'verificar_certificado':
                    $this->procesarVerificacionCertificado($firma, $certificateService);
                    break;
                    
                case 'actualizar_sellado_tiempo':
                    $this->procesarActualizacionSellado($firma, $signatureService);
                    break;
                    
                case 'verificar_cadena_confianza':
                    $this->procesarVerificacionCadena($firma, $certificateService);
                    break;
                    
                default:
                    throw new Exception("Operación no válida: {$this->operacion}");
            }
            
            Log::info("Procesamiento de firma completado exitosamente", [
                'firma_id' => $this->firmaId,
                'operacion' => $this->operacion
            ]);
            
        } catch (Exception $e) {
            Log::error("Error en procesamiento de firma digital", [
                'firma_id' => $this->firmaId,
                'operacion' => $this->operacion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->actualizarEstadoError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar validación completa de firma
     */
    private function procesarValidacionFirma(FirmaDigital $firma, DigitalSignatureService $service): void
    {
        $firma->update(['estado_procesamiento' => 'validando']);
        
        $resultadoValidacion = $service->validarFirma($firma);
        
        $firma->update([
            'resultado_validacion' => $resultadoValidacion,
            'fecha_validacion' => now(),
            'estado_procesamiento' => 'completado',
            'estado' => $this->determinarEstadoPorValidacion($resultadoValidacion)
        ]);
    }

    /**
     * Procesar verificación de certificado
     */
    private function procesarVerificacionCertificado(FirmaDigital $firma, CertificateManagementService $service): void
    {
        $certificado = $firma->certificado;
        
        // Verificar revocación por CRL
        $resultadoCRL = $service->verificarRevocacionCRL($certificado);
        
        // Verificar por OCSP
        $resultadoOCSP = $service->verificarRevocacionOCSP($certificado);
        
        // Validar cadena de certificados
        $validacionCadena = $service->validarCadenaCertificados($certificado);
        
        $verificacionCompleta = [
            'crl' => $resultadoCRL,
            'ocsp' => $resultadoOCSP,
            'cadena' => $validacionCadena,
            'fecha_verificacion' => now()->toISOString()
        ];
        
        // Actualizar certificado
        $certificado->update([
            'estado' => $this->determinarEstadoCertificado($verificacionCompleta),
            'fecha_ultima_verificacion' => now(),
            'resultado_ultima_verificacion' => $verificacionCompleta
        ]);
        
        // Si el certificado cambió de estado, actualizar la firma
        if ($certificado->wasChanged('estado')) {
            $this->actualizarFirmaPorCambioEstadoCertificado($firma, $certificado->estado);
        }
    }

    /**
     * Procesar actualización de sellado de tiempo
     */
    private function procesarActualizacionSellado(FirmaDigital $firma, DigitalSignatureService $service): void
    {
        if (!$firma->tiene_sellado_tiempo) {
            Log::info("Firma no tiene sellado de tiempo, omitiendo actualización", [
                'firma_id' => $firma->id
            ]);
            return;
        }
        
        // Verificar sellado de tiempo existente
        $validacionTSA = $service->validarSelladoTiempo($firma);
        
        $firma->update([
            'validacion_sellado_tiempo' => $validacionTSA,
            'fecha_validacion_tsa' => now()
        ]);
    }

    /**
     * Procesar verificación de cadena de confianza
     */
    private function procesarVerificacionCadena(FirmaDigital $firma, CertificateManagementService $service): void
    {
        $certificado = $firma->certificado;
        $validacionCadena = $service->validarCadenaCertificados($certificado);
        
        $firma->update([
            'validacion_cadena_confianza' => $validacionCadena,
            'fecha_validacion_cadena' => now()
        ]);
    }

    /**
     * Determinar estado de firma basado en validación
     */
    private function determinarEstadoPorValidacion(array $validacion): string
    {
        $estadoGeneral = $validacion['estado_general'] ?? 'indeterminada';
        
        return match($estadoGeneral) {
            'valida' => FirmaDigital::ESTADO_VALIDA,
            'invalida' => FirmaDigital::ESTADO_INVALIDA,
            'advertencia' => FirmaDigital::ESTADO_ADVERTENCIA,
            default => FirmaDigital::ESTADO_INDETERMINADA
        };
    }

    /**
     * Determinar estado de certificado basado en verificaciones
     */
    private function determinarEstadoCertificado(array $verificacion): string
    {
        // Verificar revocación
        if (($verificacion['crl']['revocado'] ?? false) || 
            ($verificacion['ocsp']['estado'] ?? null) === 'revoked') {
            return CertificateManagementService::ESTADO_REVOCADO;
        }
        
        if (($verificacion['ocsp']['estado'] ?? null) === 'suspended') {
            return CertificateManagementService::ESTADO_SUSPENDIDO;
        }
        
        // Verificar cadena de confianza
        if (!($verificacion['cadena']['valida'] ?? false)) {
            return CertificateManagementService::ESTADO_DESCONOCIDO;
        }
        
        return CertificateManagementService::ESTADO_VALIDO;
    }

    /**
     * Actualizar firma cuando cambia el estado del certificado
     */
    private function actualizarFirmaPorCambioEstadoCertificado(FirmaDigital $firma, string $nuevoEstado): void
    {
        $estadoFirma = match($nuevoEstado) {
            CertificateManagementService::ESTADO_REVOCADO => FirmaDigital::ESTADO_INVALIDA,
            CertificateManagementService::ESTADO_SUSPENDIDO => FirmaDigital::ESTADO_SUSPENDIDA,
            CertificateManagementService::ESTADO_DESCONOCIDO => FirmaDigital::ESTADO_INDETERMINADA,
            default => $firma->estado // Mantener estado actual
        };
        
        if ($estadoFirma !== $firma->estado) {
            $firma->update([
                'estado' => $estadoFirma,
                'motivo_cambio_estado' => "Cambio de estado del certificado: {$nuevoEstado}",
                'fecha_cambio_estado' => now()
            ]);
            
            Log::warning("Estado de firma actualizado por cambio en certificado", [
                'firma_id' => $firma->id,
                'estado_anterior' => $firma->getOriginal('estado'),
                'estado_nuevo' => $estadoFirma,
                'estado_certificado' => $nuevoEstado
            ]);
        }
    }

    /**
     * Actualizar estado de error en caso de fallo
     */
    private function actualizarEstadoError(string $mensajeError): void
    {
        try {
            FirmaDigital::where('id', $this->firmaId)->update([
                'estado_procesamiento' => 'error',
                'error_procesamiento' => $mensajeError,
                'fecha_error' => now()
            ]);
        } catch (Exception $e) {
            Log::error("Error actualizando estado de error en firma", [
                'firma_id' => $this->firmaId,
                'error_original' => $mensajeError,
                'error_actualizacion' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job de procesamiento de firma falló definitivamente", [
            'firma_id' => $this->firmaId,
            'operacion' => $this->operacion,
            'error' => $exception->getMessage(),
            'intentos' => $this->attempts()
        ]);
        
        $this->actualizarEstadoError("Job falló después de {$this->attempts()} intentos: {$exception->getMessage()}");
    }
}
