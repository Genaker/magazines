<?php

namespace App\Enums;

enum UserReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';
}
