<?php

namespace App\Support;

/** Parse public video URLs and build safe provider iframe embeds. */
class VideoEmbed
{
    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string}|null */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (self::isYouTubeHost($host)) {
            return self::parseYouTube($host, $path, $query);
        }

        if ($host === 'youtu.be') {
            $id = trim($path, '/');

            return self::youtubeResult($id);
        }

        if ($host === 'vimeo.com') {
            return self::parseVimeoPath($path);
        }

        if ($host === 'player.vimeo.com') {
            if (preg_match('#/video/(\d+)#', $path, $matches)) {
                return self::vimeoResult($matches[1]);
            }
        }

        if ($host === 'dailymotion.com' || $host === 'dai.ly') {
            return self::parseDailymotion($host, $path, $url);
        }

        if (str_ends_with($host, 'wistia.com') || $host === 'wistia.net') {
            if (preg_match('#/medias/([a-z0-9]+)#i', $path, $matches)
                || preg_match('#/embed/iframe/([a-z0-9]+)#i', $path, $matches)) {
                return self::wistiaResult($matches[1]);
            }
        }

        if ($host === 'twitch.tv' || $host === 'www.twitch.tv') {
            if (preg_match('#/videos/(\d+)#', $path, $matches)) {
                return self::twitchVideoResult($matches[1]);
            }
        }

        if ($host === 'clips.twitch.tv') {
            $clip = trim($path, '/');

            if ($clip !== '') {
                return self::twitchClipResult($clip);
            }
        }

        if ($host === 'streamable.com') {
            $id = trim($path, '/');

            if ($id !== '') {
                return [
                    'provider' => 'streamable',
                    'video_id' => $id,
                    'embed_url' => 'https://streamable.com/e/'.$id,
                    'thumbnail_url' => null,
                ];
            }
        }

        if ($host === 'loom.com') {
            if (preg_match('#/(?:share|embed)/([a-zA-Z0-9]+)#', $path, $matches)) {
                return [
                    'provider' => 'loom',
                    'video_id' => $matches[1],
                    'embed_url' => 'https://www.loom.com/embed/'.$matches[1],
                    'thumbnail_url' => null,
                ];
            }
        }

        if ($host === 'facebook.com' || $host === 'fb.watch') {
            return self::parseFacebook($host, $path, $query, $url);
        }

        return null;
    }

    public static function isSupported(?string $url): bool
    {
        return self::parse($url) !== null;
    }

    public static function iframeHtml(?string $url, ?string $title = null): ?string
    {
        $parsed = self::parse($url);

        if (! $parsed) {
            return null;
        }

        $titleAttr = htmlspecialchars($title ?: 'Video', ENT_QUOTES, 'UTF-8');
        $src = htmlspecialchars($parsed['embed_url'], ENT_QUOTES, 'UTF-8');
        $allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

        return '<iframe src="'.$src.'" title="'.$titleAttr.'" allow="'.$allow.'" allowfullscreen loading="lazy" class="absolute inset-0 h-full w-full border-0"></iframe>';
    }

    public static function thumbnailUrl(?string $url): ?string
    {
        return self::parse($url)['thumbnail_url'] ?? null;
    }

    private static function isYouTubeHost(string $host): bool
    {
        return $host === 'youtube.com'
            || $host === 'm.youtube.com'
            || $host === 'music.youtube.com'
            || str_ends_with($host, '.youtube.com');
    }

    /** @param  array<string, string>  $query */
    private static function parseYouTube(string $host, string $path, array $query): ?array
    {
        if (str_starts_with($path, '/watch') && ! empty($query['v'])) {
            return self::youtubeResult($query['v']);
        }

        if (preg_match('#^/(?:embed|live|shorts)/([a-zA-Z0-9_-]{6,})#', $path, $matches)) {
            return self::youtubeResult($matches[1]);
        }

        return null;
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function youtubeResult(string $id): ?array
    {
        $id = trim($id);

        if ($id === '' || ! preg_match('/^[a-zA-Z0-9_-]{6,}$/', $id)) {
            return null;
        }

        return [
            'provider' => 'youtube',
            'video_id' => $id,
            'embed_url' => 'https://www.youtube.com/embed/'.$id,
            'thumbnail_url' => 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg',
        ];
    }

    private static function parseVimeoPath(string $path): ?array
    {
        if (preg_match('#/(?:video/)?(\d+)#', $path, $matches)) {
            return self::vimeoResult($matches[1]);
        }

        return null;
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function vimeoResult(string $id): array
    {
        return [
            'provider' => 'vimeo',
            'video_id' => $id,
            'embed_url' => 'https://player.vimeo.com/video/'.$id,
            'thumbnail_url' => null,
        ];
    }

    private static function parseDailymotion(string $host, string $path, string $url): ?array
    {
        if ($host === 'dai.ly') {
            $id = trim($path, '/');

            if ($id !== '') {
                return self::dailymotionResult($id);
            }
        }

        if (preg_match('#/video/([a-zA-Z0-9]+)#', $path, $matches)) {
            return self::dailymotionResult($matches[1]);
        }

        if (preg_match('#[?&]video=([a-zA-Z0-9]+)#', $url, $matches)) {
            return self::dailymotionResult($matches[1]);
        }

        return null;
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function dailymotionResult(string $id): array
    {
        return [
            'provider' => 'dailymotion',
            'video_id' => $id,
            'embed_url' => 'https://www.dailymotion.com/embed/video/'.$id,
            'thumbnail_url' => 'https://www.dailymotion.com/thumbnail/video/'.$id,
        ];
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function wistiaResult(string $id): array
    {
        return [
            'provider' => 'wistia',
            'video_id' => $id,
            'embed_url' => 'https://fast.wistia.net/embed/iframe/'.$id,
            'thumbnail_url' => null,
        ];
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function twitchVideoResult(string $id): array
    {
        $parent = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return [
            'provider' => 'twitch',
            'video_id' => $id,
            'embed_url' => 'https://player.twitch.tv/?video='.$id.'&parent='.$parent,
            'thumbnail_url' => null,
        ];
    }

    /** @return array{provider: string, embed_url: string, thumbnail_url: ?string, video_id: string} */
    private static function twitchClipResult(string $clip): array
    {
        $parent = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return [
            'provider' => 'twitch',
            'video_id' => $clip,
            'embed_url' => 'https://clips.twitch.tv/embed?clip='.$clip.'&parent='.$parent,
            'thumbnail_url' => null,
        ];
    }

    /** @param  array<string, string>  $query */
    private static function parseFacebook(string $host, string $path, array $query, string $url): ?array
    {
        $videoId = $query['v'] ?? null;

        if ($host === 'fb.watch') {
            return [
                'provider' => 'facebook',
                'video_id' => trim($path, '/'),
                'embed_url' => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url).'&show_text=false',
                'thumbnail_url' => null,
            ];
        }

        if ($videoId) {
            return [
                'provider' => 'facebook',
                'video_id' => $videoId,
                'embed_url' => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url).'&show_text=false',
                'thumbnail_url' => null,
            ];
        }

        if (preg_match('#/videos/(\d+)#', $path, $matches)) {
            return [
                'provider' => 'facebook',
                'video_id' => $matches[1],
                'embed_url' => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($url).'&show_text=false',
                'thumbnail_url' => null,
            ];
        }

        return null;
    }
}
