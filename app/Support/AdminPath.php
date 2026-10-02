<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** Configurable URL prefix for the admin interface (default: admin). */
final class AdminPath
{
    public const DEFAULT = 'admin';

    /** @var list<string> First path segments reserved for public site routes. */
    private const RESERVED_SITE_PREFIXES = [
        'api',
        'authors',
        'category',
        'discover',
        'install',
        'locale',
        'login',
        'magazine',
        'magazines',
        'me',
        'profile',
        'register',
        'search',
        'subscriptions',
        'tag',
        'tags',
        'up',
        'users',
        'write',
    ];

    public static function prefix(): string
    {
        try {
            return static::normalize(static::raw());
        } catch (InvalidArgumentException) {
            return self::DEFAULT;
        }
    }

    public static function usesDefault(): bool
    {
        return static::prefix() === self::DEFAULT;
    }

    public static function raw(): string
    {
        $fromSetting = SiteSetting::getPlatformValue('admin_path');

        if (is_string($fromSetting) && $fromSetting !== '') {
            return $fromSetting;
        }

        $fromConfig = config('site.admin_path');

        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        return self::DEFAULT;
    }

    public static function set(?string $prefix): void
    {
        if ($prefix === null || $prefix === '') {
            SiteSetting::setPlatformValue('admin_path', null);

            return;
        }

        $normalized = static::normalize($prefix);
        SiteSetting::setPlatformValue(
            'admin_path',
            $normalized === self::DEFAULT ? null : $normalized,
        );
    }

    public static function normalize(string $prefix): string
    {
        $prefix = strtolower(trim($prefix, '/'));

        if ($prefix === '') {
            throw new InvalidArgumentException('Admin path cannot be empty.');
        }

        if (strlen($prefix) < 2 || strlen($prefix) > 48) {
            throw new InvalidArgumentException('Admin path must be between 2 and 48 characters.');
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $prefix)) {
            throw new InvalidArgumentException('Admin path may only contain lowercase letters, numbers, and hyphens.');
        }

        if (in_array($prefix, self::RESERVED_SITE_PREFIXES, true)) {
            throw new InvalidArgumentException("Admin path [{$prefix}] conflicts with a public site route.");
        }

        return $prefix;
    }

    /** @return list<string> */
    public static function blockedPathSegments(): array
    {
        return array_values(array_unique(array_merge(
            self::RESERVED_SITE_PREFIXES,
            [static::prefix()],
        )));
    }

    /** @return list<string> */
    public static function reservedSitePrefixes(): array
    {
        return self::RESERVED_SITE_PREFIXES;
    }

    public static function appliesTo(Request $request): bool
    {
        $prefix = static::prefix();

        return $request->is($prefix, $prefix.'/*');
    }

    public static function cookiePath(): string
    {
        return '/'.static::prefix();
    }

    public static function syncSessionConfig(): void
    {
        config(['session.admin_path' => static::cookiePath()]);
    }

    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path === ''
            ? url(static::prefix())
            : url(static::prefix().'/'.$path);
    }
}
