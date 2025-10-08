<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorEnabledNotification extends Notification
{
    use Queueable;

    protected string $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $methodName = match($this->method) {
            'totp' => 'Aplicación de Autenticación (TOTP)',
            'sms' => 'SMS',
            'email' => 'Correo Electrónico',
            default => $this->method,
        };

        return (new MailMessage)
            ->subject('Autenticación de Dos Factores Habilitada')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('La autenticación de dos factores ha sido habilitada en tu cuenta.')
            ->line('Método seleccionado: ' . $methodName)
            ->line('Desde ahora, se requerirá un código de verificación adicional al iniciar sesión.')
            ->action('Ver mi Perfil', url('/two-factor/settings'))
            ->line('Si no realizaste este cambio, contacta inmediatamente a nuestro equipo de soporte.')
            ->salutation('Equipo de ' . config('app.name'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'Autenticación de dos factores habilitada',
            'method' => $this->method,
            'timestamp' => now(),
        ];
    }
}
