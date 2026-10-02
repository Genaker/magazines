<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/** Signed verification URL generation and outbound email delivery. */
class VerificationEmail
{
    /** Build a time-limited signed URL for email verification. */
    public static function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }

    /**
     * Send the verification email and report success or failure.
     *
     * @return array{sent: bool, url: string|null}
     */
    public static function send(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return ['sent' => true, 'url' => null];
        }

        $url = self::verificationUrl($user);

        try {
            Mail::send('emails.verify-email', [
                'url' => $url,
                'user' => $user,
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your email address');
            });

            if (config('app.debug')) {
                Log::info('Verification email sent', [
                    'email' => $user->email,
                    'mailpit' => config('mail.mailpit_web_url'),
                    'mail_host' => config('mail.mailers.smtp.host'),
                    'url' => $url,
                ]);
            }

            return ['sent' => true, 'url' => $url];
        } catch (Throwable $e) {
            Log::error('Verification email failed', [
                'email' => $user->email,
                'mail_host' => config('mail.mailers.smtp.host'),
                'error' => $e->getMessage(),
            ]);

            return ['sent' => false, 'url' => $url];
        }
    }
}
