@php
    $navCategories = \App\Models\Category::cachedForNavigation();
    $navMagazinePayload = \App\Support\Features::enabled('magazines')
        ? \App\Models\Magazine::cachedNavigationPayload()
        : ['items' => collect(), 'total' => 0, 'more_count' => 0];
    $navMagazines = $navMagazinePayload['items'];
    $navMagazinesMoreCount = $navMagazinePayload['more_count'];
    $siteTagline = \App\Support\SiteBranding::localizedTagline();
    $navUrl = fn (string $name, mixed $params = []) => \App\Support\SiteUrl::navRoute($name, $params);
@endphp

<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <div>
                <a href="{{ $navUrl('home') }}" class="flex items-center gap-2.5 text-gray-900 hover:opacity-80">
                    <x-application-logo class="h-9 w-auto shrink-0" />
                    <span class="text-xl font-bold">{{ \App\Models\SiteSetting::getValue('site_name', config('app.name')) }}</span>
                </a>
                @if ($siteTagline)
                    <p class="text-xs text-gray-500 hidden sm:block mt-0.5 ml-[2.875rem]">{{ $siteTagline }}</p>
                @endif
            </div>
            <div class="hidden md:flex items-center gap-4 text-sm">
                @feature('magazines')
                    @include('partials.magazine-nav', [
                        'magazines' => $navMagazines,
                        'moreCount' => $navMagazinesMoreCount,
                    ])
                @endfeature
                @include('partials.category-nav', ['categories' => $navCategories])
                <a href="{{ $navUrl('authors.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('app.authors') }}</a>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @include('partials.locale-switcher')

            <form action="{{ $navUrl('search') }}" method="GET" class="hidden sm:block">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('app.search') }}" class="rounded-md border-gray-300 text-sm">
            </form>

            @auth
                @php
                    $activeAlias = \App\Support\ActiveAuthorAlias::resolve(auth()->user());
                @endphp
                <a href="{{ $navUrl('posts.create') }}" class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-sm font-medium bg-gray-900 text-white hover:bg-gray-800 transition whitespace-nowrap" title="Writing as {{ '@'.$activeAlias->username }}">{{ __('app.write') }}</a>
                <a href="{{ $navUrl('profile.aliases') }}" class="hidden lg:inline text-sm text-gray-600" title="Active: {{ '@'.$activeAlias->username }}">{{ '@'.$activeAlias->username }}</a>
                <a href="{{ $navUrl('notifications.index') }}" class="relative p-1 text-gray-600 hover:text-gray-900" title="{{ __('app.notifications') }}" aria-label="{{ __('app.notifications') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if (($unreadNotificationsCount ?? 0) > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                            {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ $navUrl('profile.edit') }}" class="text-sm text-gray-600">{{ __('app.profile') }}</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ $navUrl('admin.dashboard') }}" class="text-sm text-indigo-600">{{ __('app.admin') }}</a>
                @endif
                <form method="POST" action="{{ $navUrl('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600">{{ __('app.logout') }}</button>
                </form>
            @else
                <a href="{{ $navUrl('login') }}" class="text-sm text-gray-600">{{ __('app.login') }}</a>
                @if (\App\Support\RegistrationGate::allowsOpenRegistration())
                    <a href="{{ $navUrl('register') }}" class="text-sm font-medium text-gray-900">{{ __('app.register') }}</a>
                @endif
            @endauth
        </div>
    </div>
</nav>
