<x-app-layout>
    <x-profile-layout>
        <div class="flex items-center justify-between gap-4 mb-6">
            <h1 class="text-3xl font-bold">Reading lists</h1>
        </div>

        <form method="POST" action="{{ route('reading-lists.store') }}" class="mb-8 flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[12rem]">
                <label for="list-name" class="block text-sm font-medium text-gray-700 mb-1">New list</label>
                <input
                    id="list-name"
                    type="text"
                    name="name"
                    required
                    maxlength="60"
                    placeholder="e.g. Weekend reads"
                    class="w-full rounded-md border-gray-300 shadow-sm"
                    value="{{ old('name') }}"
                >
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                Create list
            </button>
        </form>

        <div class="space-y-3">
            @forelse ($lists as $list)
                <a
                    href="{{ route('reading-lists.show', $list) }}"
                    class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-gray-300"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ $list->name }}</h2>
                            @if ($list->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $list->description }}</p>
                            @endif
                        </div>
                        <span class="text-sm text-gray-500 shrink-0">
                            {{ $list->posts_count }} {{ str('story')->plural($list->posts_count) }}
                        </span>
                    </div>
                </a>
            @empty
                <p class="text-gray-600">No lists yet. Create one above or save a story from any post.</p>
            @endforelse
        </div>
    </x-profile-layout>
</x-app-layout>
