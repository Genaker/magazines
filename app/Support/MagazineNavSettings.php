<?php

namespace App\Support;

use App\Models\SiteSetting;

/** Magazine navigation dropdown limit and source (manual pins vs top magazines). */
final class MagazineNavSettings
{
    public const MODE_MANUAL = 'manual';

    public const MODE_AUTO = 'auto';

    public static function limit(): int
    {
        $value = SiteSetting::getValue('magazines_nav_limit');

        if ($value !== null && $value !== '') {
            return max(1, min(50, (int) $value));
        }

        return max(1, min(50, (int) config('magazines.nav.limit', 10)));
    }

    public static function mode(): string
    {
        $value = SiteSetting::getValue('magazines_nav_mode');

        if (in_array($value, [self::MODE_MANUAL, self::MODE_AUTO], true)) {
            return $value;
        }

        $legacyAutoFill = SiteSetting::getValue('magazines_nav_auto_fill');

        if ($legacyAutoFill !== null && $legacyAutoFill !== '') {
            return filter_var($legacyAutoFill, FILTER_VALIDATE_BOOLEAN)
                ? self::MODE_AUTO
                : self::MODE_MANUAL;
        }

        $configuredMode = config('magazines.nav.mode');

        if (in_array($configuredMode, [self::MODE_MANUAL, self::MODE_AUTO], true)) {
            return $configuredMode;
        }

        return filter_var(config('magazines.nav.auto_fill', true), FILTER_VALIDATE_BOOLEAN)
            ? self::MODE_AUTO
            : self::MODE_MANUAL;
    }

    public static function isManualMode(): bool
    {
        return static::mode() === self::MODE_MANUAL;
    }

    public static function isAutoMode(): bool
    {
        return static::mode() === self::MODE_AUTO;
    }

    /** @deprecated Use isAutoMode() — kept for existing call sites. */
    public static function autoFillEnabled(): bool
    {
        return static::isAutoMode();
    }

    public static function save(int $limit, string $mode): void
    {
        if (! in_array($mode, [self::MODE_MANUAL, self::MODE_AUTO], true)) {
            $mode = self::MODE_MANUAL;
        }

        SiteSetting::setValue('magazines_nav_limit', (string) max(1, min(50, $limit)));
        SiteSetting::setValue('magazines_nav_mode', $mode);
        SiteSetting::setValue('magazines_nav_auto_fill', $mode === self::MODE_AUTO ? '1' : '0');
    }
}
