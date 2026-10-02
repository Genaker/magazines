<x-app-layout>
    <x-profile-layout>
        <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Author aliases</h1>
            <p class="text-sm text-gray-600 mt-1">Multiple public identities under one login. Posts stay on their URLs when an alias is removed.</p>
        </div>

        @if (session('status') === 'alias-created')
            <p class="mb-4 text-sm text-green-700">Alias created and set as active for writing.</p>
        @endif
        @if (session('status') === 'alias-deleted')
            <p class="mb-4 text-sm text-green-700">Alias removed. Its stories remain available at their direct links.</p>
        @endif
        @if (session('status') === 'alias-switched')
            <p class="mb-4 text-sm text-green-700">Active alias updated.</p>
        @endif
        @error('alias')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror

        <div class="space-y-4 mb-10">
            @foreach ($aliases as $alias)
                <div class="rounded-lg border border-gray-200 p-4 flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold">{{ $alias->name }}</p>
                            <span class="text-sm text-gray-500">{{ '@'.$alias->username }}</span>
                            @if ($alias->is_primary)
                                <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">Primary</span>
                            @endif
                            @if ($activeAlias->is($alias))
                                <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded-full bg-green-100 text-green-800">Active</span>
                            @endif
                        </div>
                        @if ($alias->bio)
                            <p class="mt-1 text-sm text-gray-600">{{ $alias->bio }}</p>
                        @endif
                        <a href="{{ route('authors.show', $alias) }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">View public page</a>
                    </div>
                    <div class="flex flex-col gap-2 shrink-0">
                        @if (! $activeAlias->is($alias))
                            <form method="POST" action="{{ route('profile.aliases.switch', $alias) }}">
                                @csrf
                                <button type="submit" class="text-sm px-3 py-1.5 border rounded-md">Use for writing</button>
                            </form>
                        @endif
                        @if ($aliases->count() > 1)
                            <form method="POST" action="{{ route('profile.aliases.destroy', $alias) }}" onsubmit="return confirm('Delete this alias? Its profile will be empty but posts stay published.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm px-3 py-1.5 border border-red-200 text-red-700 rounded-md">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <h2 class="text-lg font-semibold mb-4">Create alias</h2>
        <form method="POST" action="{{ route('profile.aliases.store') }}" class="rounded-lg border border-gray-200 p-4 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Display name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300" required>
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full rounded-md border-gray-300" required pattern="[A-Za-z0-9_-]+">
                @error('username')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Bio</label>
                <textarea name="bio" rows="3" class="w-full rounded-md border-gray-300">{{ old('bio') }}</textarea>
                @error('bio')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Create alias</button>
        </form>
        </div>
    </x-profile-layout>
</x-app-layout>
