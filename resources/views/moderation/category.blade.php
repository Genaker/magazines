<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <p class="text-sm text-gray-500 mb-2">
            <a href="{{ route('moderation.index') }}" class="hover:text-gray-800">Moderation</a>
            <span class="mx-1">/</span>
            <span>{{ $category->name }}</span>
        </p>

        <h1 class="text-3xl font-bold mb-2">{{ $category->name }}</h1>
        <p class="text-gray-600 mb-8">Recent posts in this category. Hide off-topic stories from feeds or move them to a better section.</p>

        @if (session('status'))
            <p class="mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-2">{{ session('status') }}</p>
        @endif

        @if ($posts->isEmpty())
            <p class="text-gray-600">No published posts in this category yet.</p>
        @else
            <div class="space-y-6">
                @foreach ($posts as $post)
                    @include('moderation.partials.post-row', [
                        'post' => $post,
                        'moveTargets' => $moveTargets,
                        'showCategoryMove' => true,
                        'showTagEdit' => false,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
