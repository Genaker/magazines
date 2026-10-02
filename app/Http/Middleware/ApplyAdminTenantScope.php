<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyAdminTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Tenancy::enabled()) {
            TenantContext::setAdminScope(Tenancy::defaultTenantId());

            return $next($request);
        }

        if (TenantContext::allowsPlatformTenantManagement($request->getHost())) {
            $scopeId = $request->session()->get('admin_tenant_id');
            TenantContext::setAdminScope($scopeId !== null ? (int) $scopeId : null);
        } else {
            TenantContext::setAdminScope(TenantContext::id());
        }

        return $next($request);
    }
}
