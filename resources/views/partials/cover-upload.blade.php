@php
    $existingCoverUrl = $existingCoverUrl ?? null;
@endphp

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Cover image</label>
    <p class="text-xs text-gray-500 mb-2">Recommended 16:9, JPG or PNG, max 5 MB.</p>

    <div id="cover-dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-white cursor-pointer hover:border-gray-400 transition">
        <p class="text-sm text-gray-600">Drag and drop a cover image here, or click to browse</p>
        <input type="file" id="cover-image" name="cover_image" accept="image/jpeg,image/png,image/webp" class="hidden">
    </div>

    <div id="cover-preview" class="mt-3">
        @if ($existingCoverUrl)
            <div class="relative aspect-video max-w-xl overflow-hidden rounded-lg border border-gray-200">
                <img src="{{ $existingCoverUrl }}" alt="Current cover" class="w-full h-full object-cover">
            </div>
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remove_cover" value="1">
                Remove current cover
            </label>
        @endif
    </div>
</div>
