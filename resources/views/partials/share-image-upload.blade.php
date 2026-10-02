@php
    $existingShareImageUrl = $existingShareImageUrl ?? null;
@endphp

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Social share image</label>
    <p class="text-xs text-gray-500 mb-2">Optional image for Open Graph / Twitter cards (1200×630 recommended). Falls back to the cover image when empty.</p>

    <input type="file" id="share-image" name="share_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">

    <div id="share-image-preview" class="mt-3">
        @if ($existingShareImageUrl)
            <div class="relative aspect-[1200/630] max-w-xl overflow-hidden rounded-lg border border-gray-200">
                <img src="{{ $existingShareImageUrl }}" alt="Current social share image" class="w-full h-full object-cover">
            </div>
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remove_share_image" value="1">
                Remove current share image
            </label>
        @endif
    </div>
</div>
