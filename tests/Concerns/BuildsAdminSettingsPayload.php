<?php

namespace Tests\Concerns;

use App\Support\HomeLayout;
use App\Support\MediaSettings;

trait BuildsAdminSettingsPayload
{
    /** @return array<string, int|string|array<int, string>> */
    protected function adminSettingsPayload(array $overrides = []): array
    {
        $post = MediaSettings::forPreset(MediaSettings::PRESET_POST);
        $gallery = MediaSettings::forPreset(MediaSettings::PRESET_GALLERY);

        return array_merge([
            'site_name' => config('app.name'),
            'site_tagline' => '',
            'home_layout' => HomeLayout::Discover->value,
            'locales_enabled' => ['en', 'ua'],
            'locale_default' => 'en',
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
        ], $overrides);
    }
}
