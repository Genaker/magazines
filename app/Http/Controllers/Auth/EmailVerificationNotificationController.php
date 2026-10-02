<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthUserLookup;
use App\Support\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /** Resend the verification email (guest or authenticated). */
    public function store(Request $request): RedirectResponse
    {
        $email = $request->user()?->email ?? $request->input('email') ?? session('pending_verification_email');

        if (! is_string($email) || $email === '') {
            return back()->withErrors(['email' => __('app.verification_email_required')]);
        }

        $user = AuthUserLookup::findByEmail($email);

        if (! $user || $user->hasVerifiedEmail()) {
            return back()->with('status', 'verification-link-sent');
        }

        $result = VerificationEmail::send($user);

        $redirect = back()->with('status', 'verification-link-sent');

        if ($user) {
            $redirect = $redirect->with('pending_verification_email', $user->email);
        }

        if (config('app.debug') && $result['url']) {
            $redirect = $redirect->with('dev_verification_url', $result['url']);
        }

        if (! $result['sent']) {
            return $redirect->with('verification_mail_failed', true);
        }

        return $redirect;
    }
}
