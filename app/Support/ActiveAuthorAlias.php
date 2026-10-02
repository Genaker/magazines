<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Models\User;

/** Resolves and stores the active author alias for multi-alias users. */
class ActiveAuthorAlias
{
    private const string SESSION_KEY = 'active_author_alias_id';

    /** Return the session alias or fall back to the user's primary alias. */
    public static function resolve(User $user): AuthorAlias
    {
        $aliasId = session(self::SESSION_KEY);

        if ($aliasId) {
            $alias = $user->authorAliases()->whereKey($aliasId)->first();

            if ($alias) {
                return $alias;
            }
        }

        $primary = $user->primaryAlias();

        session([self::SESSION_KEY => $primary->id]);

        return $primary;
    }

    /** Switch the session to a different alias owned by the user. */
    public static function set(User $user, AuthorAlias $alias): void
    {
        abort_unless($alias->user_id === $user->id, 403);

        session([self::SESSION_KEY => $alias->id]);
    }

    /** Clear the active alias from the session (e.g. on logout). */
    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
