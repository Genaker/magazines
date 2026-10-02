<?php

namespace App\Notifications;

use App\Models\MagazineJoinRequest;
use App\Support\MagazineSubdomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagazineJoinRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MagazineJoinRequest $joinRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $magazine = $this->joinRequest->magazine;

        return (new MailMessage)
            ->subject(__('notification.magazine_join_request_received_subject', ['magazine' => $magazine->name]))
            ->line(__('notification.magazine_join_request_received_line', ['magazine' => $magazine->name]))
            ->action(
                __('notification.view_magazine'),
                MagazineSubdomain::canonicalMagazineUrl($magazine),
            );
    }
}
