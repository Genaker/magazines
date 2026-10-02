@if (($aliases ?? collect())->count() > 1)
    <div class="mb-6">
        <label class="block text-sm font-medium mb-1">Publish as</label>
        <select name="author_alias_id" class="w-full rounded-md border border-gray-300 px-3 py-2">
            @foreach ($aliases as $alias)
                <option
                    value="{{ $alias->id }}"
                    @selected(old('author_alias_id', $selectedAliasId ?? $activeAlias->id ?? $post->author_alias_id ?? null) == $alias->id)
                >
                    {{ $alias->name }} ({{ '@'.$alias->username }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            <a href="{{ route('profile.aliases') }}" class="underline">Manage aliases</a>
        </p>
    </div>
@endif
