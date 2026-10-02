<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">{{ __('app.authors') }}</h1>

        <form action="{{ route('authors.index') }}" method="GET" class="mb-8">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                class="w-full rounded-md border-gray-300"
                placeholder="{{ __('app.search_authors') }}"
            >
        </form>

        @forelse ($authors as $author)
            <a href="{{ \App\Support\SiteUrl::author($author) }}" class="flex items-start gap-4 border-b border-gray-200 py-4 hover:bg-gray-50 -mx-2 px-2 rounded">
                <x-user-avatar :author="$author" size="md" />
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold">{{ $author->name }}</h2>
                    <p class="text-sm text-gray-500">{{ '@'.$author->username }}</p>
                    @if ($author->bio)
                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($author->bio, 160) }}</p>
                    @endif
                    <p class="text-xs text-gray-500 mt-2">{{ trans_choice('app.published_story', (int) $author->posts_count, ['count' => number_format($author->posts_count)]) }}</p>
                </div>
            </a>
        @empty
            <p class="text-gray-600">{{ $query !== '' ? __('app.no_authors_found') : __('app.no_authors_yet') }}</p>
        @endforelse

        <div class="mt-6">{{ $authors->links() }}</div>
    </div>
</x-app-layout>
