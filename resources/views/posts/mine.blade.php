<x-app-layout>
    <x-profile-layout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">{{ __('app.my_stories') }}</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('posts.create.gallery') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-800 hover:bg-gray-50">{{ __('app.new_gallery') }}</a>
                <a href="{{ route('posts.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">{{ __('app.write') }}</a>
            </div>
        </div>

        @if (session('status') === 'post-pinned')
            <p class="mb-4 text-sm text-green-700">{{ __('app.post_pinned') }}</p>
        @endif
        @if (session('status') === 'post-unpinned')
            <p class="mb-4 text-sm text-green-700">{{ __('app.post_unpinned') }}</p>
        @endif

        @forelse ($posts as $post)
            <div class="border-b border-gray-200 py-4 flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full
                            @if ($post->isScheduled()) bg-blue-100 text-blue-800
                            @elseif ($post->isPublished()) bg-green-100 text-green-800
                            @elseif ($post->status->value === 'draft') bg-gray-100 text-gray-700
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            @if ($post->isScheduled())
                                scheduled
                            @else
                                {{ $post->status->value }}
                            @endif
                        </span>
                        @if ($post->isScheduled())
                            <span class="text-xs text-gray-500">{{ $post->published_at->format('M j, g:i A') }}</span>
                        @endif
                        @if ($post->isPinned())
                            <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">{{ __('app.pinned') }}</span>
                        @endif
                        @if ($post->isGallery())
                            <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ __('app.post_type_gallery') }}</span>
                        @endif
                        <span class="text-sm text-gray-500">{{ $post->updated_at->format('M j, Y') }}</span>
                    </div>
                    <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="text-lg font-semibold hover:underline">{{ $post->title }}</a>
                    <p class="text-sm text-gray-500">
                        @if ($post->category)
                            {{ $post->category->name }}
                        @else
                            Uncategorized
                        @endif
                        @if ($post->authorAlias)
                            · {{ '@'.$post->authorAlias->username }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @if ($post->isPublished())
                        <form method="POST" action="{{ route('posts.pin', $post) }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ $post->isPinned() ? __('app.unpin') : __('app.pin') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('posts.edit', $post) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.edit') }}</a>
                </div>
            </div>
        @empty
            <p class="text-gray-600">{{ __('app.no_stories_written') }}</p>
        @endforelse

        <div class="mt-6">{{ $posts->links() }}</div>
    </x-profile-layout>
</x-app-layout>
