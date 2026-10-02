<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Optional per-author subdomain pages ({username}.{site-host}). */
final class AuthorSubdomain
{
    public static function enabled(): bool
    {
        return Features::enabled('author_subdomains');
    }

    public static function redirectEnabled(): bool
    {
        if (! static::enabled()) {
            return false;
        }

        return filter_var(
            SiteSetting::getValue('author_subdomain_redirect', '0'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Main site hostname for author subdomains.
     *
     * Priority: admin override → APP_URL host (WordPress site URL style) → current request host.
     */
    public static function baseHost(): string
    {
        $stored = SiteSetting::getValue('author_subdomain_base_host');

        if (is_string($stored) && $stored !== '') {
            return strtolower(trim($stored));
        }

        if (\App\Support\Tenancy\Tenancy::enabled()) {
            $candidates = \App\Support\Tenancy\Tenancy::appTopDomainCandidates();

            if (count($candidates) === 1 && ! filter_var($candidates[0], FILTER_VALIDATE_IP)) {
                return $candidates[0];
            }
        }

        $fromAppUrl = parse_url((string) config('app.url', ''), PHP_URL_HOST);

        if (is_string($fromAppUrl) && $fromAppUrl !== '') {
            return strtolower($fromAppUrl);
        }

        if (! app()->runningInConsole() && app()->bound('request')) {
            return strtolower(request()->getHost());
        }

        return 'localhost';
    }

    public static function subdomainBaseHost(?Request $request = null): string
    {
        $request ??= app()->bound('request') ? request() : null;

        if ($request !== null) {
            return static::subdomainBaseHostForHost($request->getHost());
        }

        return static::baseHost();
    }

    public static function subdomainBaseHostForHost(string $host): string
    {
        if (\App\Support\Tenancy\Tenancy::enabled()) {
            $tenantHost = \App\Support\Tenancy\Tenancy::matchingTenantDomainSuffix($host);

            if ($tenantHost !== null) {
                return $tenantHost;
            }
        }

        return static::baseHost();
    }

    public static function baseHostForTenant(?int $tenantId): string
    {
        if ($tenantId !== null && \App\Support\Tenancy\Tenancy::enabled()) {
            $host = \App\Models\TenantDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('is_primary', true)
                ->value('host');

            if (is_string($host) && $host !== '') {
                return $host;
            }
        }

        return static::subdomainBaseHost();
    }

    public static function usesAutoDetectedBaseHost(): bool
    {
        $stored = SiteSetting::getValue('author_subdomain_base_host');

        return ! is_string($stored) || $stored === '';
    }

    /** @return list<string> */
    public static function reservedUsernames(): array
    {
        return config('author-subdomain.reserved', []);
    }

    public static function usernameFromRequest(Request $request): ?string
    {
        if (! static::enabled()) {
            return null;
        }

        return static::usernameFromHost($request);
    }

    public static function usernameFromHost(Request $request): ?string
    {
        $host = strtolower($request->getHost());

        if (\App\Support\Tenancy\Tenancy::isRegisteredTenantHost($host)) {
            return null;
        }

        $base = static::subdomainBaseHost($request);

        if ($host === $base) {
            return null;
        }

        $suffix = '.'.$base;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $username = substr($host, 0, -strlen($suffix));

        if ($username === '' || str_contains($username, '.')) {
            return null;
        }

        if (in_array($username, static::reservedUsernames(), true)) {
            return null;
        }

        if (MagazineSubdomain::enabled() && MagazineSubdomain::slugExists($username)) {
            return null;
        }

        return SubdomainLabel::forNickname($username);
    }

    public static function isSubdomainRequest(Request $request): bool
    {
        return static::enabled() && static::usernameFromHost($request) !== null;
    }

    public static function authorHomeUrl(AuthorAlias $alias): string
    {
        if (! static::enabled()) {
            return route('authors.show', $alias);
        }

        return static::buildSubdomainUrl($alias->username, '/', static::baseHostForTenant($alias->tenant_id));
    }

    public static function postUrl(Post $post): string
    {
        $alias = $post->authorAlias;

        if (! $alias || ! static::enabled()) {
            return route('posts.show', [$alias ?? $post->user->primaryAlias(), $post->slug]);
        }

        return static::buildSubdomainUrl(
            $alias->username,
            '/'.ltrim($post->slug, '/'),
            static::baseHostForTenant($alias->tenant_id),
        );
    }

    public static function canonicalAuthorUrl(AuthorAlias $alias): string
    {
        return static::enabled()
            ? static::authorHomeUrl($alias)
            : route('authors.show', $alias);
    }

    public static function canonicalPostUrl(Post $post): string
    {
        return static::enabled()
            ? static::postUrl($post)
            : route('posts.show', [$post->authorAlias ?? $post->user->primaryAlias(), $post->slug]);
    }

    public static function pathAuthorUrl(AuthorAlias $alias): string
    {
        return route('authors.show', $alias);
    }

    public static function pathPostUrl(Post $post): string
    {
        return route('posts.show', [$post->authorAlias ?? $post->user->primaryAlias(), $post->slug]);
    }

    public static function mainSiteUrl(string $path = ''): string
    {
        $parsed = parse_url((string) config('app.url', 'http://localhost'));
        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
        $host = static::resolveMainSiteHost();
        $path = '/'.ltrim($path, '/');
        $portSuffix = ($port !== null && ! in_array($port, [80, 443], true)) ? ':'.$port : '';

        return $scheme.'://'.$host.$portSuffix.$path;
    }

    /** Main site hostname for profile/settings links (tenant domain when nested under a tenant). */
    public static function resolveMainSiteHost(): string
    {
        if (app()->bound('request')) {
            $host = request()->getHost();

            if ($host !== '') {
                return static::subdomainBaseHostForHost($host);
            }
        }

        return static::baseHost();
    }

    public static function redirectToMainSite(Request $request, string $path): RedirectResponse
    {
        $url = static::mainSiteUrl($path);

        return redirect($url, $request->isMethod('GET') ? 302 : 307);
    }

    public static function buildSubdomainUrl(string $username, string $path = '/', ?string $baseHost = null): string
    {
        $parsed = parse_url((string) config('app.url', 'http://localhost'));
        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
        $host = strtolower(SubdomainLabel::forNickname($username)).'.'.($baseHost ?? static::subdomainBaseHost());
        $path = '/'.ltrim($path, '/');
        $portSuffix = ($port !== null && ! in_array($port, [80, 443], true)) ? ':'.$port : '';

        return $scheme.'://'.$host.$portSuffix.$path;
    }

    public static function setBaseHost(?string $host): void
    {
        $host = $host !== null ? strtolower(trim($host)) : '';

        if ($host === '') {
            SiteSetting::setValue('author_subdomain_base_host', null);
            SubdomainSession::configureAppUrl();

            return;
        }

        SiteSetting::setValue('author_subdomain_base_host', $host);
        SubdomainSession::configureAppUrl();
    }

    public static function setRedirect(bool $enabled): void
    {
        SiteSetting::setValue('author_subdomain_redirect', $enabled ? '1' : '0');
    }
}
