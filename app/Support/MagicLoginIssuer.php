<?php

namespace App\Support;

use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/** Creates login links and emails sign-in PIN + URL. */
final class MagicLoginIssuer
{
    /**
     * @return array{sent: bool, code: ?string, url: ?string, error: ?string}
     */
    public static function send(User $user, string $verifyRouteName, string $schemeAndHost): array
    {
        $token = Str::random(64);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginLink::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'code' => hash('sha256', $code),
            'expires_at' => now()->addMinutes((int) config('magic-login.expires_minutes', 15)),
        ]);

        $path = route($verifyRouteName, [
            'token' => $token,
            'email' => $user->email,
        ], absolute: false);

        $url = rtrim($schemeAndHost, '/').$path;

        try {
            Mail::send('emails.magic-login', [
                'url' => $url,
                'code' => $code,
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Your sign-in link');
            });
        } catch (Throwable $e) {
            Log::error('Magic login email failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'code' => null,
                'url' => null,
                'error' => 'Could not send email. Please try again.',
            ];
        }

        if (config('app.debug')) {
            Log::info('Magic login issued', [
                'email' => $user->email,
                'code' => $code,
                'url' => $url,
                'mailpit' => config('mail.mailpit_web_url'),
            ]);
        }

        return [
            'sent' => true,
            'code' => $code,
            'url' => $url,
            'error' => null,
        ];
    }
}
