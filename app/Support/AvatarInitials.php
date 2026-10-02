<?php

namespace App\Support;

/** Two-letter initials and colors derived from a username (nickname). */
final class AvatarInitials
{
    public static function fromUsername(string $username): string
    {
        $slug = ltrim(trim($username), '@');
        $letters = preg_replace('/[^a-zA-Z0-9]/', '', $slug) ?? '';

        if ($letters === '') {
            return '?';
        }

        return strtoupper(substr($letters, 0, 2));
    }

    /** @return array{r: int, g: int, b: int} */
    public static function backgroundRgb(string $username): array
    {
        $hash = crc32(ltrim(trim($username), '@'));

        return [
            'r' => 80 + ($hash % 120),
            'g' => 90 + (($hash >> 8) % 100),
            'b' => 110 + (($hash >> 16) % 90),
        ];
    }

    public static function backgroundStyle(string $username): string
    {
        $color = self::backgroundRgb($username);

        return sprintf(
            'background-color: rgb(%d, %d, %d)',
            $color['r'],
            $color['g'],
            $color['b'],
        );
    }

    public static function textColor(string $username): string
    {
        $color = self::backgroundRgb($username);
        $luminance = (0.299 * $color['r'] + 0.587 * $color['g'] + 0.114 * $color['b']) / 255;

        return $luminance > 0.6 ? '#1f2937' : '#ffffff';
    }
}
