@if (($relatedPosts ?? collect())->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-gray-100">
        @include('partials.feed-section', [
            'title' => __('app.related_stories'),
            'posts' => $relatedPosts,
            'compact' => true,
        ])
    </section>
@endif
