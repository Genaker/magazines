<x-app-layout :seo="$seo ?? null">
    @php
        $isGallery = $post->isGallery();
        $readingColumn = 'max-w-2xl mx-auto w-full';
    @endphp

    <div @class([
        'mx-auto w-full px-4 sm:px-6 pb-12',
        'max-w-6xl' => $isGallery,
        'max-w-2xl' => ! $isGallery,
    ])>
        <article class="py-10" data-post-id="{{ $post->id }}">
            @unless ($isGallery)
                <div id="reading-progress" class="fixed top-0 left-0 h-0.5 bg-ink z-50" style="width: 0%"></div>
            @endunless

            @if ($isGallery)
                <div class="{{ $readingColumn }}">
            @endif

            <p class="text-sm text-gray-500 mb-4">
                @if ($post->magazine)
                    @if ($post->magazine_submission_status?->value === 'approved')
                        <a href="{{ \App\Support\MagazineSubdomain::canonicalMagazineUrl($post->magazine) }}" class="font-medium text-gray-800">{{ $post->magazine->name }}</a>
                        ·
                    @elseif ($post->magazine_submission_status?->value === 'pending')
                        <span class="text-amber-700">{{ __('message.post_pending_magazine', ['magazine' => $post->magazine->name]) }}</span>
                        ·
                    @else
                        <span>{{ __('app.post_assigned_to_magazine', ['magazine' => $post->magazine->name]) }}</span>
                        ·
                    @endif
                @endif
                @if ($post->category)
                    <a href="{{ route('categories.show', $post->category) }}">{{ $post->category->name }}</a>
                    ·
                @endif
                @if ($isGallery)
                    {{ trans_choice('app.gallery_photo_count', $post->galleryItems->count(), ['count' => number_format($post->galleryItems->count())]) }}
                    @if (filled(strip_tags((string) $post->body)))
                        · {{ $post->reading_time }} min read
                    @endif
                @elseif ($post->isVideo())
                    {{ __('app.post_type_video') }}
                    @if (filled(strip_tags((string) $post->body)))
                        · {{ $post->reading_time }} min read
                    @endif
                @else
                    {{ $post->reading_time }} min read
                @endif
            </p>

            @if ($post->isScheduled())
                <p class="text-sm uppercase tracking-wide text-blue-700 mb-2">
                    Scheduled — goes live {{ $post->published_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}
                </p>
            @elseif ($post->status->value !== 'published')
                <p class="text-sm uppercase tracking-wide text-amber-700 mb-2">{{ $post->status->value }} — shareable by link</p>
            @endif

            <h1 class="font-serif text-4xl sm:text-5xl font-bold tracking-tight text-ink mb-3">{{ $post->title }}</h1>
            @if ($post->subtitle)
                <p class="font-sans text-xl text-ink-muted mb-8 leading-snug">{{ $post->subtitle }}</p>
            @endif

            <div class="flex items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                    @include('partials.post-author', ['post' => $post, 'class' => 'font-medium'])
                    <span class="text-gray-500">{{ $post->published_at?->format('M j, Y') }}</span>
                </div>
                @can('update', $post)
                    <a href="{{ route('posts.edit', $post) }}" class="text-sm text-gray-600 hover:text-gray-900">Edit</a>
                @endcan
            </div>

            @if ($isGallery)
                </div>
            @endif

            <x-hook name="post.before_content" :data="['post' => $post]" />

            @if ($isGallery)
                @if (filled(strip_tags((string) $post->body)))
                    <div class="{{ $readingColumn }}">
                        <div class="prose-editorial max-w-none mb-8">{!! $post->body !!}</div>
                    </div>
                @endif

                @include('partials.portfolio-gallery', [
                    'items' => $post->galleryItems,
                    'postTitle' => $post->title,
                ])
            @elseif ($post->isVideo())
                @include('partials.video-embed', ['embedHtml' => $videoEmbedHtml ?? null])

                @if (filled(strip_tags((string) $post->body)))
                    <div class="prose-editorial max-w-none mb-8">{!! $post->body !!}</div>
                @endif
            @else
                @if ($coverUrl ?? null)
                    <img src="{{ $coverUrl }}" @if($coverSrcset ?? null) srcset="{{ $coverSrcset }}" sizes="(max-width: 768px) 100vw, 768px" @endif alt="" class="w-full rounded-lg mb-8">
                @endif

                <div class="prose-editorial max-w-none mb-8">{!! $post->body !!}</div>
            @endif

            <x-hook name="post.after_content" :data="['post' => $post]" />

            @if ($isGallery)
                <div class="{{ $readingColumn }}">
            @endif

            <div class="flex flex-wrap gap-2 mb-8">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('tags.show', $tag) }}" class="px-3 py-1 bg-stone-100 text-ink-muted rounded-full text-sm hover:bg-stone-200">{{ $tag->display_name }}</a>
                @endforeach
            </div>

            <div class="flex items-center gap-6 border-y border-gray-100 py-4 mb-8">
                <button id="like-btn"
                        data-liked="{{ $liked ? '1' : '0' }}"
                        class="text-sm font-medium {{ $liked ? 'text-ink' : 'text-ink-muted' }}">
                    <span id="like-label">{{ $liked ? 'Unlike' : 'Like' }}</span>
                    · <span id="like-count">{{ $post->likes_count }}</span>
                </button>
                <span class="text-sm text-gray-500"><span id="view-count">{{ $post->views_count }}</span> views</span>
                @auth
                    @feature('reading_lists')
                        @include('partials.reading-list-picker')
                    @endfeature
                @endauth
            </div>

            @if ($isGallery)
                </div>
            @endif
        </article>

        @if ($isGallery)
            <div class="{{ $readingColumn }}">
        @endif

        @include('partials.related-posts', ['relatedPosts' => $relatedPosts ?? collect()])
        @include('partials.more-from-author', ['post' => $post, 'moreFromAuthor' => $moreFromAuthor ?? collect()])

        @feature('comments')
            @if (\App\Support\CommentSettings::useDisqus())
                @include('partials.disqus-comments', ['post' => $post])
            @else
                @include('partials.comments-section', ['post' => $post, 'comments' => $comments, 'canComment' => $canComment])
            @endif
            <x-hook name="post.after_comments" :data="['post' => $post, 'comments' => $comments ?? collect()]" />
        @endfeature

        @if ($isGallery)
            </div>
        @endif
    </div>

    @push('scripts')
        @vite('resources/js/gallery.js')
        @unless ($isGallery)
            <script src="{{ static_asset('js/post.js') }}"></script>
        @endunless
    @endpush
</x-app-layout>
