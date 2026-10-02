<?php

namespace App\Support;

use RuntimeException;

/** Safely update whitelisted keys in the project .env file. */
class EnvWriter
{
    /** @var list<string> */
    private const ALLOWED_KEYS = [
        'APP_NAME',
        'APP_URL',
        'SITE_NAME',
        'SITE_TAGLINE',
        'SITE_FOOTER_TAGLINE',
        'SITE_FOOTER_RIGHTS',
        'SITE_COPYRIGHT_START',
        'SITE_THEME_COLOR',
        'SITE_BACKGROUND_COLOR',
        'SITE_HOME_LAYOUT',
        'SITE_DEFAULT_LOCALE',
        'SITE_ENABLED_LOCALES',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'REDIS_HOST',
        'REDIS_PORT',
        'REDIS_PASSWORD',
        'CACHE_STORE',
        'SESSION_DRIVER',
        'ENTITY_CACHE_STORE',
        'SEARCH_DRIVER',
    ];

    /** @param  array<string, string|null>  $values */
    public static function set(array $values): void
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            $example = base_path('.env.example');
            if (! is_file($example)) {
                throw new RuntimeException('Missing .env and .env.example files.');
            }
            copy($example, $path);
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read .env file.');
        }

        foreach ($values as $key => $value) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                throw new RuntimeException("Env key [{$key}] is not allowed.");
            }

            $line = $key.'='.self::formatValue($value ?? '');
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('Unable to write .env file.');
        }
    }

    private static function formatValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#="\']/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
