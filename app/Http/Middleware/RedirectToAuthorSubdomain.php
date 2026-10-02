<?php

namespace App\Http\Middleware;

use App\Models\AuthorAlias;
use App\Models\Post;
use App\Support\AuthorSubdomain;
use App\Support\MagazineSubdomain;
use App\Support\PostUrl;
use App\Support\SubdomainLabel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToAuthorSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AuthorSubdomain::redirectEnabled() || AuthorSubdomain::isSubdomainRequest($request)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if (preg_match('#^@([^/]+)$#', $path, $matches) === 1) {
            $username = SubdomainLabel::forNickname($matches[1]);
            $alias = AuthorAlias::query()->active()->where('username', $username)->first();

            if ($alias) {
                return redirect(AuthorSubdomain::authorHomeUrl($alias), 301);
            }
        }

        if (preg_match('#^@([^/]+)/([^/]+)$#', $path, $matches) === 1) {
            $username = SubdomainLabel::forNickname($matches[1]);
            $alias = AuthorAlias::query()->active()->where('username', $username)->first();

            if ($alias) {
                $post = Post::query()
                    ->where('author_alias_id', $alias->id)
                    ->where('slug', trim($matches[2], '.'))
                    ->published()
                    ->first();

                if ($post) {
                    return redirect(PostUrl::canonical($post), 301);
                }
            }
        }

        return $next($request);
    }
}
