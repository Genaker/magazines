<?php

namespace App\Support;

use App\Models\SiteSetting;

/** Post and gallery image variant widths and JPEG compression from site settings. */
class MediaSettings
{
    public const PRESET_POST = 'post';

    public const PRESET_GALLERY = 'gallery';

    /** @return array{sm: int, md: int, lg: int, jpeg_quality: int, resize: bool, max_width: int, max_height: int} */
    public static function forPreset(string $preset): array
    {
        return [
            ...self::variants($preset),
            'jpeg_quality' => self::jpegQuality($preset),
            'resize' => self::resizeEnabled($preset),
            'max_width' => self::maxWidth($preset),
            'max_height' => self::maxHeight($preset),
        ];
    }

    /**
     * Responsive variant widths (sm / md / lg) for a preset.
     *
     * @return array{sm: int, md: int, lg: int}
     */
    public static function variants(string $preset): array
    {
        $defaults = config("media.{$preset}.variants");

        return [
            'sm' => self::intSetting("media_{$preset}_width_sm", (int) $defaults['sm']),
            'md' => self::intSetting("media_{$preset}_width_md", (int) $defaults['md']),
            'lg' => self::intSetting("media_{$preset}_width_lg", (int) $defaults['lg']),
        ];
    }

    public static function jpegQuality(string $preset): int
    {
        return self::intSetting(
            "media_{$preset}_jpeg_quality",
            (int) config("media.{$preset}.jpeg_quality"),
        );
    }

    public static function resizeEnabled(string $preset): bool
    {
        $default = (bool) config("media.{$preset}.resize", true);
        $doNotResize = SiteSetting::getValue("media_{$preset}_do_not_resize");

        if ($doNotResize === null || $doNotResize === '') {
            return $default;
        }

        return ! filter_var($doNotResize, FILTER_VALIDATE_BOOLEAN);
    }

    public static function maxWidth(string $preset): int
    {
        return self::intSetting(
            "media_{$preset}_max_width",
            (int) config("media.{$preset}.max_width", 1500),
        );
    }

    public static function maxHeight(string $preset): int
    {
        return self::intSetting(
            "media_{$preset}_max_height",
            (int) config("media.{$preset}.max_height", 1500),
        );
    }

    /** @param array{sm: int, md: int, lg: int, jpeg_quality: int, do_not_resize: bool, max_width: int, max_height: int} $data */
    public static function save(string $preset, array $data): void
    {
        SiteSetting::setValue("media_{$preset}_width_sm", (string) $data['sm']);
        SiteSetting::setValue("media_{$preset}_width_md", (string) $data['md']);
        SiteSetting::setValue("media_{$preset}_width_lg", (string) $data['lg']);
        SiteSetting::setValue("media_{$preset}_jpeg_quality", (string) $data['jpeg_quality']);
        SiteSetting::setValue("media_{$preset}_do_not_resize", $data['do_not_resize'] ? '1' : '0');
        SiteSetting::setValue("media_{$preset}_max_width", (string) $data['max_width']);
        SiteSetting::setValue("media_{$preset}_max_height", (string) $data['max_height']);
    }

    /**
     * Validation rules for admin site settings media fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(string $preset): array
    {
        return [
            "media_{$preset}_width_sm" => ['required', 'integer', 'min:100', 'max:4000'],
            "media_{$preset}_width_md" => ['required', 'integer', 'min:100', 'max:4000'],
            "media_{$preset}_width_lg" => ['required', 'integer', 'min:100', 'max:4000'],
            "media_{$preset}_jpeg_quality" => ['required', 'integer', 'min:1', 'max:100'],
            "media_{$preset}_do_not_resize" => ['sometimes', 'boolean'],
            "media_{$preset}_max_width" => ['required', 'integer', 'min:100', 'max:8000'],
            "media_{$preset}_max_height" => ['required', 'integer', 'min:100', 'max:8000'],
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveFromValidated(string $preset, array $data, bool $doNotResize): void
    {
        self::save($preset, [
            'sm' => (int) $data["media_{$preset}_width_sm"],
            'md' => (int) $data["media_{$preset}_width_md"],
            'lg' => (int) $data["media_{$preset}_width_lg"],
            'jpeg_quality' => (int) $data["media_{$preset}_jpeg_quality"],
            'do_not_resize' => $doNotResize,
            'max_width' => (int) $data["media_{$preset}_max_width"],
            'max_height' => (int) $data["media_{$preset}_max_height"],
        ]);
    }

    public static function widthsAreOrdered(int $sm, int $md, int $lg): bool
    {
        return $sm < $md && $md < $lg;
    }

    private static function intSetting(string $key, int $default): int
    {
        $value = SiteSetting::getValue($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }
}
