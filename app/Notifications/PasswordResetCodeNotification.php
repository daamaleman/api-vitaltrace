<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the six-digit password reset code by email.
 * The plain code is used only for delivery; only the hash is persisted.
 */
class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $validityHours,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Código para restablecer tu contraseña de VitalTrace')
            ->greeting('Restablecer contraseña')
            ->line('Usa el siguiente código para restablecer tu contraseña:')
            ->line($this->code)
            ->line("Este código expira en {$this->validityHours} horas y solo puede usarse una vez.")
            ->line('Si no solicitaste esto, ignora este correo y tu contraseña seguirá igual.')
            ->salutation('Saludos, el equipo de VitalTrace');
    }
}
