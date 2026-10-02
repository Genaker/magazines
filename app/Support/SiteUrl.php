<?php

namespace App\Support;

use App\Models\AuthorAlias;
use App\Support\Tenancy\Tenancy;
use App\Models\TenantDomain;
use Illuminate\Http\Request;

/** Absolute URLs for main site vs author/magazine subdomains. */
final class SiteUrl
{
    public static function onSubdomain(?Request $request = null): bool
    {
        $request ??= app()->bound('request') ? request() : null;

        if ($request === null) {
            return false;
        }

        return AuthorSubdomain::isSubdomainRequest($request)
            || MagazineSubdomain::isSubdomainRequest($request);
    }

    public static function author(AuthorAlias $alias): string
    {
        return AuthorSubdomain::canonicalAuthorUrl($alias);
    }

    /** Named route on the main site hostname (always absolute). */
    public static function mainRoute(string $name, mixed $parameters = []): string
    {
        if (str_starts_with($name, 'admin.')) {
            return static::adminRoute($name, $parameters);
        }

        return AuthorSubdomain::mainSiteUrl(route($name, $parameters, false));
    }

    /** Main site when on a subdomain; ordinary route on the main site. */
    public static function navRoute(string $name, mixed $parameters = []): string
    {
        if (str_starts_with($name, 'admin.')) {
            return static::adminRoute($name, $parameters);
        }

        if (static::onSubdomain()) {
            return static::mainRoute($name, $parameters);
        }

        return route($name, $parameters);
    }

    /** Admin route on the apex / platform host (no tenant subdomain). */
    public static function adminRoute(string $name, mixed $parameters = []): string
    {
        if (! static::shouldUseAdminApexUrl()) {
            return route($name, $parameters);
        }

        $root = Tenancy::adminRootUrl();

        if ($root === null) {
            return route($name, $parameters);
        }

        return rtrim($root, '/').'/'.ltrim(route($name, $parameters, false), '/');
    }

    /** Platform super-admin URLs use the apex host, not tenant subdomains like default2.lvh.me. */
    public static function shouldUseAdminApexUrl(): bool
    {
        if (! Tenancy::enabled()) {
            return false;
        }

        if (Tenancy::adminRootUrl() === null) {
            return false;
        }

        if (! app()->bound('request')) {
            return false;
        }

        $host = TenantDomain::normalizeHost(request()->getHost());

        if (Tenancy::isAppTopDomain($host)) {
            return true;
        }

        return auth()->user()?->isSuperAdmin() === true;
    }
}
