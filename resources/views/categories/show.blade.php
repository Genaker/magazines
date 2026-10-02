<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">
        @if ($category->parent)
            <p class="text-sm text-gray-500 mb-2">
                <a href="{{ route('categories.show', $category->parent) }}" class="hover:text-gray-800">{{ $category->parent->name }}</a>
                <span class="mx-1">/</span>
                <span>{{ $category->name }}</span>
            </p>
        @endif

        <header class="flex flex-wrap items-start justify-between gap-4 mb-8 border-b border-gray-200 pb-6">
            <div>
                <h1 class="text-4xl font-bold tracking-tight">{{ $category->name }}</h1>
                @if ($category->description)
                    <p class="text-gray-600 mt-2 max-w-2xl">{{ $category->description }}</p>
                @endif
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('categories.rss', $category) }}" class="text-sm text-gray-600 hover:text-gray-900 underline">RSS</a>
                @auth
                    <button id="follow-category-btn" data-category-id="{{ $category->id }}" data-following="{{ $isFollowing ? '1' : '0' }}" class="px-4 py-2 border rounded-full text-sm">
                        {{ $isFollowing ? 'Following' : 'Follow' }}
                    </button>
                @endauth
            </div>
        </header>

        @if ($childCategories->isNotEmpty())
            <nav class="mb-10" aria-label="Subcategories">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Subcategories</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($childCategories as $child)
                        <a href="{{ route('categories.show', $child) }}" class="px-3 py-1.5 bg-gray-100 rounded-full text-sm hover:bg-gray-200">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="grid lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-10">
                @include('partials.feed-section', [
                    'title' => __('app.top_stories_week'),
                    'posts' => $sections['trendingWeek'],
                    'showRank' => true,
                ])

                <div>
                    @include('partials.feed-section', [
                        'title' => __('app.latest'),
                        'posts' => $posts,
                        'empty' => 'No stories in this section yet.',
                    ])

                    <div class="mt-6">{{ $posts->links() }}</div>
                </div>
            </div>

            <div class="space-y-10">
                @include('partials.feed-section', [
                    'title' => __('app.trending_last_hour'),
                    'posts' => $sections['trendingHour'],
                    'showRank' => true,
                    'compact' => true,
                ])

                @include('partials.feed-section', [
                    'title' => __('app.trending_today'),
                    'posts' => $sections['trendingDay'],
                    'showRank' => true,
                    'compact' => true,
                ])
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ static_asset('js/post.js') }}"></script>
    @endpush
</x-app-layout>
