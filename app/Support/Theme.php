<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

/** Resolves templates/{name}/ view overrides ahead of resources/views. */
class Theme
{
    /** Register the active theme view path with Laravel's finder (safe to call multiple times). */
    public static function register(): void
    {
        if ($path = self::path()) {
            View::getFinder()->prependLocation($path);
        }
    }

    /** Active theme slug, or null when using default views only. */
    public static function name(): ?string
    {
        $name = config('theme.name');

        if (! is_string($name) || $name === '' || $name === 'default') {
            return null;
        }

        return self::isValidName($name) ? $name : null;
    }

    /** Absolute path to the active theme folder, or null when unavailable. */
    public static function path(): ?string
    {
        $name = self::name();

        if ($name === null) {
            return null;
        }

        $path = base_path('templates/'.$name);

        return is_dir($path) ? $path : null;
    }

    /** Whether a named theme exists on disk (regardless of active config). */
    public static function exists(string $name): bool
    {
        return self::isValidName($name) && is_dir(base_path('templates/'.$name));
    }

    /** @return list<string> Theme folder names under templates/ (sorted). */
    public static function available(): array
    {
        $root = base_path('templates');

        if (! is_dir($root)) {
            return [];
        }

        $names = [];

        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (self::isValidName($entry) && is_dir($root.'/'.$entry)) {
                $names[] = $entry;
            }
        }

        sort($names);

        return $names;
    }

    private static function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_-]*$/', $name);
    }
}
