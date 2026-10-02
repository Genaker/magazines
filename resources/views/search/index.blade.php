<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Search</h1>

        <form action="{{ route('search') }}" method="GET" class="mb-8">
            <input type="search" name="q" value="{{ $query }}" class="w-full rounded-md border-gray-300" placeholder="{{ \App\Support\Features::enabled('magazines') ? __('app.search_placeholder_magazines') : __('app.search_placeholder') }}">
        </form>

        @if ($query !== '')
            <h2 class="text-xl font-semibold mb-4">Posts</h2>
            @forelse ($posts as $post)
                @include('partials.post-card', ['post' => $post])
            @empty
                <p class="text-gray-600 mb-6">No posts found.</p>
            @endforelse

            @feature('magazines')
                <h2 class="text-xl font-semibold mb-4 mt-8">Magazines</h2>
                @forelse ($magazines as $magazine)
                    <p class="mb-2">
                        <a href="{{ route('magazines.show', $magazine) }}" class="underline">{{ $magazine->name }}</a>
                        @if ($magazine->description)
                            <span class="text-sm text-gray-600"> — {{ Str::limit($magazine->description, 120) }}</span>
                        @endif
                    </p>
                @empty
                    <p class="text-gray-600 mb-6">No magazines found.</p>
                @endforelse
            @endfeature

            <h2 class="text-xl font-semibold mb-4 mt-8">Authors</h2>
            @forelse ($authors as $author)
                <p class="mb-2"><a href="{{ route('authors.show', $author) }}" class="underline">{{ $author->name }} ({{ '@'.$author->username }})</a></p>
            @empty
                <p class="text-gray-600">No authors found.</p>
            @endforelse
        @endif
    </div>
</x-app-layout>
