<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Edit category</h1>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="mb-8">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full rounded-md border-gray-300" required>
            </div>
            @include('partials.admin.category-scope-fields', [
                'magazines' => $magazines,
                'selectedMagazineId' => $selectedMagazineId,
                'selectedParentId' => $selectedParentId,
                'excludeCategoryId' => $excludeCategoryId,
            ])
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" class="w-full rounded-md border-gray-300">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="mb-6">
                <label for="category-image" class="block text-sm font-medium mb-1">Image</label>
                @if ($category->imageUrl())
                    <img src="{{ $category->imageUrl() }}" alt="" class="mb-2 h-16 w-16 rounded object-cover">
                @endif
                <p class="text-xs text-gray-500 mb-2">Optional cover image, JPG or PNG, max 5 MB.</p>
                <input type="file"
                       id="category-image"
                       name="image"
                       accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
            </div>
            @if ($category->magazine_id === null)
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Moderators</label>
                    <p class="text-sm text-gray-500 mb-2">One email per line. Moderators can hide posts from feeds and recategorize stories.</p>
                    <textarea name="moderator_emails" rows="4" class="w-full rounded-md border-gray-300 text-sm font-mono">{{ old('moderator_emails', $moderatorEmails) }}</textarea>
                </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
        </form>

        @if ($mergeTargets->isNotEmpty())
            <div class="border-t pt-6 mb-8">
                <h2 class="text-lg font-semibold mb-2">Merge category</h2>
                <p class="text-sm text-gray-600 mb-3">Move all posts and subcategories into another category, then delete this one. The old slug will redirect.</p>
                <form method="POST" action="{{ route('admin.categories.merge', $category) }}" onsubmit="return confirm('Merge this category into the selected target?')">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm mb-1">Merge into</label>
                        <select name="merge_into_id" class="w-full rounded-md border-gray-300" required>
                            @foreach ($mergeTargets as $target)
                                <option value="{{ $target->id }}">{{ $target->name }}</option>
                            @endforeach
                        </select>
                        @error('merge_into_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md">Merge category</button>
                </form>
            </div>
        @endif

        <div class="border-t pt-6">
            <h2 class="text-lg font-semibold mb-2 text-red-700">Delete category</h2>
            @if ($postCount > 0)
                <p class="text-sm text-gray-600 mb-3">This category has {{ $postCount }} {{ Str::plural('post', $postCount) }}. Posts will be moved to another category — they will not be deleted.</p>
                @if ($otherCategories->isEmpty())
                    <p class="text-sm text-amber-700">Create another category before deleting this one.</p>
                @else
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <div class="mb-3">
                            <label class="block text-sm mb-1">Move posts to</label>
                            <select name="fallback_category_id" class="w-full rounded-md border-gray-300" required>
                                @foreach ($otherCategories as $other)
                                    <option value="{{ $other->id }}">{{ $other->name }}</option>
                                @endforeach
                            </select>
                            @error('fallback_category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="px-4 py-2 text-red-700 border border-red-300 rounded-md">Delete category</button>
                    </form>
                @endif
            @else
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-red-700 border border-red-300 rounded-md">Delete category</button>
                </form>
            @endif
        </div>
    </div>
</x-admin-layout>
