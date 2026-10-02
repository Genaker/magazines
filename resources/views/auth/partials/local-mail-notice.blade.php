@if (app()->environment('local') && config('mail.mailpit_web_url'))
    <div class="mb-4 rounded-md border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
        <p class="font-semibold">{{ __('app.local_mail_inbox_title') }}</p>
        <p class="mt-1">{{ __('app.local_mail_inbox_help') }}</p>
        <p class="mt-2 text-xs text-sky-900">{{ __('app.local_mail_sync_hint') }}</p>
        <p class="mt-1 text-xs text-sky-900">{{ __('app.local_mail_lvh_hint') }}</p>
        <p class="mt-2">
            <a href="{{ config('mail.mailpit_web_url') }}" class="font-medium underline" target="_blank" rel="noopener noreferrer">
                {{ __('app.local_mail_inbox_open') }}
            </a>
        </p>
    </div>
@endif
