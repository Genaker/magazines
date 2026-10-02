<?php

namespace App\Http\Middleware;

use App\Support\AppInstaller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsInstalled
{
    public function __construct(private AppInstaller $installer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installer->isInstalled()) {
            return $next($request);
        }

        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return response()->json(['message' => 'Application is not installed.'], 503);
        }

        return redirect()->route('install.index');
    }
}
