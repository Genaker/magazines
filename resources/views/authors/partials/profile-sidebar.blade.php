@php
    $isOwnProfile = auth()->check() && auth()->id() === $account->id;
@endphp

<aside class="space-y-4">
    <div class="flex flex-col items-center text-center lg:items-start lg:text-left">
        <div class="flex flex-col items-center lg:items-start mb-4">
            <x-user-avatar :author="$author" size="xl" :alt="$author->name" />
            @if ($isOwnProfile)
                @php
                    $profileUser = auth()->user();
                @endphp
                <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 lg:justify-start">
                    <form method="post"
                          action="{{ \App\Support\SiteUrl::mainRoute('profile.update') }}"
                          enctype="multipart/form-data"
                          class="inline">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="name" value="{{ $profileUser->name }}">
                        <input type="hidden" name="username" value="{{ $profileUser->username }}">
                        <input type="hidden" name="email" value="{{ $profileUser->email }}">
                        <input type="hidden" name="bio" value="{{ $profileUser->bio ?? '' }}">
                        <input type="hidden" name="website" value="{{ $profileUser->website ?? '' }}">
                        <input type="hidden" name="twitter_handle" value="{{ $profileUser->twitter_handle ?? '' }}">
                        @foreach ($profileUser->social_links ?? [] as $key => $value)
                            <input type="hidden" name="social_links[{{ $key }}]" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="allow_comments" value="{{ $profileUser->allow_comments ? '1' : '0' }}">
                        @foreach ($profileUser->custom_fields ?? [] as $key => $value)
                            <input type="hidden" name="custom_fields[{{ $key }}]" value="{{ is_bool($value) ? ($value ? '1' : '0') : $value }}">
                        @endforeach
                        <label for="profile-sidebar-avatar" class="text-xs text-indigo-600 hover:underline cursor-pointer">
                            {{ __('Upload avatar image') }}
                            <input id="profile-sidebar-avatar"
                                   type="file"
                                   name="avatar"
                                   accept="image/*"
                                   class="sr-only"
                                   onchange="this.form.submit()">
                        </label>
                    </form>
                    @if ($profileUser->avatar)
                        <form method="post"
                              action="{{ \App\Support\SiteUrl::mainRoute('profile.avatar.destroy') }}"
                              class="inline">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-xs text-red-600 hover:underline">
                                {{ __('app.remove_avatar') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <h2 class="text-xl font-bold text-gray-900">{{ $author->name }}</h2>
        <p class="text-sm text-gray-500">{{ '@'.$author->username }}</p>

        <p class="mt-2 text-sm text-gray-600">
            {{ number_format($followersCount) }} {{ Str::plural('follower', $followersCount) }}
            · {{ number_format($articlesCount) }} {{ Str::plural('story', $articlesCount) }}
        </p>

        @if ($author->bio)
            <p class="mt-3 text-sm text-gray-700 line-clamp-4">{{ $author->bio }}</p>
        @endif

        @include('authors.partials.social-links', [
            'author' => $author,
            'account' => $account,
            'showHeading' => false,
        ])
    </div>

    <div class="w-full">
        @if ($isOwnProfile)
            @php
                $profileActionUrl = fn (string $name) => \App\Support\SiteUrl::mainRoute($name);
                $profileBtn = 'inline-flex shrink-0 items-center px-4 py-2 rounded-full border border-gray-300 text-sm font-medium text-gray-900 hover:bg-gray-50 whitespace-nowrap';
            @endphp
            <div class="flex flex-wrap items-center gap-2 justify-center lg:justify-start">
                <a href="{{ $profileActionUrl('profile.edit') }}" class="{{ $profileBtn }}">
                    {{ __('app.edit_profile') }}
                </a>
                <a href="{{ $profileActionUrl('posts.mine') }}" class="{{ $profileBtn }}">
                    {{ __('app.my_stories') }}
                </a>
                <a href="{{ $profileActionUrl('stats.index') }}" class="{{ $profileBtn }}">
                    {{ __('app.stats') }}
                </a>
                @feature('magazines')
                    <a href="{{ $profileActionUrl('magazines.mine') }}" class="{{ $profileBtn }}">
                        {{ __('app.my_magazines') }}
                    </a>
                @endfeature
                <button id="follow-btn"
                        data-user-id="{{ $account->id }}"
                        data-following="{{ $isFollowing ? '1' : '0' }}"
                        data-label-follow="{{ __('app.follow') }}"
                        data-label-following="{{ __('app.following') }}"
                        @class([
                            'inline-flex shrink-0 items-center px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap cursor-pointer',
                            'bg-gray-900 text-white hover:bg-gray-800' => $isFollowing,
                            'border border-gray-900 text-gray-900 hover:bg-gray-50' => ! $isFollowing,
                        ])>
                    {{ $isFollowing ? __('app.following') : __('app.follow') }}
                </button>
            </div>
        @elseif (auth()->check())
            @php
                $actionBtn = 'inline-flex shrink-0 items-center px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap';
            @endphp
            <div class="flex flex-nowrap items-center gap-2 overflow-x-auto justify-center lg:justify-start">
                <button id="follow-btn"
                        data-user-id="{{ $account->id }}"
                        data-following="{{ $isFollowing ? '1' : '0' }}"
                        data-label-follow="{{ __('app.follow') }}"
                        data-label-following="{{ __('app.following') }}"
                        @class([
                            $actionBtn.' transition cursor-pointer',
                            'bg-gray-900 text-white hover:bg-gray-800' => $isFollowing,
                            'border border-gray-900 text-gray-900 hover:bg-gray-50' => ! $isFollowing,
                        ])>
                    {{ $isFollowing ? __('app.following') : __('app.follow') }}
                </button>
                <button id="subscribe-btn"
                        data-user-id="{{ $account->id }}"
                        data-subscribed="{{ ($isSubscribed ?? false) ? '1' : '0' }}"
                        @class([
                            $actionBtn.' transition cursor-pointer',
                            'bg-indigo-600 text-white hover:bg-indigo-700' => ($isSubscribed ?? false),
                            'border border-gray-300 text-gray-900 hover:bg-gray-50' => ! ($isSubscribed ?? false),
                        ])
                        title="{{ __('app.subscribe_hint') }}">
                    {{ ($isSubscribed ?? false) ? __('app.subscribed') : __('app.subscribe') }}
                </button>
                <form method="POST" action="{{ route('users.block', $account) }}" class="inline-flex">
                    @csrf
                    <button type="submit" class="{{ $actionBtn }} border border-gray-300 text-gray-900 hover:bg-gray-50">
                        {{ $isBlocked ? __('app.unblock') : __('app.block') }}
                    </button>
                </form>
                @feature('user_reports')
                    <button type="button"
                            onclick="document.getElementById('report-modal').classList.remove('hidden')"
                            class="{{ $actionBtn }} border border-red-200 text-red-700 hover:bg-red-50">
                        {{ __('app.report') }}
                    </button>
                @endfeature
            </div>
            @if ($isSubscribed ?? false)
                <label class="mt-2 text-xs text-gray-500 flex items-center justify-center lg:justify-start gap-1">
                    {{ __('app.email') }}
                    <select id="subscribe-delivery" data-user-id="{{ $account->id }}" class="rounded border-gray-300 text-xs py-0.5">
                        @foreach (\App\Enums\AuthorSubscriptionDelivery::cases() as $option)
                            <option value="{{ $option->value }}" @selected(($subscriptionDelivery ?? 'instant') === $option->value)>
                                {{ $option->label() }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
        @endif
    </div>
</aside>
