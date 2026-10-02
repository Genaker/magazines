<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Magazines</h1>
            @auth
                <a href="{{ route('magazines.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Create magazine</a>
            @endauth
        </div>

        <form action="{{ route('magazines.index') }}" method="GET" class="mb-8">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                class="w-full rounded-md border-gray-300"
                placeholder="Search magazines..."
            >
        </form>

        @forelse ($magazines as $magazine)
            <a href="{{ route('magazines.show', $magazine) }}" class="block border-b border-gray-200 py-4 hover:bg-gray-50 -mx-2 px-2 rounded">
                <h2 class="text-xl font-semibold">{{ $magazine->name }}</h2>
                @if ($magazine->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $magazine->description }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-2">{{ $magazine->posts_count }} published {{ Str::plural('story', $magazine->posts_count) }}</p>
            </a>
        @empty
            <p class="text-gray-600">{{ $query !== '' ? 'No magazines found.' : 'No magazines yet.' }}</p>
        @endforelse

        <div class="mt-6">{{ $magazines->links() }}</div>
    </div>
</x-app-layout>
