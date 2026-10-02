<select name="category_id" id="post-category-select" class="w-full rounded-md border border-gray-300 px-3 py-2">
    <option value="">— None —</option>
    @include('partials.category-options', [
        'categories' => $siteCategories,
        'depth' => 0,
        'selected' => $selectedCategoryId ?? null,
        'magazineId' => '',
    ])
    @foreach ($magazineCategoryTrees as $magazineId => $categories)
        @include('partials.category-options', [
            'categories' => $categories,
            'depth' => 0,
            'selected' => $selectedCategoryId ?? null,
            'magazineId' => $magazineId,
        ])
    @endforeach
</select>
