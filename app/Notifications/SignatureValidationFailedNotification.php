<?php

namespace App\Notifications;

use App\Models\FirmaDigital;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para fallo en validación de firma digital
 */
class SignatureValidationFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected FirmaDigital $firma;
    protected array $erroresValidacion;
    protected string $tipoFallo;
    protected bool $esParaAdmin;

    public function __construct(
        FirmaDigital $firma, 
        array $erroresValidacion, 
        string $tipoFallo,
        bool $esParaAdmin = false
    ) {
        $this->firma = $firma;
        $this->erroresValidacion = $erroresValidacion;
        $this->tipoFallo = $tipoFallo;
        $this->esParaAdmin = $esParaAdmin;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->esParaAdmin 
            ? 'ALERTA: Fallo crítico en validación de firma - SGDEA'
            : 'Problema con su firma digital - SGDEA';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hola {$notifiable->name}")
            ->error();

        if ($this->esParaAdmin) {
            $mail->line('Se ha detectado un fallo crítico en la validación de una firma digital.')
                ->line("**Usuario afectado:** {$this->firma->usuario->name}")
                ->line("**Documento:** {$this->firma->documento->nombre}");
        } else {
            $mail->line('Se ha detectado un problema con la validación de su firma digital.');
        }

        $mail->line("**ID de firma:** {$this->firma->id}")
            ->line("**Tipo de fallo:** " . $this->formatearTipoFallo())
            ->line("**Fecha de firma:** {$this->firma->fecha_firma->format('d/m/Y H:i:s')}")
            ->line("**Tipo de firma:** {$this->firma->tipo_firma}");

        // Agregar errores específicos
        if (!empty($this->erroresValidacion)) {
            $mail->line('**Errores detectados:**');
            foreach (array_slice($this->erroresValidacion, 0, 5) as $error) {
                $mail->line("• {$error}");
            }
        }

        // Recomendaciones según el tipo de fallo
        $mail->line($this->obtenerRecomendaciones());

        $mail->action('Ver Detalles de Firma', url("/admin/firmas/detalle/{$this->firma->id}"));

        if ($this->esParaAdmin) {
            $mail->line('Como administrador, considere revisar el estado del sistema de firmas digitales y contactar al usuario afectado.');
        }

        return $mail;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'signature_validation_failed',
            'titulo' => 'Fallo en Validación de Firma',
            'mensaje' => $this->generarMensaje(),
            'firma' => [
                'id' => $this->firma->id,
                'documento_id' => $this->firma->documento_id,
                'documento_nombre' => $this->firma->documento->nombre,
                'firmante' => $this->firma->usuario->name,
                'fecha_firma' => $this->firma->fecha_firma->toISOString()
            ],
            'fallo' => [
                'tipo' => $this->tipoFallo,
                'tipo_formateado' => $this->formatearTipoFallo(),
                'errores' => $this->erroresValidacion,
                'es_critico' => $this->esFalloCritico()
            ],
            'urls' => [
                'firma' => "/admin/firmas/detalle/{$this->firma->id}",
                'documento' => "/admin/documentos/{$this->firma->documento_id}"
            ],
            'icono' => 'alert-triangle',
            'color' => 'red',
            'es_para_admin' => $this->esParaAdmin,
            'prioridad' => $this->esFalloCritico() ? 'alta' : 'media'
        ];
    }

    private function generarMensaje(): string
    {
        $tipoFormateado = $this->formatearTipoFallo();
        
        if ($this->esParaAdmin) {
            return "Fallo '{$tipoFormateado}' en firma de {$this->firma->usuario->name} - Documento: {$this->firma->documento->nombre}";
        }
        
        return "Fallo en validación de su firma digital: {$tipoFormateado}";
    }

    private function formatearTipoFallo(): string
    {
        return match($this->tipoFallo) {
            'certificate_revoked' => 'Certificado Revocado',
            'certificate_expired' => 'Certificado Vencido',
            'chain_validation_failed' => 'Fallo en Cadena de Confianza',
            'document_modified' => 'Documento Modificado',
            'signature_corrupted' => 'Firma Corrupta',
            'timestamp_invalid' => 'Sellado de Tiempo Inválido',
            'policy_violation' => 'Violación de Política',
            'revocation_check_failed' => 'Fallo en Verificación de Revocación',
            'validation_error' => 'Error de Validación',
            default => ucfirst(str_replace('_', ' ', $this->tipoFallo))
        };
    }

    private function obtenerRecomendaciones(): string
    {
        return match($this->tipoFallo) {
            'certificate_revoked' => 'Su certificado ha sido revocado. Contacte a su autoridad de certificación para obtener un nuevo certificado.',
            'certificate_expired' => 'Su certificado ha vencido. Renueve su certificado para continuar firmando documentos.',
            'chain_validation_failed' => 'Hay problemas con la cadena de confianza de su certificado. Verifique la instalación de certificados raíz.',
            'document_modified' => 'El documento ha sido modificado después de la firma. La integridad ha sido comprometida.',
            'signature_corrupted' => 'La firma está corrupta o dañada. Considere firmar el documento nuevamente.',
            'timestamp_invalid' => 'El sellado de tiempo no es válido. Verifique la conectividad con el servicio TSA.',
            default => 'Contacte al administrador del sistema para resolver este problema.'
        };
    }

    private function esFalloCritico(): bool
    {
        $fallosCriticos = [
            'certificate_revoked',
            'document_modified',
            'signature_corrupted'
        ];
        
        return in_array($this->tipoFallo, $fallosCriticos);
    }
}
