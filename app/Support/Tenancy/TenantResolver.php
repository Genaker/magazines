<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Support\Facades\Cache;

/** Resolve tenant from HTTP host or fall back to default catch-all. */
final class TenantResolver
{
    public function resolve(?string $host, bool $allowAppTopAdminFallback = false): Tenant
    {
        if (! Tenancy::enabled()) {
            return $this->defaultTenant();
        }

        $normalizedHost = TenantDomain::normalizeHost((string) $host);

        if ($normalizedHost === '') {
            return $this->resolveCatchAllOrAbort($allowAppTopAdminFallback);
        }

        if ($this->hostIsExplicitlyMapped($normalizedHost)) {
            $tenantId = Cache::remember(
                'tenant:host:'.$normalizedHost,
                3600,
                fn (): int => $this->resolveMappedHost($normalizedHost)->id,
            );

            return Tenant::query()->findOrFail($tenantId);
        }

        $parentTenantHost = Tenancy::matchingTenantDomainSuffix($normalizedHost);

        if ($parentTenantHost !== null && $parentTenantHost !== $normalizedHost) {
            $tenantId = Cache::remember(
                'tenant:host:'.$normalizedHost,
                3600,
                fn (): int => $this->resolveMappedHost($parentTenantHost)->id,
            );

            return Tenant::query()->findOrFail($tenantId);
        }

        return $this->resolveCatchAllOrAbort($allowAppTopAdminFallback, $normalizedHost);
    }

    public function hostIsExplicitlyMapped(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);

        if ($host === '') {
            return false;
        }

        if (Tenancy::platformDomain() !== '' && $host === Tenancy::platformDomain()) {
            return true;
        }

        return TenantDomain::query()->where('host', $host)->exists();
    }

    private function resolveMappedHost(string $host): Tenant
    {
        $domain = TenantDomain::query()->where('host', $host)->with('tenant')->first();

        if ($domain?->tenant) {
            return $domain->tenant;
        }

        if (Tenancy::platformDomain() !== '' && $host === Tenancy::platformDomain()) {
            return Tenant::platform();
        }

        abort(404, 'Unknown site.');
    }

    private function resolveCatchAllOrAbort(bool $allowAppTopAdminFallback = false, ?string $host = null): Tenant
    {
        if ($allowAppTopAdminFallback && $host !== null && Tenancy::isAppTopDomain($host)) {
            return $this->defaultTenant();
        }

        $default = $this->defaultTenant();

        if ($default->isCatchAll()) {
            return $default;
        }

        abort(404, 'Unknown site.');
    }

    private function defaultTenant(): Tenant
    {
        return Tenant::query()->find(Tenancy::defaultTenantId())
            ?? Tenant::default();
    }

    public static function flushHostCache(string $host): void
    {
        Cache::forget('tenant:host:'.TenantDomain::normalizeHost($host));
    }
}
