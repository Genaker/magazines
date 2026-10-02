<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /** Mark email verified from a signed link (guest-accessible; no session required). */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', __('app.email_already_verified'));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        session()->forget('pending_verification_email');

        return redirect()
            ->route('login')
            ->with('status', __('app.email_verified_sign_in'));
    }
}
