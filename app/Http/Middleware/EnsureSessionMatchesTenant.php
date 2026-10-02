<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Log out tenant users when their session belongs to a different tenant host. */
class EnsureSessionMatchesTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Tenancy::enabled() || ! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantId = TenantContext::id();

        if ($tenantId === null || $user->tenant_id === null || $user->tenant_id === $tenantId) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $next($request);
    }
}
