<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\PostController;
use App\Models\AuthorAlias;
use App\Models\Magazine;
use App\Support\AdminPath;
use App\Support\AuthorSubdomain;
use App\Support\MagazineSubdomain;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Serves author profiles and posts on {username}.{site-host} without fixed domain routes. */
class ServeAuthorSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenancy::enabled() && Tenancy::isRegisteredTenantHost($request->getHost())) {
            return $next($request);
        }

        $username = AuthorSubdomain::usernameFromHost($request);

        if ($username === null) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (! AuthorSubdomain::enabled()) {
            if (Tenancy::enabled() && Tenancy::matchingTenantDomainSuffix($request->getHost()) !== null) {
                return $next($request);
            }

            $magazineSlug = MagazineSubdomain::parseSlugFromHost($request->getHost());

            if ($magazineSlug !== null && Magazine::query()->where('slug', $magazineSlug)->exists()) {
                return $next($request);
            }

            return AuthorSubdomain::redirectToMainSite($request, $this->mainSitePathForDisabledSubdomain($username, $path));
        }

        $firstSegment = explode('/', $path)[0] ?? '';

        if ($path !== '' && (in_array($firstSegment, AdminPath::blockedPathSegments(), true) || str_starts_with($path, 'feed.'))) {
            if ($this->shouldPassThroughToMainRoutes($request)) {
                return $next($request);
            }

            return AuthorSubdomain::redirectToMainSite($request, $path);
        }

        $alias = AuthorAlias::query()->active()->where('username', $username)->firstOrFail();

        if ($path === '') {
            return $this->toResponse($request, app(AuthorController::class)->show($alias));
        }

        if ($path === 'avatar') {
            return $this->toResponse($request, app(AvatarController::class)->author($alias));
        }

        if (! str_contains($path, '/')) {
            return $this->toResponse($request, app(PostController::class)->show($alias, $path));
        }

        abort(404);
    }

    private function mainSitePathForDisabledSubdomain(string $username, string $path): string
    {
        $base = '@'.$username;

        if ($path === '') {
            return $base;
        }

        if (! str_contains($path, '/')) {
            return $base.'/'.$path;
        }

        return $path;
    }

    /** AJAX and mutating requests stay on the subdomain host and hit normal web routes. */
    private function shouldPassThroughToMainRoutes(Request $request): bool
    {
        return ! $request->isMethod('GET') || $request->expectsJson();
    }

    private function toResponse(Request $request, View|RedirectResponse|Response $result): Response
    {
        return SubdomainRequestSession::throughWebSession(
            $request,
            fn (Request $req) => app(Router::class)->toResponse($req, $result),
        );
    }
}
