<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthenticatedSessionController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        if (! Features::enabled('magic_link_login')) {
            return view('auth.admin-login-password');
        }

        if ($request->boolean('change_email')) {
            $request->session()->forget('magic_login_email');

            return redirect()->route('admin.login');
        }

        return view('auth.admin-login');
    }

    public function createPassword(): View
    {
        return view('auth.admin-login-password');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user?->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'You do not have permission to access the admin area.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
