<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ \App\Support\SiteUrl::mainRoute('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea id="bio" name="bio" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('bio', $user->bio) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        <div>
            <x-input-label for="avatar" :value="__('Avatar')" />
            <div class="mt-2 flex flex-wrap items-center gap-4">
                <x-user-avatar :user="$user" size="lg" :alt="$user->name" />
            </div>
            <div class="mt-3" x-data="{ fileName: '' }">
                <label for="avatar" class="inline-flex cursor-pointer items-center rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50">
                    {{ __('Upload avatar image') }}
                </label>
                <input
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="sr-only"
                    @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''"
                >
                <p x-show="fileName" x-cloak class="mt-2 text-xs text-gray-600" x-text="fileName"></p>
                <p x-show="! fileName" class="mt-2 text-xs text-gray-500">{{ __('app.avatar_upload_save_hint') }}</p>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="website" :value="__('Website')" />
            <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $user->website)" placeholder="https://example.com" />
            <x-input-error class="mt-2" :messages="$errors->get('website')" />
        </div>

        <div>
            <x-input-label for="twitter_handle" :value="__('Twitter handle')" />
            <x-text-input id="twitter_handle" name="twitter_handle" type="text" class="mt-1 block w-full" :value="old('twitter_handle', $user->twitter_handle)" placeholder="username" />
            <x-input-error class="mt-2" :messages="$errors->get('twitter_handle')" />
        </div>

        @include('profile.partials.social-links-form', ['user' => $user])

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        @if (\App\Support\CustomFieldSchema::forContext(\App\Support\CustomFieldSchema::CONTEXT_USER) !== [])
            @php
                $customFieldDefinitions = \App\Support\CustomFieldSchema::forContext(\App\Support\CustomFieldSchema::CONTEXT_USER);
                $customFieldValues = old('custom_fields', $user->custom_fields ?? []);
                $customFieldErrors = collect($errors->getMessages())->keys()->contains(
                    fn (string $key) => str_starts_with($key, 'custom_fields.'),
                );
                $hasCustomFieldValues = collect($customFieldDefinitions)->contains(
                    fn (array $field) => filled($customFieldValues[$field['key']] ?? null),
                );
            @endphp
            <x-profile-accordion
                :title="__('app.profile_additional_information')"
                :description="__('app.profile_additional_information_help')"
                :open="$customFieldErrors || $hasCustomFieldValues"
            >
                @include('partials.custom-fields-input', [
                    'context' => \App\Support\CustomFieldSchema::CONTEXT_USER,
                    'value' => $user->custom_fields,
                    'heading' => null,
                ])
            </x-profile-accordion>
        @endif

        <x-profile-accordion
            :title="__('app.comments_and_privacy')"
            :description="__('app.comments_and_privacy_help')"
            :open="false"
        >
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="allow_comments" value="0">
                <input type="checkbox" name="allow_comments" value="1" class="rounded border-gray-300 text-indigo-600"
                    @checked(old('allow_comments', $user->allow_comments))>
                <span class="text-sm text-gray-700">{{ __('app.allow_comments_on_posts') }}</span>
            </label>
        </x-profile-accordion>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    @if ($user->avatar)
        @include('profile.partials.avatar-actions', ['user' => $user, 'class' => 'mt-2 text-sm'])
    @endif

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="mt-4 space-y-2">
            @include('auth.partials.local-mail-notice')

            <p class="text-sm text-gray-800">
                {{ __('Your email address is unverified.') }}
            </p>

            <form method="post" action="{{ \App\Support\SiteUrl::mainRoute('verification.send') }}" class="inline">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </form>

            @if (session('status') === 'verification-link-sent')
                <p class="font-medium text-sm text-green-600">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            @endif

            @include('auth.partials.dev-verification-banner')
        </div>
    @endif
</section>
