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
        {{ __('app.categories') }}
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
        @foreach ($categories as $category)
            <div @class(['border-b border-gray-100 last:border-0' => $category->children->isNotEmpty()])>
                <a
                    href="{{ \App\Support\SiteUrl::navRoute('categories.show', $category) }}"
                    @click="open = false"
                    class="block px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
                >
                    {{ $category->name }}
                </a>
                @foreach ($category->children as $child)
                    <a
                        href="{{ \App\Support\SiteUrl::navRoute('categories.show', $child) }}"
                        @click="open = false"
                        class="block py-1.5 pl-7 pr-4 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    >
                        {{ $child->name }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
