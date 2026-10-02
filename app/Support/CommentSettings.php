<?php

namespace App\Support;

use App\Models\SiteSetting;

/** Resolves built-in vs Disqus comment configuration. */
class CommentSettings
{
    /** Whether Disqus replaces built-in comments (requires comments feature + shortname). */
    public static function useDisqus(): bool
    {
        if (! Features::enabled('comments')) {
            return false;
        }

        return filter_var(SiteSetting::getValue('comments_use_disqus', '0'), FILTER_VALIDATE_BOOLEAN)
            && self::shortname() !== null;
    }

    /** Configured Disqus shortname, or null when not set. */
    public static function shortname(): ?string
    {
        $shortname = trim((string) SiteSetting::getValue('disqus_shortname', '')); // empty means Disqus off

        return $shortname !== '' ? $shortname : null;
    }
}
