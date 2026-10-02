@foreach ($categories as $category)
    <div class="flex justify-between border-b py-3" style="padding-left: {{ $depth * 1.25 }}rem">
        <span>{{ $category->name }}</span>
        <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm underline">Edit</a>
    </div>
    @if ($category->children->isNotEmpty())
        @include('partials.category-tree-admin', ['categories' => $category->children, 'depth' => $depth + 1])
    @endif
@endforeach
