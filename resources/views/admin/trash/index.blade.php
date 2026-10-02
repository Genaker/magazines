<x-admin-layout>
    @if (session('status'))
        <div class="max-w-6xl mx-auto px-4 pt-8">
            <p class="mb-4 text-sm text-green-700">
                @switch(session('status'))
                    @case('user-restored') User restored. @break
                    @case('post-restored') Post restored. @break
                    @case('user-permanently-deleted') User permanently deleted. @break
                    @case('post-permanently-deleted') Post permanently deleted. @break
                    @case('magazine-restored') {{ __('app.magazine-restored') }} @break
                    @case('magazine-permanently-deleted') {{ __('app.magazine-permanently-deleted') }} @break
                @endswitch
            </p>
        </div>
    @endif

    <x-admin.data-grid
        title="Trash — deleted users"
        :paginator="$trashedUsers"
        :search="$search"
        search-placeholder="Search deleted users or posts…"
        page-param="users_page"
    >
        <x-slot:head>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Deleted</th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($trashedUsers as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ '@'.$user->username }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $user->deleted_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <form method="POST" action="{{ route('admin.trash.users.restore', $user->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-indigo-600 hover:underline mr-3">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('admin.trash.users.force-delete', $user->id) }}" class="inline" onsubmit="return confirm('Permanently delete this user?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete forever</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>

    <x-admin.data-grid
        title="Trash — deleted posts"
        :paginator="$trashedPosts"
        :search="$search"
        search-placeholder="Search deleted users or posts…"
        page-param="posts_page"
    >
        <x-slot:head>
            <th class="px-4 py-3 text-left">Title</th>
            <th class="px-4 py-3 text-left">Author</th>
            <th class="px-4 py-3 text-left">Deleted</th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($trashedPosts as $post)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $post->title }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $post->user->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $post->deleted_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <form method="POST" action="{{ route('admin.trash.posts.restore', $post->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-indigo-600 hover:underline mr-3">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('admin.trash.posts.force-delete', $post->id) }}" class="inline" onsubmit="return confirm('Permanently delete this post?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete forever</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>

    @feature('magazines')
        <x-admin.data-grid
            title="Trash — deleted magazines"
            :paginator="$trashedMagazines"
            :search="$search"
            search-placeholder="Search deleted users, posts, or magazines…"
            page-param="magazines_page"
        >
            <x-slot:head>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Slug</th>
                <th class="px-4 py-3 text-left">Owner</th>
                <th class="px-4 py-3 text-left">Deleted</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </x-slot:head>

            @foreach ($trashedMagazines as $magazine)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $magazine->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $magazine->slug }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $magazine->owner?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $magazine->deleted_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('admin.trash.magazines.restore', $magazine->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-indigo-600 hover:underline mr-3">Restore</button>
                        </form>
                        <form method="POST" action="{{ route('admin.trash.magazines.force-delete', $magazine->id) }}" class="inline" onsubmit="return confirm('Permanently delete this magazine?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete forever</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-grid>
    @endfeature
</x-admin-layout>
