<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthorAlias;
use App\Models\User;
use App\Support\Features;
use App\Support\MagicLoginIssuer;
use App\Support\RegistrationGate;
use App\Support\SubdomainLabel;
use App\Support\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $inviteCode = $request->query('invite');

        if ($inviteCode) {
            $invite = RegistrationGate::findValidInvite($inviteCode);

            if ($invite) {
                session(['registration_invite_code' => $invite->code]);
            }
        }

        return view('auth.register', [
            'requiresInvite' => RegistrationGate::requiresInviteCode(),
            'inviteCode' => session('registration_invite_code'),
            'magicLinkRegistration' => Features::enabled('magic_link_login'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $magicLinkOnly = $request->boolean('magic_link_only') && Features::enabled('magic_link_login');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        ];

        if ($magicLinkOnly) {
            $rules['magic_link_only'] = ['accepted'];
        } else {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        if (RegistrationGate::requiresInviteCode()) {
            $rules['invite_code'] = ['required', 'string', 'max:32'];
        }

        $request->merge([
            'username' => SubdomainLabel::forNickname((string) $request->input('username', '')),
        ]);

        $request->validate($rules);

        $invite = null;

        if (RegistrationGate::requiresInviteCode()) {
            $code = $request->input('invite_code', session('registration_invite_code'));
            $invite = RegistrationGate::findValidInvite($code);

            if (! $invite) {
                throw ValidationException::withMessages([
                    'invite_code' => 'This invite code is invalid or has expired.',
                ]);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $magicLinkOnly ? null : Hash::make($request->password),
        ]);

        AuthorAlias::createFromUser($user, isPrimary: true);

        $invite?->redeem();
        session()->forget('registration_invite_code');

        if (Features::enabled('magic_link_login')) {
            return $this->redirectToLoginCodeStep($request, $user);
        }

        $result = VerificationEmail::send($user);

        $redirect = redirect(route('verification.notice', ['email' => $user->email], absolute: false))
            ->with('pending_verification_email', $user->email);

        if (config('app.debug') && $result['url']) {
            $redirect = $redirect->with('dev_verification_url', $result['url']);
        }

        if (! $result['sent']) {
            return $redirect->with('verification_mail_failed', true);
        }

        return $redirect;
    }

    private function redirectToLoginCodeStep(Request $request, User $user): RedirectResponse
    {
        $result = MagicLoginIssuer::send(
            $user,
            'login.magic.verify',
            $request->getSchemeAndHttpHost(),
        );

        $redirect = redirect(route('login', absolute: false))
            ->with('magic_login_email', $user->email)
            ->with('status', 'registration-code-sent');

        if (! $result['sent']) {
            return $redirect->withErrors(['email' => $result['error']]);
        }

        return $redirect
            ->with('dev_login_code', config('app.debug') ? $result['code'] : null)
            ->with('dev_mailpit_url', config('app.debug') ? config('mail.mailpit_web_url') : null);
    }
}
