<?php

/**
 * Sync Docker container environment into .env with safe quoting.
 * Called from docker/entrypoint.sh on container start.
 */

$path = getcwd().'/.env';

$values = [
    'MAIL_HOST' => envOr('MAIL_HOST', 'mailpit'),
    'MAIL_PORT' => envOr('MAIL_PORT', '1025'),
    'MAIL_MAILER' => envOr('MAIL_MAILER', 'smtp'),
    'MAIL_FROM_ADDRESS' => envOr('MAIL_FROM_ADDRESS', 'hello@magazines.test'),
    'MAILPIT_WEB_URL' => envOr('MAILPIT_WEB_URL', 'http://127.0.0.1:8025'),
    'REDIS_HOST' => envOr('REDIS_HOST', 'redis'),
    'REDIS_PORT' => envOr('REDIS_PORT', '6379'),
    'CACHE_STORE' => envOr('CACHE_STORE', 'file'),
    'ENTITY_CACHE_STORE' => envOr('ENTITY_CACHE_STORE', 'file'),
    'SESSION_DRIVER' => envOr('SESSION_DRIVER', 'file'),
    'SEARCH_DRIVER' => envOr('SEARCH_DRIVER', 'redis'),
    'DB_HOST' => envOr('DB_HOST', 'mariadb'),
    'DB_PORT' => envOr('DB_PORT', '3306'),
    'DB_DATABASE' => envOr('DB_DATABASE', 'magazines'),
    'MEDIA_DISK' => envOr('MEDIA_DISK', 'public'),
    'SITE_NAME' => envOr('SITE_NAME', 'Magazines'),
    'APP_NAME' => envOr('APP_NAME', envOr('SITE_NAME', 'Magazines')),
    'SITE_TAGLINE' => envOr('SITE_TAGLINE', 'Local communities, publishers, and bloggers — with integrated AI writing assistants.'),
    'SITE_FOOTER_TAGLINE' => envOr('SITE_FOOTER_TAGLINE', 'Platform for a free society'),
    'SITE_FOOTER_RIGHTS' => envOr('SITE_FOOTER_RIGHTS', 'All rights reserved.'),
    'SITE_COPYRIGHT_START' => envOr('SITE_COPYRIGHT_START', '2014'),
    'SITE_THEME_COLOR' => envOr('SITE_THEME_COLOR', '#111827'),
    'SITE_BACKGROUND_COLOR' => envOr('SITE_BACKGROUND_COLOR', '#f3f4f6'),
    'SITE_HOME_LAYOUT' => envOr('SITE_HOME_LAYOUT', 'discover'),
    'SITE_DEFAULT_LOCALE' => envOr('SITE_DEFAULT_LOCALE', 'en'),
    'SITE_ENABLED_LOCALES' => envOr('SITE_ENABLED_LOCALES', 'en,ua'),
    'APP_URL' => envOr('APP_URL', 'http://lvh.me:8888'),
    'SESSION_DOMAIN' => envOr('SESSION_DOMAIN', ''),
];

if (! is_file($path)) {
    $example = is_file(getcwd().'/.env.docker.example')
        ? getcwd().'/.env.docker.example'
        : getcwd().'/.env.example';
    if (! is_file($example)) {
        fwrite(STDERR, "Missing .env and .env.example\n");
        exit(1);
    }
    copy($example, $path);
}

$content = file_get_contents($path);
if ($content === false) {
    fwrite(STDERR, "Unable to read .env\n");
    exit(1);
}

foreach ($values as $key => $value) {
    $line = $key.'='.formatValue($value);
    $pattern = '/^'.preg_quote($key, '/').'=.*/m';

    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $line, $content);
    } else {
        $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
    }
}

if (file_put_contents($path, $content) === false) {
    fwrite(STDERR, "Unable to write .env\n");
    exit(1);
}

function envOr(string $key, string $default): string
{
    $value = getenv($key);

    return ($value !== false && $value !== '') ? $value : $default;
}

function formatValue(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (preg_match('/[\s#="\']/', $value)) {
        return '"'.str_replace('"', '\\"', $value).'"';
    }

    return $value;
}
