<?php

namespace App\Enums;

enum MagazineSubmissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
