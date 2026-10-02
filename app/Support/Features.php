<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Support\Tenancy\Tenancy;
use InvalidArgumentException;

/** Site-wide feature toggles stored in site_settings with config defaults. */
class Features
{
    /** @var array<string, bool> */
    private static array $enabledCache = [];

    /** @return array<string, array{label: string, description: string, default: bool}> */
    public static function definitions(): array
    {
        return config('features', []);
    }

    /** Check whether a named feature is currently enabled. */
    public static function enabled(string $feature): bool
    {
        static::assertDefined($feature);

        if (array_key_exists($feature, self::$enabledCache)) {
            return self::$enabledCache[$feature];
        }

        if ($feature === 'multi_tenancy') {
            return self::$enabledCache[$feature] = Tenancy::enabled();
        }

        $stored = SiteSetting::getValue(static::storageKey($feature)); // "1"/"0" or null → use default

        if ($stored === null) {
            return self::$enabledCache[$feature] = (bool) static::definitions()[$feature]['default'];
        }

        return self::$enabledCache[$feature] = filter_var($stored, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, bool> */
    public static function all(): array
    {
        $states = []; // feature key => enabled bool

        foreach (array_keys(static::definitions()) as $feature) {
            $states[$feature] = static::enabled($feature);
        }

        return $states;
    }

    /** Persist a feature toggle to site settings. */
    public static function set(string $feature, bool $enabled): void
    {
        static::assertDefined($feature);

        $value = $enabled ? '1' : '0';
        $key = static::storageKey($feature);

        if ($feature === 'multi_tenancy') {
            $wasEnabled = Tenancy::enabled();
            SiteSetting::setPlatformValue($key, $value);
            unset(self::$enabledCache[$feature]);

            if ($enabled || $wasEnabled !== $enabled) {
                Tenancy::clearCaches(flushApplicationCache: $enabled);
            }

            return;
        }

        SiteSetting::setValue($key, $value);
        unset(self::$enabledCache[$feature]);
    }

    /** Seed missing feature keys from config defaults (does not overwrite existing values). */
    public static function seedDefaults(): void
    {
        foreach (static::definitions() as $feature => $definition) {
            $key = static::storageKey($feature);

            if ($feature === 'multi_tenancy') {
                if (SiteSetting::withoutGlobalScopes()->where('tenant_id', \App\Models\Tenant::platform()->id)->where('key', $key)->doesntExist()) {
                    SiteSetting::setPlatformValue($key, ($definition['default'] ?? true) ? '1' : '0');
                }

                continue;
            }

            if (SiteSetting::withoutGlobalScopes()->where('tenant_id', Tenancy::defaultTenantId())->where('key', $key)->doesntExist()) {
                SiteSetting::setValue($key, ($definition['default'] ?? true) ? '1' : '0');
            }
        }

        self::$enabledCache = [];
    }

    /** Site setting key used to store a feature toggle. */
    private static function storageKey(string $feature): string
    {
        return 'feature_'.$feature;
    }

    /** Throw when the feature name is not defined in config/features.php. */
    private static function assertDefined(string $feature): void
    {
        if (! array_key_exists($feature, static::definitions())) {
            throw new InvalidArgumentException("Unknown feature [{$feature}].");
        }
    }
}
