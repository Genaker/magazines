<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLink;
use App\Support\AuthUserLookup;
use App\Support\MagicLoginIssuer;
use App\Support\MagicLoginLock;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MagicLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        return $this->sendMagicLink($request, forAdmin: false);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        return $this->sendMagicLink($request, forAdmin: true);
    }

    public function verify(Request $request): RedirectResponse
    {
        return $this->verifyToken($request, forAdmin: false);
    }

    public function verifyAdmin(Request $request): RedirectResponse
    {
        return $this->verifyToken($request, forAdmin: true);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        return $this->verifyLoginCode($request, forAdmin: false);
    }

    public function verifyCodeAdmin(Request $request): RedirectResponse
    {
        return $this->verifyLoginCode($request, forAdmin: true);
    }

    private function sendMagicLink(Request $request, bool $forAdmin): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $routes = $this->routes($forAdmin);
        $isResend = $request->session()->get('magic_login_email') === $data['email'];
        $request->session()->put('magic_login_email', $data['email']);

        $response = redirect()
            ->route($routes['login'])
            ->with('status', $isResend ? 'magic-link-resent' : 'magic-link-sent');

        $user = AuthUserLookup::findByEmail($data['email']);

        if (! $user || $user->is_banned || ($forAdmin && ! $user->isAdmin())) {
            return $response;
        }

        if (MagicLoginLock::isLocked($user)) {
            return $response->withErrors([
                'email' => __('app.magic_login_locked'),
            ]);
        }

        $result = MagicLoginIssuer::send(
            $user,
            $routes['verify'],
            $request->getSchemeAndHttpHost(),
        );

        if (! $result['sent']) {
            return $response->withErrors(['email' => $result['error']]);
        }

        return $response
            ->with('dev_login_code', config('app.debug') ? $result['code'] : null)
            ->with('dev_mailpit_url', config('app.debug') ? config('mail.mailpit_web_url') : null);
    }

    private function verifyToken(Request $request, bool $forAdmin): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $routes = $this->routes($forAdmin);
        $user = AuthUserLookup::findByEmail($request->email);

        if ($user && MagicLoginLock::isLocked($user)) {
            return $this->lockedRedirect($request, $forAdmin);
        }

        $link = LoginLink::query()
            ->where('email', $request->email)
            ->where('token', hash('sha256', $request->token))
            ->latest('id')
            ->first();

        if (! $link?->isValid()) {
            $user?->exists && MagicLoginLock::recordFailure($user);
            $request->session()->put('magic_login_email', $request->email);

            return redirect()
                ->route($routes['login'])
                ->withErrors(['code' => __('app.magic_link_invalid')]);
        }

        return $this->completeLogin($link, $request, $forAdmin);
    }

    private function verifyLoginCode(Request $request, bool $forAdmin): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = AuthUserLookup::findByEmail($data['email']);

        if ($user && MagicLoginLock::isLocked($user)) {
            throw ValidationException::withMessages([
                'code' => __('app.magic_login_locked'),
            ]);
        }

        $link = LoginLink::query()
            ->where('email', $data['email'])
            ->where('code', hash('sha256', $data['code']))
            ->latest('id')
            ->first();

        if (! $link?->isValid()) {
            $user?->exists && MagicLoginLock::recordFailure($user);
            $request->session()->put('magic_login_email', $data['email']);

            throw ValidationException::withMessages([
                'code' => __('app.magic_code_invalid'),
            ]);
        }

        return $this->completeLogin($link, $request, $forAdmin);
    }

    private function completeLogin(LoginLink $link, Request $request, bool $forAdmin): RedirectResponse
    {
        $routes = $this->routes($forAdmin);
        $user = AuthUserLookup::findByEmail($link->email);

        if (! $user || $user->is_banned) {
            throw ValidationException::withMessages([
                'email' => 'Unable to sign in.',
            ]);
        }

        if ($forAdmin && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'You do not have permission to access the admin area.',
            ]);
        }

        if (MagicLoginLock::isLocked($user)) {
            return $this->lockedRedirect($request, $forAdmin);
        }

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            $request->session()->forget('pending_verification_email');
        }

        $link->update(['used_at' => now()]);
        MagicLoginLock::clear($user);

        Auth::login($user);
        $request->session()->forget('magic_login_email');
        $request->session()->regenerate();

        return redirect()->intended(route($routes['intended'], absolute: false));
    }

    private function lockedRedirect(Request $request, bool $forAdmin): RedirectResponse
    {
        $routes = $this->routes($forAdmin);
        $request->session()->put('magic_login_email', $request->input('email'));

        return redirect()
            ->route($routes['login'])
            ->withErrors(['email' => __('app.magic_login_locked')]);
    }

    /** @return array{login: string, verify: string, intended: string} */
    private function routes(bool $forAdmin): array
    {
        if ($forAdmin) {
            return [
                'login' => 'admin.login',
                'verify' => 'admin.login.magic.verify',
                'intended' => 'admin.dashboard',
            ];
        }

        return [
            'login' => 'login',
            'verify' => 'login.magic.verify',
            'intended' => 'home',
        ];
    }
}
