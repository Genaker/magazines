<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'You do not have permission to access the admin area.',
            ]);
        }

        if (Tenancy::enabled()) {
            if (TenantContext::requiresPlatformHost($request->getHost())) {
                if (! $user->isSuperAdmin()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('admin.login')->withErrors([
                        'email' => 'Platform admin access requires a super admin account.',
                    ]);
                }
            } elseif ($user->tenant_id !== null && $user->tenant_id !== TenantContext::id()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')->withErrors([
                    'email' => 'You do not have permission to access this tenant admin area.',
                ]);
            }
        }

        return $next($request);
    }
}
