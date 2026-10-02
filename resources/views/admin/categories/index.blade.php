<x-admin-layout>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex justify-between mb-6">
            <h1 class="text-3xl font-bold">Categories</h1>
            <a href="{{ route('admin.categories.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Create</a>
        </div>

        <h2 class="text-lg font-semibold mb-3">Site sections</h2>
        @include('partials.category-tree-admin', ['categories' => $siteCategories, 'depth' => 0])

        @foreach ($magazineCategoryGroups as $group)
            <h2 class="text-lg font-semibold mt-8 mb-3">{{ $group['magazine']->name }} categories</h2>
            @include('partials.category-tree-admin', ['categories' => $group['categories'], 'depth' => 0])
        @endforeach
    </div>
</x-admin-layout>
