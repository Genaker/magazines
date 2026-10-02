<div class="mb-4 relative" data-tag-input>
    <label class="block text-sm font-medium mb-1" for="{{ $id ?? 'tags-input' }}">{{ $label ?? 'Tags' }}</label>
    @if ($help ?? null)
        <p class="text-sm text-gray-500 mb-2">{{ $help }}</p>
    @endif
    <input
        type="text"
        id="{{ $id ?? 'tags-input' }}"
        name="tags"
        value="{{ $value ?? '' }}"
        autocomplete="off"
        data-tag-input-field
        data-suggest-url="{{ route('tags.suggest') }}"
        class="w-full rounded-md border border-gray-300 px-3 py-2"
        placeholder="{{ $placeholder ?? 'technology, startups, writing' }}"
    >
    <ul
        data-tag-suggestions
        class="absolute z-20 mt-1 hidden max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
        role="listbox"
    ></ul>
    @error('tags')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
