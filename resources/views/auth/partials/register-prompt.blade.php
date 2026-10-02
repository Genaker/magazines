@php
    use App\Support\RegistrationGate;
@endphp

@if (RegistrationGate::allowsOpenRegistration() && Route::has('register'))
    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
        <p class="text-sm text-gray-600">{{ __('app.no_account_yet') }}</p>
        <a
            href="{{ route('register') }}"
            class="mt-3 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
            {{ __('app.register_now') }}
        </a>
    </div>
@elseif (RegistrationGate::requiresInviteCode())
    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
        <p class="text-sm text-gray-600">Registration is invite-only. Use the link from your site administrator.</p>
    </div>
@endif
