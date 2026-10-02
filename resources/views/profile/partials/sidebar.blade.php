@php
    $profileUrl = fn (string $name, mixed $params = []) => \App\Support\SiteUrl::mainRoute($name, $params);
    $links = [
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.edit')],
        ['label' => 'Author aliases', 'route' => 'profile.aliases', 'active' => request()->routeIs('profile.aliases*')],
        ['label' => 'My stories', 'route' => 'posts.mine', 'active' => request()->routeIs('posts.mine')],
    ];

    if (\App\Support\Features::enabled('magazines')) {
        $links[] = ['label' => __('app.my_magazines'), 'route' => 'magazines.mine', 'active' => request()->routeIs('magazines.mine')];
    }

    $links = array_merge($links, [
        ['label' => 'Stats', 'route' => 'stats.index', 'active' => request()->routeIs('stats.index')],
        ['label' => 'Reading lists', 'route' => 'reading-lists.index', 'active' => request()->routeIs('reading-lists.*', 'bookmarks.index')],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => request()->routeIs('notifications.index')],
        ['label' => 'Request category', 'route' => 'category-requests.create', 'active' => request()->routeIs('category-requests.create', 'category-requests.mine')],
    ]);

    if (\App\Support\TaxonomyModerator::isTaxonomyModerator(auth()->user())) {
        $links[] = ['label' => 'Moderation', 'route' => 'moderation.index', 'active' => request()->routeIs('moderation.*')];
    }
@endphp

<aside class="w-full lg:w-56 shrink-0">
    <nav aria-label="Profile menu" class="bg-white border border-gray-200 rounded-lg p-3 space-y-1">
        @foreach ($links as $link)
            <a
                href="{{ $profileUrl($link['route']) }}"
                @class([
                    'block rounded-md px-3 py-2 text-sm font-medium',
                    'bg-gray-900 text-white' => $link['active'],
                    'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => ! $link['active'],
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    @if (auth()->user()->isAdmin())
        <nav aria-label="Admin menu" class="mt-4 bg-white border border-gray-200 rounded-lg p-3 space-y-1">
            <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Admin</p>
            <a
                href="{{ $profileUrl('admin.dashboard') }}"
                @class([
                    'block rounded-md px-3 py-2 text-sm font-medium',
                    'bg-indigo-600 text-white' => request()->routeIs('admin.dashboard'),
                    'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => ! request()->routeIs('admin.dashboard'),
                ])
            >
                Dashboard
            </a>
            <a
                href="{{ $profileUrl('admin.users.index') }}"
                @class([
                    'block rounded-md px-3 py-2 text-sm font-medium',
                    'bg-indigo-600 text-white' => request()->routeIs('admin.users.*'),
                    'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => ! request()->routeIs('admin.users.*'),
                ])
            >
                Users
            </a>
        </nav>
    @endif
</aside>
