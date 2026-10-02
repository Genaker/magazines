@if ($tagCloud->isNotEmpty())
    <aside
        @class(['pt-10 border-t border-gray-200' => $sidebar ?? false])
        aria-labelledby="tag-cloud-heading"
    >
        <h2 id="tag-cloud-heading" class="text-xs font-semibold uppercase tracking-wider text-ink-muted border-b border-gray-100 pb-2 mb-4">
            {{ __('app.popular_tags') }}
        </h2>
        <ul class="flex flex-wrap gap-x-3 gap-y-2">
            @foreach ($tagCloud as $index => $tag)
                @php
                    $rank = $index + 1;
                    $count = $tag->published_posts_count;
                    $size = match (true) {
                        $rank === 1 => 'text-xl font-semibold',
                        $rank <= 3 => 'text-lg font-medium',
                        $rank <= 10 => 'text-base',
                        default => 'text-sm',
                    };
                @endphp
                <li>
                    <a href="{{ route('tags.show', $tag) }}"
                       class="inline-flex items-baseline text-gray-800 hover:text-gray-600 {{ $size }}"
                       aria-label="{{ $tag->display_name }}, {{ trans_choice('app.tag_post_count', $count, ['count' => $count]) }}">
                        <span>{{ $tag->display_name }}</span><span class="text-gray-500 font-normal tabular-nums text-[0.85em]">({{ number_format($count) }})</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>
@endif
