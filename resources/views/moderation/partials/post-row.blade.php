<article class="border border-gray-200 rounded-lg p-4">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
            <h2 class="font-semibold text-lg">
                <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="hover:underline">{{ $post->title }}</a>
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ '@'.$post->authorAlias->username }}
                · {{ $post->category?->name ?? 'Uncategorized' }}
                · {{ $post->published_at?->diffForHumans() }}
            </p>
            @if ($post->tags->isNotEmpty())
                <p class="text-sm text-gray-500 mt-1">
                    @foreach ($post->tags as $tag)
                        <span class="inline-block mr-2">{{ $tag->display_name }}</span>
                    @endforeach
                </p>
            @endif
        </div>

        @if ($post->isHiddenFromFeeds())
            <span class="text-xs font-medium uppercase tracking-wide text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1">Hidden from feeds</span>
        @endif
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($post->isHiddenFromFeeds())
            <form method="POST" action="{{ route('moderation.posts.show-feed', $post) }}">
                @csrf
                <button type="submit" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50">Restore to feeds</button>
            </form>
        @else
            <form method="POST" action="{{ route('moderation.posts.hide-feed', $post) }}" onsubmit="return confirm('Hide this post from home, category, tag, and search feeds?')">
                @csrf
                <button type="submit" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50">Hide from feeds</button>
            </form>
        @endif

        @if ($showCategoryMove && $moveTargets->isNotEmpty())
            <form method="POST" action="{{ route('moderation.posts.category', $post) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                @method('PUT')
                <select name="category_id" class="text-sm rounded-md border-gray-300" required>
                    @foreach ($moveTargets as $target)
                        <option value="{{ $target->id }}" @selected($target->id === $post->category_id)>{{ $target->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50">Move category</button>
            </form>
        @endif

        @if ($showTagEdit)
            <form method="POST" action="{{ route('moderation.posts.tags', $post) }}" class="flex flex-wrap items-center gap-2 flex-1 min-w-[16rem]">
                @csrf
                @method('PUT')
                <input
                    type="text"
                    name="tags"
                    value="{{ $post->tags->pluck('name')->implode(', ') }}"
                    class="text-sm rounded-md border-gray-300 flex-1 min-w-[12rem]"
                    placeholder="tag1, tag2"
                    required
                >
                <button type="submit" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50">Update tags</button>
            </form>
        @endif
    </div>
</article>
