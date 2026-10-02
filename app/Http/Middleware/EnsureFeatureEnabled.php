<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Abort with 404 when the requested feature toggle is disabled. */
class EnsureFeatureEnabled
{
    /**
     * @param  string  $feature  Feature key from config/features.php
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! Features::enabled($feature)) {
            abort(404);
        }

        return $next($request);
    }
}
