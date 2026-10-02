<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Support\HomeLayout as HomeLayoutEnum;
use InvalidArgumentException;

/** Site name, tagline, footer, and PWA colors — config defaults with site_settings overrides. */
class SiteBranding
{
    public static function get(string $key): string
    {
        $definition = config("site.keys.{$key}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown site branding key [{$key}].");
        }

        $default = config('site.'.$definition['default']);

        return (string) (SiteSetting::getValue($definition['setting'], is_string($default) ? $default : '') ?? '');
    }

    public static function localizedTagline(): string
    {
        $stored = trim(static::get('tagline'));
        $defaultEn = trim((string) config('site.defaults.tagline'));

        if ($stored === '' || $stored === $defaultEn) {
            return __('app.site_tagline');
        }

        return $stored;
    }

    /** @param  array<string, string|null>  $values  Logical keys from config/site.php keys */
    public static function apply(array $values): void
    {
        foreach (config('site.keys', []) as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];
            if ($value === null || $value === '') {
                continue;
            }

            SiteSetting::setValue($definition['setting'], $value);
        }
    }

    /** Seed missing branding rows from config/site.php defaults (.env aware). */
    public static function seedDefaults(): void
    {
        foreach (config('site.keys', []) as $definition) {
            $settingKey = $definition['setting'];

            if (SiteSetting::query()->where('key', $settingKey)->exists()) {
                continue;
            }

            SiteSetting::setValue($settingKey, config('site.'.$definition['default']));
        }

        if (SiteSetting::query()->where('key', 'home_layout')->doesntExist()) {
            HomeLayoutEnum::set(HomeLayoutEnum::tryFrom(config('site.home_layout', 'discover')) ?? HomeLayoutEnum::Discover);
        }

        if (SiteSetting::query()->where('key', 'site_locales_enabled')->doesntExist()) {
            $enabled = config('site.enabled_locales', ['en']);

            if ($enabled === []) {
                $enabled = ['en'];
            }

            SiteLocale::setEnabled($enabled);
        }

        if (SiteSetting::query()->where('key', 'site_locale_default')->doesntExist()) {
            SiteLocale::setDefault(config('site.default_locale', 'en'));
        }
    }

    /** @return array<string, string> */
    public static function envSnapshot(): array
    {
        return [
            'SITE_NAME' => static::get('name'),
            'SITE_TAGLINE' => static::get('tagline'),
            'SITE_FOOTER_TAGLINE' => static::get('footer_tagline'),
            'SITE_FOOTER_RIGHTS' => static::get('footer_rights'),
            'SITE_COPYRIGHT_START' => static::get('copyright_start_year'),
            'SITE_THEME_COLOR' => static::get('theme_color'),
            'SITE_BACKGROUND_COLOR' => static::get('background_color'),
            'APP_NAME' => static::get('name'),
        ];
    }
}
