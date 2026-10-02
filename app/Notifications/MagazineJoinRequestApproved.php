<?php

namespace App\Notifications;

use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Support\MagazineSubdomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagazineJoinRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MagazineJoinRequest $joinRequest,
        public Magazine $magazine,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notification.magazine_join_request_approved_subject', ['magazine' => $this->magazine->name]))
            ->line(__('notification.magazine_join_request_approved_line', ['magazine' => $this->magazine->name]))
            ->action(
                __('notification.view_magazine'),
                MagazineSubdomain::canonicalMagazineUrl($this->magazine),
            );
    }
}
