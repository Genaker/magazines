<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">{{ $tag->display_name }}</h1>
            @auth
                <button id="follow-tag-btn" data-tag-id="{{ $tag->id }}" data-following="{{ $isFollowing ? '1' : '0' }}" class="px-4 py-2 border rounded-full text-sm">
                    {{ $isFollowing ? 'Following' : 'Follow' }}
                </button>
            @endauth
        </div>

        @foreach ($posts as $post)
            @include('partials.post-card', ['post' => $post])
        @endforeach

        <div class="mt-6">{{ $posts->links() }}</div>
    </div>

    @push('scripts')
        <script src="{{ static_asset('js/post.js') }}"></script>
    @endpush
</x-app-layout>
