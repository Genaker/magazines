@php
    $header = $adminHeader ?? \App\Support\AdminHeader::data();
@endphp

<header class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 min-w-0">
                <a href="{{ route('admin.dashboard') }}" class="font-semibold text-gray-900 hover:text-indigo-600 truncate">
                    {{ $header['siteName'] }}
                </a>
                <span class="text-gray-300 hidden sm:inline" aria-hidden="true">·</span>
                <span class="text-sm text-gray-500">Admin</span>
                @if ($header['tenantLabel'])
                    <span class="text-gray-300 hidden sm:inline" aria-hidden="true">·</span>
                    <span class="text-xs font-medium uppercase tracking-wide text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">
                        {{ $header['tenantLabel'] }}
                    </span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                <span class="rounded-full bg-gray-100 px-2.5 py-1">
                    <span class="font-semibold text-gray-900">{{ number_format($header['stats']['users']) }}</span> users
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1">
                    <span class="font-semibold text-gray-900">{{ number_format($header['stats']['posts']) }}</span> posts
                </span>
                @feature('category_requests')
                    @isset($header['stats']['pending_requests'])
                        <a href="{{ route('admin.category-requests.index') }}" @class([
                            'rounded-full px-2.5 py-1 hover:bg-amber-100',
                            'bg-amber-50 text-amber-800' => $header['stats']['pending_requests'] > 0,
                            'bg-gray-100' => $header['stats']['pending_requests'] === 0,
                        ])>
                            <span class="font-semibold">{{ number_format($header['stats']['pending_requests']) }}</span> pending requests
                        </a>
                    @endisset
                @endfeature
                @feature('user_reports')
                    @isset($header['stats']['reports'])
                        <a href="{{ route('admin.user-reports.index') }}" class="rounded-full bg-gray-100 px-2.5 py-1 hover:bg-gray-200">
                            <span class="font-semibold text-gray-900">{{ number_format($header['stats']['reports']) }}</span> reports
                        </a>
                    @endisset
                @endfeature
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <nav aria-label="Admin quick links" class="hidden md:flex items-center gap-1 mr-2">
                    @foreach ($header['quickLinks'] as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'px-2.5 py-1 text-sm rounded-md whitespace-nowrap',
                                'bg-gray-900 text-white font-medium' => $link['active'],
                                'text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! $link['active'],
                            ])
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>

                <span class="hidden lg:inline text-sm text-gray-500 truncate max-w-[12rem]" title="{{ auth()->user()->email }}">
                    {{ auth()->user()->name }}
                </span>

                <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                    Back to site
                </a>
            </div>
        </div>
    </div>
</header>
