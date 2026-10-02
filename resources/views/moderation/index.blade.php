<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Moderation</h1>
        <p class="text-gray-600 mb-8">Manage posts in categories and tags you moderate.</p>

        @if (session('status'))
            <p class="mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-2">{{ session('status') }}</p>
        @endif

        @if ($categories->isNotEmpty())
            <section class="mb-10">
                <h2 class="text-lg font-semibold mb-3">Categories</h2>
                <ul class="divide-y border border-gray-200 rounded-lg">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('moderation.categories.show', $category) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                <span>{{ $category->name }}</span>
                                <span class="text-sm text-gray-500">Manage →</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($tags->isNotEmpty())
            <section>
                <h2 class="text-lg font-semibold mb-3">Tags</h2>
                <ul class="divide-y border border-gray-200 rounded-lg">
                    @foreach ($tags as $tag)
                        <li>
                            <a href="{{ route('moderation.tags.show', $tag) }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                <span>{{ $tag->display_name }}</span>
                                <span class="text-sm text-gray-500">Manage →</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($categories->isEmpty() && $tags->isEmpty())
            <p class="text-gray-600">You are not assigned as a moderator yet.</p>
        @endif
    </div>
</x-app-layout>
