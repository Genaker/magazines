<x-admin-layout>
    @if (session('status'))
        <div class="max-w-6xl mx-auto px-4 pt-8">
            <p class="mb-4 text-sm text-green-700">
                @switch(session('status'))
                    @case('magazine-updated') {{ __('app.admin_magazine_updated') }} @break
                    @case('magazine-deleted') {{ __('app.admin_magazine_deleted') }} @break
                @endswitch
            </p>
        </div>
    @endif

    <x-admin.data-grid
        :title="__('app.admin_magazines')"
        :paginator="$magazines"
        :search="$search"
        search-placeholder="Search name or slug…"
        :sort="$sort"
        :dir="$dir"
    >
        <x-slot:actions>
            <a href="{{ route('admin.magazines.index') }}" class="text-sm text-indigo-600 hover:underline">
                {{ __('app.admin_magazine_nav_link') }}
            </a>
        </x-slot:actions>

        <x-slot:head>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="name" label="Name" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="slug" label="Slug" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left">{{ __('app.owner') }}</th>
            <th class="px-4 py-3 text-left">{{ __('app.stories') }}</th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="created_at" label="Created" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($magazines as $magazine)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $magazine->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $magazine->slug }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $magazine->owner->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ number_format($magazine->posts_count) }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $magazine->created_at->format('M j, Y') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.magazines.posts', $magazine) }}" class="text-indigo-600 hover:underline mr-3">{{ __('app.admin_magazine_view_posts') }}</a>
                    <a href="{{ route('admin.magazines.edit', $magazine) }}" class="text-indigo-600 hover:underline mr-3">Edit</a>
                    <form method="POST" action="{{ route('admin.magazines.destroy', $magazine) }}" class="inline" onsubmit="return confirm(@js(__('app.admin_magazine_delete_confirm')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>
</x-admin-layout>
