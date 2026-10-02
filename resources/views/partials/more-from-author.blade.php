@if ($moreFromAuthor->isNotEmpty() && $author = $post->authorAlias)
    <section class="mt-10 pt-8 border-t border-gray-100">
        <div class="flex items-center gap-4 mb-6">
            <x-user-avatar :author="$author" size="xl" />
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wide">More from</p>
                @if (! $author->isRetired())
                    <a href="{{ \App\Support\SiteUrl::author($author) }}" class="text-xl font-semibold hover:underline">{{ $author->name }}</a>
                @else
                    <span class="text-xl font-semibold text-gray-500">{{ $author->name }}</span>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            @foreach ($moreFromAuthor as $related)
                <article class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                    <div class="text-sm text-gray-500 mb-2">
                        <span class="font-medium text-gray-800">{{ $author->name }}</span>
                        <span class="mx-1">·</span>
                        <time datetime="{{ $related->published_at?->toIso8601String() }}">
                            {{ $related->published_at?->format('M j, Y, g:i') }}
                        </time>
                    </div>
                    <h3 class="text-lg font-bold mb-2">
                        <a href="{{ \App\Support\PostUrl::for($related) }}" class="hover:underline">
                            {{ $related->title }}
                        </a>
                    </h3>
                    @if ($related->excerpt())
                        <p class="text-gray-600 leading-relaxed">{{ $related->excerpt() }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
