<?php

namespace App\Http\Middleware;

use App\Support\RegistrationGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! RegistrationGate::registerRoutesEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}
