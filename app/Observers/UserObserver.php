<?php

namespace App\Observers;

use App\Models\User;
use App\Support\EntityCache;

/** Invalidates author stats cache when user profiles change. */
class UserObserver
{
    /** Invalidate caches after a user is created or updated. */
    public function saved(User $user): void
    {
        $this->flushCaches($user);
    }

    /** Invalidate caches after a user is soft-deleted. */
    public function deleted(User $user): void
    {
        $this->flushCaches($user);
    }

    /** Invalidate caches after a soft-deleted user is restored. */
    public function restored(User $user): void
    {
        $this->flushCaches($user);
    }

    /** Invalidate caches after a user is permanently removed. */
    public function forceDeleted(User $user): void
    {
        $this->flushCaches($user);
    }

    /** Drop per-user stats cache and flush the authors tag. */
    private function flushCaches(User $user): void
    {
        EntityCache::forget(EntityCache::key('authors', 'stats', $user->id));
        EntityCache::flushTag(config('entity-cache.tags.authors'));
    }
}
