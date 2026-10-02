<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Edit post</h1>
        <p class="text-sm text-gray-500 mb-6">by {{ $post->user->name }} · {{ $post->status->value }}</p>

        <form method="POST" action="{{ route('admin.posts.update', $post) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $post->title) }}" class="w-full rounded-md border-gray-300" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Category (optional)</label>
                <select name="category_id" class="w-full rounded-md border-gray-300">
                    <option value="">— None —</option>
                    @include('partials.category-options', [
                        'categories' => $categories,
                        'depth' => 0,
                        'selected' => old('category_id', $post->category_id),
                    ])
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300">
                    @foreach (['draft', 'published', 'unlisted'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $post->status->value) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
                <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
