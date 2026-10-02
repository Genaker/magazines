<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MediaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, int|string> */
    private function defaultMediaFields(): array
    {
        $post = MediaSettings::forPreset(MediaSettings::PRESET_POST);
        $gallery = MediaSettings::forPreset(MediaSettings::PRESET_GALLERY);

        return [
            'media_post_width_sm' => $post['sm'],
            'media_post_width_md' => $post['md'],
            'media_post_width_lg' => $post['lg'],
            'media_post_jpeg_quality' => $post['jpeg_quality'],
            'media_post_do_not_resize' => $post['resize'] ? '0' : '1',
            'media_post_max_width' => $post['max_width'],
            'media_post_max_height' => $post['max_height'],
            'media_gallery_width_sm' => $gallery['sm'],
            'media_gallery_width_md' => $gallery['md'],
            'media_gallery_width_lg' => $gallery['lg'],
            'media_gallery_jpeg_quality' => $gallery['jpeg_quality'],
            'media_gallery_do_not_resize' => $gallery['resize'] ? '0' : '1',
            'media_gallery_max_width' => $gallery['max_width'],
            'media_gallery_max_height' => $gallery['max_height'],
        ];
    }

    public function test_media_settings_use_config_defaults_when_not_stored(): void
    {
        $post = MediaSettings::forPreset(MediaSettings::PRESET_POST);
        $gallery = MediaSettings::forPreset(MediaSettings::PRESET_GALLERY);

        $this->assertSame(config('media.post.variants.sm'), $post['sm']);
        $this->assertSame(config('media.gallery.variants.lg'), $gallery['lg']);
        $this->assertSame(config('media.post.jpeg_quality'), $post['jpeg_quality']);
        $this->assertSame(1500, $post['max_width']);
        $this->assertTrue($post['resize']);
    }

    public function test_super_admin_can_disable_image_resize(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => 'discover',
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
                ...$this->defaultMediaFields(),
                'media_post_do_not_resize' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertFalse(MediaSettings::resizeEnabled(MediaSettings::PRESET_POST));
    }

    public function test_super_admin_can_update_media_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => 'discover',
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
                ...$this->defaultMediaFields(),
                'media_post_width_sm' => 400,
                'media_post_width_md' => 900,
                'media_post_width_lg' => 1400,
                'media_post_jpeg_quality' => 75,
                'media_gallery_width_sm' => 320,
                'media_gallery_width_md' => 960,
                'media_gallery_width_lg' => 1600,
                'media_gallery_jpeg_quality' => 80,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('400', SiteSetting::getValue('media_post_width_sm'));
        $this->assertSame('80', SiteSetting::getValue('media_gallery_jpeg_quality'));
        $this->assertSame(960, MediaSettings::forPreset(MediaSettings::PRESET_GALLERY)['md']);
    }

    public function test_media_widths_must_be_in_ascending_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => 'discover',
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
                ...$this->defaultMediaFields(),
                'media_post_width_sm' => 1200,
                'media_post_width_md' => 800,
                'media_post_width_lg' => 1600,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('media_post_width_md');
    }
}
