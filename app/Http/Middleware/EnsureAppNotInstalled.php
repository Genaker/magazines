<?php

namespace App\Http\Middleware;

use App\Support\AppInstaller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppNotInstalled
{
    public function __construct(private AppInstaller $installer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installer->isInstalled()) {
            return $next($request);
        }

        return redirect()->route('home');
    }
}
