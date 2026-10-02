<?php

namespace App\Enums;

enum MagazineJoinRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
