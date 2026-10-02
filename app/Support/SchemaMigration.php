<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Version-prefixed migration filenames (V00001_description). */
class SchemaMigration
{
    public static function prefix(): string
    {
        return (string) config('schema.prefix', 'V');
    }

    public static function pad(): int
    {
        return (int) config('schema.pad', 5);
    }

    public static function migrationsPath(): string
    {
        return database_path('migrations');
    }

    public static function formatBasename(int $version, string $name): string
    {
        $padded = str_pad((string) $version, self::pad(), '0', STR_PAD_LEFT);
        $snake = Str::snake(str_replace('-', '_', $name));

        return self::prefix().$padded.'_'.$snake;
    }

    public static function formatFilename(int $version, string $name): string
    {
        return self::formatBasename($version, $name).'.php';
    }

    /** @return list<int> */
    public static function definedVersions(): array
    {
        $pattern = self::migrationsPath().'/'.self::prefix().'*.php';
        $versions = [];

        foreach (glob($pattern) ?: [] as $file) {
            if (preg_match('/'.preg_quote(self::prefix(), '/').'(\d+)_/', basename($file), $matches)) {
                $versions[] = (int) $matches[1];
            }
        }

        sort($versions);

        return $versions;
    }

    public static function latestDefinedVersion(): int
    {
        $versions = self::definedVersions();

        return $versions === [] ? 0 : max($versions);
    }

    public static function nextVersion(): int
    {
        return self::latestDefinedVersion() + 1;
    }

    public static function displayVersion(?int $version = null): string
    {
        $version ??= self::latestDefinedVersion();

        return self::prefix().str_pad((string) $version, self::pad(), '0', STR_PAD_LEFT);
    }
}
