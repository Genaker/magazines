<?php

namespace App\Listeners;

use App\Events\AuthorSubscribed;
use App\Services\ActivityNotifier;

class NotifyAuthorOfSubscription
{
    public function handle(AuthorSubscribed $event): void
    {
        ActivityNotifier::notify(
            $event->author,
            $event->subscriber,
            'story_subscription',
        );
    }
}
