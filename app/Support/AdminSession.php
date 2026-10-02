<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Separate session cookie for /admin (path-scoped, independent from site login). */
final class AdminSession
{
    public static function appliesTo(Request $request): bool
    {
        return AdminPath::appliesTo($request);
    }

    public static function configure(): void
    {
        config([
            'session.cookie' => static::cookieName(),
            'session.path' => static::cookiePath(),
        ]);
    }

    public static function cookieName(): string
    {
        $configured = config('session.admin_cookie');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return Str::slug((string) config('app.name', 'laravel')).'-admin-session';
    }

    public static function cookiePath(): string
    {
        return AdminPath::cookiePath();
    }
}
