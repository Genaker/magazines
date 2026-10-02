<?php

namespace App\Services;

use App\Enums\MagazineMemberRole;
use App\Models\Magazine;
use App\Models\MagazineJoinRequest;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MagazineJoinRequestApproved;
use App\Notifications\MagazineJoinRequestReceived;
use App\Notifications\MagazineJoinRequestRejected;
use App\Notifications\MagazineJoinRequestSubmitted;
use App\Notifications\MagazinePostApproved;
use App\Notifications\MagazinePostRejected;
use App\Notifications\MagazinePostSubmissionReceived;
use App\Notifications\MagazinePostSubmitted;
use App\Support\MailRecipient;
use App\Support\NotificationSender;
use Illuminate\Support\Collection;

/** Email notifications for magazine join requests and story submissions. */
class MagazineNotifier
{
    public static function joinRequestSubmitted(MagazineJoinRequest $joinRequest): void
    {
        $joinRequest->loadMissing(['user', 'magazine.owner']);

        NotificationSender::sendToMany(
            self::reviewers($joinRequest->magazine, excludeUserId: $joinRequest->user_id),
            new MagazineJoinRequestSubmitted($joinRequest),
        );

        if (MailRecipient::canReceiveEmail($joinRequest->user)) {
            NotificationSender::send($joinRequest->user, new MagazineJoinRequestReceived($joinRequest));
        }
    }

    public static function joinRequestApproved(MagazineJoinRequest $joinRequest, Magazine $magazine): void
    {
        $joinRequest->loadMissing('user');

        if (! MailRecipient::canReceiveEmail($joinRequest->user)) {
            return;
        }

        NotificationSender::send($joinRequest->user, new MagazineJoinRequestApproved($joinRequest, $magazine));
    }

    public static function joinRequestRejected(MagazineJoinRequest $joinRequest, Magazine $magazine): void
    {
        $joinRequest->loadMissing('user');

        if (! MailRecipient::canReceiveEmail($joinRequest->user)) {
            return;
        }

        NotificationSender::send($joinRequest->user, new MagazineJoinRequestRejected($joinRequest, $magazine));
    }

    public static function postSubmitted(Post $post): void
    {
        $post->loadMissing(['user', 'magazine.owner']);

        if ($post->magazine === null) {
            return;
        }

        NotificationSender::sendToMany(
            self::reviewers($post->magazine, excludeUserId: $post->user_id),
            new MagazinePostSubmitted($post),
        );

        if (MailRecipient::canReceiveEmail($post->user)) {
            NotificationSender::send($post->user, new MagazinePostSubmissionReceived($post));
        }
    }

    public static function postApproved(Post $post): void
    {
        $post->loadMissing(['user', 'magazine', 'authorAlias']);

        if ($post->magazine === null || ! MailRecipient::canReceiveEmail($post->user)) {
            return;
        }

        NotificationSender::send($post->user, new MagazinePostApproved($post));
    }

    public static function postRejected(Post $post): void
    {
        $post->loadMissing(['user', 'magazine']);

        if ($post->magazine === null || ! MailRecipient::canReceiveEmail($post->user)) {
            return;
        }

        NotificationSender::send($post->user, new MagazinePostRejected($post));
    }

    /** @return Collection<int, User> */
    public static function reviewers(Magazine $magazine, ?int $excludeUserId = null): Collection
    {
        $magazine->loadMissing('owner');

        $recipients = collect([$magazine->owner]);

        $recipients = $recipients->merge(
            $magazine->members()
                ->wherePivot('role', MagazineMemberRole::Editor->value)
                ->get(),
        );

        return $recipients
            ->unique('id')
            ->filter(fn (User $user) => MailRecipient::canReceiveEmail($user) && $user->id !== $excludeUserId)
            ->values();
    }
}
