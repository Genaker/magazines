<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;

/** Notifies author subscribers for posts whose scheduled publish time has passed. */
class ScheduledPublicationService
{
    /**
     * Find published posts that are due but not yet notified, and email subscribers.
     *
     * Processes at most 25 posts per run to avoid long-running cron jobs.
     */
    public function notifyDuePosts(): void
    {
        // Scheduled posts past published_at that haven't triggered subscriber emails yet
        Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('subscription_notified_at')
            ->limit(25)
            ->get()
            ->each(function (Post $post): void {
                app(AuthorSubscriptionNotifier::class)->notifyForPublishedPost($post);
                // Mark notified without re-firing model events
                $post->updateQuietly(['subscription_notified_at' => now()]);
            });
    }
}
