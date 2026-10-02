<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\AppInstaller;
use App\Support\EntityCache;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use BelongsToTenant;

    /** @var array<string, ?string> */
    private static array $requestCache = [];

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (! app(AppInstaller::class)->isInstalled()) {
            return $default;
        }

        $tenantId = TenantContext::scopeTenantId() ?? Tenancy::defaultTenantId();

        $value = static::getValueForTenant($tenantId, $key, null);

        if ($value === null && $tenantId !== Tenancy::defaultTenantId()) {
            $value = static::getValueForTenant(Tenancy::defaultTenantId(), $key, $default);
        }

        return $value ?? $default;
    }

    public static function getPlatformValue(string $key, ?string $default = null): ?string
    {
        if (! app(AppInstaller::class)->isInstalled()) {
            return $default;
        }

        try {
            $tenantId = Tenant::platform()->id;
        } catch (\Throwable) {
            $tenantId = Tenancy::defaultTenantId();
        }

        return static::getValueForTenant($tenantId, $key, $default);
    }

    public static function setValue(string $key, ?string $value): void
    {
        $tenantId = TenantContext::scopeTenantId() ?? Tenancy::defaultTenantId();

        static::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value],
        );

        static::forgetCached($key, $tenantId);
    }

    public static function setPlatformValue(string $key, ?string $value): void
    {
        $tenantId = Tenant::platform()->id;

        static::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value],
        );

        static::forgetCached($key, $tenantId);
    }

    public static function forgetValue(string $key): void
    {
        $tenantId = TenantContext::scopeTenantId() ?? Tenancy::defaultTenantId();

        static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();

        static::forgetCached($key, $tenantId);
    }

    private static function getValueForTenant(int $tenantId, string $key, ?string $default): ?string
    {
        $requestKey = $tenantId.':'.$key;

        if (array_key_exists($requestKey, self::$requestCache)) {
            return self::$requestCache[$requestKey];
        }

        $cacheKey = EntityCache::key('settings', $tenantId, $key);

        return self::$requestCache[$requestKey] = EntityCache::rememberForever(
            $cacheKey,
            fn () => static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('key', $key)
                ->value('value') ?? $default,
            config('entity-cache.tags.settings'),
        );
    }

    private static function forgetCached(string $key, int $tenantId): void
    {
        unset(self::$requestCache[$tenantId.':'.$key]);

        EntityCache::forget(EntityCache::key('settings', $tenantId, $key));
        EntityCache::flushTag(config('entity-cache.tags.settings'));
    }
}
