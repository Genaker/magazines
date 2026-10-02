<?php

namespace App\Http\Middleware;

use App\Support\SubdomainSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sets session cookie domain before StartSession when subdomains are enabled. */
class ConfigureSubdomainSession
{
    public function handle(Request $request, Closure $next): Response
    {
        SubdomainSession::configure();

        return $next($request);
    }
}
