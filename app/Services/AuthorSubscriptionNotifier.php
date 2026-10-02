<?php

namespace App\Services;

use App\Enums\AuthorSubscriptionDelivery;
use App\Models\Post;
use App\Models\SubscriptionDigestItem;
use App\Models\User;
use App\Notifications\AuthorStoryPublished;
use App\Support\NotificationSender;

/** Delivers new-post notifications to verified author subscribers. */
class AuthorSubscriptionNotifier
{
    /**
     * Notify each eligible subscriber according to their email delivery preference.
     *
     * Skips the author, blocked users, and unverified or banned accounts.
     */
    public function notifyForPublishedPost(Post $post): void
    {
        if (! config('subscriptions.email_enabled')) {
            return;
        }

        $post->loadMissing('user');
        $author = $post->user; // story owner whose subscribers we notify

        $author->storySubscribers()
            ->whereNotNull('email_verified_at')
            ->where('is_banned', false)
            ->each(function (User $subscriber) use ($post, $author) {
                if ($subscriber->id === $author->id) {
                    return; // Never notify the author about their own post
                }

                if ($author->hasBlocked($subscriber) || $subscriber->hasBlocked($author)) {
                    return; // Respect block lists
                }

                // Per-subscriber preference from the pivot (instant, daily digest, or off)
                $delivery = AuthorSubscriptionDelivery::tryFrom(
                    $subscriber->pivot->email_delivery ?? config('subscriptions.default_delivery', 'instant'),
                ) ?? AuthorSubscriptionDelivery::Instant;

                match ($delivery) {
                    AuthorSubscriptionDelivery::Instant => NotificationSender::send(
                        $subscriber,
                        new AuthorStoryPublished($post, $author),
                    ),
                    AuthorSubscriptionDelivery::Daily => SubscriptionDigestItem::query()->firstOrCreate([
                        'subscriber_id' => $subscriber->id,
                        'post_id' => $post->id,
                    ]),
                    AuthorSubscriptionDelivery::Off => null,
                };
            });
    }
}
