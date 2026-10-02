@if ($magazines->isNotEmpty())
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Magazine (optional)</label>
        <select name="magazine_id" id="post-magazine-select" class="w-full rounded-md border border-gray-300 px-3 py-2">
            <option value="">— None —</option>
            @foreach ($magazines as $magazine)
                <option value="{{ $magazine->id }}" @selected((int) ($selectedMagazineId ?? 0) === $magazine->id)>{{ $magazine->name }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Category (optional)</label>
    @include('partials.post-category-select', [
        'siteCategories' => $siteCategories,
        'magazineCategoryTrees' => $magazineCategoryTrees,
        'selectedCategoryId' => $selectedCategoryId ?? null,
    ])
</div>
