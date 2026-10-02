<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AuthorSubscribed
{
    use Dispatchable;

    public function __construct(
        public User $author,
        public User $subscriber,
    ) {}
}
