<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Services\ActivityNotifier;
use App\Support\CommentMentions;

class NotifyCommentParticipants
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment->loadMissing('user', 'post.user', 'parent.user');
        $post = $comment->post;
        $actor = $comment->user;

        if ($comment->parent_id && $comment->parent) {
            ActivityNotifier::notify($comment->parent->user, $actor, 'comment_reply', $post, $comment);
        } else {
            ActivityNotifier::notify($post->user, $actor, 'comment', $post, $comment);
        }

        $skipMentionIds = collect([$actor->id]);
        if ($comment->parent_id && $comment->parent) {
            $skipMentionIds->push($comment->parent->user->id);
        } else {
            $skipMentionIds->push($post->user->id);
        }

        foreach (CommentMentions::resolveUsers($comment->body) as $mentionedUser) {
            if ($skipMentionIds->contains($mentionedUser->id)) {
                continue;
            }

            ActivityNotifier::notify($mentionedUser, $actor, 'comment_mention', $post, $comment);
        }
    }
}
