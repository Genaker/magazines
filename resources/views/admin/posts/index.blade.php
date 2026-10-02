<x-admin-layout>
    <x-admin.data-grid
        title="Posts"
        :paginator="$posts"
        :search="$search"
        search-placeholder="Search title or author…"
        :sort="$sort"
        :dir="$dir"
    >
        <x-slot:filters>
            <label class="text-sm text-gray-700">
                Status
                <select name="status" class="ml-2 rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach (['draft', 'published', 'unlisted'] as $option)
                        <option value="{{ $option }}" @selected(($status ?? '') === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </label>
        </x-slot:filters>

        <x-slot:head>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="title" label="Title" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left">Author</th>
            <th class="px-4 py-3 text-left">Category</th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="status" label="Status" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="views_count" label="Views" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="created_at" label="Created" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($posts as $post)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $post->title }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $post->user->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $post->category?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs uppercase px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ $post->status->value }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ number_format($post->views_count) }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $post->created_at->format('M j, Y') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.posts.edit', $post) }}" class="text-indigo-600 hover:underline mr-3">Edit</a>
                    <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="inline" onsubmit="return confirm('Move this post to trash?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>
</x-admin-layout>
