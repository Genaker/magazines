<?php

namespace App\Support;

use App\Enums\MagazineSubmissionStatus;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/** Optional per-magazine subdomain pages ({slug}.{site-host}). */
final class MagazineSubdomain
{
    public static function enabled(): bool
    {
        return Features::enabled('magazine_subdomains');
    }

    public static function redirectEnabled(): bool
    {
        if (! static::enabled()) {
            return false;
        }

        return filter_var(
            SiteSetting::getValue('magazine_subdomain_redirect', '0'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function slugFromHost(Request $request): ?string
    {
        if (! static::enabled()) {
            return null;
        }

        return static::parseSlugFromHost($request->getHost());
    }

    public static function parseSlugFromHost(string $host): ?string
    {
        $host = strtolower($host);

        if (\App\Support\Tenancy\Tenancy::isRegisteredTenantHost($host)) {
            return null;
        }

        $base = AuthorSubdomain::subdomainBaseHostForHost($host);

        if ($host === $base) {
            return null;
        }

        $suffix = '.'.$base;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $slug = substr($host, 0, -strlen($suffix));

        if ($slug === '' || str_contains($slug, '.')) {
            return null;
        }

        if (in_array($slug, AuthorSubdomain::reservedUsernames(), true)) {
            return null;
        }

        return SubdomainLabel::normalize($slug);
    }

    public static function slugExists(string $slug): bool
    {
        return Magazine::query()
            ->where('slug', SubdomainLabel::normalize($slug))
            ->exists();
    }

    public static function isSubdomainRequest(Request $request): bool
    {
        return static::slugFromHost($request) !== null;
    }

    public static function servesPost(Post $post): bool
    {
        if (! static::enabled() || $post->magazine_id === null) {
            return false;
        }

        return $post->magazine_submission_status === MagazineSubmissionStatus::Approved;
    }

    public static function magazineUrl(Magazine $magazine, array $query = []): string
    {
        if (! static::enabled()) {
            return route('magazines.show', array_merge([$magazine], $query));
        }

        $url = static::buildSubdomainUrl($magazine->slug, '/', $magazine->tenant_id);

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    public static function canonicalMagazineUrl(Magazine $magazine, array $query = []): string
    {
        return static::enabled()
            ? static::magazineUrl($magazine, $query)
            : route('magazines.show', array_merge([$magazine], $query));
    }

    public static function pathMagazineUrl(Magazine $magazine, array $query = []): string
    {
        return $query === []
            ? route('magazines.show', $magazine)
            : route('magazines.show', [$magazine, ...$query]);
    }

    public static function postUrl(Post $post): string
    {
        $magazine = $post->magazine;

        if (! $magazine || ! static::servesPost($post)) {
            return AuthorSubdomain::canonicalPostUrl($post);
        }

        return static::buildSubdomainUrl($magazine->slug, '/'.ltrim($post->slug, '/'), $magazine->tenant_id);
    }

    public static function buildSubdomainUrl(string $slug, string $path = '/', ?int $tenantId = null): string
    {
        $baseHost = $tenantId !== null
            ? AuthorSubdomain::baseHostForTenant($tenantId)
            : null;

        return AuthorSubdomain::buildSubdomainUrl(SubdomainLabel::normalize($slug), $path, $baseHost);
    }

    public static function setRedirect(bool $enabled): void
    {
        SiteSetting::setValue('magazine_subdomain_redirect', $enabled ? '1' : '0');
    }
}
