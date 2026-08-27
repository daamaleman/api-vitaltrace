<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetToken extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly int $validityMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Restablece tu contraseña de VitalTrace')
            ->greeting('Recuperación de contraseña')
            ->line('Usa el siguiente token en la aplicación VitalTrace:')
            ->line($this->token)
            ->line("Este token expira en {$this->validityMinutes} minutos y sólo puede usarse una vez.")
            ->line('Si no solicitaste este cambio, ignora este correo.')
            ->salutation('Saludos, el equipo de VitalTrace');
    }
}
