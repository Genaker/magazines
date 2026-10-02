<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ $user->hasPassword() ? __('Update Password') : __('Set Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            @if ($user->hasPassword())
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            @else
                {{ __('app.set_password_optional_hint') }}
            @endif
        </p>
    </header>

    <form method="post" action="{{ \App\Support\SiteUrl::mainRoute('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        @if ($user->hasPassword())
            <div>
                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="update_password_password" :value="$user->hasPassword() ? __('New Password') : __('Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
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
</section>
