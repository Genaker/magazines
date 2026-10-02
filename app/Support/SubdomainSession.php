<?php

namespace App\Support;

use App\Models\TenantDomain;
use App\Support\Tenancy\Tenancy;

/** Session cookie domain for main site + author/magazine subdomains. */
final class SubdomainSession
{
    /** Browsers treat localhost as a public suffix — Domain=.localhost cookies are rejected. */
    public const LOCALHOST_COOKIE_DOMAIN_UNSUPPORTED = true;

    public static function configure(): void
    {
        static::configureAppUrl();
        config(['session.domain' => static::cookieDomain()]);
    }

    /** Align APP_URL host with subdomain base host (Docker may still inject 127.0.0.1). */
    public static function configureAppUrl(): void
    {
        if (! static::subdomainsEnabled()) {
            return;
        }

        if (AuthorSubdomain::usesAutoDetectedBaseHost()) {
            $appUrl = static::originalAppUrl();

            if ($appUrl !== null) {
                static::forceAppUrl($appUrl);
            }

            return;
        }

        $baseHost = AuthorSubdomain::baseHost();

        if (filter_var($baseHost, FILTER_VALIDATE_IP) || $baseHost === 'localhost') {
            return;
        }

        $parsed = parse_url((string) config('app.url', 'http://localhost'));
        $currentHost = strtolower((string) ($parsed['host'] ?? ''));

        if ($currentHost === strtolower($baseHost)) {
            return;
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;

        if ($baseHost === 'lvh.me' && ($port === 8000 || $port === null)) {
            $port = (int) env('APP_PORT', 8888);
        }

        $portSuffix = ($port !== null && ! in_array($port, [80, 443], true)) ? ':'.$port : '';
        $url = $scheme.'://'.$baseHost.$portSuffix;

        static::forceAppUrl($url);
    }

    private static function forceAppUrl(string $url): void
    {
        config(['app.url' => $url]);

        if (! class_exists(\Illuminate\Support\Facades\URL::class)) {
            return;
        }

        $generator = app(\Illuminate\Routing\UrlGenerator::class);
        $generator->forceRootUrl($url);

        $reflection = new \ReflectionClass($generator);

        if ($reflection->hasProperty('cachedRoot')) {
            $cachedRoot = $reflection->getProperty('cachedRoot');
            $cachedRoot->setAccessible(true);
            $cachedRoot->setValue($generator, null);
        }
    }

    public static function cookieDomain(?string $host = null): ?string
    {
        if (Tenancy::enabled()) {
            return static::multiTenantCookieDomain($host);
        }

        $fromEnv = static::readEnvDomain();

        if ($fromEnv !== null) {
            return static::normalizeDomain($fromEnv);
        }

        if (! static::subdomainsEnabled()) {
            return null;
        }

        return static::normalizeDomain(static::domainForBaseHost(AuthorSubdomain::baseHost()));
    }

    /**
     * Per-tenant cookie scope when multi-tenancy is on.
     * Domain `.tenant1.example` shares login across tenant1.example, author.tenant1.example,
     * and magazine.tenant1.example — but not other tenant hosts.
     */
    private static function multiTenantCookieDomain(?string $host): ?string
    {
        $host = TenantDomain::normalizeHost($host ?? (app()->bound('request') ? request()->getHost() : ''));

        if ($host === '') {
            return null;
        }

        $tenantHost = Tenancy::matchingTenantDomainSuffix($host);

        if ($tenantHost !== null) {
            if (Tenancy::registeredSubdomainTenantExists($tenantHost)) {
                return null;
            }

            return static::normalizeDomain('.'.$tenantHost);
        }

        return null;
    }

    /** Whether shared login cookies can work for this subdomain base host. */
    public static function supportsSharedCookies(string $baseHost): bool
    {
        return static::normalizeDomain(static::domainForBaseHost($baseHost)) !== null;
    }

    /** Recommended base host for local Docker when subdomains + shared login are needed. */
    public static function recommendedLocalBaseHost(): string
    {
        return 'lvh.me';
    }

    private static function originalAppUrl(): ?string
    {
        $appUrl = getenv('APP_URL');

        if (is_string($appUrl) && $appUrl !== '') {
            return $appUrl;
        }

        $appUrl = env('APP_URL');

        return is_string($appUrl) && $appUrl !== '' ? $appUrl : null;
    }

    private static function readEnvDomain(): ?string
    {
        $configured = env('SESSION_DOMAIN');

        if ($configured === null || $configured === '' || strtolower((string) $configured) === 'null') {
            return null;
        }

        return (string) $configured;
    }

    private static function subdomainsEnabled(): bool
    {
        return Features::enabled('author_subdomains')
            || Features::enabled('magazine_subdomains');
    }

    private static function domainForBaseHost(string $baseHost): ?string
    {
        if (filter_var($baseHost, FILTER_VALIDATE_IP)) {
            return null;
        }

        return match ($baseHost) {
            'localhost' => null,
            'lvh.me' => '.lvh.me',
            default => str_contains($baseHost, '.') ? '.'.ltrim($baseHost, '.') : null,
        };
    }

    private static function normalizeDomain(?string $domain): ?string
    {
        if ($domain === null || $domain === '') {
            return null;
        }

        $domain = str_starts_with($domain, '.') ? $domain : '.'.$domain;
        $host = ltrim($domain, '.');

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $domain;
    }
}
