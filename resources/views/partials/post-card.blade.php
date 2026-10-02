<article @class([
    'border-b border-gray-100 py-4',
    'py-3' => ! empty($compact),
    'py-6' => ! empty($profileFeed),
])>
    @php
        $postHref = \App\Support\PostUrl::for($post);
    @endphp
    <div @class([
        'flex gap-4 items-start' => ! empty($rank) || $post->feedCoverUrl() || ! empty($profileFeed),
        'flex-row-reverse' => ! empty($profileFeed) && $post->feedCoverUrl(),
    ])>
        @if (! empty($rank))
            <span class="text-2xl font-bold text-gray-300 leading-none pt-1 w-7 shrink-0">{{ $rank }}</span>
        @endif

        @if ($thumb = $post->feedCoverUrl())
            <a href="{{ $postHref }}" @class([
                'shrink-0',
                'hidden sm:block' => empty($profileFeed),
                'block' => ! empty($profileFeed),
            ])>
                <img
                    src="{{ $thumb }}"
                    alt=""
                    @class([
                        'rounded object-cover',
                        'w-20 h-20' => empty($profileFeed),
                        'w-28 h-28 sm:w-32 sm:h-32' => ! empty($profileFeed),
                    ])
                >
            </a>
        @endif

        <div class="min-w-0 flex-1">
            <div class="text-sm text-gray-500 mb-1 flex flex-wrap items-center gap-x-1 gap-y-1">
                @if ($post->isPinned())
                    <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-stone-100 text-stone-700">{{ __('app.pinned') }}</span>
                    <span>·</span>
                @endif
                @if ($post->isGallery())
                    <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ __('app.post_type_gallery') }}</span>
                    <span>·</span>
                @elseif ($post->isVideo())
                    <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-violet-100 text-violet-800">{{ __('app.post_type_video') }}</span>
                    <span>·</span>
                @endif
                @if (empty($profileFeed))
                    @include('partials.post-author', ['post' => $post, 'class' => 'font-medium text-gray-800'])
                    @if ($post->category)
                        <span>·</span>
                        <a href="{{ route('categories.show', $post->category) }}">{{ $post->category->name }}</a>
                    @endif
                    <span>·</span>
                    <span>{{ $post->published_at?->diffForHumans() }}</span>
                @else
                    <span>{{ $post->published_at?->format('M j') }}</span>
                @endif
            </div>

            <h2 @class([
                'font-bold mb-1',
                'text-2xl' => empty($compact),
                'text-lg' => ! empty($compact),
            ])>
                <a href="{{ $postHref }}" class="hover:underline">{{ $post->title }}</a>
            </h2>

            @if ($post->subtitle && empty($compact))
                <p class="text-gray-600 mb-2">{{ $post->subtitle }}</p>
            @endif

            <div class="flex items-center gap-4 text-sm text-gray-500">
                @if ($post->isGallery())
                    <span>{{ trans_choice('app.gallery_photo_count', $post->gallery_items_count ?? $post->galleryItems->count(), ['count' => number_format($post->gallery_items_count ?? $post->galleryItems->count())]) }}</span>
                @elseif ($post->isVideo())
                    <span>{{ __('app.post_type_video') }}</span>
                @else
                    <span>{{ $post->reading_time }} min read</span>
                @endif
                <span>{{ number_format($post->views_count) }} views</span>
                <span>{{ number_format($post->likes_count) }} likes</span>
            </div>
        </div>
    </div>
</article>
