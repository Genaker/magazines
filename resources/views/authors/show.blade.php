<x-app-layout :seo="$seo ?? null">
    @php
        $activeTab = request('tab') === 'about' ? 'about' : 'home';
        $onAuthorSubdomain = \App\Support\AuthorSubdomain::isSubdomainRequest(request());
        $authorHomeUrl = $onAuthorSubdomain ? url('/') : route('authors.show', $author);
        $authorAboutUrl = $onAuthorSubdomain ? url('/?tab=about') : route('authors.show', $author).'?tab=about';
    @endphp

    <div class="max-w-6xl mx-auto px-4 py-8">
        @if ($isRetired ?? false)
            <div class="mb-8 text-center py-12">
                <p class="text-gray-400 text-sm uppercase tracking-wide">{{ '@'.$author->username }}</p>
                <p class="mt-4 text-gray-500">This author profile is no longer available.</p>
            </div>
        @else
            @php
                $profileSidebarData = [
                    'author' => $author,
                    'account' => $account,
                    'articlesCount' => $articlesCount,
                    'followersCount' => $followersCount,
                    'followingCount' => $followingCount,
                    'isFollowing' => $isFollowing,
                    'isSubscribed' => $isSubscribed ?? false,
                    'subscriptionDelivery' => $subscriptionDelivery ?? null,
                    'isBlocked' => $isBlocked ?? false,
                ];
            @endphp

            <div class="mb-8">
                @include('authors.partials.profile-sidebar', $profileSidebarData)
            </div>

            <div class="min-w-0 max-w-3xl">
                    <header class="mb-6">
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 truncate">{{ $author->name }}</h1>
                    </header>

                    <nav class="border-b border-gray-200 mb-8 flex gap-8" aria-label="Author profile">
                        <a
                            href="{{ $authorHomeUrl }}"
                            @class([
                                'pb-3 text-sm font-medium border-b-2 -mb-px',
                                'border-gray-900 text-gray-900' => $activeTab === 'home',
                                'border-transparent text-gray-500 hover:text-gray-800' => $activeTab !== 'home',
                            ])
                        >
                            {{ __('app.home') }}
                        </a>
                        <a
                            href="{{ $authorAboutUrl }}"
                            @class([
                                'pb-3 text-sm font-medium border-b-2 -mb-px',
                                'border-gray-900 text-gray-900' => $activeTab === 'about',
                                'border-transparent text-gray-500 hover:text-gray-800' => $activeTab !== 'about',
                            ])
                        >
                            {{ __('app.about') }}
                        </a>
                    </nav>

                    @if (session('status') === 'report-submitted')
                        <p class="mb-4 text-sm text-green-700">Report submitted. Admins will review it.</p>
                    @endif
                    @if (session('status') === 'user-blocked')
                        <p class="mb-4 text-sm text-green-700">User blocked. They can no longer view your posts or comment.</p>
                    @endif
                    @if (session('status') === 'user-unblocked')
                        <p class="mb-4 text-sm text-green-700">User unblocked.</p>
                    @endif
                    @if ($viewerIsBlocked ?? false)
                        <p class="mb-4 text-sm text-gray-600">This author's posts are not available to you.</p>
                    @endif

                    @if ($activeTab === 'about')
                        <div class="max-w-2xl space-y-6 text-gray-700">
                            @if ($author->bio)
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.bio') }}</h2>
                                    <p class="whitespace-pre-line">{{ $author->bio }}</p>
                                </div>
                            @endif

                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.stats') }}</h2>
                                <ul class="text-sm space-y-1">
                                    <li>{{ number_format($articlesCount) }} {{ Str::plural('published story', $articlesCount) }}</li>
                                    <li>{{ number_format($followersCount) }} {{ Str::plural('follower', $followersCount) }}</li>
                                    <li>{{ number_format($followingCount) }} following</li>
                                </ul>
                            </div>

                            @include('authors.partials.social-links', [
                                'author' => $author,
                                'account' => $account,
                            ])

                            @include('partials.custom-fields-display', [
                                'values' => $account->custom_fields,
                                'context' => \App\Support\CustomFieldSchema::CONTEXT_USER,
                            ])
                        </div>
                    @else
                        @forelse ($posts as $post)
                            @include('partials.post-card', [
                                'post' => $post,
                                'profileFeed' => true,
                            ])
                        @empty
                            <p class="text-gray-600 py-8">{{ __('app.no_stories_yet') }}</p>
                        @endforelse

                        <div class="mt-6">{{ $posts->appends(['tab' => $activeTab === 'about' ? 'about' : null])->links() }}</div>
                    @endif
            </div>

            @feature('user_reports')
            @auth
                @if (auth()->id() !== $account->id)
                    <div id="report-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-lg max-w-md w-full p-6">
                            <h2 class="text-lg font-semibold mb-2">Report {{ $author->name }}</h2>
                            <form method="POST" action="{{ route('users.report', $account) }}">
                                @csrf
                                <textarea name="reason" rows="4" class="w-full rounded-md border-gray-300" placeholder="Describe the issue (min 10 characters)" required minlength="10"></textarea>
                                <div class="mt-4 flex gap-2 justify-end">
                                    <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')" class="px-4 py-2 border rounded-md text-sm">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm">Submit report</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endauth
            @endfeature
        @endif
    </div>

    @if (! ($isRetired ?? false))
        @push('scripts')
            <script src="{{ static_asset('js/post.js') }}"></script>
        @endpush
    @endif
</x-app-layout>
