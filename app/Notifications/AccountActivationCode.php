<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers the six-digit account activation code by email.
 *
 * The plain code is passed in memory only for delivery; it is never persisted
 * (RN-10). The stored record keeps just the hash.
 */
class AccountActivationCode extends Notification
{
    use Queueable;

    /**
     * @param  string  $code  Plain six-digit activation code (delivery only).
     * @param  int  $validityHours  Validity window shown to the user.
     */
    public function __construct(
        private readonly string $code,
        private readonly int $validityHours,
    ) {}

    /**
     * Delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Tu código de activación de VitalTrace')
            ->greeting('Bienvenido a VitalTrace')
            ->line('Usa el siguiente código para activar tu cuenta:')
            ->line($this->code)
            ->line("Este código expira en {$this->validityHours} horas y solo puede usarse una vez.")
            ->line('Si no solicitaste esto, ignora este correo.')
            ->salutation('Saludos, el equipo de VitalTrace');
    }
}
