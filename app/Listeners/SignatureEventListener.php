<?php

namespace App\Listeners;

use App\Events\DocumentSignedEvent;
use App\Events\SignatureValidationFailedEvent;
use App\Events\CertificateExpiringEvent;
use App\Models\User;
use App\Notifications\DocumentSignedNotification;
use App\Notifications\SignatureValidationFailedNotification;
use App\Notifications\CertificateExpiringNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para eventos de firmas digitales
 */
class SignatureEventListener implements ShouldQueue
{
    /**
     * Handle document signed events
     */
    public function handleDocumentSigned(DocumentSignedEvent $event): void
    {
        try {
            // Log del evento
            Log::info('Documento firmado digitalmente', [
                'documento_id' => $event->documento->id,
                'documento_nombre' => $event->documento->nombre,
                'firma_id' => $event->firma->id,
                'firmante_id' => $event->firmante->id,
                'firmante_name' => $event->firmante->name,
                'tipo_firma' => $event->firma->tipo_firma,
                'fecha_firma' => $event->firma->fecha_firma->toISOString()
            ]);

            // Notificar al firmante
            $event->firmante->notify(new DocumentSignedNotification(
                $event->documento,
                $event->firma
            ));

            // Notificar a otros usuarios relevantes (propietario del expediente, etc.)
            $usuariosANotificar = $this->obtenerUsuariosRelevantes($event->documento);
            
            foreach ($usuariosANotificar as $usuario) {
                if ($usuario->id !== $event->firmante->id) {
                    $usuario->notify(new DocumentSignedNotification(
                        $event->documento,
                        $event->firma,
                        false // No es el firmante
                    ));
                }
            }

            // Actualizar estadísticas del documento
            $this->actualizarEstadisticasDocumento($event->documento, $event->firma);
            
        } catch (\Exception $e) {
            Log::error('Error procesando evento de documento firmado', [
                'documento_id' => $event->documento->id,
                'firma_id' => $event->firma->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle signature validation failed events
     */
    public function handleSignatureValidationFailed(SignatureValidationFailedEvent $event): void
    {
        try {
            // Log crítico del fallo
            Log::critical('Fallo en validación de firma digital', [
                'firma_id' => $event->firma->id,
                'documento_id' => $event->firma->documento_id,
                'usuario_firmante_id' => $event->firma->usuario_id,
                'tipo_fallo' => $event->tipoFallo,
                'errores' => $event->erroresValidacion,
                'fecha_fallo' => now()->toISOString()
            ]);

            // Notificar al firmante original
            if ($event->firma->usuario) {
                $event->firma->usuario->notify(new SignatureValidationFailedNotification(
                    $event->firma,
                    $event->erroresValidacion,
                    $event->tipoFallo
                ));
            }

            // Notificar a administradores si es un fallo crítico
            if ($this->esFalloCritico($event->tipoFallo, $event->erroresValidacion)) {
                $administradores = User::role('admin')->get();
                Notification::send($administradores, new SignatureValidationFailedNotification(
                    $event->firma,
                    $event->erroresValidacion,
                    $event->tipoFallo,
                    true // Es notificación para admin
                ));
            }

            // Actualizar estado de la firma
            $event->firma->update([
                'estado' => 'invalida',
                'motivo_invalidez' => $event->tipoFallo,
                'errores_validacion' => $event->erroresValidacion,
                'fecha_invalidez' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando evento de fallo de validación', [
                'firma_id' => $event->firma->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle certificate expiring events
     */
    public function handleCertificateExpiring(CertificateExpiringEvent $event): void
    {
        try {
            // Log de advertencia
            Log::warning('Certificado próximo a vencer', [
                'certificado_id' => $event->certificado->id,
                'usuario_id' => $event->certificado->usuario_id,
                'dias_restantes' => $event->diasRestantes,
                'fecha_vencimiento' => $event->certificado->fecha_vencimiento->toISOString(),
                'nivel_urgencia' => $event->nivelUrgencia
            ]);

            // Notificar al propietario del certificado
            if ($event->certificado->usuario) {
                $event->certificado->usuario->notify(new CertificateExpiringNotification(
                    $event->certificado,
                    $event->diasRestantes,
                    $event->nivelUrgencia
                ));
            }

            // Si es urgencia crítica, notificar también a administradores
            if ($event->nivelUrgencia === 'critica') {
                $administradores = User::role('admin')->get();
                Notification::send($administradores, new CertificateExpiringNotification(
                    $event->certificado,
                    $event->diasRestantes,
                    $event->nivelUrgencia,
                    true // Es para admin
                ));
            }

            // Marcar certificado con advertencia de vencimiento
            $event->certificado->update([
                'tiene_advertencia_vencimiento' => true,
                'fecha_ultima_advertencia' => now(),
                'nivel_urgencia_vencimiento' => $event->nivelUrgencia
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando evento de certificado por vencer', [
                'certificado_id' => $event->certificado->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener usuarios relevantes para notificar sobre firma
     */
    private function obtenerUsuariosRelevantes($documento): array
    {
        $usuarios = [];
        
        // Agregar creador del documento
        if ($documento->usuarioCreador) {
            $usuarios[] = $documento->usuarioCreador;
        }
        
        // Agregar responsable del expediente
        if ($documento->expediente && $documento->expediente->usuarioResponsable) {
            $usuarios[] = $documento->expediente->usuarioResponsable;
        }
        
        // Agregar usuarios con permisos de visualización del expediente
        if ($documento->expediente) {
            $usuariosConPermiso = User::permission('ver_expediente_' . $documento->expediente->id)->get();
            $usuarios = array_merge($usuarios, $usuariosConPermiso->toArray());
        }
        
        // Eliminar duplicados
        return array_unique($usuarios, SORT_REGULAR);
    }

    /**
     * Actualizar estadísticas del documento tras firma
     */
    private function actualizarEstadisticasDocumento($documento, $firma): void
    {
        $totalFirmas = $documento->firmas()->count();
        $firmasValidas = $documento->firmas()->where('estado', 'valida')->count();
        
        $documento->update([
            'total_firmas' => $totalFirmas,
            'firmas_validas' => $firmasValidas,
            'ultima_firma_fecha' => $firma->fecha_firma,
            'estado_firma' => $this->determinarEstadoFirmaDocumento($totalFirmas, $firmasValidas)
        ]);
    }

    /**
     * Determinar si es un fallo crítico que requiere notificación a admin
     */
    private function esFalloCritico(string $tipoFallo, array $errores): bool
    {
        $fallosCriticos = [
            'certificate_revoked',
            'certificate_expired',
            'chain_validation_failed',
            'document_modified',
            'signature_corrupted'
        ];
        
        return in_array($tipoFallo, $fallosCriticos) || 
               count($errores) > 3; // Múltiples errores simultáneos
    }

    /**
     * Determinar estado general de firmas del documento
     */
    private function determinarEstadoFirmaDocumento(int $total, int $validas): string
    {
        if ($total === 0) return 'sin_firmas';
        if ($validas === $total) return 'todas_validas';
        if ($validas > 0) return 'algunas_validas';
        return 'ninguna_valida';
    }
}
