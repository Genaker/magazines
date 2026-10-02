<?php

namespace App\Console\Commands;

use App\Support\AppInstaller;
use App\Support\HomeLayout;
use App\Support\SiteBranding;
use App\Support\SiteLocale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppInstallCommand extends Command
{
    protected $signature = 'app:install
        {--site-name= : Site title}
        {--site-tagline= : Site tagline}
        {--footer-tagline= : Footer headline}
        {--footer-rights= : Footer rights text}
        {--copyright-start= : Copyright start year}
        {--theme-color= : PWA theme color (hex)}
        {--background-color= : PWA background color (hex)}
        {--home-layout= : discover, latest, or trending}
        {--locale-default= : Default locale code}
        {--locales= : Comma-separated enabled locale codes}
        {--name= : Super-admin display name}
        {--username= : Super-admin username}
        {--email= : Super-admin email}
        {--password= : Super-admin password (generated when omitted)}
        {--app-url= : Public site URL for .env}
        {--db-connection= : mysql or pgsql}
        {--db-host= : Database host}
        {--db-port= : Database port}
        {--db-database= : Database name or SQLite filename}
        {--db-username= : Database username}
        {--db-password= : Database password}
        {--redis-host= : Redis host}
        {--redis-port= : Redis port}
        {--redis-password= : Redis password}
        {--skip-redis : Use file cache and database search instead of Redis}
        {--seed-demo : Seed sample categories, posts, magazine, and demo author}';

    protected $description = 'Install the application (migrate, branding, super-admin) from the CLI';

    private ?string $generatedPassword = null;

    public function handle(AppInstaller $installer): int
    {
        if ($installer->hasUsers()) {
            $this->components->error('Application is already installed (users exist).');

            return self::FAILURE;
        }

        if (! $installer->requirementsMet()) {
            $this->components->error('Server requirements are not met:');
            foreach ($installer->requirements() as $requirement) {
                if (! $requirement['ok']) {
                    $this->line('  ✗ '.$requirement['label']);
                }
            }

            return self::FAILURE;
        }

        try {
            $this->configureInfrastructure($installer);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $payload = $this->buildInstallPayload();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Installing Magazines…');

        try {
            $user = $installer->install($payload);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->success('Installation complete.');
        $this->line("  Site: {$payload['name_brand']}");
        $this->line("  Super admin: {$user->email} (@{$user->username})");

        if ($this->generatedPassword !== null) {
            $this->newLine();
            $this->components->warn("Generated super-admin password: {$this->generatedPassword}");
            $this->line('  Save this password — it will not be shown again.');
        }

        if ($this->option('seed-demo')) {
            $this->newLine();
            $this->line('  Demo authors: author@magazines.test, reporter@magazines.test, tech@magazines.test / password');
        }

        $this->newLine();
        $this->comment('Create more accounts with: php artisan app:user:create --role=user');

        return self::SUCCESS;
    }

    private function configureInfrastructure(AppInstaller $installer): void
    {
        if ($this->shouldPersistDatabase()) {
            $database = $this->resolveDatabaseConfig($installer);
            $installer->saveDatabaseConfig($database);
        }

        $database = $installer->testDatabaseConnection();
        if (! $database['ok']) {
            throw new RuntimeException('Database connection failed: '.($database['message'] ?? 'unknown error'));
        }

        if ($this->option('skip-redis')) {
            $installer->saveFileCacheConfig();
        } elseif ($this->shouldPersistRedis()) {
            $installer->saveRedisConfig($this->resolveRedisConfig($installer));
        }

        if (! $installer->redisReady()) {
            $redis = $installer->testRedisConnection();

            throw new RuntimeException(
                'Redis is not ready: '.($redis['message'] ?? 'connection failed')
                .'. Configure REDIS_* in .env, pass --redis-host/--redis-port, or use --skip-redis.'
            );
        }
    }

    /** @return array<string, mixed> */
    private function buildInstallPayload(): array
    {
        $siteName = $this->option('site-name') ?: SiteBranding::get('name');
        $homeLayout = $this->option('home-layout') ?: config('site.home_layout', HomeLayout::Discover->value);

        if (! in_array($homeLayout, array_keys(HomeLayout::options()), true)) {
            throw new RuntimeException("Invalid home layout [{$homeLayout}].");
        }

        $locales = $this->parseLocales($this->option('locales'));
        $localeDefault = $this->option('locale-default') ?: config('site.default_locale', 'en');

        if (! in_array($localeDefault, $locales, true)) {
            throw new RuntimeException('Default locale must be included in --locales.');
        }

        if (! $this->option('no-interaction') && ! $this->option('site-name')) {
            $siteName = (string) $this->ask('Site name', $siteName);
        }

        $password = $this->resolvePassword();

        return [
            'name_brand' => $siteName,
            'tagline' => $this->option('site-tagline') ?: SiteBranding::get('tagline'),
            'footer_tagline' => $this->option('footer-tagline') ?: SiteBranding::get('footer_tagline'),
            'footer_rights' => $this->option('footer-rights') ?: SiteBranding::get('footer_rights'),
            'copyright_start_year' => $this->option('copyright-start') ?: SiteBranding::get('copyright_start_year'),
            'theme_color' => $this->option('theme-color') ?: SiteBranding::get('theme_color'),
            'background_color' => $this->option('background-color') ?: SiteBranding::get('background_color'),
            'home_layout' => $homeLayout,
            'locales_enabled' => $locales,
            'locale_default' => $localeDefault,
            'name' => $this->resolveAdminField('name', 'Administrator'),
            'username' => $this->resolveAdminField('username', 'admin'),
            'email' => $this->resolveAdminField('email', 'admin@magazines.test'),
            'password' => Hash::make($password),
            'app_url' => $this->option('app-url') ?: config('app.url'),
            'seed_demo' => $this->option('seed-demo'),
        ];
    }

    private function resolvePassword(): string
    {
        if ($this->option('password')) {
            return (string) $this->option('password');
        }

        if (! $this->option('no-interaction')) {
            do {
                $password = (string) $this->secret('Admin password (leave empty to generate)');
                if ($password === '') {
                    break;
                }
                $confirm = (string) $this->secret('Confirm password');
            } while ($password !== $confirm);

            if ($password !== '') {
                $validator = validator(['password' => $password], ['password' => Password::defaults()]);
                if ($validator->fails()) {
                    throw new RuntimeException($validator->errors()->first('password'));
                }

                return $password;
            }
        }

        $this->generatedPassword = Str::password(16, symbols: true);

        return $this->generatedPassword;
    }

    private function resolveAdminField(string $option, string $default): string
    {
        $value = $this->option($option);
        if (filled($value)) {
            return (string) $value;
        }

        if ($this->option('no-interaction')) {
            return $default;
        }

        return (string) $this->ask(match ($option) {
            'name' => 'Admin name',
            'username' => 'Admin username',
            'email' => 'Admin email',
            default => $option,
        }, $default);
    }

    /** @return list<string> */
    private function parseLocales(?string $value): array
    {
        if ($value === null || $value === '') {
            return config('site.enabled_locales', ['en']);
        }

        $locales = array_values(array_filter(array_map('trim', explode(',', $value))));

        foreach ($locales as $locale) {
            if (! in_array($locale, SiteLocale::available(), true)) {
                throw new RuntimeException("Unknown locale [{$locale}].");
            }
        }

        return $locales !== [] ? $locales : config('site.enabled_locales', ['en']);
    }

    private function shouldPersistDatabase(): bool
    {
        foreach (['db-connection', 'db-host', 'db-port', 'db-database', 'db-username', 'db-password'] as $option) {
            if ($this->option($option) !== null) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string|null> */
    private function resolveDatabaseConfig(AppInstaller $installer): array
    {
        $defaults = $installer->databaseFormDefaults();
        $connection = (string) ($this->option('db-connection') ?? $defaults['connection']);

        if (! in_array($connection, ['mysql', 'pgsql'], true)) {
            throw new RuntimeException("Invalid database connection [{$connection}]. Use mysql or pgsql.");
        }

        return [
            'connection' => $connection,
            'host' => (string) ($this->option('db-host') ?? $defaults['host']),
            'port' => (string) ($this->option('db-port') ?? $defaults['port']),
            'database' => (string) ($this->option('db-database') ?? $defaults['database']),
            'username' => (string) ($this->option('db-username') ?? $defaults['username']),
            'password' => $this->option('db-password') ?? ($defaults['password_set'] ? null : ''),
        ];
    }

    private function shouldPersistRedis(): bool
    {
        foreach (['redis-host', 'redis-port', 'redis-password'] as $option) {
            if ($this->option($option) !== null) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string|null> */
    private function resolveRedisConfig(AppInstaller $installer): array
    {
        $defaults = $installer->redisFormDefaults();

        return [
            'host' => (string) ($this->option('redis-host') ?? $defaults['host']),
            'port' => (string) ($this->option('redis-port') ?? $defaults['port']),
            'password' => $this->option('redis-password') ?? ($defaults['password_set'] ? null : ''),
        ];
    }
}
