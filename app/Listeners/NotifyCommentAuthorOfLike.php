<?php

namespace App\Listeners;

use App\Events\CommentLiked;
use App\Services\ActivityNotifier;

class NotifyCommentAuthorOfLike
{
    public function handle(CommentLiked $event): void
    {
        $comment = $event->comment->loadMissing('user', 'post.user');

        ActivityNotifier::notify(
            $comment->user,
            $event->liker,
            'comment_like',
            $comment->post,
            $comment,
        );
    }
}
