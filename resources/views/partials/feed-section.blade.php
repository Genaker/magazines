<section class="mb-10">
    <h2 class="text-xs font-semibold uppercase tracking-wider text-ink-muted border-b border-gray-100 pb-2 mb-4">{{ $title }}</h2>

    @forelse ($posts as $index => $post)
        @include('partials.post-card', [
            'post' => $post,
            'rank' => ($showRank ?? false) ? $index + 1 : null,
            'compact' => $compact ?? false,
        ])
    @empty
        <p class="text-gray-500 text-sm py-4">{{ $empty ?? __('app.no_stories_yet') }}</p>
    @endforelse
</section>
