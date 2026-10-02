<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AppInstaller;
use App\Support\Features;
use App\Support\HomeLayout;
use App\Support\SiteBranding;
use App\Support\SiteLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * End-to-end installation flow without RefreshDatabase — starts from an empty database
 * and runs migrations only through the web installer (DatabaseMigrator).
 */
class InstallationIntegrationTest extends TestCase
{
    private ?string $envBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $envPath = base_path('.env');
        if (is_file($envPath)) {
            $this->envBackup = file_get_contents($envPath) ?: null;
        }

        config(['installer.enforce_in_tests' => true]);
        File::delete(storage_path('app/.installed'));

        $this->wipeForInstallTests();
    }

    protected function tearDown(): void
    {
        if ($this->envBackup !== null) {
            file_put_contents(base_path('.env'), $this->envBackup);
        }

        File::delete(storage_path('app/.installed'));
        config(['installer.enforce_in_tests' => false]);

        if ($this->app->bound('config')) {
            $this->artisan('config:clear');
        }

        try {
            $this->artisan('migrate:fresh', ['--force' => true]);
        } catch (\Throwable) {
            // Fresh database may not exist yet when install tests fail early.
        }

        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$lazilyRefreshed = false;

        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function validInstallForm(): array
    {
        return [
            'site_name' => 'Integration Blog',
            'site_tagline' => 'Stories from our community',
            'footer_tagline' => 'Built for writers',
            'footer_rights' => 'All rights reserved.',
            'copyright_start_year' => '2022',
            'theme_color' => '#abcdef',
            'background_color' => '#fafafa',
            'home_layout' => HomeLayout::Trending->value,
            'locales_enabled' => ['en', 'ua'],
            'locale_default' => 'ua',
            'name' => 'Integration Admin',
            'username' => 'intadmin',
            'email' => 'admin@integration.test',
            'password' => 'Str0ngPass!',
            'password_confirmation' => 'Str0ngPass!',
        ];
    }

    public function test_full_http_installation_from_empty_database(): void
    {
        $installer = app(AppInstaller::class);

        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('migrations'));
        $this->assertFalse($installer->isInstalled());

        $this->get('/')->assertRedirect(route('install.index'));
        $this->get('/login')->assertRedirect(route('install.index'));

        $this->get('/install')
            ->assertOk()
            ->assertSee('Install Magazines')
            ->assertSee('Site name')
            ->assertSee('Administrator account');

        $this->post('/install', $this->validInstallForm())
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('status');

        $this->assertTrue(Schema::hasTable('migrations'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('site_settings'));
        $this->assertGreaterThan(20, DB::table('migrations')->count());

        $this->assertTrue($installer->isInstalled());
        $this->assertFileExists(storage_path('app/.installed'));

        $this->assertAuthenticatedAs($admin = User::query()->first());
        $this->assertSame(UserRole::SuperAdmin, $admin->role);
        $this->assertSame('admin@integration.test', $admin->email);
        $this->assertNotNull($admin->email_verified_at);

        $this->assertDatabaseHas('author_aliases', [
            'user_id' => $admin->id,
            'username' => 'intadmin',
            'is_primary' => true,
        ]);
        $this->assertInstanceOf(AuthorAlias::class, $admin->primaryAlias());

        $this->assertSame('Integration Blog', SiteBranding::get('name'));
        $this->assertSame('Stories from our community', SiteBranding::get('tagline'));
        $this->assertSame('Built for writers', SiteBranding::get('footer_tagline'));
        $this->assertSame('#abcdef', SiteBranding::get('theme_color'));
        $this->assertSame(HomeLayout::Trending, HomeLayout::current());
        $this->assertSame(['en', 'ua'], SiteLocale::enabled());
        $this->assertSame('ua', SiteLocale::default());

        $this->assertTrue(
            SiteSetting::query()->where('key', 'feature_registration')->exists(),
            'Feature defaults should be seeded during install.',
        );
        $this->assertTrue(Features::enabled('registration'));

        $this->get('/install')->assertRedirect(route('home'));
        $this->get('/')->assertOk();
        $this->get('/login')->assertRedirect(route('dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Integration Blog');

        $this->get(route('manifest'))
            ->assertOk()
            ->assertJson([
                'name' => 'Integration Blog',
                'short_name' => 'Integration Blog',
                'description' => 'Stories from our community',
                'theme_color' => '#abcdef',
                'background_color' => '#fafafa',
            ]);
    }

    public function test_install_rejects_invalid_form_without_persisting_user(): void
    {
        $this->get('/install')->assertOk();

        $this->from('/install')
            ->post('/install', [
                'site_name' => '',
                'home_layout' => HomeLayout::Discover->value,
                'locales_enabled' => ['en'],
                'locale_default' => 'en',
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect('/install')
            ->assertSessionHasErrors(['site_name', 'email', 'password']);

        $this->assertFalse(app(AppInstaller::class)->isInstalled());
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(File::exists(storage_path('app/.installed')));
    }

    public function test_installed_site_rejects_second_install_attempt(): void
    {
        $this->post('/install', $this->validInstallForm())->assertRedirect(route('admin.settings.edit'));

        $this->post('/install', array_merge($this->validInstallForm(), [
            'email' => 'other@integration.test',
            'username' => 'otheradmin',
        ]))->assertRedirect(route('home'));

        $this->assertSame(1, User::query()->count());
        $this->assertSame('admin@integration.test', User::query()->value('email'));
    }
}
