<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthUserLookup;
use App\Support\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /** Prompt to verify email after registration (guest; no login until verified). */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $unknownEmail = null;

        if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
            $pendingUser = $request->user();
        } elseif ($request->has('email')) {
            $pendingUser = $this->findUnverifiedUserByEmail((string) $request->query('email'));

            if ($pendingUser === null) {
                $queryEmail = strtolower(trim((string) $request->query('email', '')));

                if ($queryEmail !== '' && filter_var($queryEmail, FILTER_VALIDATE_EMAIL)) {
                    $existing = AuthUserLookup::findByEmail($queryEmail);

                    if ($existing?->hasVerifiedEmail()) {
                        return redirect()
                            ->route('login')
                            ->with('status', __('app.verification_already_complete'));
                    }

                    $unknownEmail = $queryEmail;
                }

                $request->session()->forget('pending_verification_email');
            }
        } else {
            $pendingUser = $this->findUnverifiedUserByEmail(
                (string) session('pending_verification_email', ''),
            );

            if ($pendingUser === null && session()->has('pending_verification_email')) {
                $request->session()->forget('pending_verification_email');
            }
        }

        $pendingEmail = $pendingUser?->email;

        if ($pendingEmail) {
            $request->session()->put('pending_verification_email', $pendingEmail);
        }

        return view('auth.verify-email', [
            'pendingEmail' => $pendingEmail,
            'unknownEmail' => $unknownEmail,
            'devVerificationUrl' => ($pendingUser && config('app.debug'))
                ? VerificationEmail::verificationUrl($pendingUser)
                : session('dev_verification_url'),
        ]);
    }

    private function findUnverifiedUserByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = AuthUserLookup::findByEmail($email);

        return ($user && ! $user->hasVerifiedEmail()) ? $user : null;
    }
}
