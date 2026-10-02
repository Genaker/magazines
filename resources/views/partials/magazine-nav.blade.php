<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        class="flex items-center gap-1 text-gray-600 hover:text-gray-900"
    >
        {{ __('app.magazines') }}
        <svg
            class="h-4 w-4 transition-transform"
            :class="open ? 'rotate-180' : ''"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute left-0 top-full z-30 mt-1 max-h-[70vh] min-w-[14rem] overflow-y-auto rounded-md border border-gray-200 bg-white py-2 shadow-lg"
    >
        @forelse ($magazines as $magazine)
            <a
                href="{{ \App\Support\MagazineSubdomain::canonicalMagazineUrl($magazine) }}"
                @click="open = false"
                class="block px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
            >
                {{ $magazine->name }}
            </a>
        @empty
            <p class="px-4 py-2 text-sm text-gray-500">{{ __('app.no_magazines_yet') }}</p>
        @endforelse

        @if (($moreCount ?? 0) > 0)
            <p class="flex items-center gap-1.5 px-4 py-2 text-xs text-gray-500">
                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M3 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM8.5 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM14 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" />
                </svg>
                {{ trans_choice('app.magazines_nav_more_available', $moreCount, ['count' => $moreCount]) }}
            </p>
        @endif

        <div class="mt-1 border-t border-gray-100 pt-1">
            <a
                href="{{ \App\Support\SiteUrl::navRoute('magazines.index') }}"
                @click="open = false"
                class="block px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
            >
                {{ __('app.see_all_magazines') }}
            </a>
        </div>
    </div>
</div>
