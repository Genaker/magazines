<?php

namespace App\Support;

use App\Models\AuthorAlias;

/** Turns @username mentions in comment bodies into author profile links. */
class CommentFormatter
{
    /** @var array<string, AuthorAlias|null> */
    private static array $aliasCache = [];

    /** Escape HTML and link recognized @mentions to author profiles. */
    public static function format(string $body): string
    {
        $escaped = e($body);

        return (string) preg_replace_callback(
            '/@([a-zA-Z0-9_]+)/',
            function (array $matches): string {
                $username = $matches[1];

                if (! array_key_exists($username, self::$aliasCache)) {
                    self::$aliasCache[$username] = AuthorAlias::query()
                        ->active()
                        ->where('username', $username)
                        ->first();
                }

                $alias = self::$aliasCache[$username];

                if (! $alias) {
                    return '@'.e($username);
                }

                $url = route('authors.show', $alias);

                return '<a href="'.e($url).'" class="font-medium text-indigo-700 hover:underline">@'.e($alias->username).'</a>';
            },
            $escaped,
        );
    }

    /** Clear the per-request alias lookup cache (useful in long-running tests). */
    public static function flushCache(): void
    {
        self::$aliasCache = [];
    }
}
