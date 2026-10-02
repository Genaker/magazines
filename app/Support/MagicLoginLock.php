<?php

namespace App\Support;

use App\Models\User;

/** Tracks failed magic-link attempts and locks accounts until an admin unlocks them. */
final class MagicLoginLock
{
    public static function maxAttempts(): int
    {
        return max(1, (int) config('magic-login.max_failed_attempts', 5));
    }

    public static function isLocked(User $user): bool
    {
        return $user->magic_login_locked_at !== null;
    }

    public static function recordFailure(User $user): void
    {
        if (static::isLocked($user)) {
            return;
        }

        $attempts = $user->magic_login_failed_attempts + 1;

        $user->forceFill([
            'magic_login_failed_attempts' => $attempts,
            'magic_login_locked_at' => $attempts >= static::maxAttempts() ? now() : null,
        ])->save();
    }

    public static function clear(User $user): void
    {
        if ($user->magic_login_failed_attempts === 0 && $user->magic_login_locked_at === null) {
            return;
        }

        $user->forceFill([
            'magic_login_failed_attempts' => 0,
            'magic_login_locked_at' => null,
        ])->save();
    }

    public static function unlock(User $user): void
    {
        static::clear($user);
    }
}
