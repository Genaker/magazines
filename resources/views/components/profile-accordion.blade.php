@props([
    'title',
    'description' => null,
    'open' => false,
])

<div {{ $attributes->merge(['class' => 'border-t border-gray-200']) }} x-data="{ open: @js($open) }">
    <button
        type="button"
        class="flex w-full items-start justify-between gap-4 py-4 text-left"
        @click="open = ! open"
        :aria-expanded="open"
    >
        <div class="min-w-0">
            <h3 class="text-sm font-medium text-gray-900">{{ $title }}</h3>
            @if ($description)
                <p class="mt-1 text-xs text-gray-500">{{ $description }}</p>
            @endif
        </div>
        <svg
            class="mt-0.5 h-5 w-5 shrink-0 text-gray-500 transition-transform duration-200"
            :class="{ 'rotate-180': open }"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.585l3.71-3.354a.75.75 0 111.02 1.1l-4.25 3.85a.75.75 0 01-1.02 0l-4.25-3.85a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="pb-6"
    >
        {{ $slot }}
    </div>
</div>
