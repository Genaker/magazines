@if (\App\Support\SiteLocale::isSwitchable())
    <div class="flex items-center gap-1 text-sm">
        <span class="sr-only">{{ __('app.language') }}</span>
        @foreach (\App\Support\SiteLocale::enabled() as $code)
            <a
                href="{{ route('locale.switch', $code) }}"
                @class([
                    'px-2 py-1 rounded',
                    'font-semibold text-gray-900 bg-gray-100' => app()->getLocale() === $code,
                    'text-gray-500 hover:text-gray-900' => app()->getLocale() !== $code,
                ])
            >
                {{ strtoupper($code) }}
            </a>
        @endforeach
    </div>
@endif
