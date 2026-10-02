<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use App\Notifications\UserActivity;

/** Sends in-app activity notifications while respecting blocks and self-actions. */
class ActivityNotifier
{
    /**
     * Notify a user about an activity performed by another user.
     *
     * No-op when recipient equals actor or either party has blocked the other.
     */
    public static function notify(
        User $recipient,
        User $actor,
        string $kind,
        ?Post $post = null,
        ?Comment $comment = null,
        ?Magazine $magazine = null,
    ): void {
        if ($recipient->id === $actor->id) {
            return;
        }

        if ($recipient->hasBlocked($actor) || $actor->hasBlocked($recipient)) {
            return;
        }

        $recipient->notify(new UserActivity($kind, $actor, $post, $comment, $magazine));
    }
}
