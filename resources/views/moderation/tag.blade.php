<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <p class="text-sm text-gray-500 mb-2">
            <a href="{{ route('moderation.index') }}" class="hover:text-gray-800">Moderation</a>
            <span class="mx-1">/</span>
            <span>{{ $tag->display_name }}</span>
        </p>

        <h1 class="text-3xl font-bold mb-2">{{ $tag->display_name }}</h1>
        <p class="text-gray-600 mb-8">Fix mis-tagged posts or hide spam from feeds.</p>

        @if (session('status'))
            <p class="mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-2">{{ session('status') }}</p>
        @endif

        @if ($posts->isEmpty())
            <p class="text-gray-600">No published posts with this tag yet.</p>
        @else
            <div class="space-y-6">
                @foreach ($posts as $post)
                    @include('moderation.partials.post-row', [
                        'post' => $post,
                        'moveTargets' => collect(),
                        'showCategoryMove' => false,
                        'showTagEdit' => true,
                    ])
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
