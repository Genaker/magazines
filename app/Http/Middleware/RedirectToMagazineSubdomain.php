<?php

namespace App\Http\Middleware;

use App\Models\Magazine;
use App\Support\MagazineSubdomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToMagazineSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! MagazineSubdomain::redirectEnabled() || MagazineSubdomain::isSubdomainRequest($request)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (preg_match('#^magazine/([^/]+)$#', $path, $matches) === 1) {
            $magazine = Magazine::query()->where('slug', $matches[1])->first();

            if ($magazine) {
                $query = $request->query();

                return redirect(MagazineSubdomain::magazineUrl($magazine, $query), 301);
            }
        }

        return $next($request);
    }
}
