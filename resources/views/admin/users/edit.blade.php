<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Edit user</h1>
        <p class="text-sm text-gray-600 mb-4">{{ $user->email }}</p>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mb-6">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm mb-1">Role</label>
                <select name="role" class="w-full rounded-md border-gray-300">
                    <option value="user" @selected($user->role->value === 'user')>User</option>
                    <option value="admin" @selected($user->role->value === 'admin')>Admin</option>
                    <option value="super_admin" @selected($user->role->value === 'super_admin')>Super Admin</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_banned" value="0">
                    <input type="checkbox" name="is_banned" value="1" @checked($user->is_banned)>
                    Banned
                </label>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
        </form>

        @if ($user->authorAliases->isNotEmpty())
            <section class="mb-6 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold mb-2">Author aliases</h2>
                <p class="text-sm text-gray-600 mb-4">Public identities used for posts and author pages.</p>

                <div class="space-y-3">
                    @foreach ($user->authorAliases as $alias)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="font-medium text-gray-900">{{ $alias->name }}</p>
                            <p class="text-sm text-gray-500">{{ '@'.$alias->username }}</p>
                            @if ($alias->is_primary)
                                <span class="mt-1 inline-block text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">Primary</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($user->id !== auth()->id() && auth()->user()->isSuperAdmin())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Move this user to trash?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-red-700 border border-red-300 rounded-md">Delete user</button>
            </form>
        @endif
    </div>
</x-admin-layout>
