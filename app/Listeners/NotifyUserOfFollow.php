<?php

namespace App\Listeners;

use App\Events\UserFollowed;
use App\Services\ActivityNotifier;

class NotifyUserOfFollow
{
    public function handle(UserFollowed $event): void
    {
        ActivityNotifier::notify($event->followed, $event->follower, 'follow');
    }
}
