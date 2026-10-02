<x-app-layout :seo="$seo ?? null">
    @if ($coverUrl)
        <div class="relative w-full h-52 md:h-72 bg-gray-900">
            <img src="{{ $coverUrl }}" alt="{{ $magazine->name }}" class="absolute inset-0 h-full w-full object-cover opacity-90">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/10"></div>
            <div class="relative max-w-3xl mx-auto px-4 h-full flex items-end pb-8">
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold">{{ $magazine->name }}</h1>
                    @if ($magazine->description)
                        <p class="mt-2 text-white/90 max-w-2xl">{{ $magazine->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-3xl mx-auto px-4 py-8">
        @unless ($coverUrl)
            <div class="mb-8">
                <h1 class="text-3xl font-bold">{{ $magazine->name }}</h1>
                @if ($magazine->description)
                    <p class="text-gray-600 mt-2">{{ $magazine->description }}</p>
                @endif
            </div>
        @endunless

        <div class="flex items-start justify-between gap-4 mb-6">
            <p class="text-sm text-gray-500">
                Curated by {{ $magazine->owner->name }}
                · {{ number_format($followersCount ?? 0) }} {{ Str::plural('follower', $followersCount ?? 0) }}
            </p>

            <div class="flex flex-col items-end gap-2 shrink-0">
                @auth
                    <button
                        id="follow-magazine-btn"
                        data-magazine-id="{{ $magazine->id }}"
                        data-following="{{ ($isFollowing ?? false) ? '1' : '0' }}"
                        class="px-4 py-2 border rounded-full text-sm"
                    >
                        {{ ($isFollowing ?? false) ? 'Following' : 'Follow' }}
                    </button>

                    @if ($isMember ?? false)
                        <span class="px-4 py-2 border rounded-full text-sm bg-gray-50 text-gray-700">{{ __('message.magazine_you_are_member') }}</span>
                    @elseif ($hasPendingJoinRequest ?? false)
                        <span class="px-4 py-2 border border-amber-200 rounded-full text-sm bg-amber-50 text-amber-800">{{ __('message.magazine_join_pending') }}</span>
                    @else
                        <details class="text-right">
                            <summary class="cursor-pointer px-4 py-2 border rounded-full text-sm list-none">Request to join</summary>
                            <form method="POST" action="{{ \App\Support\SiteUrl::mainRoute('magazines.join-requests.store', $magazine) }}" class="mt-2 w-72 rounded-lg border border-gray-200 bg-white p-3 text-left shadow-sm">
                                @csrf
                                <label class="block text-sm font-medium text-gray-700 mb-1">Why do you want to join?</label>
                                <textarea name="message" rows="3" maxlength="1000" class="w-full rounded-md border-gray-300 text-sm" placeholder="Optional message to the editors"></textarea>
                                <button type="submit" class="mt-2 w-full rounded-md bg-gray-900 px-3 py-2 text-sm text-white">Send request</button>
                            </form>
                        </details>
                    @endif

                    @can('reviewSubmissions', $magazine)
                        <a href="{{ \App\Support\SiteUrl::navRoute('magazines.submissions', $magazine) }}" class="text-sm underline">Manage magazine</a>
                    @endcan
                @else
                    <a href="{{ \App\Support\SiteUrl::navRoute('login') }}" class="px-4 py-2 border rounded-full text-sm">Log in to follow</a>
                @endauth
            </div>
        </div>

        @if ($categories->isNotEmpty())
            <nav class="mb-8 -mx-1 overflow-x-auto" aria-label="Magazine categories">
                <div class="flex gap-2 min-w-max px-1 pb-1">
                    <a href="{{ \App\Support\MagazineSubdomain::canonicalMagazineUrl($magazine) }}"
                       class="px-3 py-1.5 rounded-full text-sm whitespace-nowrap {{ ! $activeCategory ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        All stories
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ \App\Support\MagazineSubdomain::canonicalMagazineUrl($magazine, ['category' => $category->slug]) }}"
                           class="px-3 py-1.5 rounded-full text-sm whitespace-nowrap {{ ($activeCategory?->id === $category->id) ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        @if ($activeCategory)
            <h2 class="text-xl font-semibold mb-4">{{ $activeCategory->name }}</h2>
        @endif

        @forelse ($posts as $post)
            @include('partials.post-card', ['post' => $post])
        @empty
            <p class="text-gray-600">No stories in this section yet.</p>
        @endforelse

        <div class="mt-6">{{ $posts->links() }}</div>
    </div>

    @auth
        @push('scripts')
            <script src="{{ static_asset('js/post.js') }}"></script>
        @endpush
    @endauth
</x-app-layout>
