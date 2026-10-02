<?php

namespace Tests\Unit;

use App\Support\EmbeddedMedia;
use PHPUnit\Framework\TestCase;

class EmbeddedMediaTest extends TestCase
{
    public function test_it_strips_width_and_height_from_iframes_and_videos(): void
    {
        $html = '<p>Watch:</p><iframe width="560" height="314" style="max-width:560px" src="https://www.youtube.com/embed/test" title="YouTube" allowfullscreen></iframe>'
            .'<video width="640" height="360" controls src="/clip.mp4"></video>';

        $normalized = EmbeddedMedia::normalize($html);

        $this->assertStringNotContainsString('width=', $normalized);
        $this->assertStringNotContainsString('height=', $normalized);
        $this->assertStringNotContainsString('style=', $normalized);
        $this->assertStringContainsString('youtube.com/embed/test', $normalized);
        $this->assertStringContainsString('/clip.mp4', $normalized);
    }

    public function test_it_leaves_unrelated_html_unchanged(): void
    {
        $html = '<p>Hello</p><img width="100" height="80" src="/photo.jpg" alt="Photo">';

        $this->assertSame($html, EmbeddedMedia::normalize($html));
    }
}
