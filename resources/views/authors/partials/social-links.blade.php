@php
    $legacyLinks = \App\Support\AuthorSocialLinks::legacyLinks($author->website, $author->twitter_handle);
    $socialLinks = \App\Support\AuthorSocialLinks::forAuthor($author, $account);
    $allLinks = array_merge($legacyLinks, $socialLinks);
    $grouped = collect($allLinks)->groupBy('group');
@endphp

@if ($allLinks !== [])
    <div @class(['space-y-4', 'mt-3' => ! ($showHeading ?? true)])>
        @if ($grouped->has(\App\Support\AuthorSocialLinks::GROUP_SOCIAL))
            <div>
        @if ($showHeading ?? true)
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.social_links') }}</h2>
                @else
                    <p class="sr-only">{{ __('app.social_links') }}</p>
                @endif
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach ($grouped[\App\Support\AuthorSocialLinks::GROUP_SOCIAL] as $link)
                        <a href="{{ $link['href'] }}" class="text-indigo-600 hover:underline" target="_blank" rel="noopener noreferrer">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($grouped->has(\App\Support\AuthorSocialLinks::GROUP_CROWDFUNDING))
            <div>
                @if ($showHeading ?? true)
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.crowdfunding_links') }}</h2>
                @endif
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach ($grouped[\App\Support\AuthorSocialLinks::GROUP_CROWDFUNDING] as $link)
                        <a href="{{ $link['href'] }}" class="text-indigo-600 hover:underline" target="_blank" rel="noopener noreferrer">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
