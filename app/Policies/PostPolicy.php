<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(?User $user, Post $post): bool
    {
        if ($user && $user->id !== $post->user_id && $post->user->hasBlocked($user)) {
            return false;
        }

        if ($post->status === PostStatus::Published) {
            if ($post->published_at === null) {
                return false;
            }

            if ($post->published_at->isFuture()) {
                return $user && ($user->id === $post->user_id || $user->isAdmin());
            }

            return true;
        }

        if ($post->status === PostStatus::Unlisted) {
            return true;
        }

        if ($post->status === PostStatus::Draft) {
            return $user && ($user->id === $post->user_id || $user->isAdmin());
        }

        return $user && ($user->id === $post->user_id || $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return ! $user->is_banned;
    }

    public function update(User $user, Post $post): bool
    {
        return ! $user->is_banned && ($user->id === $post->user_id || $user->isAdmin());
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function pin(User $user, Post $post): bool
    {
        return ! $user->is_banned
            && $user->id === $post->user_id
            && $post->status === PostStatus::Published
            && $post->published_at !== null;
    }
}
