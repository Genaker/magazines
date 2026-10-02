<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Support\AppInstaller;
use App\Support\SiteBranding;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AppInstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['installer.enforce_in_tests' => true]);
        File::delete(storage_path('app/.installed'));

        $this->wipeForInstallTests();
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/.installed'));
        config(['installer.enforce_in_tests' => false]);

        try {
            $this->artisan('migrate:fresh', ['--force' => true]);
        } catch (\Throwable) {
            // Fresh database may not exist yet when install tests fail early.
        }

        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$lazilyRefreshed = false;

        parent::tearDown();
    }

    public function test_installs_application_from_cli(): void
    {
        $this->assertFalse(app(AppInstaller::class)->hasUsers());

        $this->artisan('app:install', [
            '--no-interaction' => true,
            '--site-name' => 'CLI Site',
            '--name' => 'CLI Admin',
            '--username' => 'cliadmin',
            '--email' => 'cli@example.test',
            '--password' => 'password',
            '--locales' => 'en',
            '--locale-default' => 'en',
        ])
            ->assertSuccessful();

        $this->assertTrue(app(AppInstaller::class)->isInstalled());
        $this->assertSame('CLI Site', SiteBranding::get('name'));
        $this->assertDatabaseHas('users', [
            'email' => 'cli@example.test',
            'role' => UserRole::SuperAdmin->value,
        ]);
    }

    public function test_installs_with_defaults_and_generated_password(): void
    {
        $this->artisan('app:install', ['--no-interaction' => true])
            ->expectsOutputToContain('Generated super-admin password:')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@magazines.test',
            'role' => UserRole::SuperAdmin->value,
        ]);
        $this->assertDatabaseHas('author_aliases', [
            'username' => 'admin',
            'is_primary' => true,
        ]);
        $this->assertSame(SiteBranding::get('name'), config('site.defaults.name'));
    }

    public function test_installs_with_demo_seed_flag(): void
    {
        $this->artisan('app:install', [
            '--no-interaction' => true,
            '--seed-demo' => true,
            '--password' => 'password',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'author@magazines.test']);
        $this->assertDatabaseHas('categories', ['slug' => 'technology']);
        $this->assertGreaterThan(8, \App\Models\Post::query()->count());
    }

    public function test_persists_database_and_redis_options_from_cli(): void
    {
        $installer = app(AppInstaller::class);

        $this->artisan('app:install', [
            '--no-interaction' => true,
            '--skip-redis' => true,
            '--db-connection' => 'mysql',
            '--db-host' => env('DB_HOST', '127.0.0.1'),
            '--db-port' => env('DB_PORT', '3307'),
            '--db-database' => env('DB_DATABASE', 'magazines_test'),
            '--db-username' => env('DB_USERNAME', 'root'),
            '--db-password' => env('DB_PASSWORD', 'secret'),
            '--password' => 'password',
        ])->assertSuccessful();

        $this->assertTrue($installer->isInstalled());

        $env = (string) file_get_contents(base_path('.env'));
        $this->assertStringContainsString('SESSION_DRIVER=file', $env);
        $this->assertStringContainsString('SEARCH_DRIVER=database', $env);
    }

    public function test_refuses_when_already_installed(): void
    {
        $this->artisan('app:install', [
            '--no-interaction' => true,
            '--site-name' => 'First',
            '--name' => 'Admin',
            '--username' => 'admin1',
            '--email' => 'first@example.test',
            '--password' => 'password',
            '--locales' => 'en',
            '--locale-default' => 'en',
        ])->assertSuccessful();

        $this->artisan('app:install', [
            '--no-interaction' => true,
            '--site-name' => 'Second',
            '--name' => 'Other',
            '--username' => 'admin2',
            '--email' => 'second@example.test',
            '--password' => 'password',
            '--locales' => 'en',
            '--locale-default' => 'en',
        ])->assertFailed();
    }
}
