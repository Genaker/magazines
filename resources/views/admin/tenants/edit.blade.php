<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Edit tenant</h1>

        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-4 bg-white p-6 rounded-lg border border-gray-200">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $tenant->name) }}" required class="mt-1 w-full rounded border-gray-300">
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug', $tenant->slug) }}" required class="mt-1 w-full rounded border-gray-300">
                @error('slug')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="host" class="block text-sm font-medium text-gray-700">Primary domain</label>
                <input id="host" name="host" type="text" value="{{ old('host', $tenant->domains->firstWhere('is_primary', true)?->host) }}" required placeholder="lvh.me, tenant1.lvh.me, or lvh2.me" class="mt-1 w-full rounded border-gray-300">
                @error('host')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border-gray-300">
                    <option value="active" @selected(old('status', $tenant->status) === 'active')>Active</option>
                    <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspended</option>
                </select>
                @error('status')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Save</button>
                <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
