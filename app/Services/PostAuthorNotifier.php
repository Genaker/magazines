<?php

namespace App\Services;

use App\Models\Post;
use App\Notifications\AuthorPostPublished;
use App\Support\MailRecipient;
use App\Support\NotificationSender;

/** Email confirmations when an author's story is published. */
class PostAuthorNotifier
{
    public static function published(Post $post): void
    {
        $post->loadMissing(['user', 'authorAlias', 'magazine']);

        if (! MailRecipient::canReceiveEmail($post->user)) {
            return;
        }

        NotificationSender::send($post->user, new AuthorPostPublished($post));
    }
}
