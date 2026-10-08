<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the six-digit two-factor authentication code by email.
 * The plain code is used only for delivery; only the hash is persisted.
 */
class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $validityMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Codigo de verificacion de VitalTrace')
            ->greeting('Verificacion en dos pasos')
            ->line('Usa el siguiente codigo para completar tu inicio de sesion:')
            ->line($this->code)
            ->line("Este codigo expira en {$this->validityMinutes} minutos y solo puede usarse una vez.")
            ->line('Si no intentaste iniciar sesion, cambia tu contrasena de inmediato.')
            ->salutation('Saludos, el equipo de VitalTrace');
    }
}