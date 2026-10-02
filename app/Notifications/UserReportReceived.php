<?php

namespace App\Notifications;

use App\Models\UserReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReportReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserReport $report) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notification.user_report_received_subject'))
            ->line(__('notification.user_report_received_line', [
                'username' => $this->report->reported->username,
            ]));
    }
}
