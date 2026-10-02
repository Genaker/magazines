<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <header class="mb-8 border-b border-gray-200 pb-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('app.home') }}</h1>
            <p class="text-gray-600 mt-2 max-w-2xl">{{ __('app.home_intro') }}</p>
        </header>

        @if ($layout === \App\Support\HomeLayout::Discover && ! empty($personalized))
            <div class="grid lg:grid-cols-2 gap-10 mb-12">
                @include('partials.feed-section', [
                    'title' => __('app.latest_from_feed'),
                    'posts' => $personalized['latest'],
                    'empty' => __('app.follow_to_personalize'),
                ])

                @include('partials.feed-section', [
                    'title' => __('app.popular_in_feed'),
                    'posts' => $personalized['popular'],
                    'empty' => __('app.follow_to_personalize'),
                    'compact' => true,
                ])
            </div>
        @endif

        @if ($layout === \App\Support\HomeLayout::Latest)
            <section>
                @include('partials.feed-section', [
                    'title' => __('app.latest'),
                    'posts' => $posts,
                    'empty' => __('app.no_stories_yet'),
                ])

                <div class="mt-6">{{ $posts->links() }}</div>
            </section>
        @elseif ($layout === \App\Support\HomeLayout::Trending)
            <div class="grid lg:grid-cols-3 gap-10 mb-12">
                <div class="lg:col-span-2">
                    @include('partials.feed-section', [
                        'title' => __('app.top_stories_week'),
                        'posts' => $sections['popularWeek'],
                        'showRank' => true,
                    ])
                </div>

                <div class="space-y-10">
                    @include('partials.feed-section', [
                        'title' => __('app.trending_last_hour'),
                        'posts' => $sections['popularHour'],
                        'showRank' => true,
                        'compact' => true,
                    ])

                    @include('partials.feed-section', [
                        'title' => __('app.trending_today'),
                        'posts' => $sections['popularDay'],
                        'showRank' => true,
                        'compact' => true,
                    ])

                    @include('partials.tag-cloud', ['tagCloud' => $tagCloud, 'sidebar' => true])
                </div>
            </div>
        @else
            <div class="grid lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2 space-y-10">
                    @include('partials.feed-section', [
                        'title' => __('app.top_stories_week'),
                        'posts' => $sections['popularWeek'],
                        'showRank' => true,
                    ])

                    <div>
                        @include('partials.feed-section', [
                            'title' => __('app.latest'),
                            'posts' => $posts,
                            'empty' => __('app.no_stories_yet'),
                        ])

                        <div class="mt-6">{{ $posts->links() }}</div>
                    </div>
                </div>

                <div class="space-y-10">
                    @include('partials.feed-section', [
                        'title' => __('app.trending_last_hour'),
                        'posts' => $sections['popularHour'],
                        'showRank' => true,
                        'compact' => true,
                    ])

                    @include('partials.feed-section', [
                        'title' => __('app.trending_today'),
                        'posts' => $sections['popularDay'],
                        'showRank' => true,
                        'compact' => true,
                    ])

                    @include('partials.tag-cloud', ['tagCloud' => $tagCloud, 'sidebar' => true])
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
