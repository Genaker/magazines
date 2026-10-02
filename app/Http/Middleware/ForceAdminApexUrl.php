<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use App\Support\SiteUrl;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Keep platform admin on the apex host and generate admin URLs without tenant subdomains. */
class ForceAdminApexUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteUrl::shouldUseAdminApexUrl()) {
            return $next($request);
        }

        $rootUrl = Tenancy::adminRootUrl();

        if ($rootUrl === null) {
            return $next($request);
        }

        URL::forceRootUrl($rootUrl);

        $rootHost = TenantDomain::normalizeHost((string) parse_url($rootUrl, PHP_URL_HOST));
        $currentHost = TenantDomain::normalizeHost($request->getHost());

        if ($rootHost !== '' && $currentHost !== $rootHost && $request->isMethod('GET') && ! $request->expectsJson()) {
            $target = rtrim($rootUrl, '/').'/'.ltrim($request->path(), '/');

            if ($request->getQueryString()) {
                $target .= '?'.$request->getQueryString();
            }

            return redirect($target);
        }

        return $next($request);
    }
}
