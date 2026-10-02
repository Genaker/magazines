<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AppInstaller;
use App\Support\HomeLayout;
use App\Support\SiteBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AppInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['installer.enforce_in_tests' => true]);
        File::delete(storage_path('app/.installed'));
    }

    /** @return array<string, mixed> */
    private function installPayload(array $overrides = []): array
    {
        return array_merge([
            'name_brand' => 'My Magazine',
            'tagline' => 'Local voices',
            'footer_tagline' => 'Stories for everyone',
            'footer_rights' => 'All rights reserved.',
            'copyright_start_year' => '2020',
            'theme_color' => '#112233',
            'background_color' => '#f3f4f6',
            'home_layout' => HomeLayout::Latest->value,
            'locales_enabled' => ['en'],
            'locale_default' => 'en',
            'name' => 'Site Owner',
            'username' => 'owner',
            'email' => 'owner@example.test',
            'password' => bcrypt('password'),
            'app_url' => 'http://127.0.0.1:8000',
        ], $overrides);
    }

    public function test_uninstalled_app_redirects_to_installer(): void
    {
        $this->get('/')->assertRedirect(route('install.index'));
        $this->get('/login')->assertRedirect(route('install.index'));
    }

    public function test_installer_page_is_available_when_not_installed(): void
    {
        $response = $this->get('/install');

        $response->assertOk();
        $response->assertSee('Install Magazines');
        $response->assertSee('Footer tagline');
    }

    public function test_installed_app_blocks_installer(): void
    {
        User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->get('/install')->assertRedirect(route('home'));
    }

    public function test_install_creates_super_admin_and_site_settings(): void
    {
        $installer = app(AppInstaller::class);

        $this->assertFalse($installer->isInstalled());

        $user = $installer->install($this->installPayload());

        $this->assertTrue($installer->isInstalled());
        $this->assertSame(UserRole::SuperAdmin, $user->fresh()->role);
        $this->assertSame('owner', $user->primaryAlias()->username);
        $this->assertSame('My Magazine', SiteBranding::get('name'));
        $this->assertSame('Stories for everyone', SiteBranding::get('footer_tagline'));
        $this->assertSame(HomeLayout::Latest, HomeLayout::current());
        $this->assertTrue(File::exists(storage_path('app/.installed')));

        $this->get('/')->assertOk();
        $this->get('/install')->assertRedirect(route('home'));
    }

    public function test_install_http_flow(): void
    {
        $response = $this->post('/install', [
            'site_name' => 'Test Site',
            'site_tagline' => 'A tagline',
            'footer_tagline' => 'Footer line',
            'footer_rights' => 'All rights reserved.',
            'copyright_start_year' => '2014',
            'theme_color' => '#111827',
            'background_color' => '#f3f4f6',
            'home_layout' => HomeLayout::Discover->value,
            'locales_enabled' => ['en'],
            'locale_default' => 'en',
            'name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'role' => UserRole::SuperAdmin->value,
        ]);
        $this->assertSame('Footer line', SiteSetting::getValue('site_footer_tagline'));
    }

    public function test_install_can_seed_demo_content(): void
    {
        $installer = app(AppInstaller::class);

        $installer->install(array_merge($this->installPayload(), ['seed_demo' => true]));

        $this->assertDatabaseHas('users', ['email' => 'author@magazines.test']);
        $this->assertDatabaseHas('categories', ['slug' => 'technology']);
        $this->assertDatabaseHas('magazines', ['slug' => 'the-commons']);
        $this->assertGreaterThan(8, \App\Models\Post::query()->count());

        if (extension_loaded('gd')) {
            $this->assertTrue(\App\Models\Post::query()->whereNotNull('cover_image')->exists());
        }
    }
}
