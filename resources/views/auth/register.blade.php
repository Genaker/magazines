<x-guest-layout>
    @if ($requiresInvite ?? false)
        <p class="mb-4 text-sm text-gray-600">
            Registration is invite-only. Enter the code from your administrator, or use the invite link they sent you.
        </p>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        @if ($requiresInvite ?? false)
            <div>
                <x-input-label for="invite_code" :value="__('Invite code')" />
                <x-text-input
                    id="invite_code"
                    class="block mt-1 w-full uppercase tracking-widest"
                    type="text"
                    name="invite_code"
                    :value="old('invite_code', $inviteCode ?? '')"
                    required
                    autocomplete="off"
                />
                <x-input-error :messages="$errors->get('invite_code')" class="mt-2" />
            </div>
        @endif

        <!-- Name -->
        <div @class(['mt-4' => $requiresInvite ?? false])>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Username -->
        <div class="mt-4">
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        @if ($magicLinkRegistration ?? false)
            <div class="mt-4" x-data="{ magicOnly: @json((bool) old('magic_link_only')) }">
                <label class="inline-flex items-start gap-2">
                    <input
                        type="checkbox"
                        name="magic_link_only"
                        value="1"
                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        x-model="magicOnly"
                        @checked(old('magic_link_only'))
                    >
                    <span class="text-sm text-gray-700">{{ __('app.register_magic_link_only') }}</span>
                </label>
                <x-input-error :messages="$errors->get('magic_link_only')" class="mt-2" />

                <div class="mt-4 space-y-4" x-show="!magicOnly" x-cloak>
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1 w-full"
                                        type="password"
                                        name="password"
                                        x-bind:required="!magicOnly"
                                        autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                        type="password"
                                        name="password_confirmation"
                                        x-bind:required="!magicOnly"
                                        autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <p class="mt-3 text-sm text-gray-600" x-show="magicOnly" x-cloak>
                    {{ __('app.register_magic_link_only_hint') }}
                </p>
            </div>
        @else
            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        @endif

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
