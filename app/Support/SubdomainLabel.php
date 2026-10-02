<?php

namespace App\Support;

use App\Models\SiteSetting;

/** DNS-safe host labels — same rules for @nickname paths and {label}.domain subdomains. */
final class SubdomainLabel
{
    /** Normalize author nickname / magazine slug for URLs and storage. */
    public static function forNickname(string $label): string
    {
        return self::normalize($label);
    }

    public static function normalize(string $label): string
    {
        $separator = self::separator();
        $label = strtolower(trim($label));
        $label = ltrim($label, '@');

        if ($label === '') {
            return '';
        }

        $label = preg_replace('/[\s_]+/u', $separator, $label) ?? $label;
        $escaped = preg_quote($separator, '/');
        $label = preg_replace('/'.$escaped.'+/u', $separator, $label) ?? $label;
        $label = preg_replace('/[^a-z0-9'.preg_quote($separator, '/').']/u', '', $label) ?? $label;

        return trim($label, $separator);
    }

    public static function separator(): string
    {
        $stored = SiteSetting::getValue('subdomain_label_separator');

        if (is_string($stored) && $stored !== '' && preg_match('/^[a-z0-9-]$/i', $stored) === 1) {
            return $stored;
        }

        return (string) config('subdomain.separator', '-');
    }
}
