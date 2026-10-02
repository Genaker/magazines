<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginLink extends Notification
{
    use Queueable;

    public function __construct(
        private string $url,
        private string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your sign-in link')
            ->line('Use this one-time sign-in code (expires in 15 minutes):')
            ->line('# '.$this->code)
            ->line('Or click the button below to sign in from this device.')
            ->action('Sign in', $this->url)
            ->line('If you did not request this, you can ignore this email.');
    }
}
