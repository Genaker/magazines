@php
    $platforms = \App\Support\AuthorSocialLinks::platforms();
    $values = old('social_links', $user->social_links ?? []);
    $socialPlatforms = collect($platforms)->where('group', \App\Support\AuthorSocialLinks::GROUP_SOCIAL);
    $crowdfundingPlatforms = collect($platforms)->where('group', \App\Support\AuthorSocialLinks::GROUP_CROWDFUNDING);

    $socialLinkErrors = collect($errors->getMessages())->keys()->contains(
        fn (string $key) => str_starts_with($key, 'social_links.'),
    );

    $hasSocialValues = $socialPlatforms->contains(
        fn (array $platform, string $key) => filled($values[$key] ?? ''),
    );

    $hasCrowdfundingValues = $crowdfundingPlatforms->contains(
        fn (array $platform, string $key) => filled($values[$key] ?? ''),
    );
@endphp

<x-profile-accordion
    :title="__('app.social_links')"
    :description="__('app.social_links_help')"
    :open="$socialLinkErrors || $hasSocialValues"
>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($socialPlatforms as $key => $platform)
            <div>
                <x-input-label :for="'social_links_'.$key" :value="$platform['label']" />
                <x-text-input
                    id="social_links_{{ $key }}"
                    name="social_links[{{ $key }}]"
                    type="text"
                    class="mt-1 block w-full"
                    :value="$values[$key] ?? ''"
                    :placeholder="$platform['type'] === 'url' ? 'https://…' : __('app.username_or_url')"
                />
                <x-input-error class="mt-2" :messages="$errors->get('social_links.'.$key)" />
            </div>
        @endforeach
    </div>
</x-profile-accordion>

<x-profile-accordion
    :title="__('app.crowdfunding_links')"
    :description="__('app.crowdfunding_links_help')"
    :open="$socialLinkErrors || $hasCrowdfundingValues"
>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($crowdfundingPlatforms as $key => $platform)
            <div>
                <x-input-label :for="'social_links_'.$key" :value="$platform['label']" />
                <x-text-input
                    id="social_links_{{ $key }}"
                    name="social_links[{{ $key }}]"
                    type="text"
                    class="mt-1 block w-full"
                    :value="$values[$key] ?? ''"
                    placeholder="{{ __('app.username_or_url') }}"
                />
                <x-input-error class="mt-2" :messages="$errors->get('social_links.'.$key)" />
            </div>
        @endforeach
    </div>
</x-profile-accordion>
