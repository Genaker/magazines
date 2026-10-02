<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\HomeLayout as HomeLayoutEnum;
use App\Support\SiteBranding;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class AppInstaller
{
    private static ?bool $installedCache = null;

    private static ?bool $hasUsersCache = null;

    /** @return array<string, array{label: string, ok: bool, hint: string|null}> */
    public function requirements(): array
    {
        return [
            'php' => [
                'label' => 'PHP '.PHP_VERSION.' (8.3+ required)',
                'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'hint' => null,
            ],
            'extensions' => [
                'label' => 'Required PHP extensions (pdo, mbstring, openssl, tokenizer, xml, curl, fileinfo)',
                'ok' => $this->extensionsLoaded(['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'curl', 'fileinfo']),
                'hint' => null,
            ],
            'vendor' => [
                'label' => 'Composer dependencies (vendor/)',
                'ok' => is_file(base_path('vendor/autoload.php')),
                'hint' => 'Run composer install on the server.',
            ],
            'storage' => [
                'label' => 'Writable storage/ directory',
                'ok' => is_writable(storage_path()),
                'hint' => 'chmod -R ug+rwx storage bootstrap/cache',
            ],
            'bootstrap_cache' => [
                'label' => 'Writable bootstrap/cache/ directory',
                'ok' => is_writable(base_path('bootstrap/cache')),
                'hint' => 'chmod -R ug+rwx bootstrap/cache',
            ],
            'env' => [
                'label' => '.env file present',
                'ok' => is_file(base_path('.env')) || is_file(base_path('.env.example')),
                'hint' => 'Copy .env.example to .env',
            ],
        ];
    }

    public function requirementsMet(): bool
    {
        foreach ($this->requirements() as $requirement) {
            if (! $requirement['ok']) {
                return false;
            }
        }

        return true;
    }

    public function isInstalled(): bool
    {
        if ($this->shouldSkipInstallCheck()) {
            return true;
        }

        if (self::$installedCache !== null) {
            return self::$installedCache;
        }

        return self::$installedCache = $this->hasUsers();
    }

    public function hasUsers(): bool
    {
        if (self::$hasUsersCache !== null) {
            return self::$hasUsersCache;
        }

        try {
            return self::$hasUsersCache = User::withoutGlobalScope('tenant')->exists();
        } catch (Throwable) {
            return self::$hasUsersCache = false;
        }
    }

    public function shouldSkipInstallCheck(): bool
    {
        return app()->runningUnitTests() && ! config('installer.enforce_in_tests');
    }

    /** Use file drivers during the web installer so Redis is not required yet. */
    public function configureRuntimeForInstallPhase(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'entity-cache.store' => 'file',
        ]);
    }

    public function databaseConfigured(): bool
    {
        return filled(env('DB_DATABASE'))
            || (filled(config('database.default'))
                && filled(config('database.connections.'.config('database.default').'.database')));
    }

    /** @return array<string, mixed> */
    public function databaseFormDefaults(): array
    {
        $connection = (string) env('DB_CONNECTION', config('database.default', 'mysql'));
        if (! in_array($connection, ['mysql', 'pgsql'], true)) {
            $connection = 'mysql';
        }
        $config = config('database.connections.'.$connection, []);

        return [
            'connection' => $connection,
            'connection_label' => $this->databaseConnectionLabel($connection),
            'host' => (string) (env('DB_HOST') ?? $config['host'] ?? '127.0.0.1'),
            'port' => (string) (env('DB_PORT') ?? $config['port'] ?? ($connection === 'pgsql' ? '5432' : '3306')),
            'database' => (string) (env('DB_DATABASE') ?? $config['database'] ?? ''),
            'username' => (string) (env('DB_USERNAME') ?? $config['username'] ?? ''),
            'password' => '',
            'password_set' => filled(env('DB_PASSWORD')),
            'from_env' => filled(env('DB_DATABASE')),
        ];
    }

    /** @return array<string, mixed> */
    public function redisFormDefaults(): array
    {
        $usesRedis = ! $this->usesNonRedisDrivers();

        return [
            'uses_redis' => $usesRedis,
            'host' => (string) (env('REDIS_HOST') ?? config('database.redis.default.host', '127.0.0.1')),
            'port' => (string) (env('REDIS_PORT') ?? config('database.redis.default.port', '6379')),
            'password' => '',
            'password_set' => filled(env('REDIS_PASSWORD')),
            'from_env' => $usesRedis && filled(env('REDIS_HOST')),
            'cache_store' => (string) env('CACHE_STORE', 'redis'),
            'session_driver' => (string) env('SESSION_DRIVER', 'redis'),
            'entity_cache_store' => (string) env('ENTITY_CACHE_STORE', 'redis'),
            'search_driver' => (string) env('SEARCH_DRIVER', 'redis'),
        ];
    }

    /** @return array{ok: bool, message: string|null} */
    public function testDatabaseConnection(?array $overrides = null): array
    {
        if ($overrides !== null) {
            $this->applyDatabaseOverrides($overrides);
        }

        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** @param  array<string, string|null>  $database */
    public function saveDatabaseConfig(array $database): void
    {
        $values = [
            'DB_CONNECTION' => $database['connection'],
            'DB_HOST' => $database['host'] ?? '',
            'DB_PORT' => $database['port'] ?? '',
            'DB_DATABASE' => $database['database'],
            'DB_USERNAME' => $database['username'] ?? '',
        ];

        if (filled($database['password'] ?? null)) {
            $values['DB_PASSWORD'] = $database['password'];
        }

        EnvWriter::set($values);

        Artisan::call('config:clear');
    }

    public function redisReady(): bool
    {
        if ($this->usesNonRedisDrivers()) {
            return true;
        }

        return $this->testRedisConnection()['ok'];
    }

    /** @return array{ok: bool, message: string|null} */
    public function testRedisConnection(?array $overrides = null): array
    {
        if ($this->usesNonRedisDrivers()) {
            return ['ok' => true, 'message' => null];
        }

        if ($overrides !== null) {
            $this->applyRedisOverrides($overrides);
        }

        try {
            Redis::connection()->ping();

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** @param  array<string, string|null>  $redis */
    public function saveRedisConfig(array $redis): void
    {
        $values = [
            'REDIS_HOST' => $redis['host'],
            'REDIS_PORT' => $redis['port'],
            'CACHE_STORE' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'ENTITY_CACHE_STORE' => 'redis',
            'SEARCH_DRIVER' => 'redis',
        ];

        if (filled($redis['password'] ?? null)) {
            $values['REDIS_PASSWORD'] = $redis['password'];
        }

        EnvWriter::set($values);

        Artisan::call('config:clear');
    }

    /** Persist file/array drivers when Redis is unavailable (local or minimal installs). */
    public function saveFileCacheConfig(): void
    {
        EnvWriter::set([
            'CACHE_STORE' => 'file',
            'SESSION_DRIVER' => 'file',
            'ENTITY_CACHE_STORE' => 'file',
            'SEARCH_DRIVER' => 'database',
        ]);

        Artisan::call('config:clear');
    }

    /** @param  array<string, string>  $data */
    public function install(array $data): User
    {
        if ($this->hasUsers()) {
            throw new RuntimeException('Application is already installed.');
        }

        if (! $this->requirementsMet()) {
            throw new RuntimeException('Server requirements are not met.');
        }

        $this->ensureAppKey();
        $this->ensureStorageLink();

        app(DatabaseMigrator::class)->run();

        if (User::query()->exists()) {
            throw new RuntimeException('Database already contains users.');
        }

        $user = app(UserProvisioner::class)->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ], UserRole::SuperAdmin, verified: true);

        SiteBranding::apply([
            'name' => $data['name_brand'] ?? $data['site_name'] ?? '',
            'tagline' => $data['tagline'] ?? null,
            'footer_tagline' => $data['footer_tagline'] ?? null,
            'footer_rights' => $data['footer_rights'] ?? null,
            'copyright_start_year' => $data['copyright_start_year'] ?? null,
            'theme_color' => $data['theme_color'] ?? null,
            'background_color' => $data['background_color'] ?? null,
        ]);

        HomeLayoutEnum::set(HomeLayoutEnum::tryFrom($data['home_layout'] ?? config('site.home_layout', 'discover')) ?? HomeLayoutEnum::Discover);

        $enabledLocales = $data['locales_enabled'] ?? config('site.enabled_locales', ['en']);
        SiteLocale::setEnabled($enabledLocales);
        SiteLocale::setDefault($data['locale_default'] ?? config('site.default_locale', 'en'));

        Features::seedDefaults();

        File::put(storage_path('app/.installed'), now()->toIso8601String());

        EnvWriter::set(array_filter([
            'APP_NAME' => $data['name_brand'] ?? $data['site_name'] ?? null,
            'APP_URL' => $data['app_url'] ?? null,
            'SITE_NAME' => $data['name_brand'] ?? $data['site_name'] ?? null,
            'SITE_TAGLINE' => $data['tagline'] ?? null,
            'SITE_FOOTER_TAGLINE' => $data['footer_tagline'] ?? null,
            'SITE_FOOTER_RIGHTS' => $data['footer_rights'] ?? null,
            'SITE_COPYRIGHT_START' => $data['copyright_start_year'] ?? null,
            'SITE_THEME_COLOR' => $data['theme_color'] ?? null,
            'SITE_BACKGROUND_COLOR' => $data['background_color'] ?? null,
            'SITE_HOME_LAYOUT' => $data['home_layout'] ?? null,
            'SITE_DEFAULT_LOCALE' => $data['locale_default'] ?? null,
            'SITE_ENABLED_LOCALES' => isset($data['locales_enabled'])
                ? implode(',', $data['locales_enabled'])
                : null,
        ], fn ($value) => filled($value)));

        if (filled($data['app_url'] ?? null) || filled($data['name_brand'] ?? $data['site_name'] ?? null)) {
            Artisan::call('config:clear');
        }

        if (! empty($data['seed_demo'])) {
            $this->seedDemoContent();
        }

        return $user;
    }

    /** Seed sample categories, posts, magazine, and demo author after install. */
    public function seedDemoContent(): void
    {
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\DemoContentSeeder::class,
            '--force' => true,
        ]);
    }

    /** @param  array<string, string|null>  $overrides */
    private function applyDatabaseOverrides(array $overrides): void
    {
        $connection = $overrides['connection'] ?? config('database.default');
        $config = config('database.connections.'.$connection, []);

        config([
            'database.default' => $connection,
            'database.connections.'.$connection => array_merge($config, array_filter([
                'driver' => $this->databaseDriver($connection),
                'host' => $overrides['host'] ?? null,
                'port' => $overrides['port'] ?? null,
                'database' => $overrides['database'] ?? null,
                'username' => $overrides['username'] ?? null,
                'password' => $overrides['password'] ?? null,
            ], fn ($value) => $value !== null)),
        ]);

        DB::purge($connection);
    }

    /** @param  array<string, string|null>  $overrides */
    private function applyRedisOverrides(array $overrides): void
    {
        $host = $overrides['host'] ?? config('database.redis.default.host');
        $port = $overrides['port'] ?? config('database.redis.default.port');
        $password = $overrides['password'] ?? config('database.redis.default.password');

        foreach (['default', 'cache', 'search'] as $connection) {
            config([
                "database.redis.{$connection}.host" => $host,
                "database.redis.{$connection}.port" => $port,
                "database.redis.{$connection}.password" => $password,
            ]);
        }

        if (app()->bound('redis')) {
            foreach (['default', 'cache', 'search'] as $connection) {
                Redis::purge($connection);
            }
        }
    }

    private function usesNonRedisDrivers(): bool
    {
        $cache = env('CACHE_STORE', 'redis');
        $session = env('SESSION_DRIVER', 'redis');

        return $cache !== 'redis' && $session !== 'redis';
    }

    private function databaseDriver(string $connection): string
    {
        return $connection === 'pgsql' ? 'pgsql' : 'mysql';
    }

    private function databaseConnectionLabel(string $connection): string
    {
        return $connection === 'pgsql' ? 'PostgreSQL' : 'MySQL / MariaDB';
    }

    private function ensureAppKey(): void
    {
        if (filled(config('app.key'))) {
            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
        Artisan::call('config:clear');
    }

    private function ensureStorageLink(): void
    {
        if (is_link(public_path('storage'))) {
            return;
        }

        Artisan::call('storage:link', ['--force' => true]);
    }

    /** @param  list<string>  $extensions */
    private function extensionsLoaded(array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (! extension_loaded($extension)) {
                return false;
            }
        }

        return true;
    }
}
