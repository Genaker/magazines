<x-app-layout>
    <x-profile-layout>
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Stats</h1>
            <p class="mt-1 text-sm text-gray-600">Overview of your stories and audience.</p>
        </div>

        <section class="mb-10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Monthly</h2>
                    <p class="text-sm text-gray-500">{{ $monthly['range_label'] }}</p>
                </div>
                @if ($monthOptions->isNotEmpty())
                    <form method="GET" action="{{ route('stats.index') }}" class="shrink-0">
                        <label for="month-picker" class="sr-only">Month</label>
                        <select
                            id="month-picker"
                            name="month-picker"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            onchange="const [year, month] = this.value.split('-'); window.location.href = '{{ route('stats.index') }}?year=' + year + '&month=' + month;"
                        >
                            @foreach ($monthOptions as $option)
                                <option
                                    value="{{ $option['value'] }}"
                                    @selected($option['year'] === $selectedYear && $option['month'] === $selectedMonth)
                                >
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($monthly['views']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Views</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($monthly['likes']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Likes</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($monthly['comments']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Comments</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($monthly['saves']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Saves</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">+{{ number_format($monthly['followers_gained']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Followers</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">+{{ number_format($monthly['subscribers_gained']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Subscribers</p>
                </div>
            </div>

            <h3 class="text-base font-semibold text-gray-900 mb-4">By story ({{ $monthly['label'] }})</h3>
            @if ($monthly['posts']->isEmpty())
                <p class="text-sm text-gray-500">No story activity this month.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Story</th>
                                <th class="px-4 py-3 text-right">Views</th>
                                <th class="px-4 py-3 text-right">Likes</th>
                                <th class="px-4 py-3 text-right">Comments</th>
                                <th class="px-4 py-3 text-right">Saves</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($monthly['posts'] as $row)
                                @php($post = $row['post'])
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-3">
                                        @if ($post->authorAlias && in_array($post->status->value, ['published', 'unlisted'], true))
                                            <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="font-medium text-gray-900 hover:underline">
                                                {{ $post->title }}
                                            </a>
                                        @else
                                            <span class="font-medium text-gray-900">{{ $post->title }}</span>
                                        @endif
                                        @if ($post->published_at)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $post->published_at->format('M j, Y') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['views']) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['likes']) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['comments']) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['saves']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="mb-10">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Audience</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['followers']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Followers</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['subscribers']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Email subscribers</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['following']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Following</p>
                </div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Lifetime</h2>
            <p class="text-sm text-gray-500 mb-4">
                {{ number_format($stats['published_posts']) }} published
                · {{ number_format($stats['draft_posts']) }} drafts
                · {{ number_format($stats['unlisted_posts']) }} unlisted
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['total_views']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Total views</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['views_last_30_days']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Views (30 days)</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['total_likes']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Total likes</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['total_comments']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Comments</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold tabular-nums">{{ number_format($stats['total_saves']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Saves</p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">By story (lifetime)</h2>
            @if ($stats['posts']->isEmpty())
                <p class="text-sm text-gray-500">No stories yet. <a href="{{ route('posts.create') }}" class="text-indigo-600 hover:underline">Write one</a>.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Story</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Views</th>
                                <th class="px-4 py-3 text-right">Likes</th>
                                <th class="px-4 py-3 text-right">Comments</th>
                                <th class="px-4 py-3 text-right">Read time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($stats['posts'] as $post)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-3">
                                        @if ($post->authorAlias && in_array($post->status->value, ['published', 'unlisted'], true))
                                            <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="font-medium text-gray-900 hover:underline">
                                                {{ $post->title }}
                                            </a>
                                        @else
                                            <span class="font-medium text-gray-900">{{ $post->title }}</span>
                                        @endif
                                        @if ($post->published_at)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $post->published_at->format('M j, Y') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 capitalize text-gray-600">{{ $post->status->value }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($post->views_count) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($post->likes_count) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($post->comments_count) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $post->reading_time }} min</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </x-profile-layout>
</x-app-layout>
