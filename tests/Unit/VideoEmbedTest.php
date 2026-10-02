<?php

namespace Tests\Unit;

use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    #[DataProvider('supportedUrlsProvider')]
    public function test_it_parses_supported_video_urls(string $url, string $provider): void
    {
        $parsed = VideoEmbed::parse($url);

        $this->assertNotNull($parsed);
        $this->assertSame($provider, $parsed['provider']);
        $this->assertNotEmpty($parsed['embed_url']);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function supportedUrlsProvider(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube'],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', 'youtube'],
            'youtube shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube'],
            'vimeo' => ['https://vimeo.com/123456789', 'vimeo'],
            'dailymotion' => ['https://www.dailymotion.com/video/x8abc123', 'dailymotion'],
            'wistia' => ['https://company.wistia.com/medias/abc123def', 'wistia'],
            'streamable' => ['https://streamable.com/abc123', 'streamable'],
            'loom' => ['https://www.loom.com/share/abc123def456', 'loom'],
        ];
    }

    public function test_it_rejects_unsupported_urls(): void
    {
        $this->assertNull(VideoEmbed::parse('https://example.com/video.mp4'));
        $this->assertNull(VideoEmbed::parse('not-a-url'));
    }

    public function test_youtube_thumbnail_url(): void
    {
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        $this->assertSame(
            'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            VideoEmbed::thumbnailUrl($url),
        );
    }
}
