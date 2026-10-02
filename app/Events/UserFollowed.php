<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserFollowed
{
    use Dispatchable;

    public function __construct(
        public User $followed,
        public User $follower,
    ) {}
}
