<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\ImageService;
use App\Support\MediaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_process_upload_skips_resize_when_disabled(): void
    {
        SiteSetting::setValue('media_post_do_not_resize', '1');

        $file = UploadedFile::fake()->image('wide.jpg', 2400, 1200);
        $processed = app(ImageService::class)->processUpload($file, 'editor', MediaSettings::PRESET_POST);

        $this->assertSame(['original' => $processed['path']], $processed['variants']);
    }

    public function test_process_upload_fits_image_inside_max_box(): void
    {
        SiteSetting::setValue('media_post_max_width', '1500');
        SiteSetting::setValue('media_post_max_height', '1500');

        $file = UploadedFile::fake()->image('wide.jpg', 3000, 1500);
        $processed = app(ImageService::class)->processUpload($file, 'editor', MediaSettings::PRESET_POST);

        $originalPath = Storage::disk('public')->path($processed['variants']['original']);
        [$width, $height] = getimagesize($originalPath);

        $this->assertLessThanOrEqual(1500, $width);
        $this->assertLessThanOrEqual(1500, $height);
        $this->assertSame(1500, max($width, $height));
    }

    public function test_url_returns_relative_path_for_local_disk(): void
    {
        $url = app(ImageService::class)->url('media/magazines/test/sm.jpg');

        $this->assertSame('/storage/media/magazines/test/sm.jpg', $url);
    }

    public function test_url_can_return_absolute_path_for_local_disk(): void
    {
        \Illuminate\Support\Facades\URL::forceRootUrl('http://lvh.me:8888');

        $url = app(ImageService::class)->url('media/magazines/test/sm.jpg', absolute: true);

        $this->assertSame('http://lvh.me:8888/storage/media/magazines/test/sm.jpg', $url);
    }
}
