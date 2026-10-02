<?php

namespace App\Http\Middleware;

use App\Support\SubdomainSession;
use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/** Run cookie + session middleware before subdomain views (no matching web route). */
final class SubdomainRequestSession
{
    /** @param  Closure(Request): Response  $respond */
    public static function throughWebSession(Request $request, Closure $respond): Response
    {
        if ($request->hasSession(true)) {
            return $respond($request);
        }

        SubdomainSession::configure();

        return app(Pipeline::class)
            ->send($request)
            ->through([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
            ])
            ->then(fn (Request $req) => $respond($req));
    }
}
