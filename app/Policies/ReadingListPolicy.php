<?php

namespace App\Policies;

use App\Models\ReadingList;
use App\Models\User;

class ReadingListPolicy
{
    public function view(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id;
    }

    public function update(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id;
    }

    public function delete(User $user, ReadingList $readingList): bool
    {
        return $user->id === $readingList->user_id && ! $readingList->is_default;
    }
}
