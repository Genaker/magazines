<?php

namespace App\Http\Middleware;

use App\Http\Controllers\MagazineController;
use App\Http\Controllers\PostController;
use App\Models\AuthorAlias;
use App\Models\Magazine;
use App\Models\Tenant;
use App\Support\AdminPath;
use App\Support\AuthorSubdomain;
use App\Support\MagazineSubdomain;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use App\Support\SubdomainLabel;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Serves magazine pages and magazine posts on {slug}.{site-host}. */
class ServeMagazineSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenancy::enabled() && Tenancy::isRegisteredTenantHost($request->getHost())) {
            return $next($request);
        }

        $slug = MagazineSubdomain::parseSlugFromHost($request->getHost());

        if ($slug === null) {
            return $next($request);
        }

        if (! MagazineSubdomain::enabled()) {
            if (Tenancy::enabled() && Tenancy::matchingTenantDomainSuffix($request->getHost()) !== null) {
                return $next($request);
            }

            if ($this->magazineForSubdomainSlug($slug) !== null) {
                abort(404);
            }

            return $next($request);
        }

        $path = trim($request->path(), '/');
        $firstSegment = explode('/', $path)[0] ?? '';

        if ($path !== '' && (in_array($firstSegment, AdminPath::blockedPathSegments(), true) || str_starts_with($path, 'feed.'))) {
            if ($this->shouldPassThroughToMainRoutes($request)) {
                return $next($request);
            }

            return AuthorSubdomain::redirectToMainSite($request, $path);
        }

        if (preg_match('#^@([^/]+)$#', $path, $matches) === 1) {
            $alias = AuthorAlias::query()->active()->where('username', SubdomainLabel::normalize($matches[1]))->first();

            if ($alias) {
                return redirect(AuthorSubdomain::canonicalAuthorUrl($alias));
            }

            return AuthorSubdomain::redirectToMainSite($request, $path);
        }

        $magazine = $this->magazineForSubdomainSlug($slug);

        if ($magazine === null) {
            return $next($request);
        }

        if ($path === '') {
            return $this->serveMagazine($request, $magazine, fn () => app(MagazineController::class)->show($magazine, $request));
        }

        if (! str_contains($path, '/')) {
            return $this->serveMagazine($request, $magazine, fn () => app(PostController::class)->showForMagazine($magazine, $path));
        }

        abort(404);
    }

    private function magazineForSubdomainSlug(string $slug): ?Magazine
    {
        return Magazine::withoutGlobalScope('tenant')->where('slug', $slug)->first();
    }

    /** @param  callable(): (View|RedirectResponse|Response)  $action */
    private function serveMagazine(Request $request, Magazine $magazine, callable $action): Response
    {
        if ($magazine->tenant_id !== null) {
            $tenant = Tenant::query()->find($magazine->tenant_id);

            if ($tenant !== null) {
                TenantContext::set($tenant);
            }
        }

        return $this->toResponse($request, $action());
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
