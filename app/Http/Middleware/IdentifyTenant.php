<?php

namespace App\Http\Middleware;

use App\Support\AdminPath;
use App\Support\AppInstaller;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(private TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::reset();

        if (! app(AppInstaller::class)->isInstalled()) {
            return $next($request);
        }

        $host = $request->getHost();
        $allowAppTopFallback = $this->allowsAppTopDomainFallback($request);
        $tenant = $this->resolver->resolve($host, $allowAppTopFallback);
        TenantContext::set($tenant);

        if (! $tenant->isActive()) {
            $explicit = $this->resolver->hostIsExplicitlyMapped($host);

            abort($explicit ? 404 : 503, $explicit
                ? 'This site is not available.'
                : 'This site is temporarily unavailable.');
        }

        return $next($request);
    }

    /** Platform apex (e.g. lvh.me): admin routes when default tenant has a strict domain. */
    private function allowsAppTopDomainFallback(Request $request): bool
    {
        if (! Tenancy::isAppTopDomain($request->getHost())) {
            return false;
        }

        return AdminPath::appliesTo($request);
    }
}
