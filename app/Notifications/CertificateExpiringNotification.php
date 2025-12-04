<?php

namespace App\Notifications;

use App\Models\CertificadoDigital;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para certificado próximo a vencer
 */
class CertificateExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected CertificadoDigital $certificado;
    protected int $diasRestantes;
    protected string $nivelUrgencia;
    protected bool $esParaAdmin;

    public function __construct(
        CertificadoDigital $certificado, 
        int $diasRestantes, 
        string $nivelUrgencia,
        bool $esParaAdmin = false
    ) {
        $this->certificado = $certificado;
        $this->diasRestantes = $diasRestantes;
        $this->nivelUrgencia = $nivelUrgencia;
        $this->esParaAdmin = $esParaAdmin;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Enviar email solo para urgencias altas y críticas
        if (in_array($this->nivelUrgencia, ['alta', 'critica'])) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $urgencyText = match($this->nivelUrgencia) {
            'critica' => 'URGENTE',
            'alta' => 'IMPORTANTE', 
            'media' => 'Aviso',
            default => 'Información'
        };

        $subject = "{$urgencyText}: Certificado próximo a vencer - SGDEA";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hola {$notifiable->name}")
            ->line("Su certificado digital está próximo a vencer en **{$this->diasRestantes} días**.")
            ->line("**Certificado:** {$this->certificado->subject}")
            ->line("**Número de serie:** {$this->certificado->serial_number}")
            ->line("**Fecha de vencimiento:** {$this->certificado->fecha_vencimiento->format('d/m/Y')}")
            ->line("**Nivel de urgencia:** " . $this->formatearNivelUrgencia());

        if ($this->diasRestantes <= 7) {
            $mail->line('⚠️ **ACCIÓN REQUERIDA:** Es crítico renovar este certificado inmediatamente para evitar interrupciones en las firmas digitales.');
        } elseif ($this->diasRestantes <= 15) {
            $mail->line('⚡ **ACCIÓN RECOMENDADA:** Se recomienda iniciar el proceso de renovación lo antes posible.');
        } else {
            $mail->line('📅 Planifique la renovación de este certificado para evitar inconvenientes.');
        }

        $mail->action('Gestionar Certificados', url('/admin/certificados'))
            ->line('Mantenga sus certificados actualizados para garantizar la continuidad de las firmas digitales.');

        if ($this->esParaAdmin) {
            $usuarioNombre = $this->certificado->usuario ? $this->certificado->usuario->name : 'Sistema';
            $mail->line("**Usuario afectado:** {$usuarioNombre}")
                ->line("Como administrador, considere contactar al usuario para coordinar la renovación.");
        }

        return $mail;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'certificate_expiring',
            'titulo' => 'Certificado Próximo a Vencer',
            'mensaje' => $this->generarMensaje(),
            'certificado' => [
                'id' => $this->certificado->id,
                'subject' => $this->certificado->subject,
                'serial_number' => $this->certificado->serial_number,
                'fecha_vencimiento' => $this->certificado->fecha_vencimiento->toDateString()
            ],
            'urgencia' => [
                'nivel' => $this->nivelUrgencia,
                'dias_restantes' => $this->diasRestantes,
                'es_critica' => $this->nivelUrgencia === 'critica'
            ],
            'urls' => [
                'certificados' => '/admin/certificados',
                'detalle' => "/admin/certificados/{$this->certificado->id}"
            ],
            'icono' => $this->obtenerIconoPorUrgencia(),
            'color' => $this->obtenerColorPorUrgencia(),
            'es_para_admin' => $this->esParaAdmin
        ];
    }

    private function generarMensaje(): string
    {
        $urgenciaTexto = $this->formatearNivelUrgencia();
        
        if ($this->esParaAdmin) {
            $propietario = $this->certificado->usuario ? $this->certificado->usuario->name : 'Sistema';
            return "El certificado de {$propietario} vence en {$this->diasRestantes} días ({$urgenciaTexto})";
        }
        
        return "Su certificado digital vence en {$this->diasRestantes} días ({$urgenciaTexto})";
    }

    private function formatearNivelUrgencia(): string
    {
        return match($this->nivelUrgencia) {
            'critica' => 'Crítica',
            'alta' => 'Alta',
            'media' => 'Media',
            'baja' => 'Baja',
            default => 'Normal'
        };
    }

    private function obtenerIconoPorUrgencia(): string
    {
        return match($this->nivelUrgencia) {
            'critica' => 'alert-triangle',
            'alta' => 'alert-circle',
            'media' => 'clock',
            default => 'info'
        };
    }

    private function obtenerColorPorUrgencia(): string
    {
        return match($this->nivelUrgencia) {
            'critica' => 'red',
            'alta' => 'orange',
            'media' => 'yellow',
            default => 'blue'
        };
    }
}
