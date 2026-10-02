<?php

namespace App\Console\Commands;

use App\Models\SubscriptionDigestItem;
use App\Models\User;
use App\Notifications\AuthorStoryDigest;
use App\Support\NotificationSender;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendSubscriptionDigestCommand extends Command
{
    protected $signature = 'subscriptions:send-digest';

    protected $description = 'Email daily digests to subscribers with queued story alerts';

    public function handle(): int
    {
        if (! config('subscriptions.email_enabled')) {
            $this->info('Subscription emails are disabled.');

            return self::SUCCESS;
        }

        $subscriberIds = SubscriptionDigestItem::query()
            ->distinct()
            ->pluck('subscriber_id');

        foreach ($subscriberIds as $subscriberId) {
            $subscriber = User::query()
                ->whereKey($subscriberId)
                ->whereNotNull('email_verified_at')
                ->where('is_banned', false)
                ->first();

            if (! $subscriber) {
                SubscriptionDigestItem::query()->where('subscriber_id', $subscriberId)->delete();

                continue;
            }

            /** @var Collection<int, SubscriptionDigestItem> $items */
            $items = SubscriptionDigestItem::query()
                ->with(['post.user'])
                ->where('subscriber_id', $subscriberId)
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                continue;
            }

            $items = $items->filter(function (SubscriptionDigestItem $item) use ($subscriber) {
                $post = $item->post;
                $author = $post?->user;

                if (! $post || ! $author || ! $post->isPublished()) {
                    $item->delete();

                    return false;
                }

                if ($author->hasBlocked($subscriber) || $subscriber->hasBlocked($author)) {
                    $item->delete();

                    return false;
                }

                return true;
            });

            if ($items->isEmpty()) {
                continue;
            }

            NotificationSender::send($subscriber, new AuthorStoryDigest($items));

            SubscriptionDigestItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->delete();

            $this->line("Sent digest to {$subscriber->email} ({$items->count()} stories)");
        }

        return self::SUCCESS;
    }
}
