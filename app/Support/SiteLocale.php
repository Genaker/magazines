<?php

namespace App\Support;

use App\Models\SiteSetting;
use InvalidArgumentException;

/** Site locale configuration: enabled languages, defaults, and normalization. */
class SiteLocale
{
    /** @var list<string>|null */
    private static ?array $enabledCache = null;

    private static ?string $defaultCache = null;

    /** Map legacy uk code to ua (ISO 3166-1 alpha-2). */
    public static function normalize(string $locale): string
    {
        return $locale === 'uk' ? 'ua' : $locale;
    }

    /**
     * All locale codes defined in config with display labels.
     *
     * @return list<string>
     */
    public static function available(): array
    {
        return array_keys(config('locales.labels', []));
    }

    /**
     * Locales currently enabled for the site (from settings or config fallback).
     *
     * @return list<string>
     */
    public static function enabled(): array
    {
        if (self::$enabledCache !== null) {
            return self::$enabledCache;
        }

        $stored = SiteSetting::getValue('site_locales_enabled'); // JSON array of locale codes

        if ($stored === null || $stored === '') {
            return self::$enabledCache = config('locales.supported', self::available());
        }

        $decoded = json_decode($stored, true); // invalid JSON falls back to config

        if (! is_array($decoded)) {
            return self::$enabledCache = config('locales.supported', self::available());
        }

        $enabled = array_values(array_filter(
            array_map(fn ($code) => is_string($code) ? self::normalize($code) : $code, $decoded),
            fn ($code) => is_string($code) && in_array($code, self::available(), true),
        )); // drop unknown codes

        return self::$enabledCache = $enabled !== [] ? $enabled : config('locales.supported', self::available());
    }

    /** Default locale for new visitors, validated against enabled list. */
    public static function default(): string
    {
        if (self::$defaultCache !== null) {
            return self::$defaultCache;
        }

        $enabled = self::enabled(); // current allowlist for locale switching
        $stored = SiteSetting::getValue('site_locale_default');

        $stored = $stored !== null && $stored !== '' ? self::normalize($stored) : null; // admin override

        if ($stored !== null && in_array($stored, $enabled, true)) {
            return self::$defaultCache = $stored;
        }

        $fallback = config('app.locale', 'en'); // config default when stored default is invalid

        return self::$defaultCache = in_array($fallback, $enabled, true) ? $fallback : $enabled[0];
    }

    /** Whether a locale code is enabled for the site. */
    public static function isEnabled(string $locale): bool
    {
        return in_array(self::normalize($locale), self::enabled(), true);
    }

    /** Whether the locale switcher should be shown (more than one locale enabled). */
    public static function isSwitchable(): bool
    {
        return count(self::enabled()) > 1;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return config('locales.labels', []);
    }

    /**
     * Persist the list of enabled locale codes (must be non-empty).
     *
     * @param list<string> $locales
     */
    public static function setEnabled(array $locales): void
    {
        $locales = array_values(array_unique(array_filter(
            array_map(fn ($code) => is_string($code) ? self::normalize($code) : $code, $locales),
            fn ($code) => is_string($code) && in_array($code, self::available(), true),
        )));

        if ($locales === []) {
            throw new InvalidArgumentException('At least one locale must be enabled.');
        }

        SiteSetting::setValue('site_locales_enabled', json_encode($locales));
        self::$enabledCache = null;
        self::$defaultCache = null;
    }

    /** Set the default locale (must be a known, enabled code). */
    public static function setDefault(string $locale): void
    {
        $locale = self::normalize($locale);

        if (! in_array($locale, self::available(), true)) {
            throw new InvalidArgumentException("Unknown locale [{$locale}].");
        }

        SiteSetting::setValue('site_locale_default', $locale);
        self::$defaultCache = null;
    }
}
