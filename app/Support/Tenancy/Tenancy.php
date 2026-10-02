<?php

namespace App\Support\Tenancy;

use App\Models\TenantDomain;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use App\Support\EntityCache;

/** Multi-tenancy feature gate and default tenant id. */
final class Tenancy
{
    /** @var array<string, bool> */
    private static array $registeredHostExists = [];

    private static ?bool $enabledCache = null;

    public static function enabled(): bool
    {
        if (self::$enabledCache !== null) {
            return self::$enabledCache;
        }

        $stored = SiteSetting::getPlatformValue('feature_multi_tenancy');

        if ($stored === null) {
            return self::$enabledCache = (bool) config('features.multi_tenancy.default', false);
        }

        return self::$enabledCache = filter_var($stored, FILTER_VALIDATE_BOOLEAN);
    }

    public static function defaultTenantId(): int
    {
        return (int) config('tenancy.default_tenant_id', 1);
    }

    public static function platformDomain(): string
    {
        return TenantDomain::normalizeHost((string) config('tenancy.platform_domain', ''));
    }

    public static function appDomain(): string
    {
        return TenantDomain::normalizeHost((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    }

    /** Apex host where platform admin is reachable (e.g. lvh.me when tenants use *.lvh.me). */
    public static function isAppTopDomain(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);

        if ($host === '') {
            return false;
        }

        return in_array($host, self::appTopDomainCandidates(), true);
    }

    /** @return list<string> */
    public static function appTopDomainCandidates(): array
    {
        $candidates = [];

        $appDomain = self::appDomain();
        if ($appDomain !== '' && ! self::isLoopbackHost($appDomain)) {
            $candidates[] = $appDomain;
        }

        $storedBase = SiteSetting::getValue('author_subdomain_base_host');
        if (is_string($storedBase) && $storedBase !== '') {
            $candidates[] = TenantDomain::normalizeHost($storedBase);
        }

        $platform = self::platformDomain();
        if ($platform !== '') {
            $candidates[] = TenantDomain::normalizeHost($platform);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /** Another tenant is registered under this host (e.g. tenant1.lvh.me under lvh.me). */
    public static function registeredSubdomainTenantExists(string $tenantHost): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $tenantHost = TenantDomain::normalizeHost($tenantHost);

        foreach (self::registeredTenantHosts() as $other) {
            if ($other !== $tenantHost && str_ends_with($other, '.'.$tenantHost)) {
                return true;
            }
        }

        return false;
    }

    public static function isRegisteredTenantHost(string $host): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $host = TenantDomain::normalizeHost($host);

        if ($host === '') {
            return false;
        }

        return self::$registeredHostExists[$host] ??= TenantDomain::query()->where('host', $host)->exists();
    }

    /** Longest registered tenant domain matching host (e.g. tenant1.lvh.me for john.tenant1.lvh.me). */
    public static function matchingTenantDomainSuffix(string $host): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $host = TenantDomain::normalizeHost($host);

        if ($host === '') {
            return null;
        }

        foreach (self::registeredTenantHosts() as $tenantHost) {
            if ($host === $tenantHost || str_ends_with($host, '.'.$tenantHost)) {
                return $tenantHost;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function registeredTenantHosts(): array
    {
        if (! self::enabled()) {
            return [];
        }

        return Cache::remember('tenancy:registered_hosts', 3600, static function (): array {
            return TenantDomain::query()
                ->pluck('host')
                ->sortByDesc(static fn (string $host): int => strlen($host))
                ->values()
                ->all();
        });
    }

    public static function flushRegisteredTenantHostsCache(): void
    {
        Cache::forget('tenancy:registered_hosts');
    }

    public static function publicUrlForHost(?string $host): ?string
    {
        if ($host === null || $host === '') {
            return null;
        }

        $host = TenantDomain::normalizeHost($host);

        if (! app()->bound('request')) {
            return self::buildRootUrl($host, (string) config('app.url')).'/';
        }

        return self::buildRootUrl($host, request()).'/';
    }

    /** Absolute base URL for platform admin (apex or configured platform domain). */
    public static function adminRootUrl(): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $host = self::platformDomain();

        if ($host === '') {
            $candidates = self::appTopDomainCandidates();

            if ($candidates === []) {
                return null;
            }

            $host = $candidates[0];
        }

        if (! app()->bound('request')) {
            return self::buildRootUrl($host, (string) config('app.url'));
        }

        return self::buildRootUrl($host, request());
    }

    private static function buildRootUrl(string $host, \Illuminate\Http\Request|string $from): string
    {
        if ($from instanceof \Illuminate\Http\Request) {
            $scheme = $from->getScheme();
            $port = $from->getPort();
        } else {
            $parsed = parse_url($from);
            $scheme = $parsed['scheme'] ?? 'http';
            $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
        }

        if ($port === null || in_array($port, [80, 443], true)) {
            $appParsed = parse_url((string) config('app.url'));
            $appPort = isset($appParsed['port']) ? (int) $appParsed['port'] : null;

            if ($appPort !== null && ! in_array($appPort, [80, 443], true)) {
                $port = $appPort;
            }
        }

        $portSuffix = ($port !== null && ! in_array($port, [80, 443], true)) ? ':'.$port : '';

        return $scheme.'://'.$host.$portSuffix;
    }

    private static function isLoopbackHost(string $host): bool
    {
        if (in_array($host, ['127.0.0.1', 'localhost', '0.0.0.0', '::1'], true)) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    /** Drop tenant resolution and settings caches after toggling multi-tenancy. */
    public static function clearCaches(bool $flushApplicationCache = false): void
    {
        self::$enabledCache = null;

        $hosts = TenantDomain::query()->pluck('host');

        if ($platformDomain = self::platformDomain()) {
            $hosts->push($platformDomain);
        }

        $appHost = self::appDomain();

        if ($appHost !== '') {
            $hosts->push($appHost);
        }

        foreach ($hosts->filter()->unique() as $host) {
            TenantResolver::flushHostCache($host);
        }

        self::flushRegisteredTenantHostsCache();

        EntityCache::flushTag(config('entity-cache.tags.settings'));

        if ($flushApplicationCache) {
            Cache::flush();
        }
    }
}
