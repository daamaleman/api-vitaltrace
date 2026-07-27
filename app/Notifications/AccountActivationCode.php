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
            ->subject('Your VitalTrace activation code')
            ->greeting('Welcome to VitalTrace')
            ->line('Use the following code to activate your account:')
            ->line($this->code)
            ->line("This code expires in {$this->validityHours} hours and can be used only once.")
            ->line('If you did not request this, please ignore this email.');
    }
}
