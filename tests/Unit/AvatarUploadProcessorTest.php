<?php

namespace Tests\Unit;

use App\Support\AvatarUploadProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }
    }

    public function test_store_resizes_landscape_image_to_configured_square(): void
    {
        Storage::fake('public');

        $path = app(AvatarUploadProcessor::class)->store(
            UploadedFile::fake()->image('wide.jpg', 800, 400),
        );

        Storage::disk('public')->assertExists($path);
        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $size = (int) config('media.avatar.size', 100);

        $this->assertSame($size, $width);
        $this->assertSame($size, $height);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_store_center_crops_portrait_image_to_square(): void
    {
        Storage::fake('public');

        $path = app(AvatarUploadProcessor::class)->store(
            UploadedFile::fake()->image('tall.jpg', 400, 900),
        );

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame($width, $height);
    }

    public function test_store_keeps_small_square_images_at_target_size(): void
    {
        Storage::fake('public');

        $path = app(AvatarUploadProcessor::class)->store(
            UploadedFile::fake()->image('small.jpg', 50, 50),
        );

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame((int) config('media.avatar.size', 100), $width);
        $this->assertSame((int) config('media.avatar.size', 100), $height);
    }
}
