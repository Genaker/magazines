@php($devVerifyUrl = $devVerificationUrl ?? session('dev_verification_url'))

@if ($devVerifyUrl)
    <div class="mt-3 rounded-md border-2 border-green-500 bg-green-50 p-4 text-sm text-green-950">
        <p class="font-semibold">{{ __('app.dev_verify_email_title') }}</p>
        <p class="mt-2">{{ __('app.dev_verify_email_help') }}</p>
        <p class="mt-2">
            <a href="{{ $devVerifyUrl }}" class="break-all font-medium underline text-green-900">
                {{ __('app.dev_verify_email_link') }}
            </a>
        </p>
        @if (config('mail.mailpit_web_url'))
            <p class="mt-3 text-xs text-green-800">
                {{ __('app.dev_verify_email_mailpit') }}
                <a href="{{ config('mail.mailpit_web_url') }}" class="underline" target="_blank" rel="noopener noreferrer">Mailpit</a>
            </p>
        @endif
    </div>
@endif

@if (session('verification_mail_failed'))
    <p class="mt-2 text-sm text-red-600">
        {{ __('app.verification_mail_failed') }}
    </p>
@endif
