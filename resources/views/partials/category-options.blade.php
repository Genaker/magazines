@foreach ($categories as $category)
    <option
        value="{{ $category->id }}"
        data-magazine-id="{{ $magazineId ?? $category->magazine_id ?? '' }}"
        @selected((int) ($selected ?? null) === $category->id)
    >
        {{ str_repeat('— ', $depth) }}{{ $category->name }}
    </option>
    @if ($category->children->isNotEmpty())
        @include('partials.category-options', [
            'categories' => $category->children,
            'depth' => $depth + 1,
            'selected' => $selected ?? null,
            'magazineId' => $magazineId ?? null,
        ])
    @endif
@endforeach
