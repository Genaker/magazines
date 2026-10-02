<?php

namespace App\Notifications;

use App\Models\MagazineJoinRequest;
use App\Support\SiteUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagazineJoinRequestSubmitted extends Notification implements ShouldQueue
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
        $applicant = $this->joinRequest->user;

        $message = (new MailMessage)
            ->subject(__('notification.magazine_join_request_submitted_subject', ['magazine' => $magazine->name]))
            ->line(__('notification.magazine_join_request_submitted_line', [
                'name' => $applicant->name,
                'username' => $applicant->username,
                'magazine' => $magazine->name,
            ]));

        if (filled($this->joinRequest->message)) {
            $message->line(__('notification.magazine_join_request_message', ['message' => $this->joinRequest->message]));
        }

        return $message->action(
            __('notification.review_submissions'),
            SiteUrl::mainRoute('magazines.submissions', $magazine),
        );
    }
}
