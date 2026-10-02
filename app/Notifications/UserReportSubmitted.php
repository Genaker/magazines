<?php

namespace App\Notifications;

use App\Models\UserReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReportSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserReport $report) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reporter = $this->report->reporter;
        $reported = $this->report->reported;

        return (new MailMessage)
            ->subject(__('notification.user_report_submitted_subject', ['username' => $reported->username]))
            ->line(__('notification.user_report_submitted_line', [
                'reporter' => $reporter->name,
                'username' => $reported->username,
            ]))
            ->line(__('notification.user_report_reason', ['reason' => $this->report->reason]))
            ->action(__('notification.review_user_reports'), route('admin.user-reports.index'));
    }
}
