<?php

namespace App\Support;

use App\Models\User;

final class MailRecipient
{
    public static function canReceiveEmail(User $user): bool
    {
        return $user->email_verified_at !== null && ! $user->is_banned;
    }
}
