<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\HomeLayout;
use App\Support\MediaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
    }

    public function test_super_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'My Blog',
                'site_tagline' => 'Stories for everyone',
                'home_layout' => HomeLayout::Discover->value,
                'locales_enabled' => ['en', 'ua'],
                'locale_default' => 'en',
                'features' => [
                    'registration' => '1',
                    'magazines' => '1',
                ],
                ...$this->defaultMediaFields(),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('My Blog', SiteSetting::getValue('site_name'));
        $this->assertSame('Stories for everyone', SiteSetting::getValue('site_tagline'));
        $this->assertTrue(Features::enabled('registration'));
    }

    public function test_super_admin_settings_page_lists_features(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Features')
            ->assertSee('Magazines')
            ->assertSee('Images')
            ->assertSee('Post images');
    }

    public function test_regular_admin_cannot_access_site_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::User]);
        $admin->update(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_super_admin_can_enable_disqus_comments(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => HomeLayout::Discover->value,
                'locales_enabled' => ['en', 'ua'],
                'locale_default' => 'en',
                'comments_use_disqus' => '1',
                'disqus_shortname' => 'myforum',
                ...$this->defaultMediaFields(),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('1', SiteSetting::getValue('comments_use_disqus'));
        $this->assertSame('myforum', SiteSetting::getValue('disqus_shortname'));
    }

    public function test_disqus_shortname_required_when_disqus_enabled(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => HomeLayout::Discover->value,
                'locales_enabled' => ['en', 'ua'],
                'locale_default' => 'en',
                'comments_use_disqus' => '1',
                ...$this->defaultMediaFields(),
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('disqus_shortname');
    }

    public function test_super_admin_settings_page_lists_author_subdomain_docs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Author subdomain pages')
            ->assertSee('DNS & web server setup');
    }

    public function test_super_admin_can_configure_author_subdomains(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                ...$this->defaultMediaFields(),
                'site_name' => config('app.name'),
                'site_tagline' => '',
                'home_layout' => HomeLayout::Discover->value,
                'locales_enabled' => ['en', 'ua'],
                'locale_default' => 'en',
                'author_subdomain_base_host' => 'example.com',
                'author_subdomain_redirect' => '1',
                'features' => [
                    'author_subdomains' => '1',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('example.com', \App\Models\SiteSetting::getValue('author_subdomain_base_host'));
        $this->assertTrue(\App\Support\AuthorSubdomain::redirectEnabled());
        $this->assertTrue(Features::enabled('author_subdomains'));
    }

    public function test_super_admin_can_clear_base_host_to_use_app_url(): void
    {
        \App\Support\AuthorSubdomain::setBaseHost('custom.test');
        Features::set('author_subdomains', true);

        AuthorSubdomain::setBaseHost(null);
        \App\Support\SubdomainSession::configure();

        $this->assertNull(\App\Models\SiteSetting::getValue('author_subdomain_base_host'));
        $this->assertTrue(\App\Support\AuthorSubdomain::usesAutoDetectedBaseHost());
        $this->assertSame('http://127.0.0.1:8000/admin/settings', route('admin.settings.edit'));
    }
}
