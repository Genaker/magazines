<?php

namespace App\Http\Middleware;

use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Use a dedicated session cookie for admin routes before StartSession runs. */
class ConfigureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminSession::appliesTo($request)) {
            AdminSession::configure();
        }

        return $next($request);
    }
}
