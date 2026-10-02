<?php

namespace App\Enums;

enum MagazineMemberRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Writer = 'writer';

    public function canEditMagazine(): bool
    {
        return in_array($this, [self::Owner, self::Editor], true);
    }

    public function canReviewSubmissions(): bool
    {
        return in_array($this, [self::Owner, self::Editor], true);
    }

    public function canSubmit(): bool
    {
        return true;
    }
}
