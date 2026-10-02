<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    public function create(?User $user, Post $post): bool
    {
        if (! $user || $user->is_banned) {
            return false;
        }

        if (! $post->user->allow_comments) {
            return false;
        }

        if ($post->user->hasBlocked($user)) {
            return false;
        }

        return $user->can('view', $post);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return ! $user->is_banned && (
            $user->id === $comment->user_id
            || $user->id === $comment->post->user_id
            || $user->isAdmin()
        );
    }

    public function update(User $user, Comment $comment): bool
    {
        return ! $user->is_banned && $user->id === $comment->user_id;
    }
}
