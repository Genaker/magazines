<x-guest-layout>
    @include('auth.partials.local-mail-notice')

    @include('auth.partials.dev-verification-banner', ['devVerificationUrl' => $devVerificationUrl ?? null])

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4 text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('verification_mail_failed') && ! ($devVerificationUrl ?? session('dev_verification_url')))
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ __('app.verification_mail_failed') }}
        </div>
    @endif

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if ($unknownEmail ?? false)
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ __('app.verification_email_not_found', ['email' => $unknownEmail]) }}
        </div>
    @endif

    @if ($pendingEmail ?? false)
        <p class="mb-4 text-sm text-gray-700">
            {{ __('app.verification_sent_to') }} <strong>{{ $pendingEmail }}</strong>
        </p>
        <p class="mb-4 text-sm text-gray-600">
            {{ __('app.sign_in_after_verification') }}
        </p>
    @endif

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
            @csrf

            <div>
                <x-input-label for="verification-email" :value="__('Email')" />
                <x-text-input
                    id="verification-email"
                    class="block mt-1 w-full"
                    type="email"
                    name="email"
                    :value="old('email', $pendingEmail ?? ($unknownEmail ?? ''))"
                    required
                    autocomplete="username"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
            {{ __('app.back_to_login') }}
        </a>
    </div>
</x-guest-layout>
