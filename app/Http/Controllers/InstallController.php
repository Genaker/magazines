<?php

namespace App\Http\Controllers;

use App\Support\AppInstaller;
use App\Support\HomeLayout;
use App\Support\SiteBranding;
use App\Support\SiteLocale;
use App\Support\SubdomainLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function __construct(private AppInstaller $installer) {}

    public function index(): View|RedirectResponse
    {
        if (! $this->installer->requirementsMet()) {
            return view('install.requirements', [
                'requirements' => $this->installer->requirements(),
            ]);
        }

        $forceDatabase = request()->boolean('database');
        $database = $this->installer->testDatabaseConnection();
        $databaseOk = ! $forceDatabase && $database['ok'];

        $forceRedis = request()->boolean('redis');
        $redisTest = $databaseOk ? $this->installer->testRedisConnection() : ['ok' => false, 'message' => null];
        $redisOk = $databaseOk && ! $forceRedis && $this->installer->redisReady();

        return view('install.setup', [
            'databaseOk' => $databaseOk,
            'databaseError' => $database['message'],
            'db' => $this->installer->databaseFormDefaults(),
            'redisOk' => $redisOk,
            'redisError' => $redisTest['message'],
            'redis' => $this->installer->redisFormDefaults(),
            'branding' => [
                'site_name' => SiteBranding::get('name'),
                'site_tagline' => SiteBranding::get('tagline'),
                'footer_tagline' => SiteBranding::get('footer_tagline'),
                'footer_rights' => SiteBranding::get('footer_rights'),
                'copyright_start_year' => SiteBranding::get('copyright_start_year'),
                'theme_color' => SiteBranding::get('theme_color'),
                'background_color' => SiteBranding::get('background_color'),
            ],
            'homeLayout' => config('site.home_layout', HomeLayout::Discover->value),
            'homeLayoutOptions' => HomeLayout::options(),
            'localeOptions' => SiteLocale::labels(),
            'enabledLocales' => config('site.enabled_locales', ['en']),
            'defaultLocale' => config('site.default_locale', 'en'),
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        $connection = $request->input('db_connection', 'mysql');

        $validated = $request->validate([
            'db_connection' => ['required', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'string', 'max:10'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        $this->installer->saveDatabaseConfig([
            'connection' => $connection,
            'host' => $validated['db_host'],
            'port' => $validated['db_port'],
            'database' => $validated['db_database'],
            'username' => $validated['db_username'],
            'password' => $validated['db_password'] ?? null,
        ]);

        $test = $this->installer->testDatabaseConnection();

        if (! $test['ok']) {
            return back()
                ->withInput()
                ->withErrors(['database' => $test['message'] ?? 'Could not connect to the database.']);
        }

        return redirect()->route('install.index');
    }

    public function storeRedis(Request $request): RedirectResponse
    {
        $database = $this->installer->testDatabaseConnection();
        if (! $database['ok']) {
            return redirect()->route('install.index')
                ->withErrors(['database' => 'Configure the database first.']);
        }

        $validated = $request->validate([
            'redis_host' => ['required', 'string', 'max:255'],
            'redis_port' => ['required', 'string', 'max:10'],
            'redis_password' => ['nullable', 'string', 'max:255'],
        ]);

        $this->installer->saveRedisConfig([
            'host' => $validated['redis_host'],
            'port' => $validated['redis_port'],
            'password' => $validated['redis_password'] ?? '',
        ]);

        $test = $this->installer->testRedisConnection();

        if (! $test['ok']) {
            return back()
                ->withInput()
                ->withErrors(['redis' => $test['message'] ?? 'Could not connect to Redis.']);
        }

        return redirect()->route('install.index');
    }

    public function skipRedis(): RedirectResponse
    {
        $database = $this->installer->testDatabaseConnection();
        if (! $database['ok']) {
            return redirect()->route('install.index')
                ->withErrors(['database' => 'Configure the database first.']);
        }

        $this->installer->saveFileCacheConfig();

        return redirect()->route('install.index');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->installer->requirementsMet()) {
            return redirect()->route('install.index');
        }

        $database = $this->installer->testDatabaseConnection();
        if (! $database['ok']) {
            return back()->withErrors(['database' => $database['message'] ?? 'Configure the database first.']);
        }

        if (! $this->installer->redisReady()) {
            return back()->withErrors(['redis' => 'Configure Redis or choose file-based cache before continuing.']);
        }

        $availableLocales = SiteLocale::available();

        $request->merge([
            'username' => SubdomainLabel::forNickname((string) $request->input('username', '')),
        ]);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:500'],
            'footer_tagline' => ['nullable', 'string', 'max:255'],
            'footer_rights' => ['nullable', 'string', 'max:255'],
            'copyright_start_year' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:9999'],
            'theme_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'home_layout' => ['required', Rule::enum(HomeLayout::class)],
            'locales_enabled' => ['required', 'array', 'min:1'],
            'locales_enabled.*' => ['string', Rule::in($availableLocales)],
            'locale_default' => ['required', 'string', Rule::in($availableLocales)],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $enabledLocales = array_values(array_unique($validated['locales_enabled']));
        if (! in_array($validated['locale_default'], $enabledLocales, true)) {
            return back()
                ->withErrors(['locale_default' => 'The default language must be enabled.'])
                ->withInput();
        }

        $user = $this->installer->install([
            'name_brand' => $validated['site_name'],
            'tagline' => $validated['site_tagline'] ?? null,
            'footer_tagline' => $validated['footer_tagline'] ?? null,
            'footer_rights' => $validated['footer_rights'] ?? null,
            'copyright_start_year' => $validated['copyright_start_year'] ?? null,
            'theme_color' => $validated['theme_color'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
            'home_layout' => $validated['home_layout'],
            'locales_enabled' => $enabledLocales,
            'locale_default' => $validated['locale_default'],
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'app_url' => $request->getSchemeAndHttpHost(),
            'seed_demo' => $request->boolean('seed_demo'),
        ]);

        Auth::login($user);

        $status = 'Welcome! Your site is ready.';
        if ($request->boolean('seed_demo')) {
            $status .= ' Demo content loaded — author@magazines.test, reporter@magazines.test, tech@magazines.test / password.';
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', $status);
    }
}
