<x-admin-layout>
    <x-admin.data-grid
        title="Users"
        :paginator="$users"
        :search="$search"
        search-placeholder="Search name, email, or username…"
        :sort="$sort"
        :dir="$dir"
    >
        <x-slot:head>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="name" label="Name" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="email" label="Email" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="role" label="Role" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="created_at" label="Joined" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">{{ $user->name }}</span>
                    <span class="text-gray-500">{{ '@'.$user->username }}</span>
                    @if ($user->is_banned)
                        <span class="ml-2 text-xs px-2 py-0.5 bg-red-100 text-red-800 rounded">banned</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->role->value }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $user->created_at->format('M j, Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline">Edit</a>
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>
</x-admin-layout>
