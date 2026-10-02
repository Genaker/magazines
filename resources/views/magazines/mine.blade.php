<x-app-layout>
    <x-profile-layout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">{{ __('app.my_magazines') }}</h1>
            <a href="{{ route('magazines.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">{{ __('app.create_magazine') }}</a>
        </div>

        @forelse ($magazines as $magazine)
            @php
                $role = $magazine->memberRole(auth()->user()) ?? 'writer';
            @endphp
            <div class="border-b border-gray-200 py-4 flex items-start justify-between gap-4">
                <div>
                    <a href="{{ \App\Support\MagazineSubdomain::canonicalMagazineUrl($magazine) }}" class="text-lg font-semibold hover:underline">{{ $magazine->name }}</a>
                    @if ($magazine->description)
                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $magazine->description }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mt-1">
                        <span class="capitalize">{{ $role }}</span>
                        · {{ trans_choice('app.published_story', (int) $magazine->posts_count, ['count' => number_format($magazine->posts_count)]) }}
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @can('reviewSubmissions', $magazine)
                        <a href="{{ route('magazines.submissions', $magazine) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.manage_magazine') }}</a>
                    @endcan
                </div>
            </div>
        @empty
            <p class="text-gray-600 mb-4">{{ __('app.no_magazines_in_account') }}</p>
            <a href="{{ route('magazines.create') }}" class="inline-block px-4 py-2 bg-gray-900 text-white rounded-md text-sm">{{ __('app.create_magazine') }}</a>
        @endforelse
    </x-profile-layout>
</x-app-layout>
