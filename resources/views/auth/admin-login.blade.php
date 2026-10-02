<x-guest-layout>
    @include('partials.admin-path-warning')
    @include('auth.partials.local-mail-notice')

    <x-auth-session-status class="mb-4" :status="match (session('status')) {
        'magic-link-sent' => __('app.magic_link_sent_status'),
        'magic-link-resent' => __('app.magic_link_resent_status'),
        default => session('status'),
    }" />

    @if (session('dev_login_code'))
        <div class="mb-4 rounded-md border-2 border-green-500 bg-green-50 p-4 text-sm text-green-950">
            <p class="font-semibold text-base">Your sign-in code (also emailed)</p>
            <p class="mt-2 font-mono text-3xl tracking-widest">{{ session('dev_login_code') }}</p>
            @if (config('app.debug'))
                <p class="mt-3">
                    <a href="{{ session('dev_mailpit_url', config('mail.mailpit_web_url')) }}" class="underline font-medium" target="_blank">
                        Open Mailpit inbox →
                    </a>
                </p>
            @endif
        </div>
    @endif

    @if (! session('magic_login_email'))
        <p class="mb-4 text-sm text-gray-600">{{ __('app.admin_magic_login_intro') }}</p>

        <form method="POST" action="{{ route('admin.login.magic.send') }}">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.login.password') }}">
                    {{ __('app.use_password_instead') }}
                </a>
                <x-primary-button>{{ __('app.email_me_link') }}</x-primary-button>
            </div>
        </form>
    @else
        <p class="mb-4 text-sm text-gray-600">{{ __('app.magic_code_intro') }}</p>

        <x-input-error :messages="$errors->get('email')" class="mb-4" />

        <form method="POST" action="{{ route('admin.login.magic.verify.code') }}">
            @csrf
            <input type="hidden" name="email" value="{{ session('magic_login_email') }}">

            <div>
                <x-input-label for="code" :value="__('app.sign_in_code')" />
                <x-text-input
                    id="code"
                    class="block mt-1 w-full tracking-[0.3em] font-mono text-lg text-center"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    :value="old('code', session('dev_login_code'))"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="000000"
                />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.login') }}?change_email=1">
                    {{ __('app.use_different_email') }}
                </a>
                <x-primary-button>{{ __('app.sign_in') }}</x-primary-button>
            </div>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">{{ __('app.magic_code_resend_hint') }}</p>
        <form method="POST" action="{{ route('admin.login.magic.send') }}" class="mt-2 text-center">
            @csrf
            <input type="hidden" name="email" value="{{ session('magic_login_email') }}">
            <button type="submit" class="underline text-sm font-medium text-gray-700 hover:text-gray-900">
                {{ __('app.resend_sign_in_email') }}
            </button>
        </form>

        <p class="mt-6 text-center">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.login.password') }}">
                {{ __('app.use_password_instead') }}
            </a>
        </p>
    @endif

    @include('partials.locale-switcher')
</x-guest-layout>
