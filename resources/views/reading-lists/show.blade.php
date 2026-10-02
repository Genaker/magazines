<x-app-layout>
    <x-profile-layout>
        <div class="mb-6">
            <a href="{{ route('reading-lists.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; All lists</a>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $readingList->name }}</h1>
                @if ($readingList->description)
                    <p class="text-gray-600 mt-2">{{ $readingList->description }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-2">{{ $posts->total() }} {{ str('story')->plural($posts->total()) }}</p>
            </div>

            @can('update', $readingList)
                <details class="relative">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">Edit list</summary>
                    <div class="mt-3 rounded-lg border border-gray-200 bg-white p-4 space-y-3 min-w-[16rem]">
                        <form method="POST" action="{{ route('reading-lists.update', $readingList) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="edit-list-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input
                                    id="edit-list-name"
                                    type="text"
                                    name="name"
                                    required
                                    maxlength="60"
                                    value="{{ old('name', $readingList->name) }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm"
                                >
                            </div>
                            <div>
                                <label for="edit-list-description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea
                                    id="edit-list-description"
                                    name="description"
                                    maxlength="280"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm"
                                >{{ old('description', $readingList->description) }}</textarea>
                            </div>
                            <button type="submit" class="px-3 py-1.5 bg-gray-900 text-white text-sm rounded-md">Save</button>
                        </form>

                        @can('delete', $readingList)
                            <form method="POST" action="{{ route('reading-lists.destroy', $readingList) }}" onsubmit="return confirm('Delete this list? Stories stay published.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete list</button>
                            </form>
                        @endcan
                    </div>
                </details>
            @endcan
        </div>

        @forelse ($posts as $post)
            @include('partials.post-card', ['post' => $post])
        @empty
            <p class="text-gray-600">No stories in this list yet.</p>
        @endforelse

        <div class="mt-6">{{ $posts->links() }}</div>
    </x-profile-layout>
</x-app-layout>
