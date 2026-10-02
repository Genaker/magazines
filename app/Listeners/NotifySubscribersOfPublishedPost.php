<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Services\AuthorSubscriptionNotifier;

class NotifySubscribersOfPublishedPost
{
    public function __construct(private AuthorSubscriptionNotifier $notifier) {}

    public function handle(PostPublished $event): void
    {
        $this->notifier->notifyForPublishedPost($event->post);
        $event->post->updateQuietly(['subscription_notified_at' => now()]);
    }
}
