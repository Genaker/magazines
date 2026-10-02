@extends('install.layout')

@section('content')
    @if (! $databaseOk)
        <h2 class="text-lg font-semibold mb-2">Database connection</h2>
        <p class="text-sm text-gray-600 mb-4">Enter your database credentials. They are saved to <code class="text-xs bg-gray-100 px-1 rounded">.env</code>.</p>

        @if ($databaseError)
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $databaseError }}
            </div>
        @endif

        <x-input-error :messages="$errors->get('database')" class="mb-4" />

        @include('install.partials.database-env-summary')

        <form method="POST" action="{{ route('install.database') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="db_connection" value="Database type" />
                <select id="db_connection" name="db_connection" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="mysql" @selected(old('db_connection', $db['connection']) === 'mysql')>MySQL / MariaDB</option>
                    <option value="pgsql" @selected(old('db_connection', $db['connection']) === 'pgsql')>PostgreSQL</option>
                </select>
            </div>

            <div class="space-y-4">
                <div>
                    <x-input-label for="db_host" value="Host" />
                    <x-text-input id="db_host" name="db_host" class="mt-1 block w-full" :value="old('db_host', $db['host'])" />
                </div>
                <div>
                    <x-input-label for="db_port" value="Port" />
                    <x-text-input id="db_port" name="db_port" class="mt-1 block w-full" :value="old('db_port', $db['port'])" />
                </div>
                <div>
                    <x-input-label for="db_username" value="Username" />
                    <x-text-input id="db_username" name="db_username" class="mt-1 block w-full" :value="old('db_username', $db['username'])" />
                </div>
                <div>
                    <x-input-label for="db_password" value="Password" />
                    <x-text-input id="db_password" name="db_password" type="password" class="mt-1 block w-full" :value="old('db_password', $db['password'])" autocomplete="new-password" />
                    @if ($db['password_set'] ?? false)
                        <p class="mt-1 text-xs text-gray-500">A password is already set in <code>.env</code>. Leave blank to keep it.</p>
                    @endif
                </div>
            </div>

            <div>
                <x-input-label for="db_database" value="Database name" />
                <x-text-input id="db_database" name="db_database" class="mt-1 block w-full" :value="old('db_database', $db['database'])" required />
            </div>

            <x-primary-button>Test connection &amp; continue</x-primary-button>
        </form>
    @elseif (! $redisOk)
        <h2 class="text-lg font-semibold mb-2">Redis connection</h2>
        <p class="text-sm text-gray-600 mb-4">
            Database connected. Configure Redis for cache, sessions, and search (Redis Stack recommended), or skip for a file-based setup.
        </p>

        @include('install.partials.database-env-summary', ['db' => $db])

        @if ($db['from_env'] ?? false)
            <p class="mb-4 text-xs text-gray-500">Database values above are loaded from <code>.env</code>.</p>
        @endif

        @if ($redisError)
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $redisError }}
            </div>
        @endif

        <x-input-error :messages="$errors->get('redis')" class="mb-4" />

        @include('install.partials.redis-env-summary')

        <form method="POST" action="{{ route('install.redis') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="redis_host" value="Host" />
                <x-text-input id="redis_host" name="redis_host" class="mt-1 block w-full" :value="old('redis_host', $redis['host'])" required />
                <p class="mt-1 text-xs text-gray-500">Use <code>redis</code> when the app runs in Docker Compose on the same network.</p>
            </div>
            <div>
                <x-input-label for="redis_port" value="Port" />
                <x-text-input id="redis_port" name="redis_port" class="mt-1 block w-full" :value="old('redis_port', $redis['port'])" required />
            </div>
            <div>
                <x-input-label for="redis_password" value="Password (optional)" />
                <x-text-input id="redis_password" name="redis_password" type="password" class="mt-1 block w-full" :value="old('redis_password', $redis['password'])" autocomplete="new-password" />
                @if ($redis['password_set'] ?? false)
                    <p class="mt-1 text-xs text-gray-500">A password is already set in <code>.env</code>. Leave blank to keep it.</p>
                @endif
            </div>

            <x-primary-button>Test connection &amp; continue</x-primary-button>
        </form>

        <form method="POST" action="{{ route('install.redis.skip') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">
                Skip Redis — use file cache and database search instead
            </button>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            Need different database settings?
            <a href="{{ route('install.index') }}?database=1" class="underline">Reconfigure database</a>
        </p>
    @else
        <h2 class="text-lg font-semibold mb-2">Site &amp; administrator</h2>
        <p class="text-sm text-gray-600 mb-6">Database and cache are configured. Customize your site and create the super-admin account.</p>

        @include('install.partials.infrastructure-summary', ['db' => $db, 'redis' => $redis])

        <x-input-error :messages="$errors->get('database')" class="mb-4" />
        <x-input-error :messages="$errors->get('redis')" class="mb-4" />

        <form method="POST" action="{{ route('install.store') }}" class="space-y-6">
            @csrf

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Branding</h3>

                <div>
                    <x-input-label for="site_name" value="Site name" />
                    <x-text-input id="site_name" name="site_name" class="mt-1 block w-full" :value="old('site_name', $branding['site_name'])" required autofocus />
                </div>

                <div>
                    <x-input-label for="site_tagline" value="Tagline" />
                    <x-text-input id="site_tagline" name="site_tagline" class="mt-1 block w-full" :value="old('site_tagline', $branding['site_tagline'])" />
                    <p class="mt-1 text-xs text-gray-500">Shown in meta tags and the PWA manifest.</p>
                </div>

                <div>
                    <x-input-label for="footer_tagline" value="Footer tagline" />
                    <x-text-input id="footer_tagline" name="footer_tagline" class="mt-1 block w-full" :value="old('footer_tagline', $branding['footer_tagline'])" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="footer_rights" value="Footer rights text" />
                        <x-text-input id="footer_rights" name="footer_rights" class="mt-1 block w-full" :value="old('footer_rights', $branding['footer_rights'])" />
                    </div>
                    <div>
                        <x-input-label for="copyright_start_year" value="Copyright start year" />
                        <x-text-input id="copyright_start_year" name="copyright_start_year" class="mt-1 block w-full" :value="old('copyright_start_year', $branding['copyright_start_year'])" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="theme_color" value="Theme color" />
                        <x-text-input id="theme_color" name="theme_color" type="color" class="mt-1 block w-full h-10" :value="old('theme_color', $branding['theme_color'])" />
                    </div>
                    <div>
                        <x-input-label for="background_color" value="Background color" />
                        <x-text-input id="background_color" name="background_color" type="color" class="mt-1 block w-full h-10" :value="old('background_color', $branding['background_color'])" />
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Home &amp; language</h3>

                <div>
                    <x-input-label for="home_layout" value="Home page layout" />
                    <select id="home_layout" name="home_layout" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($homeLayoutOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('home_layout', $homeLayout) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium">Enabled languages</p>
                    @foreach ($localeOptions as $code => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="locales_enabled[]" value="{{ $code }}" class="rounded border-gray-300"
                                @checked(in_array($code, old('locales_enabled', $enabledLocales), true))>
                            {{ $label }}
                        </label>
                    @endforeach
                    @error('locales_enabled')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-input-label for="locale_default" value="Default language" />
                    <select id="locale_default" name="locale_default" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($localeOptions as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale_default', $defaultLocale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('locale_default')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Administrator account</h3>

                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                </div>

                <div>
                    <x-input-label for="username" value="Username" />
                    <x-text-input id="username" name="username" class="mt-1 block w-full" :value="old('username')" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                </div>
            </div>

            <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="seed_demo" value="1" class="rounded border-gray-300 mt-0.5"
                        @checked(old('seed_demo'))>
                    <span>
                        <span class="font-medium text-gray-900">Seed demo data</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            Adds sample categories, posts with cover images, a photo gallery, a video story, a magazine with logo, and demo authors
                            (<code>author@magazines.test</code>, <code>reporter@magazines.test</code>, <code>tech@magazines.test</code> — password <code>password</code>).
                        </span>
                    </span>
                </label>
            </div>

            <x-primary-button>Install Magazines</x-primary-button>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            Need different database or Redis settings?
            <a href="{{ route('install.index') }}?database=1" class="underline">Reconfigure database</a>
            ·
            <a href="{{ route('install.index') }}?redis=1" class="underline">Reconfigure Redis</a>
        </p>
    @endif
@endsection
