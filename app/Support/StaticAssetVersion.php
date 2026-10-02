<?php

namespace App\Support;

/** Cache-busting version string appended to static asset URLs. */
class StaticAssetVersion
{
    /** Path to the static-asset version file on disk. */
    public static function path(): string
    {
        return base_path('static-asset-version');
    }

    /** Read the current version string, defaulting to "1" when missing. */
    public static function get(): string
    {
        $path = static::path();

        if (! is_file($path)) {
            return '1';
        }

        $value = trim((string) file_get_contents($path));

        return $value !== '' ? $value : '1';
    }

    /** Increment and persist the version counter (used by deploy/artisan commands). */
    public static function bump(): string
    {
        $next = (string) ((int) static::get() + 1);
        file_put_contents(static::path(), $next.PHP_EOL);

        return $next;
    }

    /** Append ?v= or &v= query param for browser cache invalidation. */
    public static function append(string $url): string
    {
        $version = static::get();

        if ($version === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.rawurlencode($version);
    }
}
