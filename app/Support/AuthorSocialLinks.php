<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Models\User;

/** Normalizes and resolves author social / crowdfunding profile links. */
final class AuthorSocialLinks
{
    public const GROUP_SOCIAL = 'social';

    public const GROUP_CROWDFUNDING = 'crowdfunding';

    /** @return array<string, array{label: string, group: string, type: string, url?: string}> */
    public static function platforms(): array
    {
        return [
            'instagram' => ['label' => 'Instagram', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://instagram.com/%s'],
            'facebook' => ['label' => 'Facebook', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://facebook.com/%s'],
            'linkedin' => ['label' => 'LinkedIn', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://linkedin.com/in/%s'],
            'github' => ['label' => 'GitHub', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://github.com/%s'],
            'youtube' => ['label' => 'YouTube', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://youtube.com/@%s'],
            'mastodon' => ['label' => 'Mastodon', 'group' => self::GROUP_SOCIAL, 'type' => 'url'],
            'bluesky' => ['label' => 'Bluesky', 'group' => self::GROUP_SOCIAL, 'type' => 'username', 'url' => 'https://bsky.app/profile/%s'],
            'patreon' => ['label' => 'Patreon', 'group' => self::GROUP_CROWDFUNDING, 'type' => 'username', 'url' => 'https://patreon.com/%s'],
            'ko_fi' => ['label' => 'Ko-fi', 'group' => self::GROUP_CROWDFUNDING, 'type' => 'username', 'url' => 'https://ko-fi.com/%s'],
            'buy_me_a_coffee' => ['label' => 'Buy Me a Coffee', 'group' => self::GROUP_CROWDFUNDING, 'type' => 'username', 'url' => 'https://buymeacoffee.com/%s'],
            'github_sponsors' => ['label' => 'GitHub Sponsors', 'group' => self::GROUP_CROWDFUNDING, 'type' => 'username', 'url' => 'https://github.com/sponsors/%s'],
            'liberapay' => ['label' => 'Liberapay', 'group' => self::GROUP_CROWDFUNDING, 'type' => 'username', 'url' => 'https://liberapay.com/%s'],
        ];
    }

    /** @return array<string, array{nullable, string, string, max:255}> */
    public static function validationRules(): array
    {
        $rules = ['social_links' => ['nullable', 'array']];

        foreach (array_keys(static::platforms()) as $key) {
            $rules["social_links.{$key}"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /** @param  array<string, mixed>|null  $input */
    public static function normalize(?array $input): array
    {
        $normalized = [];

        foreach (static::platforms() as $key => $platform) {
            $raw = trim((string) ($input[$key] ?? ''));

            if ($raw === '') {
                continue;
            }

            if ($platform['type'] === 'url') {
                $url = static::normalizeUrl($raw);

                if ($url !== null) {
                    $normalized[$key] = $url;
                }

                continue;
            }

            $value = static::normalizeUsername($raw);

            if ($value !== null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return list<array{key: string, label: string, group: string, href: string, display: string}>
     */
    public static function forAuthor(AuthorAlias $author, User $account): array
    {
        $stored = $author->social_links ?? $account->social_links ?? [];
        $links = [];

        foreach (static::normalize(is_array($stored) ? $stored : []) as $key => $value) {
            $platform = static::platforms()[$key] ?? null;

            if ($platform === null) {
                continue;
            }

            $href = $platform['type'] === 'url'
                ? $value
                : sprintf($platform['url'], rawurlencode($value));

            $links[] = [
                'key' => $key,
                'label' => $platform['label'],
                'group' => $platform['group'],
                'href' => $href,
                'display' => $platform['type'] === 'url' ? parse_url($href, PHP_URL_HOST) ?? $platform['label'] : $value,
            ];
        }

        return $links;
    }

    /** @return list<array{key: string, label: string, group: string, href: string, display: string}> */
    public static function legacyLinks(?string $website, ?string $twitterHandle): array
    {
        $links = [];

        if (filled($website)) {
            $links[] = [
                'key' => 'website',
                'label' => __('app.website'),
                'group' => self::GROUP_SOCIAL,
                'href' => $website,
                'display' => __('app.website'),
            ];
        }

        if (filled($twitterHandle)) {
            $links[] = [
                'key' => 'twitter',
                'label' => 'X / Twitter',
                'group' => self::GROUP_SOCIAL,
                'href' => 'https://twitter.com/'.ltrim($twitterHandle, '@'),
                'display' => '@'.ltrim($twitterHandle, '@'),
            ];
        }

        return $links;
    }

    private static function normalizeUsername(string $raw): ?string
    {
        $raw = ltrim(trim($raw), '@');

        if ($raw === '') {
            return null;
        }

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($raw, PHP_URL_PATH), '/');
            $segment = str_contains($path, '/') ? substr($path, strrpos($path, '/') + 1) : $path;

            return $segment !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $segment) === 1
                ? $segment
                : null;
        }

        return preg_match('/^[A-Za-z0-9._-]+$/', $raw) === 1 ? $raw : null;
    }

    private static function normalizeUrl(string $raw): ?string
    {
        if (! str_starts_with($raw, 'http://') && ! str_starts_with($raw, 'https://')) {
            $raw = 'https://'.$raw;
        }

        return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : null;
    }
}
