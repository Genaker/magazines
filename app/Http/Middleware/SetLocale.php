<?php

namespace App\Http\Middleware;

use App\Support\AppInstaller;
use App\Support\SiteLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(AppInstaller::class)->isInstalled()) {
            $locale = SiteLocale::normalize(session('locale', config('site.default_locale', config('app.locale', 'en'))));
            app()->setLocale($locale);

            if (class_exists(\Carbon\Carbon::class)) {
                \Carbon\Carbon::setLocale(config("locales.intl.{$locale}", $locale));
            }

            return $next($request);
        }

        $locale = SiteLocale::normalize(session('locale', SiteLocale::default()));

        if (! SiteLocale::isEnabled($locale)) {
            $locale = SiteLocale::default();
        }

        app()->setLocale($locale);

        if (class_exists(\Carbon\Carbon::class)) {
            \Carbon\Carbon::setLocale(config("locales.intl.{$locale}", $locale));
        }

        return $next($request);
    }
}
