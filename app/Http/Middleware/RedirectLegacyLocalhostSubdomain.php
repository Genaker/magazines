<?php

namespace App\Http\Middleware;

use App\Support\AuthorSubdomain;
use App\Support\MagazineSubdomain;
use App\Support\SubdomainLabel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Redirect *.localhost dev URLs to the configured subdomain base host (e.g. lvh.me). */
class RedirectLegacyLocalhostSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $base = AuthorSubdomain::baseHost();

        if (in_array($base, ['localhost', '127.0.0.1'], true)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());

        if ($host === 'localhost' || $host === '127.0.0.1') {
            return redirect(
                AuthorSubdomain::mainSiteUrl(ltrim($request->getRequestUri(), '/')),
                301,
            );
        }

        if (! str_ends_with($host, '.localhost')) {
            return $next($request);
        }

        $label = substr($host, 0, -strlen('.localhost'));

        if ($label === '' || str_contains($label, '.')) {
            return $next($request);
        }

        if (! AuthorSubdomain::enabled() && ! MagazineSubdomain::enabled()) {
            $path = ltrim($request->getRequestUri(), '/');

            return redirect(
                AuthorSubdomain::mainSiteUrl($this->mainSitePathForLabel($label, $path)),
                301,
            );
        }

        $portSuffix = $this->portSuffix($request);

        return redirect(
            $request->getScheme().'://'
            .SubdomainLabel::normalize($label).'.'.$base
            .$portSuffix
            .$request->getRequestUri(),
            301,
        );
    }

    private function mainSitePathForLabel(string $label, string $path): string
    {
        $normalized = SubdomainLabel::normalize($label);

        if ($path === '') {
            return '@'.$normalized;
        }

        if (! str_contains($path, '/')) {
            return '@'.$normalized.'/'.$path;
        }

        return $path;
    }

    private function portSuffix(Request $request): string
    {
        $port = $request->getPort();
        $parsed = parse_url((string) config('app.url', ''));
        $appPort = isset($parsed['port']) ? (int) $parsed['port'] : null;

        if (in_array($port, [80, 443], true)) {
            $port = $appPort;
        }

        if ($port === null || in_array($port, [80, 443], true)) {
            return '';
        }

        return ':'.$port;
    }
}
