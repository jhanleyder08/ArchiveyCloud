<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorDisabledNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Autenticación de Dos Factores Deshabilitada')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('La autenticación de dos factores ha sido deshabilitada en tu cuenta.')
            ->line('⚠️ Esto reduce el nivel de seguridad de tu cuenta.')
            ->line('Si no realizaste este cambio, tu cuenta podría estar comprometida.')
            ->action('Habilitar 2FA Nuevamente', url('/two-factor/settings'))
            ->line('Si tienes dudas, contacta a nuestro equipo de soporte.')
            ->salutation('Equipo de ' . config('app.name'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'Autenticación de dos factores deshabilitada',
            'warning' => 'Nivel de seguridad reducido',
            'timestamp' => now(),
        ];
    }
}
