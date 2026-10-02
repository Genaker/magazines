<?php

namespace App\Support;

use App\Models\Magazine;
use App\Models\AuthorAlias;
use App\Models\Post;
use App\Services\ImageService;
use App\Support\MediaSettings;
use App\Support\VideoEmbed;
use Illuminate\Support\Str;

/** Open Graph, Twitter Card, and meta tag payloads for pages. */
class Seo
{
    /** Default site-wide meta for pages without specific SEO data. */
    public static function defaults(): array
    {
        $siteName = SiteBranding::get('name');
        $tagline = SiteBranding::localizedTagline();

        return self::withImageMeta([
            'title' => $siteName,
            'description' => $tagline,
            'image' => self::defaultSiteImage(),
            'url' => url()->current(),
            'type' => 'website',
            'site_name' => $siteName,
        ]);
    }

    /** Meta tags for a published post (article type). */
    public static function forPost(Post $post): array
    {
        $imageService = app(ImageService::class);
        $post->loadMissing('galleryItems');

        $description = $post->subtitle;

        if (! $description) {
            if ($post->isGallery()) {
                $text = Str::limit(strip_tags((string) $post->body), 160);
                $description = filled($text)
                    ? $text
                    : trans_choice('app.gallery_photo_count', $post->galleryItems->count(), ['count' => $post->galleryItems->count()]);
            } elseif ($post->isVideo()) {
                $text = Str::limit(strip_tags((string) $post->body), 160);
                $description = filled($text) ? $text : __('app.post_type_video');
            } else {
                $description = Str::limit(strip_tags((string) $post->body), 160);
            }
        }

        $image = self::postShareImageUrl($post, $imageService) ?? self::defaultSiteImage();

        $title = $post->title;
        if ($post->relationLoaded('magazine') && $post->magazine) {
            $title = $post->title.' · '.$post->magazine->name;
        }

        return self::withImageMeta([
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'image_alt' => $post->title,
            'url' => PostUrl::canonical($post),
            'type' => 'article',
            'site_name' => SiteBranding::get('name'),
            'author' => $post->authorAlias?->name ?? $post->user->name,
            'published_time' => $post->published_at?->toIso8601String(),
            'modified_time' => $post->updated_at?->toIso8601String(),
            'section' => $post->category?->name,
            'twitter_site' => config('syndication.twitter_site'),
        ], $post->share_image_variants ?? $post->cover_variants);
    }

    /** Meta tags for a public author profile. */
    public static function forAuthor(AuthorAlias $alias): array
    {
        $description = $alias->bio
            ? Str::limit(strip_tags($alias->bio), 160)
            : 'Stories by '.$alias->name;

        return self::withImageMeta([
            'title' => $alias->name.' · '.SiteBranding::get('name'),
            'description' => $description,
            'image' => self::defaultSiteImage(),
            'url' => AuthorSubdomain::canonicalAuthorUrl($alias),
            'type' => 'profile',
            'site_name' => SiteBranding::get('name'),
        ]);
    }

    /** Meta tags for a magazine landing page. */
    public static function forMagazine(Magazine $magazine): array
    {
        $imageService = app(ImageService::class);

        return self::withImageMeta([
            'title' => $magazine->name,
            'description' => $magazine->description ?: 'Stories from '.$magazine->name,
            'image' => $imageService->url($magazine->logo_variants['md'] ?? $magazine->logo),
            'url' => MagazineSubdomain::canonicalMagazineUrl($magazine),
            'type' => 'website',
            'site_name' => SiteBranding::get('name'),
        ], $magazine->logo_variants ?? null);
    }

    /** Prefer share image variants, then cover, for social previews. */
    public static function postShareImageUrl(Post $post, ?ImageService $imageService = null): ?string
    {
        $imageService ??= app(ImageService::class);

        $variants = $post->share_image_variants ?? $post->cover_variants;
        $path = $post->share_image ?? $post->cover_image;

        $url = $imageService->url($variants['lg'] ?? $variants['md'] ?? $path)
            ?? $imageService->url($path);

        if ($url) {
            return $url;
        }

        if ($post->isVideo() && filled($post->video_url)) {
            return VideoEmbed::thumbnailUrl($post->video_url);
        }

        return null;
    }

    /** Fallback OG image from public/images when no post-specific image exists. */
    public static function defaultSiteImage(): ?string
    {
        if (is_file(public_path('images/og-default.png'))) {
            return asset('images/og-default.png');
        }

        if (is_file(public_path('images/favicon.png'))) {
            return asset('images/favicon.png');
        }

        return null;
    }

    /**
     * Attach image width/height meta when a responsive variant is available.
     *
     * @param  array<string, mixed>  $seo
     * @param  array<string, string>|null  $variants
     * @return array<string, mixed>
     */
    private static function withImageMeta(array $seo, ?array $variants = null): array
    {
        if (empty($seo['image'])) {
            return $seo;
        }

        if (str_starts_with((string) $seo['image'], '/')) {
            $seo['image'] = url($seo['image']);
        }

        $variantName = collect(['lg', 'md', 'sm'])
            ->first(fn (string $name) => isset($variants[$name]));

        if ($variantName) {
            $seo['image_width'] = MediaSettings::variants(MediaSettings::PRESET_POST)[$variantName];
            $seo['image_height'] = (int) round($seo['image_width'] * 9 / 16);
        }

        return $seo;
    }
}
