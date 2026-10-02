<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Support\Collection;

/** Parses @username tokens and resolves them to User models via active aliases. */
class CommentMentions
{
    /**
     * Extract unique @username tokens from comment body text.
     *
     * @return array<int, string>
     */
    public static function extractUsernames(string $body): array
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Resolve @mentions in body text to User models via active author aliases.
     *
     * @return Collection<int, User>
     */
    public static function resolveUsers(string $body): Collection
    {
        $usernames = static::extractUsernames($body);

        if ($usernames === []) {
            return collect();
        }

        return AuthorAlias::query()
            ->active()
            ->whereIn('username', $usernames)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }
}
