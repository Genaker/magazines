@props([
    'title',
    'paginator',
    'search' => '',
    'searchPlaceholder' => 'Search…',
    'sort' => null,
    'dir' => null,
    'pageParam' => 'page',
])

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ $title }}</h1>
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-end gap-3">
                @foreach (request()->except(['q', $pageParam]) as $key => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="flex-1 min-w-[12rem]">
                    <label for="grid-search" class="sr-only">Search</label>
                    <input
                        id="grid-search"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    >
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800">
                    Search
                </button>

                @if ($search !== '')
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except(['q', $pageParam])) }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        Clear
                    </a>
                @endif

                @isset($filters)
                    <div class="w-full flex flex-wrap gap-3">{{ $filters }}</div>
                @endisset
            </form>

            <p class="mt-3 text-xs text-gray-500">
                {{ $paginator->total() }} {{ Str::plural('result', $paginator->total()) }}
                @if ($search !== '')
                    for “{{ $search }}”
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        @if ($paginator->isEmpty())
            <p class="p-8 text-center text-gray-500">No records found.</p>
        @endif

        @if ($paginator->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $paginator->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
