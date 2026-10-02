<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Closure;

/** Request-scoped current tenant and admin filter scope. */
final class TenantContext
{
    private static ?Tenant $current = null;

    private static ?int $adminScopeId = null;

    public static function enabled(): bool
    {
        return Tenancy::enabled();
    }

    public static function current(): ?Tenant
    {
        return self::$current;
    }

    public static function id(): ?int
    {
        return self::$current?->id;
    }

    public static function set(Tenant $tenant): void
    {
        self::$current = $tenant;
    }

    public static function setAdminScope(?int $tenantId): void
    {
        self::$adminScopeId = $tenantId;
    }

    public static function adminScopeId(): ?int
    {
        return self::$adminScopeId;
    }

    public static function effectiveId(): int
    {
        $scoped = self::scopeTenantId();

        if ($scoped === null) {
            return Tenancy::defaultTenantId();
        }

        return $scoped;
    }

    /** Tenant id for read query scoping, or null to skip scope (platform admin, all tenants). */
    public static function scopeTenantId(): ?int
    {
        if (! self::enabled()) {
            if (self::$current !== null && ! self::$current->is_platform) {
                return self::$current->id;
            }

            return Tenancy::defaultTenantId();
        }

        if (self::$adminScopeId !== null) {
            return self::$adminScopeId;
        }

        if (self::$current !== null && ! self::$current->is_platform) {
            return self::$current->id;
        }

        if (self::$current === null) {
            return Tenancy::defaultTenantId();
        }

        return null;
    }

    /** Tenant id required when creating tenant-owned records. */
    public static function effectiveIdForWrite(): int
    {
        $scoped = self::scopeTenantId();

        if ($scoped === null) {
            abort(422, 'Select a tenant before creating records.');
        }

        return $scoped;
    }

    public static function requiresTenant(): Tenant
    {
        $tenant = self::$current;

        if (! $tenant) {
            abort(404);
        }

        return $tenant;
    }

    public static function isPlatformHost(string $host): bool
    {
        $platformDomain = Tenancy::platformDomain();

        return $platformDomain !== '' && TenantDomain::normalizeHost($host) === $platformDomain;
    }

    /** Super-admin tenant management UI (platform host, platform tenant, or bootstrap before domain is set). */
    public static function allowsPlatformTenantManagement(?string $host = null): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $host = TenantDomain::normalizeHost((string) ($host ?? request()->getHost()));

        if (self::isPlatformHost($host)) {
            return true;
        }

        if (Tenancy::isAppTopDomain($host)) {
            return true;
        }

        if (Tenancy::platformDomain() === '') {
            return true;
        }

        return self::$current?->is_platform === true;
    }

    /** Super-admin platform admin routes (strict host match once platform domain is configured). */
    public static function requiresPlatformHost(string $host): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $platformDomain = Tenancy::platformDomain();

        return $platformDomain !== '' && TenantDomain::normalizeHost($host) === $platformDomain;
    }

    /** @template TReturn */
    public static function withoutScope(Closure $callback): mixed
    {
        $previousTenant = self::$current;
        $previousScope = self::$adminScopeId;

        self::$current = null;
        self::$adminScopeId = null;

        try {
            return $callback();
        } finally {
            self::$current = $previousTenant;
            self::$adminScopeId = $previousScope;
        }
    }

    public static function reset(): void
    {
        self::$current = null;
        self::$adminScopeId = null;
    }
}
