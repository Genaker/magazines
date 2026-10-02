<?php

namespace App\Services;

use App\Models\UserReport;
use App\Notifications\UserReportReceived;
use App\Notifications\UserReportSubmitted;
use App\Support\MailRecipient;
use App\Support\NotificationSender;

/** Email notifications for user reports. */
class UserReportNotifier
{
    public static function submitted(UserReport $report): void
    {
        $report->loadMissing(['reporter', 'reported']);

        AdminNotifier::notify(new UserReportSubmitted($report));

        if (MailRecipient::canReceiveEmail($report->reporter)) {
            NotificationSender::send($report->reporter, new UserReportReceived($report));
        }
    }
}
